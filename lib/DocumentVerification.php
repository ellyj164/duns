<?php
/**
 * Document Verification Manager
 * Handles document tracking, verification, and status management
 */

require_once __DIR__ . '/../config.php';

class DocumentVerification
{
    private $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Register a new document for verification
     * 
     * @param array $data Document data
     * @return array Result with success status and verification details
     */
    public function registerDocument($data)
    {
        $required = ['doc_type', 'doc_id', 'doc_number', 'issue_date', 'issuer_user_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }
        
        try {
            // Generate unique barcode ID
            $barcodeID = BarcodeGenerator::generateDocumentBarcodeID(
                $data['doc_type'],
                $data['doc_id'],
                $data['issue_date']
            );
            
            // Generate verification hash
            $secret = defined('DOCUMENT_VERIFICATION_SECRET') ? DOCUMENT_VERIFICATION_SECRET : 'DEFAULT_SECRET';
            $hashData = $data['doc_type'] . $data['doc_id'] . 
                       ($data['doc_amount'] ?? '') . $data['issue_date'] . $secret;
            $verificationHash = hash('sha256', $hashData);
            
            // Check if document already exists
            $checkStmt = $this->pdo->prepare(
                "SELECT id FROM document_verifications WHERE doc_type = :doc_type AND doc_id = :doc_id"
            );
            $checkStmt->execute([
                ':doc_type' => $data['doc_type'],
                ':doc_id' => $data['doc_id']
            ]);
            
            if ($checkStmt->fetch()) {
                // Update existing record
                $stmt = $this->pdo->prepare("
                    UPDATE document_verifications 
                    SET doc_number = :doc_number,
                        barcode_id = :barcode_id,
                        verification_hash = :verification_hash,
                        doc_amount = :doc_amount,
                        doc_currency = :doc_currency,
                        issue_date = :issue_date,
                        issuer_user_id = :issuer_user_id,
                        status = :status,
                        client_id = :client_id,
                        company_id = :company_id,
                        metadata = :metadata,
                        updated_at = NOW()
                    WHERE doc_type = :doc_type AND doc_id = :doc_id
                ");
            } else {
                // Insert new record
                $stmt = $this->pdo->prepare("
                    INSERT INTO document_verifications 
                    (doc_type, doc_id, doc_number, barcode_id, verification_hash, 
                     doc_amount, doc_currency, issue_date, issuer_user_id, status, 
                     client_id, company_id, metadata)
                    VALUES 
                    (:doc_type, :doc_id, :doc_number, :barcode_id, :verification_hash,
                     :doc_amount, :doc_currency, :issue_date, :issuer_user_id, :status,
                     :client_id, :company_id, :metadata)
                ");
            }
            
            $stmt->execute([
                ':doc_type' => $data['doc_type'],
                ':doc_id' => $data['doc_id'],
                ':doc_number' => $data['doc_number'],
                ':barcode_id' => $barcodeID,
                ':verification_hash' => $verificationHash,
                ':doc_amount' => $data['doc_amount'] ?? null,
                ':doc_currency' => $data['doc_currency'] ?? null,
                ':issue_date' => $data['issue_date'],
                ':issuer_user_id' => $data['issuer_user_id'],
                ':status' => $data['status'] ?? 'active',
                ':client_id' => $data['client_id'] ?? null,
                ':company_id' => $data['company_id'] ?? null,
                ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
            ]);
            
            return [
                'success' => true,
                'barcode_id' => $barcodeID,
                'verification_hash' => $verificationHash,
                'short_hash' => substr($verificationHash, 0, 12)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verify a document by barcode ID or hash
     * 
     * @param string $doc_type Document type
     * @param int $doc_id Document ID
     * @param string $hash Optional verification hash
     * @return array Verification result
     */
    public function verifyDocument($doc_type, $doc_id, $hash = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT dv.*, u.first_name, u.last_name, u.email as issuer_email,
                       c.client_name, c.reg_no as client_reg_no
                FROM document_verifications dv
                LEFT JOIN users u ON dv.issuer_user_id = u.id
                LEFT JOIN clients c ON dv.client_id = c.id
                WHERE dv.doc_type = :doc_type AND dv.doc_id = :doc_id
            ");
            $stmt->execute([
                ':doc_type' => $doc_type,
                ':doc_id' => $doc_id
            ]);
            
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$document) {
                return [
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'Document not found in verification system'
                ];
            }
            
            // Verify hash if provided
            if ($hash) {
                $shortHash = substr($document['verification_hash'], 0, 12);
                if ($hash !== $shortHash) {
                    return [
                        'success' => false,
                        'status' => 'invalid_hash',
                        'message' => 'Document verification hash does not match'
                    ];
                }
            }
            
            return [
                'success' => true,
                'status' => $document['status'],
                'document' => $document,
                'message' => $this->getStatusMessage($document['status'])
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update document status
     * 
     * @param string $doc_type Document type
     * @param int $doc_id Document ID
     * @param string $status New status
     * @return array Result
     */
    public function updateDocumentStatus($doc_type, $doc_id, $status)
    {
        $validStatuses = ['active', 'cancelled', 'void', 'revised'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE document_verifications 
                SET status = :status, updated_at = NOW()
                WHERE doc_type = :doc_type AND doc_id = :doc_id
            ");
            $stmt->execute([
                ':status' => $status,
                ':doc_type' => $doc_type,
                ':doc_id' => $doc_id
            ]);
            
            return ['success' => true, 'message' => 'Document status updated'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get human-readable status message
     * 
     * @param string $status Status code
     * @return string Status message
     */
    private function getStatusMessage($status)
    {
        $messages = [
            'active' => 'This document is valid and active',
            'cancelled' => 'This document has been cancelled',
            'void' => 'This document is void and no longer valid',
            'revised' => 'This document has been revised. A newer version exists'
        ];
        
        return $messages[$status] ?? 'Unknown status';
    }
    
    /**
     * Get all documents by status
     * 
     * @param string $status Filter by status
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Documents list
     */
    public function getDocumentsByStatus($status = null, $limit = 50, $offset = 0)
    {
        try {
            $sql = "
                SELECT dv.*, u.first_name, u.last_name, c.client_name
                FROM document_verifications dv
                LEFT JOIN users u ON dv.issuer_user_id = u.id
                LEFT JOIN clients c ON dv.client_id = c.id
            ";
            
            if ($status) {
                $sql .= " WHERE dv.status = :status";
            }
            
            $sql .= " ORDER BY dv.issue_date DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($status) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return [
                'success' => true,
                'documents' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
}
