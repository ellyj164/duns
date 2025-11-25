<?php
/**
 * Notification Manager
 * Handles system notifications, alerts, and reminders
 */

class NotificationManager
{
    private $pdo;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a notification
     * 
     * @param array $data Notification data
     * @return array Result with success status
     */
    public function createNotification($data)
    {
        $required = ['type', 'category', 'title', 'message'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, type, category, title, message, action_url, action_label, 
                 priority, related_type, related_id, metadata, expires_at)
                VALUES 
                (:user_id, :type, :category, :title, :message, :action_url, :action_label,
                 :priority, :related_type, :related_id, :metadata, :expires_at)
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'] ?? null,
                ':type' => $data['type'],
                ':category' => $data['category'],
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':action_url' => $data['action_url'] ?? null,
                ':action_label' => $data['action_label'] ?? null,
                ':priority' => $data['priority'] ?? 'normal',
                ':related_type' => $data['related_type'] ?? null,
                ':related_id' => $data['related_id'] ?? null,
                ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                ':expires_at' => $data['expires_at'] ?? null
            ]);
            
            return [
                'success' => true,
                'notification_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user notifications
     * 
     * @param int $userId User ID
     * @param bool $unreadOnly Only unread notifications
     * @param int $limit Limit results
     * @return array Notifications list
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 50)
    {
        try {
            $sql = "
                SELECT * FROM notifications
                WHERE (user_id = :user_id OR user_id IS NULL)
                AND (expires_at IS NULL OR expires_at > NOW())
            ";
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY priority DESC, created_at DESC LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'success' => true,
                'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for security check)
     * @return array Result
     */
    public function markAsRead($notificationId, $userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)
            ");
            $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $userId
            ]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId User ID
     * @return array Result
     */
    public function markAllAsRead($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = 0
            ");
            $stmt->execute([':user_id' => $userId]);
            
            return ['success' => true, 'count' => $stmt->rowCount()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get unread notification count
     * 
     * @param int $userId User ID
     * @return int Unread count
     */
    public function getUnreadCount($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications
                WHERE (user_id = :user_id OR user_id IS NULL)
                AND is_read = 0
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($result['count'] ?? 0);
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Check and trigger alert rules
     * 
     * @return array Triggered alerts
     */
    public function checkAlertRules()
    {
        $triggered = [];
        
        try {
            // Get active alert rules
            $stmt = $this->pdo->query("
                SELECT * FROM alert_rules
                WHERE is_active = 1
                ORDER BY alert_type
            ");
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rules as $rule) {
                $result = $this->evaluateAlertRule($rule);
                if ($result['triggered']) {
                    $triggered[] = $result;
                }
            }
            
            return [
                'success' => true,
                'triggered_count' => count($triggered),
                'alerts' => $triggered
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Evaluate a single alert rule
     * 
     * @param array $rule Alert rule
     * @return array Evaluation result
     */
    private function evaluateAlertRule($rule)
    {
        $condition = json_decode($rule['condition_json'], true);
        $action = json_decode($rule['action_json'], true);
        $triggered = false;
        $items = [];
        
        switch ($rule['alert_type']) {
            case 'overdue_invoice':
                $daysOverdue = $condition['days_overdue'] ?? 7;
                $stmt = $this->pdo->prepare("
                    SELECT * FROM invoices
                    WHERE status = 'unpaid'
                    AND due_date < DATE_SUB(NOW(), INTERVAL :days DAY)
                    AND total > :min_amount
                    LIMIT 50
                ");
                $stmt->execute([
                    ':days' => $daysOverdue,
                    ':min_amount' => $condition['min_amount'] ?? 0
                ]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $triggered = count($items) > 0;
                
                if ($triggered && $action['notification']) {
                    foreach ($items as $invoice) {
                        $this->createNotification([
                            'type' => 'alert',
                            'category' => 'invoice',
                            'title' => 'Overdue Invoice',
                            'message' => "Invoice #{$invoice['invoice_number']} is overdue by more than {$daysOverdue} days. Amount: {$invoice['currency']} " . number_format($invoice['total'], 2),
                            'action_url' => "create_invoice.php?id={$invoice['id']}",
                            'action_label' => 'View Invoice',
                            'priority' => 'high',
                            'related_type' => 'invoice',
                            'related_id' => $invoice['id']
                        ]);
                    }
                }
                break;
                
            case 'low_balance':
                $threshold = $condition['threshold_amount'] ?? 10000;
                $stmt = $this->pdo->prepare("
                    SELECT SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance
                    FROM petty_cash
                    WHERE currency = :currency
                    AND approval_status = 'approved'
                ");
                $stmt->execute([':currency' => $condition['currency'] ?? 'RWF']);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $balance = $result['balance'] ?? 0;
                
                if ($balance < $threshold) {
                    $triggered = true;
                    if ($action['notification']) {
                        $this->createNotification([
                            'type' => 'alert',
                            'category' => 'petty_cash',
                            'title' => 'Low Petty Cash Balance',
                            'message' => "Petty cash balance ({$condition['currency']} " . number_format($balance, 2) . ") is below threshold ({$condition['currency']} " . number_format($threshold, 2) . ")",
                            'action_url' => 'petty_cash.php',
                            'action_label' => 'View Petty Cash',
                            'priority' => 'high',
                            'related_type' => 'petty_cash',
                            'related_id' => null
                        ]);
                    }
                }
                break;
                
            case 'pending_approval':
                $pendingDays = $condition['pending_days'] ?? 2;
                $stmt = $this->pdo->prepare("
                    SELECT * FROM petty_cash
                    WHERE approval_status = 'pending'
                    AND transaction_date < DATE_SUB(NOW(), INTERVAL :days DAY)
                    LIMIT 50
                ");
                $stmt->execute([':days' => $pendingDays]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $triggered = count($items) > 0;
                
                if ($triggered && $action['notification']) {
                    $this->createNotification([
                        'type' => 'reminder',
                        'category' => 'petty_cash',
                        'title' => 'Pending Approvals',
                        'message' => count($items) . " petty cash transactions awaiting approval for more than {$pendingDays} days",
                        'action_url' => 'petty_cash_approvals.php',
                        'action_label' => 'Review Approvals',
                        'priority' => 'normal',
                        'related_type' => 'petty_cash',
                        'related_id' => null
                    ]);
                }
                break;
        }
        
        // Update rule trigger stats
        if ($triggered) {
            $updateStmt = $this->pdo->prepare("
                UPDATE alert_rules 
                SET last_triggered = NOW(), trigger_count = trigger_count + 1
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $rule['id']]);
        }
        
        return [
            'triggered' => $triggered,
            'rule_id' => $rule['id'],
            'rule_name' => $rule['name'],
            'alert_type' => $rule['alert_type'],
            'item_count' => count($items)
        ];
    }
    
    /**
     * Clean up expired notifications
     * 
     * @return int Number of deleted notifications
     */
    public function cleanupExpired()
    {
        try {
            $stmt = $this->pdo->query("
                DELETE FROM notifications
                WHERE expires_at IS NOT NULL AND expires_at < NOW()
            ");
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            return 0;
        }
    }
}
