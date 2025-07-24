<?php
/**
 * HabeshaEqub - Members Management API
 * AJAX endpoint for all member CRUD operations
 */

require_once '../../includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$admin_id = $_SESSION['admin_id'] ?? 1;

// Get the action from POST data
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        addMember();
        break;
    case 'edit':
        editMember();
        break;
    case 'update':
        updateMember();
        break;
    case 'delete':
        deleteMember();
        break;
    case 'toggle_status':
        toggleMemberStatus();
        break;
    case 'get_member':
        getMember();
        break;
    case 'list':
        listMembers();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Add new member
 */
function addMember() {
    global $pdo;
    
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'monthly_payment', 'payout_position'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                return;
            }
        }
        
        // Sanitize inputs
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $monthly_payment = floatval($_POST['monthly_payment']);
        $payout_position = intval($_POST['payout_position']);
        
        // Optional fields
        $guarantor_first_name = sanitize_input($_POST['guarantor_first_name'] ?? '');
        $guarantor_last_name = sanitize_input($_POST['guarantor_last_name'] ?? '');
        $guarantor_phone = sanitize_input($_POST['guarantor_phone'] ?? '');
        $guarantor_email = sanitize_input($_POST['guarantor_email'] ?? '');
        $guarantor_relationship = sanitize_input($_POST['guarantor_relationship'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            return;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
        
        // Check if payout position is already taken
        $stmt = $pdo->prepare("SELECT id FROM members WHERE payout_position = ?");
        $stmt->execute([$payout_position]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Payout position already taken']);
            return;
        }
        
        // Generate member ID
        $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
        
        // Find next available number for these initials
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id LIKE ? ORDER BY member_id DESC LIMIT 1");
        $stmt->execute(["HEM-{$initials}%"]);
        $last_member = $stmt->fetch();
        
        if ($last_member) {
            $last_number = intval(substr($last_member['member_id'], -1));
            $next_number = $last_number + 1;
        } else {
            $next_number = 1;
        }
        
        $member_id = "HEM-{$initials}{$next_number}";
        
        // Generate random 6-character password
        $password = generateRandomPassword();
        
        // Calculate payout month (assuming monthly cycle starting from current month)
        $start_date = new DateTime();
        $payout_date = clone $start_date;
        $payout_date->add(new DateInterval("P" . ($payout_position - 1) . "M"));
        $payout_month = $payout_date->format('Y-m');
        
        // Insert member
        $stmt = $pdo->prepare("
            INSERT INTO members (
                member_id, first_name, last_name, email, phone, password, 
                monthly_payment, payout_position, payout_month, total_contributed, 
                has_received_payout, guarantor_first_name, guarantor_last_name, 
                guarantor_phone, guarantor_email, guarantor_relationship, 
                is_active, is_approved, email_verified, join_date, 
                notification_preferences, notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, 1, 1, 0, NOW(), 'email,sms', ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $member_id, $first_name, $last_name, $email, $phone, $password,
            $monthly_payment, $payout_position, $payout_month,
            $guarantor_first_name, $guarantor_last_name, $guarantor_phone, 
            $guarantor_email, $guarantor_relationship, $notes
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Member added successfully',
                'member_id' => $member_id,
                'password' => $password
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add member']);
        }
        
    } catch (PDOException $e) {
        error_log("Add member error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Get member details for editing
 */
function getMember() {
    global $pdo;
    
    $member_id = intval($_POST['member_id'] ?? 0);
    
    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($member) {
            echo json_encode(['success' => true, 'member' => $member]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Member not found']);
        }
    } catch (PDOException $e) {
        error_log("Get member error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Update member
 */
function updateMember() {
    global $pdo;
    
    $member_id = intval($_POST['member_id'] ?? 0);
    
    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
        return;
    }
    
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'monthly_payment', 'payout_position'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                return;
            }
        }
        
        // Sanitize inputs
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $monthly_payment = floatval($_POST['monthly_payment']);
        $payout_position = intval($_POST['payout_position']);
        
        // Optional fields
        $guarantor_first_name = sanitize_input($_POST['guarantor_first_name'] ?? '');
        $guarantor_last_name = sanitize_input($_POST['guarantor_last_name'] ?? '');
        $guarantor_phone = sanitize_input($_POST['guarantor_phone'] ?? '');
        $guarantor_email = sanitize_input($_POST['guarantor_email'] ?? '');
        $guarantor_relationship = sanitize_input($_POST['guarantor_relationship'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            return;
        }
        
        // Check if email already exists (excluding current member)
        $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $stmt->execute([$email, $member_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
        
        // Check if payout position is already taken (excluding current member)
        $stmt = $pdo->prepare("SELECT id FROM members WHERE payout_position = ? AND id != ?");
        $stmt->execute([$payout_position, $member_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Payout position already taken']);
            return;
        }
        
        // Update member
        $stmt = $pdo->prepare("
            UPDATE members SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, 
                monthly_payment = ?, payout_position = ?,
                guarantor_first_name = ?, guarantor_last_name = ?, 
                guarantor_phone = ?, guarantor_email = ?, guarantor_relationship = ?, 
                notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $first_name, $last_name, $email, $phone, $monthly_payment, $payout_position,
            $guarantor_first_name, $guarantor_last_name, $guarantor_phone, 
            $guarantor_email, $guarantor_relationship, $notes, $member_id
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update member']);
        }
        
    } catch (PDOException $e) {
        error_log("Update member error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Delete member
 */
function deleteMember() {
    global $pdo;
    
    $member_id = intval($_POST['member_id'] ?? 0);
    
    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
        return;
    }
    
    try {
        // Check if member has payments or payouts
        $stmt = $pdo->prepare("SELECT COUNT(*) as payment_count FROM payments WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $payment_count = $stmt->fetch()['payment_count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as payout_count FROM payouts WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $payout_count = $stmt->fetch()['payout_count'];
        
        if ($payment_count > 0 || $payout_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete member with existing payments or payouts']);
            return;
        }
        
        // Delete member
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $result = $stmt->execute([$member_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete member']);
        }
        
    } catch (PDOException $e) {
        error_log("Delete member error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Toggle member status (active/inactive)
 */
function toggleMemberStatus() {
    global $pdo, $admin_id;
    
    $member_id = intval($_POST['member_id'] ?? 0);
    $status = intval($_POST['status'] ?? 0);
    
    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE members SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$status, $member_id]);
        
        if ($result) {
            $status_text = $status ? 'activated' : 'deactivated';
            echo json_encode(['success' => true, 'message' => "Member {$status_text} successfully"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update member status']);
        }
        
    } catch (PDOException $e) {
        error_log("Toggle member status error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * List members with filters
 */
function listMembers() {
    global $pdo;
    
    try {
        $where_conditions = [];
        $params = [];
        
        // Search filter
        if (!empty($_POST['search'])) {
            $search = '%' . sanitize_input($_POST['search']) . '%';
            $where_conditions[] = "(CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ? OR member_id LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
        }
        
        // Status filter
        if (isset($_POST['status']) && $_POST['status'] !== '') {
            $where_conditions[] = "is_active = ?";
            $params[] = intval($_POST['status']);
        }
        
        // Payout status filter
        if (isset($_POST['payout_status']) && $_POST['payout_status'] !== '') {
            $where_conditions[] = "has_received_payout = ?";
            $params[] = intval($_POST['payout_status']);
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "
            SELECT m.*, 
                   COUNT(p.id) as total_payments,
                   COALESCE(SUM(p.amount), 0) as total_paid
            FROM members m 
            LEFT JOIN payments p ON m.id = p.member_id AND p.status = 'completed'
            {$where_clause}
            GROUP BY m.id 
            ORDER BY m.payout_position ASC, m.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'members' => $members]);
        
    } catch (PDOException $e) {
        error_log("List members error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Generate random 6-character alphanumeric password
 */
function generateRandomPassword($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}
?> 