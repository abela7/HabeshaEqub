<?php
/**
 * HabeshaEqub User Authentication API
 * Handles member login, registration, logout, and auth checking
 * Returns JSON responses for AJAX calls
 */

// Include database connection
require_once '../../includes/db.php';

// Set JSON header for all responses
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// CORS headers (if needed for development)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send JSON response and exit
 */
function send_json_response($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

/**
 * Validate and sanitize input data
 */
function validate_user_input($data, $type = 'text') {
    $data = trim($data);
    
    switch ($type) {
        case 'username':
            if (empty($data)) {
                return ['valid' => false, 'message' => 'Username is required'];
            }
            if (strlen($data) < 3 || strlen($data) > 30) {
                return ['valid' => false, 'message' => 'Username must be 3-30 characters'];
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data)) {
                return ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
            }
            return ['valid' => true, 'value' => sanitize_input($data)];
            
        case 'email':
            if (empty($data)) {
                return ['valid' => false, 'message' => 'Email is required'];
            }
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return ['valid' => false, 'message' => 'Please enter a valid email address'];
            }
            return ['valid' => true, 'value' => sanitize_input($data)];
            
        case 'phone':
            if (empty($data)) {
                return ['valid' => false, 'message' => 'Phone number is required'];
            }
            // Remove all non-digit characters
            $clean_phone = preg_replace('/[^0-9]/', '', $data);
            if (strlen($clean_phone) < 10) {
                return ['valid' => false, 'message' => 'Please enter a valid phone number'];
            }
            return ['valid' => true, 'value' => $clean_phone];
            
        case 'password':
            if (empty($data)) {
                return ['valid' => false, 'message' => 'Password is required'];
            }
            if (strlen($data) < 6) {
                return ['valid' => false, 'message' => 'Password must be at least 6 characters'];
            }
            if (!preg_match('/(?=.*[a-zA-Z])(?=.*\d)/', $data)) {
                return ['valid' => false, 'message' => 'Password must contain both letters and numbers'];
            }
            return ['valid' => true, 'value' => $data]; // Don't sanitize passwords
            
        case 'full_name':
            if (empty($data)) {
                return ['valid' => false, 'message' => 'Full name is required'];
            }
            if (strlen($data) < 2 || strlen($data) > 100) {
                return ['valid' => false, 'message' => 'Full name must be 2-100 characters'];
            }
            return ['valid' => true, 'value' => sanitize_input($data)];
            
        default:
            return ['valid' => true, 'value' => sanitize_input($data)];
    }
}

/**
 * Check if username already exists
 */
function user_username_exists($username) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        error_log("Username check error: " . $e->getMessage());
        return true; // Assume exists to be safe
    }
}

/**
 * Check if email already exists
 */
function user_email_exists($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return true; // Assume exists to be safe
    }
}

/**
 * Create new member account
 */
