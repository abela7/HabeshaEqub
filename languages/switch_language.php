<?php
/**
 * HabeshaEqub - Language Switching Endpoint
 * Handle language changes via GET or POST
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the translator
require_once 'translator.php';
require_once '../includes/db.php';

// Handle both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET request (used by login page and navigation)
    $language = $_GET['lang'] ?? '';
    $redirect = $_GET['redirect'] ?? '../user/login.php';
    
    // Validate language
    if (in_array($language, ['en', 'am'])) {
        // Set the language using the translator
        setLanguage($language);
    }
    
    // Redirect back to the page
    header('Location: ' . $redirect);
    exit;
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST request (AJAX)
    header('Content-Type: application/json');
    
    // Get POST data
    $language = $_POST['language'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Basic CSRF protection for AJAX requests
    if (empty($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'CSRF token required']);
        exit;
    }
    
    // Validate language
    if (!in_array($language, ['en', 'am'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid language']);
        exit;
    }
    
    // Set the language using the existing translator
    if (setLanguage($language)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Language changed successfully',
            'language' => $language
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change language']);
    }
} else {
    // Method not allowed
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 