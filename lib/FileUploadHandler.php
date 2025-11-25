<?php
/**
 * File Upload Handler
 * Handles secure file uploads for receipts, documents, and attachments
 */

class FileUploadHandler
{
    private $pdo;
    private $uploadBasePath;
    private $allowedMimeTypes;
    private $maxFileSize;
    
    public function __construct($pdo, $uploadBasePath = null)
    {
        $this->pdo = $pdo;
        $this->uploadBasePath = $uploadBasePath ?? __DIR__ . '/../uploads';
        
        // Allowed MIME types for security
        $this->allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        // Max file size: 10MB
        $this->maxFileSize = 10 * 1024 * 1024;
        
        // Ensure upload directories exist
        $this->ensureDirectories();
    }
    
    /**
     * Ensure upload directories exist and are secure
     */
    private function ensureDirectories()
    {
        $dirs = [
            $this->uploadBasePath,
            $this->uploadBasePath . '/receipts',
            $this->uploadBasePath . '/invoices',
            $this->uploadBasePath . '/petty_cash',
            $this->uploadBasePath . '/documents'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            
            // Add .htaccess for security (Apache)
            $htaccessPath = $dir . '/.htaccess';
            if (!file_exists($htaccessPath)) {
                $htaccessContent = "Options -Indexes\n";
                $htaccessContent .= "php_flag engine off\n";
                @file_put_contents($htaccessPath, $htaccessContent);
            }
            
            // Add index.php to prevent directory listing
            $indexPath = $dir . '/index.php';
            if (!file_exists($indexPath)) {
                @file_put_contents($indexPath, '<?php http_response_code(403); ?>');
            }
        }
    }
    
    /**
     * Upload a file
     * 
     * @param array $file $_FILES array element
     * @param string $entityType Entity type (petty_cash, invoice, receipt, etc.)
     * @param int $entityId Entity ID
     * @param string $fileType File category (receipt, invoice_copy, etc.)
     * @param int $uploadedBy User ID
     * @param string $description Optional description
     * @return array Result with success status and file info
     */
    public function uploadFile($file, $entityType, $entityId, $fileType, $uploadedBy, $description = null)
    {
        // Validate file upload
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No valid file uploaded'];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds maximum allowed size (' . ($this->maxFileSize / 1024 / 1024) . 'MB)'
            ];
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'success' => false,
                'error' => 'File type not allowed. Allowed types: images, PDF, Word, Excel'
            ];
        }
        
        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $this->generateSecureFilename($entityType, $entityId, $extension);
        
        // Determine subdirectory based on entity type
        $subdir = $this->getSubdirectory($entityType);
        $targetDir = $this->uploadBasePath . '/' . $subdir;
        $targetPath = $targetDir . '/' . $filename;
        $relativePath = $subdir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file'];
        }
        
        // Set file permissions
        @chmod($targetPath, 0644);
        
        // Save to database
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO document_attachments
                (entity_type, entity_id, file_name, original_name, file_path, file_size,
                 mime_type, file_type, description, uploaded_by)
                VALUES
                (:entity_type, :entity_id, :file_name, :original_name, :file_path, :file_size,
                 :mime_type, :file_type, :description, :uploaded_by)
            ");
            
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':file_name' => $filename,
                ':original_name' => $file['name'],
                ':file_path' => $relativePath,
                ':file_size' => $file['size'],
                ':mime_type' => $mimeType,
                ':file_type' => $fileType,
                ':description' => $description,
                ':uploaded_by' => $uploadedBy
            ]);
            
            $attachmentId = $this->pdo->lastInsertId();
            
            // Update entity attachment count
            $this->updateEntityAttachmentCount($entityType, $entityId);
            
            return [
                'success' => true,
                'attachment_id' => $attachmentId,
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_path' => $relativePath,
                'file_size' => $file['size'],
                'mime_type' => $mimeType
            ];
            
        } catch (PDOException $e) {
            // Delete uploaded file if database insert fails
            @unlink($targetPath);
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attachments for an entity
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @return array Attachments list
     */
    public function getAttachments($entityType, $entityId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT da.*, u.username, u.first_name, u.last_name
                FROM document_attachments da
                LEFT JOIN users u ON da.uploaded_by = u.id
                WHERE da.entity_type = :entity_type AND da.entity_id = :entity_id
                ORDER BY da.created_at DESC
            ");
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId
            ]);
            
            return [
                'success' => true,
                'attachments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete an attachment
     * 
     * @param int $attachmentId Attachment ID
     * @param int $userId User ID (for permission check)
     * @return array Result
     */
    public function deleteAttachment($attachmentId, $userId)
    {
        try {
            // Get attachment info
            $stmt = $this->pdo->prepare("
                SELECT * FROM document_attachments WHERE id = :id
            ");
            $stmt->execute([':id' => $attachmentId]);
            $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attachment) {
                return ['success' => false, 'error' => 'Attachment not found'];
            }
            
            // Delete file from filesystem
            $filePath = $this->uploadBasePath . '/' . $attachment['file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Delete from database
            $deleteStmt = $this->pdo->prepare("DELETE FROM document_attachments WHERE id = :id");
            $deleteStmt->execute([':id' => $attachmentId]);
            
            // Update entity attachment count
            $this->updateEntityAttachmentCount($attachment['entity_type'], $attachment['entity_id']);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate secure filename
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param string $extension File extension
     * @return string Secure filename
     */
    private function generateSecureFilename($entityType, $entityId, $extension)
    {
        $prefix = substr($entityType, 0, 3);
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return strtolower("{$prefix}_{$entityId}_{$timestamp}_{$random}.{$extension}");
    }
    
    /**
     * Get subdirectory for entity type
     * 
     * @param string $entityType Entity type
     * @return string Subdirectory name
     */
    private function getSubdirectory($entityType)
    {
        $mapping = [
            'petty_cash' => 'petty_cash',
            'invoice' => 'invoices',
            'receipt' => 'receipts',
            'quotation' => 'documents',
            'client' => 'documents'
        ];
        
        return $mapping[$entityType] ?? 'documents';
    }
    
    /**
     * Update entity attachment count
     * 
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     */
    private function updateEntityAttachmentCount($entityType, $entityId)
    {
        if ($entityType === 'petty_cash') {
            try {
                // Check if columns exist before updating
                $checkStmt = $this->pdo->query("SHOW COLUMNS FROM petty_cash LIKE 'has_attachments'");
                if ($checkStmt->rowCount() === 0) {
                    return; // Columns don't exist yet, skip update
                }
                
                $countStmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count FROM document_attachments
                    WHERE entity_type = 'petty_cash' AND entity_id = :id
                ");
                $countStmt->execute([':id' => $entityId]);
                $result = $countStmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['count'] ?? 0;
                
                $updateStmt = $this->pdo->prepare("
                    UPDATE petty_cash
                    SET has_attachments = :has_attachments, attachment_count = :count
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':has_attachments' => $count > 0 ? 1 : 0,
                    ':count' => $count,
                    ':id' => $entityId
                ]);
            } catch (PDOException $e) {
                // Ignore errors - columns might not exist yet
                error_log("Failed to update attachment count: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($errorCode)
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum allowed size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $messages[$errorCode] ?? 'Unknown upload error';
    }
}