function create_member($full_name, $email, $phone, $password) {
    global $pdo;
    
    try {
        // Hash password with bcrypt
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Generate username from email (first part before @)
        $username = explode('@', $email)[0];
        
        $stmt = $pdo->prepare("
            INSERT INTO members (
                full_name, username, email, phone, password, 
                status, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, 'active', 1, NOW())
        ");
        
        $stmt->execute([$full_name, $username, $email, $phone, $password_hash]);
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Member creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Authenticate member login
 */
function authenticate_member($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, password, status, is_active 
            FROM members 
            WHERE email = ? 
            LIMIT 1
        ");
        
        $stmt->execute([$email]);
        $member = $stmt->fetch();
        
        if (!$member) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        if (!$member['is_active']) {
            return ['success' => false, 'message' => 'Your account is inactive. Please contact support.'];
        }
        
        if ($member['status'] !== 'active') {
            return ['success' => false, 'message' => 'Your account is pending approval. Please wait for admin confirmation.'];
        }
        
        if (password_verify($password, $member['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $member['id'];
            $_SESSION['user_username'] = $member['username'];
            $_SESSION['user_full_name'] = $member['full_name'];
            $_SESSION['user_email'] = $member['email'];
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_login_time'] = time();
            
            // Update last login (optional)
            $update_stmt = $pdo->prepare("UPDATE members SET last_login = NOW() WHERE id = ?");
            $update_stmt->execute([$member['id']]);
            
            return [
                'success' => true, 
                'message' => 'Login successful! Welcome back.',
                'redirect' => 'dashboard.php'
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
    } catch (PDOException $e) {
        error_log("Member authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication failed. Please try again.'];
    }
}

/**
 * Check if member is authenticated
 */
function is_member_authenticated() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['user_logged_in']) && 
           $_SESSION['user_logged_in'] === true;
}

/**
 * Logout member
 */
function logout_member() {
    // Clear session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['user_username']);
    unset($_SESSION['user_full_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_login_time']);
    
    // Destroy session if no other important data
    if (empty($_SESSION) || (count($_SESSION) === 1 && isset($_SESSION['csrf_token']))) {
        session_destroy();
    }
    
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// ===== MAIN API HANDLER =====

try {
    // Get action from request
    $action = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
    }

    // Validate action
    if (empty($action)) {
        send_json_response(false, 'No action specified');
    }

    // Handle different actions
    switch ($action) {
        
        case 'login':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                send_json_response(false, 'Security token mismatch. Please refresh and try again.');
            }
            
            // Validate input
            $email_validation = validate_user_input($_POST['email'] ?? '', 'email');
            if (!$email_validation['valid']) {
                send_json_response(false, $email_validation['message']);
            }
            
            $password_validation = validate_user_input($_POST['password'] ?? '', 'password');
            if (!$password_validation['valid']) {
                send_json_response(false, $password_validation['message']);
            }
            
            // Attempt authentication
            $auth_result = authenticate_member(
                $email_validation['value'], 
                $password_validation['value']
            );
            
            send_json_response(
                $auth_result['success'], 
                $auth_result['message'], 
                ['redirect' => $auth_result['redirect'] ?? '']
            );
            break;
            
        case 'register':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                send_json_response(false, 'Security token mismatch. Please refresh and try again.');
            }
            
            // Validate all input fields
            $validations = [
                'full_name' => validate_user_input($_POST['full_name'] ?? '', 'full_name'),
                'email' => validate_user_input($_POST['email'] ?? '', 'email'),
                'phone' => validate_user_input($_POST['phone'] ?? '', 'phone'),
                'password' => validate_user_input($_POST['password'] ?? '', 'password')
            ];
            
            // Check for validation errors
            foreach ($validations as $field => $validation) {
                if (!$validation['valid']) {
                    send_json_response(false, $validation['message']);
                }
            }
            
            // Check password confirmation
            $confirm_password = $_POST['confirm_password'] ?? '';
            if ($validations['password']['value'] !== $confirm_password) {
                send_json_response(false, 'Passwords do not match');
            }
            
            // Check terms agreement
            if (!isset($_POST['agree_terms']) || $_POST['agree_terms'] !== 'on') {
                send_json_response(false, 'You must agree to the terms and conditions');
            }
            
            // Check if email already exists
            if (user_email_exists($validations['email']['value'])) {
                send_json_response(false, 'Email already registered. Please use a different email.');
            }
            
            // Create member account
            $member_id = create_member(
                $validations['full_name']['value'],
                $validations['email']['value'],
                $validations['phone']['value'],
                $validations['password']['value']
            );
            
            if ($member_id) {
                send_json_response(true, 'Account created successfully! You can now sign in.');
            } else {
                send_json_response(false, 'Failed to create account. Please try again.');
            }
            break;
            
        case 'logout':
            $logout_result = logout_member();
            send_json_response($logout_result['success'], $logout_result['message']);
            break;
            
        case 'check_auth':
            $is_authenticated = is_member_authenticated();
            send_json_response(true, 'Auth status checked', [
                'authenticated' => $is_authenticated,
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['user_username'] ?? null,
                'full_name' => $_SESSION['user_full_name'] ?? null
            ]);
            break;
            

            
        case 'check_email':
            // AJAX endpoint to check email availability
            $email = validate_user_input($_POST['email'] ?? '', 'email');
            if (!$email['valid']) {
                send_json_response(false, $email['message']);
            }
            
            $exists = user_email_exists($email['value']);
            send_json_response(true, 'Email checked', ['available' => !$exists]);
            break;
            
        default:
            send_json_response(false, 'Invalid action specified');
            break;
    }

} catch (Exception $e) {
    // Log error for debugging
    error_log("User Auth API error: " . $e->getMessage());
    
    // Send generic error message (don't expose internal errors)
    send_json_response(false, 'An unexpected error occurred. Please try again.');
}
?> 