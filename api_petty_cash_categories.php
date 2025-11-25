<?php
/**
 * api_petty_cash_categories.php
 * 
 * API endpoint for managing petty cash categories
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all categories or specific category
        if (isset($_GET['id'])) {
            $sql = "SELECT * FROM petty_cash_categories WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_GET['id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Category not found.']);
            }
        } else {
            // Get all categories
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
            $sql = "SELECT * FROM petty_cash_categories";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY name ASC";
            
            $stmt = $pdo->query($sql);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $categories]);
        }
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action is required.']);
            exit;
        }
        
        switch ($data['action']) {
            case 'create':
                // Validate required fields
                if (empty($data['name'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Category name is required.']);
                    exit;
                }
                
                // Validate max_amount if provided
                if (isset($data['max_amount']) && $data['max_amount'] !== null && !is_numeric($data['max_amount'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Max amount must be numeric.']);
                    exit;
                }
                
                // Insert new category
                $sql = "INSERT INTO petty_cash_categories (name, description, max_amount, icon, color, is_active)
                        VALUES (:name, :description, :max_amount, :icon, :color, :is_active)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([
                    ':name' => $data['name'],
                    ':description' => $data['description'] ?? null,
                    ':max_amount' => $data['max_amount'] ?? null,
                    ':icon' => $data['icon'] ?? null,
                    ':color' => $data['color'] ?? '#6b7280',
                    ':is_active' => $data['is_active'] ?? 1
                ]);
                
                $newId = $pdo->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'message' => 'Category created successfully.',
                    'id' => $newId
                ]);
                break;
                
            case 'update':
                // Validate required fields
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Category ID is required.']);
                    exit;
                }
                
                // Validate max_amount if provided
                if (isset($data['max_amount']) && $data['max_amount'] !== null && !is_numeric($data['max_amount'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Max amount must be numeric.']);
                    exit;
                }
                
                // Update category
                $sql = "UPDATE petty_cash_categories
                        SET name = :name,
                            description = :description,
                            max_amount = :max_amount,
                            icon = :icon,
                            color = :color,
                            is_active = :is_active
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                
                $stmt->execute([
                    ':id' => $data['id'],
                    ':name' => $data['name'],
                    ':description' => $data['description'] ?? null,
                    ':max_amount' => $data['max_amount'] ?? null,
                    ':icon' => $data['icon'] ?? null,
                    ':color' => $data['color'] ?? '#6b7280',
                    ':is_active' => $data['is_active'] ?? 1
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Category updated successfully.'
                ]);
                break;
                
            case 'delete':
                // Validate required fields
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Category ID is required.']);
                    exit;
                }
                
                // Check if category is in use
                $checkSql = "SELECT COUNT(*) as count FROM petty_cash WHERE category_id = :id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([':id' => $data['id']]);
                $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Cannot delete category. It is being used by ' . $result['count'] . ' transaction(s).'
                    ]);
                    exit;
                }
                
                // Delete category
                $sql = "DELETE FROM petty_cash_categories WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $data['id']]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Category deleted successfully.'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Category not found.'
                    ]);
                }
                break;
                
            case 'toggle':
                // Toggle active status
                if (empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Category ID is required.']);
                    exit;
                }
                
                $sql = "UPDATE petty_cash_categories SET is_active = NOT is_active WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $data['id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Category status toggled successfully.'
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
                break;
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
