<?php
/**
 * api_petty_cash_export.php
 * 
 * API endpoint for exporting petty cash reports (PDF, Excel, CSV)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';

// Check if FPDF library exists, otherwise provide helpful error
if (!file_exists(__DIR__ . '/fpdf/fpdf.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'FPDF library not found. Please install FPDF in the /fpdf directory. Download from: http://www.fpdf.org/'
    ]);
    exit;
}
require_once 'fpdf/fpdf.php';

$exportType = $_GET['type'] ?? 'csv';
$reportType = $_GET['report'] ?? 'ledger';

try {
    switch ($reportType) {
        case 'ledger':
            exportLedger($pdo, $exportType);
            break;
            
        case 'reconciliation':
            exportReconciliation($pdo, $exportType);
            break;
            
        case 'monthly_category':
            exportMonthlyCategorySummary($pdo, $exportType);
            break;
            
        case 'replenishment':
            exportReplenishment($pdo, $exportType);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid report type.']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Export error: ' . $e->getMessage()
    ]);
}

// Export Functions

function exportLedger($pdo, $format) {
    $params = [];
    $sql = "SELECT pc.*, c.name as category_name, u.username
            FROM petty_cash pc
            LEFT JOIN petty_cash_categories c ON pc.category_id = c.id
            LEFT JOIN users u ON pc.user_id = u.id
            WHERE pc.approval_status = 'approved'";
    
    // Date range filter
    if (!empty($_GET['from'])) {
        $sql .= " AND pc.transaction_date >= :from";
        $params[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $sql .= " AND pc.transaction_date <= :to";
        $params[':to'] = $_GET['to'];
    }
    
    $sql .= " ORDER BY pc.transaction_date ASC, pc.id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'pdf') {
        exportLedgerPDF($transactions);
    } elseif ($format === 'excel' || $format === 'csv') {
        exportLedgerCSV($transactions, $format);
    }
}

function exportLedgerPDF($transactions) {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Petty Cash Ledger', 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Description', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Category', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Money In', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Money Out', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Balance', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Reference', 1, 1, 'C', true);
    
    // Table data
    $pdf->SetFont('Arial', '', 8);
    $balance = 0;
    $totalIn = 0;
    $totalOut = 0;
    
    foreach ($transactions as $row) {
        $pdf->Cell(25, 6, date('Y-m-d', strtotime($row['transaction_date'])), 1);
        $pdf->Cell(60, 6, substr($row['description'], 0, 40), 1);
        $pdf->Cell(35, 6, substr($row['category_name'] ?? '-', 0, 20), 1);
        
        if ($row['transaction_type'] === 'credit') {
            $pdf->Cell(30, 6, number_format($row['amount'], 2), 1, 0, 'R');
            $pdf->Cell(30, 6, '-', 1, 0, 'R');
            $balance += $row['amount'];
            $totalIn += $row['amount'];
        } else {
            $pdf->Cell(30, 6, '-', 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($row['amount'], 2), 1, 0, 'R');
            $balance -= $row['amount'];
            $totalOut += $row['amount'];
        }
        
        $pdf->Cell(30, 6, number_format($balance, 2), 1, 0, 'R');
        $pdf->Cell(40, 6, substr($row['reference'] ?? '-', 0, 15), 1, 1);
    }
    
    // Totals
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(120, 7, 'TOTALS:', 1, 0, 'R', true);
    $pdf->Cell(30, 7, number_format($totalIn, 2), 1, 0, 'R', true);
    $pdf->Cell(30, 7, number_format($totalOut, 2), 1, 0, 'R', true);
    $pdf->Cell(30, 7, number_format($balance, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 7, '', 1, 1, 'R', true);
    
    $pdf->Output('D', 'petty_cash_ledger_' . date('Y-m-d') . '.pdf');
}

function exportLedgerCSV($transactions, $format) {
    $filename = 'petty_cash_ledger_' . date('Y-m-d') . '.' . $format;
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, ['Date', 'Description', 'Beneficiary', 'Category', 'Money In', 'Money Out', 'Balance', 'Reference', 'Status']);
    
    // Data rows
    $balance = 0;
    foreach ($transactions as $row) {
        $moneyIn = $row['transaction_type'] === 'credit' ? $row['amount'] : 0;
        $moneyOut = $row['transaction_type'] === 'debit' ? $row['amount'] : 0;
        $balance += $moneyIn - $moneyOut;
        
        fputcsv($output, [
            $row['transaction_date'],
            $row['description'],
            $row['beneficiary'] ?? '',
            $row['category_name'] ?? '',
            number_format($moneyIn, 2),
            number_format($moneyOut, 2),
            number_format($balance, 2),
            $row['reference'] ?? '',
            ucfirst($row['approval_status'])
        ]);
    }
    
    fclose($output);
    exit;
}

function exportReconciliation($pdo, $format) {
    $params = [];
    $sql = "SELECT r.*, u.username as reconciled_by_name
            FROM petty_cash_reconciliation r
            LEFT JOIN users u ON r.reconciled_by = u.id
            WHERE 1=1";
    
    if (!empty($_GET['from'])) {
        $sql .= " AND r.reconciliation_date >= :from";
        $params[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $sql .= " AND r.reconciliation_date <= :to";
        $params[':to'] = $_GET['to'];
    }
    
    $sql .= " ORDER BY r.reconciliation_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reconciliations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'pdf') {
        exportReconciliationPDF($reconciliations);
    } else {
        exportReconciliationCSV($reconciliations);
    }
}

function exportReconciliationPDF($reconciliations) {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Petty Cash Reconciliation Summary', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(30, 7, 'Date', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Expected', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Actual', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Difference', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Status', 1, 1, 'C', true);
    
    // Data
    $pdf->SetFont('Arial', '', 9);
    foreach ($reconciliations as $row) {
        $pdf->Cell(30, 6, $row['reconciliation_date'], 1);
        $pdf->Cell(35, 6, number_format($row['expected_balance'], 2), 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($row['actual_balance'], 2), 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($row['difference'], 2), 1, 0, 'R');
        $pdf->Cell(30, 6, ucfirst($row['status']), 1, 1);
        
        if ($row['explanation']) {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(10, 5, '', 0);
            $pdf->MultiCell(180, 5, 'Note: ' . $row['explanation'], 0);
            $pdf->SetFont('Arial', '', 9);
        }
    }
    
    $pdf->Output('D', 'reconciliation_summary_' . date('Y-m-d') . '.pdf');
}

function exportReconciliationCSV($reconciliations) {
    $filename = 'reconciliation_summary_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Date', 'Expected Balance', 'Actual Balance', 'Difference', 'Status', 'Reconciled By', 'Explanation']);
    
    foreach ($reconciliations as $row) {
        fputcsv($output, [
            $row['reconciliation_date'],
            number_format($row['expected_balance'], 2),
            number_format($row['actual_balance'], 2),
            number_format($row['difference'], 2),
            ucfirst($row['status']),
            $row['reconciled_by_name'],
            $row['explanation'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

function exportMonthlyCategorySummary($pdo, $format) {
    $params = [];
    $sql = "SELECT 
            DATE_FORMAT(pc.transaction_date, '%Y-%m') as month,
            c.name as category,
            SUM(pc.amount) as total,
            COUNT(pc.id) as count
            FROM petty_cash pc
            LEFT JOIN petty_cash_categories c ON pc.category_id = c.id
            WHERE pc.transaction_type = 'debit' 
            AND pc.approval_status = 'approved'";
    
    if (!empty($_GET['from'])) {
        $sql .= " AND pc.transaction_date >= :from";
        $params[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $sql .= " AND pc.transaction_date <= :to";
        $params[':to'] = $_GET['to'];
    }
    
    $sql .= " GROUP BY month, category
             ORDER BY month DESC, total DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'monthly_category_summary_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Month', 'Category', 'Total Spent', 'Transaction Count']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['month'],
            $row['category'] ?? 'Uncategorized',
            number_format($row['total'], 2),
            $row['count']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportReplenishment($pdo, $format) {
    if (empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Replenishment ID is required.']);
        exit;
    }
    
    $sql = "SELECT r.*, u1.username as requested_by_name, u2.username as approved_by_name
            FROM petty_cash_replenishment r
            LEFT JOIN users u1 ON r.requested_by = u1.id
            LEFT JOIN users u2 ON r.approved_by = u2.id
            WHERE r.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_GET['id']]);
    $replenishment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$replenishment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Replenishment not found.']);
        exit;
    }
    
    // Generate PDF report
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'PETTY CASH REPLENISHMENT REQUEST', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Request details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Request No:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'REPL-' . str_pad($replenishment['id'], 6, '0', STR_PAD_LEFT), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Request Date:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $replenishment['request_date'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Requested By:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $replenishment['requested_by_name'], 0, 1);
    
    $pdf->Ln(5);
    
    // Financial details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Current Balance:', 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, number_format($replenishment['current_balance'], 2), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Requested Amount:', 0);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, number_format($replenishment['requested_amount'], 2), 0, 1);
    
    $pdf->Ln(5);
    
    // Justification
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Justification:', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 6, $replenishment['justification']);
    
    $pdf->Ln(5);
    
    // Approval section
    if ($replenishment['status'] === 'approved') {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'APPROVED', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(50, 7, 'Approved By:', 0);
        $pdf->Cell(0, 7, $replenishment['approved_by_name'] ?? '', 0, 1);
        $pdf->Cell(50, 7, 'Approved At:', 0);
        $pdf->Cell(0, 7, $replenishment['approved_at'] ?? '', 0, 1);
    }
    
    $pdf->Output('D', 'replenishment_request_' . $replenishment['id'] . '.pdf');
}
?>
