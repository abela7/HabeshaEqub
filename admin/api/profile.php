<?php
/**
 * HabeshaEqub - Profile API
 * Handle admin profile updates and password changes
 */

require_once '../../includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$action = $_POST['action'] ?? '';

// CSRF token verification
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid security token. Please refresh the page and try again.'
    ]);
    exit;
}

try {
    switch ($action) {
        case 'update_profile':
            updateProfile();
            break;
        case 'change_password':
            changePassword();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Profile API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred processing your request']);
}

/**
 * Update admin profile information
 */
function updateProfile() {
    global $pdo, $admin_id;
    
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    // Validate required fields
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        return;
    }
    
    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters and contain only letters, numbers, and underscores']);
        return;
    }
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        return;
    }
    
    // Validate phone if provided
    if (!empty($phone) && !preg_match('/^[\+]?[\d\s\-\(\)]{7,20}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        return;
    }
    
    // Check if username is already taken by another admin
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt->execute([$username, $admin_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username is already taken']);
        return;
    }
    
    // Check if email is already taken by another admin (if provided)
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email is already in use']);
            return;
        }
    }
    
    // Update profile
    $stmt = $pdo->prepare("
        UPDATE admins 
        SET username = ?, email = ?, phone = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([$username, $email ?: null, $phone ?: null, $admin_id]);
    
    // Update session if username changed
    $new_username = null;
    if ($_SESSION['admin_username'] !== $username) {
        $_SESSION['admin_username'] = $username;
        $new_username = $username;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully',
        'new_username' => $new_username
    ]);
}

/**
 * Change admin password
 */
function changePassword() {
    global $pdo, $admin_id;
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        return;
    }
    
    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }
    
    // Validate password strength
    if (!validatePasswordStrength($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password does not meet security requirements']);
        return;
    }
    
    // Get current admin data
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        return;
    }
    
    // Verify current password
    if (!password_verify($current_password, $admin['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    // Check if new password is different from current
    if (password_verify($new_password, $admin['password'])) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from current password']);
        return;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashed_password, $admin_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password changed successfully'
    ]);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    // Password must be at least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // Must contain at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Must contain at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Must contain at least one number
    if (!preg_match('/\d/', $password)) {
        return false;
    }
    
    return true;
}
?> 