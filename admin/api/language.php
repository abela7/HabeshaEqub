<?php
/**
 * Language Switching API
 * Handle AJAX requests for changing language
 */

// Include required files
require_once '../../includes/db.php';
require_once '../../languages/translator.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle POST request for language switching
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $language = sanitize_input($_POST['language'] ?? '');
    
    if (empty($language)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Language is required']);
        exit;
    }
    
    // Set the language
    if (setLanguage($language)) {
        echo json_encode([
            'success' => true,
            'message' => 'Language changed successfully',
            'current_language' => getCurrentLanguage(),
            'available_languages' => getAvailableLanguages()
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid language']);
    }
    exit;
}

// Handle GET request for current language info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'current_language' => getCurrentLanguage(),
        'available_languages' => getAvailableLanguages()
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?> 