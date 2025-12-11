<?php
/**
 * API Endpoint: Save User Preference
 * Saves user preferences like theme, language, dashboard layout
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db.php';

// Check database connection
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$userId = $_SESSION['user_id'];
$preference = $_POST['preference'] ?? '';
$value = $_POST['value'] ?? '';

// Validate input
if (empty($preference)) {
    echo json_encode(['success' => false, 'error' => 'Preference key is required']);
    exit;
}

// Allowed preferences
$allowedPreferences = ['theme', 'language', 'dashboard_layout', 'items_per_page'];

if (!in_array($preference, $allowedPreferences)) {
    echo json_encode(['success' => false, 'error' => 'Invalid preference key']);
    exit;
}

try {
    // Check if preference exists
    $stmt = $pdo->prepare("
        SELECT id FROM user_preferences 
        WHERE user_id = ? AND preference_key = ?
    ");
    $stmt->execute([$userId, $preference]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing preference
        $stmt = $pdo->prepare("
            UPDATE user_preferences 
            SET preference_value = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND preference_key = ?
        ");
        $stmt->execute([$value, $userId, $preference]);
    } else {
        // Insert new preference
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $preference, $value]);
    }
    
    // Update session if it's a language change
    if ($preference === 'language') {
        $_SESSION['lang'] = $value;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Preference saved successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save preference: ' . $e->getMessage()
    ]);
}
