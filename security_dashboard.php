<?php
/**
 * Security Dashboard
 * View failed login attempts, account lockouts, and security events
 */

session_start();
require_once 'db.php';
require_once 'rbac.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$canManageSecurity = userHasPermission($_SESSION['user_id'], 'manage-roles') || 
                     userHasPermission($_SESSION['user_id'], 'manage-users');

if (!$canManageSecurity) {
    die('Access Denied. You do not have permission to view security dashboard.');
}

// Get filter parameters
$filter_days = intval($_GET['days'] ?? 7);
$filter_type = $_GET['type'] ?? 'all';
$filter_user = intval($_GET['user_id'] ?? 0);

// Fetch failed login attempts
$where_clauses = ["fla.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)"];
$params = [':days' => $filter_days];

if ($filter_type !== 'all') {
    $where_clauses[] = "fla.attempt_type = :type";
    $params[':type'] = $filter_type;
}

if ($filter_user > 0) {
    $where_clauses[] = "fla.user_id = :user_id";
    $params[':user_id'] = $filter_user;
}

$where_sql = implode(' AND ', $where_clauses);

try {
    $stmt = $pdo->prepare("
        SELECT 
            fla.*,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.locked_until
        FROM failed_login_attempts fla
        LEFT JOIN users u ON fla.user_id = u.id
        WHERE {$where_sql}
        ORDER BY fla.created_at DESC
        LIMIT 500
    ");
    $stmt->execute($params);
    $failed_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT ip_address) as unique_ips,
            SUM(CASE WHEN attempt_type = 'password' THEN 1 ELSE 0 END) as password_failures,
            SUM(CASE WHEN attempt_type = 'otp' THEN 1 ELSE 0 END) as otp_failures
        FROM failed_login_attempts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
    ");
    $stats_stmt->execute([':days' => $filter_days]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get currently locked accounts
    $locked_stmt = $pdo->query("
        SELECT 
            id,
            username,
            email,
            first_name,
            last_name,
            locked_until,
            locked_by_admin,
            failed_login_attempts,
            last_failed_attempt_at
        FROM users
        WHERE locked_until > NOW()
        ORDER BY locked_until DESC
    ");
    $locked_accounts = $locked_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all users for filter dropdown
    $users_stmt = $pdo->query("SELECT id, username, email, first_name, last_name FROM users ORDER BY username");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $failed_attempts = [];
    $locked_accounts = [];
    $all_users = [];
    $stats = [
        'total_attempts' => 0,
        'unique_users' => 0,
        'unique_ips' => 0,
        'password_failures' => 0,
        'otp_failures' => 0
    ];
}

require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0071ce;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
            border-radius: 12px;
        }
        
        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .page-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-card.danger { border-left-color: var(--danger-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .card-header h2 {
            font-size: 20px;
            color: var(--text-primary);
        }
        
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        select, input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a9e;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: var(--bg-light);
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>🔒 Security Dashboard</h1>
            <p>Monitor failed login attempts and account security</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card danger">
                <h3>Failed Attempts</h3>
                <div class="value"><?php echo number_format($stats['total_attempts']); ?></div>
            </div>
            <div class="stat-card warning">
                <h3>Locked Accounts</h3>
                <div class="value"><?php echo count($locked_accounts); ?></div>
            </div>
            <div class="stat-card">
                <h3>Affected Users</h3>
                <div class="value"><?php echo number_format($stats['unique_users']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Unique IPs</h3>
                <div class="value"><?php echo number_format($stats['unique_ips']); ?></div>
            </div>
            <div class="stat-card danger">
                <h3>Password Failures</h3>
                <div class="value"><?php echo number_format($stats['password_failures']); ?></div>
            </div>
            <div class="stat-card warning">
                <h3>OTP Failures</h3>
                <div class="value"><?php echo number_format($stats['otp_failures']); ?></div>
            </div>
        </div>
        
        <!-- Locked Accounts Section -->
        <?php if (count($locked_accounts) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h2>🔐 Currently Locked Accounts</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Failed Attempts</th>
                        <th>Last Failed</th>
                        <th>Locked Until</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locked_accounts as $account): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($account['username']); ?></strong><br>
                            <small><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($account['email']); ?></td>
                        <td><span class="badge badge-danger"><?php echo $account['failed_login_attempts']; ?></span></td>
                        <td><?php echo $account['last_failed_attempt_at'] ? date('M j, Y g:i A', strtotime($account['last_failed_attempt_at'])) : 'N/A'; ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($account['locked_until'])); ?></td>
                        <td>
                            <?php if ($account['locked_by_admin']): ?>
                                <span class="badge badge-danger">Admin Locked</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Auto Locked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="admin_unlock_account.php" style="display: inline;">
                                <input type="hidden" name="unlock_user_id" value="<?php echo $account['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Unlock this account?')">
                                    Unlock
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Failed Attempts Section -->
        <div class="card">
            <div class="card-header">
                <h2>⚠️ Failed Login Attempts</h2>
            </div>
            
            <form method="get" class="filters">
                <div class="filter-group">
                    <label>Time Period</label>
                    <select name="days">
                        <option value="1" <?php echo $filter_days == 1 ? 'selected' : ''; ?>>Last 24 hours</option>
                        <option value="7" <?php echo $filter_days == 7 ? 'selected' : ''; ?>>Last 7 days</option>
                        <option value="30" <?php echo $filter_days == 30 ? 'selected' : ''; ?>>Last 30 days</option>
                        <option value="90" <?php echo $filter_days == 90 ? 'selected' : ''; ?>>Last 90 days</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Attempt Type</label>
                    <select name="type">
                        <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="password" <?php echo $filter_type == 'password' ? 'selected' : ''; ?>>Password</option>
                        <option value="otp" <?php echo $filter_type == 'otp' ? 'selected' : ''; ?>>OTP</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>User</label>
                    <select name="user_id">
                        <option value="0">All Users</option>
                        <?php foreach ($all_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
            
            <?php if (count($failed_attempts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Username/Email</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failed_attempts as $attempt): ?>
                    <tr>
                        <td><?php echo date('M j, Y g:i:s A', strtotime($attempt['created_at'])); ?></td>
                        <td>
                            <?php if ($attempt['username']): ?>
                                <strong><?php echo htmlspecialchars($attempt['username']); ?></strong><br>
                                <small><?php echo htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']); ?></small>
                            <?php else: ?>
                                <em>Unknown User</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($attempt['attempt_type'] == 'password'): ?>
                                <span class="badge badge-danger">Password</span>
                            <?php else: ?>
                                <span class="badge badge-warning">OTP</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($attempt['username_or_email']); ?></code></td>
                        <td><code><?php echo htmlspecialchars($attempt['ip_address']); ?></code></td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <small><?php echo htmlspecialchars($attempt['user_agent']); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3>No Failed Attempts Found</h3>
                <p>There are no failed login attempts matching your filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
