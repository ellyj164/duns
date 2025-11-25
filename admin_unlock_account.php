<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Load RBAC functions
if (file_exists(__DIR__ . '/rbac.php')) {
    require_once __DIR__ . '/rbac.php';
}

// Check if user has permission to manage users (only Super Admin and Admin)
$canManageUsers = false;
if (isset($_SESSION['user_id']) && function_exists('userHasPermission')) {
    $canManageUsers = userHasPermission($_SESSION['user_id'], 'manage-roles') || 
                      userHasPermission($_SESSION['user_id'], 'manage-users');
}

if (!$canManageUsers) {
    die('Access Denied. You do not have permission to unlock accounts.');
}

$message = '';
$message_type = '';

// Handle unlock request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_user_id'])) {
    $unlock_user_id = intval($_POST['unlock_user_id']);
    
    try {
        // Reset failed attempts and unlock account
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_attempt_at = NULL, locked_until = NULL, locked_by_admin = 0 WHERE id = :id");
        $stmt->execute([':id' => $unlock_user_id]);
        
        $message = 'Account unlocked successfully.';
        $message_type = 'success';
        
        // Log activity
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], 'unlock-account', 'users', $unlock_user_id, 
                       json_encode(['unlocked_by' => $_SESSION['username']]));
        }
    } catch (PDOException $e) {
        $message = 'Failed to unlock account: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle manual lock request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_user_id'])) {
    $lock_user_id = intval($_POST['lock_user_id']);
    
    try {
        // Lock account indefinitely
        $stmt = $pdo->prepare("UPDATE users SET locked_until = '2099-12-31 23:59:59', locked_by_admin = 1 WHERE id = :id");
        $stmt->execute([':id' => $lock_user_id]);
        
        $message = 'Account locked successfully.';
        $message_type = 'success';
        
        // Log activity
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], 'lock-account', 'users', $lock_user_id, 
                       json_encode(['locked_by' => $_SESSION['username']]));
        }
    } catch (PDOException $e) {
        $message = 'Failed to lock account: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch all users with their lock status
try {
    $stmt = $pdo->query("
        SELECT 
            id, 
            username, 
            email, 
            first_name, 
            last_name,
            failed_login_attempts, 
            last_failed_attempt_at, 
            locked_until,
            locked_by_admin
        FROM users 
        ORDER BY locked_until DESC, failed_login_attempts DESC, username ASC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $message = 'Failed to fetch users: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #1f2937;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table thead {
            background: #f9fafb;
        }
        
        .users-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .users-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .users-table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-badge.locked {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-unlock {
            background: #10b981;
            color: white;
        }
        
        .btn-unlock:hover {
            background: #059669;
        }
        
        .btn-lock {
            background: #ef4444;
            color: white;
        }
        
        .btn-lock:hover {
            background: #dc2626;
        }
        
        .btn-back {
            background: #6b7280;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background: #4b5563;
        }
        
        .timestamp {
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-back">← Back to Dashboard</a>
        
        <div class="header">
            <h1>🔐 Account Lock Management</h1>
            <p>Unlock user accounts that have been locked due to failed login attempts</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>User Accounts</h2>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Failed Attempts</th>
                        <th>Last Failed Attempt</th>
                        <th>Locked Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $now = new DateTime();
                        $locked_until = $user['locked_until'] ? new DateTime($user['locked_until']) : null;
                        $is_locked = $locked_until && $locked_until > $now;
                        $is_admin_locked = $user['locked_by_admin'] == 1;
                        
                        if ($is_locked) {
                            $status = 'locked';
                            $status_text = $is_admin_locked ? 'Locked by Admin' : 'Locked (Auto)';
                        } elseif ($user['failed_login_attempts'] > 0) {
                            $status = 'warning';
                            $status_text = 'Warning (' . $user['failed_login_attempts'] . '/3)';
                        } else {
                            $status = 'active';
                            $status_text = 'Active';
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                <br>
                                <span style="color: #6b7280; font-size: 0.875rem;">
                                    @<?php echo htmlspecialchars($user['username']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td><?php echo $user['failed_login_attempts']; ?></td>
                            <td class="timestamp">
                                <?php echo $user['last_failed_attempt_at'] ? date('M j, Y g:i A', strtotime($user['last_failed_attempt_at'])) : 'N/A'; ?>
                            </td>
                            <td class="timestamp">
                                <?php 
                                if ($locked_until && $locked_until > $now) {
                                    if ($is_admin_locked) {
                                        echo 'Indefinite';
                                    } else {
                                        echo date('M j, Y g:i A', strtotime($user['locked_until']));
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($is_locked): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="unlock_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-unlock" onclick="return confirm('Unlock this account?');">
                                            🔓 Unlock
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="lock_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-lock" onclick="return confirm('Lock this account? User will not be able to login until unlocked.');">
                                            🔒 Lock
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
