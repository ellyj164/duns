<?php
/**
 * Vendors Management Page
 * Manage suppliers and vendors
 */

require_once 'header.php';
require_once 'db.php';

// Check if user has permission
$canManageVendors = true; // Can be restricted via RBAC later

if (!$canManageVendors) {
    header('Location: index.php');
    exit;
}

// Fetch all vendors
$vendors = [];
try {
    $stmt = $pdo->query("
        SELECT v.*, u.username as created_by_name
        FROM vendors v
        LEFT JOIN users u ON v.created_by = u.id
        ORDER BY v.created_at DESC
    ");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch vendors: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Feza Logistics</title>
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .vendors-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .vendors-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .vendors-header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin: 0;
        }
        
        .btn-add-vendor {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-add-vendor:hover {
            background: var(--primary-hover);
        }
        
        .vendors-table {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: var(--secondary-color);
        }
        
        table th {
            padding: 12px 16px;
            text-align: left;
            color: var(--text-color);
            font-weight: 600;
        }
        
        table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        table tbody tr:hover {
            background: var(--secondary-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 4px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-action:hover {
            opacity: 0.8;
        }
        
        .no-vendors {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-color);
        }
        
        .no-vendors p {
            font-size: 1.25rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="vendors-container">
        <div class="vendors-header">
            <h1>👥 Vendor Management</h1>
            <a href="add_vendor.php" class="btn-add-vendor">
                <span>➕</span>
                Add New Vendor
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($vendors)): ?>
            <div class="no-vendors">
                <p>No vendors found</p>
                <a href="add_vendor.php" class="btn-add-vendor">Add Your First Vendor</a>
            </div>
        <?php else: ?>
            <div class="vendors-table">
                <table>
                    <thead>
                        <tr>
                            <th>Vendor Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Payment Terms</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($vendor['vendor_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vendor['contact_person'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vendor['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vendor['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vendor['payment_terms']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $vendor['status']; ?>">
                                        <?php echo ucfirst($vendor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="edit_vendor.php?id=<?php echo $vendor['id']; ?>" class="btn-action btn-edit">Edit</a>
                                        <button onclick="deleteVendor(<?php echo $vendor['id']; ?>)" class="btn-action btn-delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function deleteVendor(vendorId) {
            if (confirm('Are you sure you want to delete this vendor? This action cannot be undone.')) {
                // In production, this should use AJAX
                window.location.href = `delete_vendor.php?id=${vendorId}`;
            }
        }
    </script>
</body>
</html>
