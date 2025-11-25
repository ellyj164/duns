<?php
session_start();
require_once 'db.php';
require_once 'rbac.php';
require_once 'activity_logger.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if user has permission to view audit logs
if (!userHasPermission($_SESSION['user_id'], 'view-audit-logs')) {
    $_SESSION['error_message'] = "Access denied. You don't have permission to view audit logs.";
    header('Location: index.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get logs
$logs = getAllActivityLogs($perPage, $offset);
$totalLogs = countActivityLogs();
$totalPages = ceil($totalLogs / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0052cc;
            --primary-hover: #0041a3;
            --secondary-color: #f4f7f6;
            --text-color: #333;
            --border-color: #dee2e6;
            --white-color: #fff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            padding: 20px;
            color: var(--text-color);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--white-color);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--secondary-color);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card h3 {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: 700;
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--secondary-color);
            font-weight: 600;
            color: var(--text-color);
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .action-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .action-badge.create {
            background-color: #28a745;
            color: white;
        }

        .action-badge.update {
            background-color: #0052cc;
            color: white;
        }

        .action-badge.delete {
            background-color: #dc3545;
            color: white;
        }

        .action-badge.view {
            background-color: #6c757d;
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
        }

        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .pagination .active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .details {
            font-size: 0.9em;
            color: #666;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .timestamp {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Activity Logs</h1>
        <p style="color: #666; margin-bottom: 30px;">System audit trail - Track all user activities</p>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Logs</h3>
                <div class="value"><?php echo number_format($totalLogs); ?></div>
            </div>
            <div class="stat-card">
                <h3>Current Page</h3>
                <div class="value"><?php echo $page; ?> / <?php echo $totalPages; ?></div>
            </div>
            <div class="stat-card">
                <h3>Showing</h3>
                <div class="value"><?php echo count($logs); ?> logs</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                            No activity logs found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $actionType = 'view';
                        if (strpos($log['action'], 'create') !== false) $actionType = 'create';
                        elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) $actionType = 'update';
                        elseif (strpos($log['action'], 'delete') !== false || strpos($log['action'], 'remove') !== false) $actionType = 'delete';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['id']); ?></td>
                            <td>
                                <?php if ($log['username']): ?>
                                    <strong><?php echo htmlspecialchars($log['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($log['email']); ?></small>
                                <?php else: ?>
                                    <em style="color: #999;">System</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="action-badge <?php echo $actionType; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['target_type'] && $log['target_id']): ?>
                                    <?php echo htmlspecialchars($log['target_type']); ?> 
                                    <small>(ID: <?php echo htmlspecialchars($log['target_id']); ?>)</small>
                                <?php else: ?>
                                    <em style="color: #999;">N/A</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['details']): ?>
                                    <div class="details" title="<?php echo htmlspecialchars($log['details']); ?>">
                                        <?php echo htmlspecialchars($log['details']); ?>
                                    </div>
                                <?php else: ?>
                                    <em style="color: #999;">N/A</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            <td class="timestamp">
                                <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">« First</a>
                    <a href="?page=<?php echo $page - 1; ?>">‹ Previous</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next ›</a>
                    <a href="?page=<?php echo $totalPages; ?>">Last »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
