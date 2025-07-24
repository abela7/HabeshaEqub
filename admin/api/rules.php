<?php
/**
 * HabeshaEqub Rules API
 * Handles CRUD operations for Equb rules management
 * Supports bilingual (English/Amharic) content
 */

require_once '../../includes/db.php';

// Set JSON header for all responses
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    send_json_response(false, 'Unauthorized access. Please login.');
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
 * Validate rule input data
 */
function validate_rule_data($data) {
    $errors = [];
    
    // Rule number validation
    if (empty($data['rule_number']) || !is_numeric($data['rule_number']) || $data['rule_number'] < 1) {
        $errors[] = 'Valid rule number is required (minimum 1)';
    }
    
    // English rule validation
    if (empty(trim($data['rule_en']))) {
        $errors[] = 'English rule content is required';
    }
    
    // Amharic rule validation
    if (empty(trim($data['rule_am']))) {
        $errors[] = 'Amharic rule content is required';
    }
    
    return $errors;
}

/**
 * Check if rule number already exists (for add/edit operations)
 */
function rule_number_exists($rule_number, $exclude_id = null) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM equb_rules WHERE rule_number = ?";
        $params = [$rule_number];
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        error_log("Rule number check error: " . $e->getMessage());
        return true; // Assume exists to be safe
    }
}

/**
 * Add new rule
 */
function add_rule($data) {
    global $pdo;
    
    try {
        // Check if rule number already exists
        if (rule_number_exists($data['rule_number'])) {
            return ['success' => false, 'message' => 'Rule number already exists. Please choose a different number.'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO equb_rules (
                rule_number, 
                rule_en, 
                rule_am, 
                is_active
            ) VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['rule_number'],
            trim($data['rule_en']),
            trim($data['rule_am']),
            isset($data['is_active']) ? 1 : 0
        ]);
        
        return ['success' => true, 'message' => 'Rule added successfully!'];
        
    } catch (PDOException $e) {
        error_log("Add rule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add rule. Please try again.'];
    }
}

/**
 * Get single rule for editing
 */
function get_rule($rule_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM equb_rules WHERE id = ? LIMIT 1");
        $stmt->execute([$rule_id]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rule) {
            return ['success' => false, 'message' => 'Rule not found.'];
        }
        
        return ['success' => true, 'rule' => $rule];
        
    } catch (PDOException $e) {
        error_log("Get rule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to retrieve rule data.'];
    }
}

/**
 * Update existing rule
 */
function update_rule($data) {
    global $pdo;
    
    try {
        $rule_id = $data['rule_id'];
        
        // Check if rule exists
        $stmt = $pdo->prepare("SELECT id FROM equb_rules WHERE id = ? LIMIT 1");
        $stmt->execute([$rule_id]);
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'Rule not found.'];
        }
        
        // Check if rule number conflicts with other rules
        if (rule_number_exists($data['rule_number'], $rule_id)) {
            return ['success' => false, 'message' => 'Rule number already exists. Please choose a different number.'];
        }
        
        $stmt = $pdo->prepare("
            UPDATE equb_rules SET 
                rule_number = ?, 
                rule_en = ?, 
                rule_am = ?, 
                is_active = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['rule_number'],
            trim($data['rule_en']),
            trim($data['rule_am']),
            isset($data['is_active']) ? 1 : 0,
            $rule_id
        ]);
        
        return ['success' => true, 'message' => 'Rule updated successfully!'];
        
    } catch (PDOException $e) {
        error_log("Update rule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update rule. Please try again.'];
    }
}

/**
 * Delete rule
 */
function delete_rule($rule_id) {
    global $pdo;
    
    try {
        // Check if rule exists
        $stmt = $pdo->prepare("SELECT rule_number, rule_en FROM equb_rules WHERE id = ? LIMIT 1");
        $stmt->execute([$rule_id]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rule) {
            return ['success' => false, 'message' => 'Rule not found.'];
        }
        
        // Delete the rule
        $stmt = $pdo->prepare("DELETE FROM equb_rules WHERE id = ?");
        $stmt->execute([$rule_id]);
        
        return ['success' => true, 'message' => "Rule #{$rule['rule_number']} deleted successfully!"];
        
    } catch (PDOException $e) {
        error_log("Delete rule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete rule. Please try again.'];
    }
}

/**
 * Get all rules (for listing)
 */
function get_all_rules() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM equb_rules 
            ORDER BY rule_number ASC
        ");
        
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'rules' => $rules];
        
    } catch (PDOException $e) {
        error_log("Get all rules error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to retrieve rules.'];
    }
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
        
        case 'add':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                send_json_response(false, 'Security token mismatch. Please refresh and try again.');
            }
            
            // Validate input data
            $validation_errors = validate_rule_data($_POST);
            if (!empty($validation_errors)) {
                send_json_response(false, implode('; ', $validation_errors));
            }
            
            // Add rule
            $result = add_rule($_POST);
            send_json_response($result['success'], $result['message']);
            break;
            
        case 'get':
            // Get single rule for editing
            $rule_id = $_GET['id'] ?? '';
            if (empty($rule_id) || !is_numeric($rule_id)) {
                send_json_response(false, 'Invalid rule ID');
            }
            
            $result = get_rule($rule_id);
            send_json_response($result['success'], $result['message'] ?? '', $result['rule'] ?? []);
            break;
            
        case 'edit':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                send_json_response(false, 'Security token mismatch. Please refresh and try again.');
            }
            
            // Validate rule ID
            if (empty($_POST['rule_id']) || !is_numeric($_POST['rule_id'])) {
                send_json_response(false, 'Invalid rule ID');
            }
            
            // Validate input data
            $validation_errors = validate_rule_data($_POST);
            if (!empty($validation_errors)) {
                send_json_response(false, implode('; ', $validation_errors));
            }
            
            // Update rule
            $result = update_rule($_POST);
            send_json_response($result['success'], $result['message']);
            break;
            
        case 'delete':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                send_json_response(false, 'Security token mismatch. Please refresh and try again.');
            }
            
            // Validate rule ID
            $rule_id = $_POST['rule_id'] ?? '';
            if (empty($rule_id) || !is_numeric($rule_id)) {
                send_json_response(false, 'Invalid rule ID');
            }
            
            // Delete rule
            $result = delete_rule($rule_id);
            send_json_response($result['success'], $result['message']);
            break;
            
        case 'list':
            // Get all rules
            $result = get_all_rules();
            send_json_response($result['success'], $result['message'] ?? '', $result['rules'] ?? []);
            break;
            
        default:
            send_json_response(false, 'Invalid action specified');
            break;
    }

} catch (Exception $e) {
    // Log error for debugging
    error_log("Rules API error: " . $e->getMessage());
    
    // Send generic error message (don't expose internal errors)
    send_json_response(false, 'An unexpected error occurred. Please try again.');
}
?> 