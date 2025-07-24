<?php
/**
 * HabeshaEqub - Payouts API
 * Handle all payout-related CRUD operations and management
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
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF token verification for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'list') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid security token. Please refresh the page and try again.'
        ]);
        exit;
    }
}

try {
    switch ($action) {
        case 'add':
            addPayout();
            break;
        case 'get':
            getPayout();
            break;
        case 'update':
            updatePayout();
            break;
        case 'delete':
            deletePayout();
            break;
        case 'process':
            processPayout();
            break;
        case 'list':
            listPayouts();
            break;
        case 'get_csrf_token':
            echo json_encode([
                'success' => true, 
                'csrf_token' => generate_csrf_token()
            ]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Payouts API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred processing your request']);
}

/**
 * Add new payout
 */
function addPayout() {
    global $pdo, $admin_id;
    
    $member_id = intval($_POST['member_id'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    
    if (!$member_id || !$total_amount || !$scheduled_date) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be provided']);
        return;
    }
    
    if ($total_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total amount must be greater than 0']);
        return;
    }
    
    // Validate member exists and is active
    $stmt = $pdo->prepare("SELECT id, member_id, first_name, last_name FROM members WHERE id = ? AND is_active = 1");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Invalid or inactive member selected']);
        return;
    }
    
    // Check if member already has a payout for the same scheduled date
    $stmt = $pdo->prepare("SELECT id FROM payouts WHERE member_id = ? AND scheduled_date = ?");
    $stmt->execute([$member_id, $scheduled_date]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payout for this member and date already exists']);
        return;
    }
    
    // Optional fields
    $payout_method = sanitize_input($_POST['payout_method'] ?? 'bank_transfer');
    $admin_fee = floatval($_POST['admin_fee'] ?? 0);
    $status = sanitize_input($_POST['status'] ?? 'scheduled');
    $payout_notes = sanitize_input($_POST['payout_notes'] ?? '');
    
    // Calculate net amount
    $net_amount = $total_amount - $admin_fee;
    
    // Generate payout ID: PAYOUT-MEMBERINITIALS-MMYYYY (e.g., PAYOUT-MW-012024)
    $payout_id = generatePayoutId($member['member_id'], $scheduled_date);
    
    // Ensure payout ID is unique
    $stmt = $pdo->prepare("SELECT id FROM payouts WHERE payout_id = ?");
    $stmt->execute([$payout_id]);
    if ($stmt->fetch()) {
        // Add counter if duplicate
        $counter = 1;
        do {
            $counter++;
            $temp_id = $payout_id . '-' . $counter;
            $stmt->execute([$temp_id]);
        } while ($stmt->fetch());
        $payout_id = $temp_id;
    }
    
    // Insert payout
    $stmt = $pdo->prepare("
        INSERT INTO payouts 
        (payout_id, member_id, total_amount, scheduled_date, status, payout_method, 
         admin_fee, net_amount, payout_notes, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $payout_id, $member_id, $total_amount, $scheduled_date, $status, 
        $payout_method, $admin_fee, $net_amount, $payout_notes
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payout scheduled successfully',
        'payout_id' => $payout_id
    ]);
}

/**
 * Get payout by ID
 */
function getPayout() {
    global $pdo;
    
    $payout_id = intval($_GET['payout_id'] ?? 0);
    
    if (!$payout_id) {
        echo json_encode(['success' => false, 'message' => 'Payout ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, m.first_name, m.last_name, m.member_id as member_code
        FROM payouts p
        LEFT JOIN members m ON p.member_id = m.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payout_id]);
    $payout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payout) {
        echo json_encode(['success' => false, 'message' => 'Payout not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'payout' => $payout]);
}

/**
 * Update payout
 */
function updatePayout() {
    global $pdo, $admin_id;
    
    $payout_id = intval($_POST['payout_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    
    if (!$payout_id || !$member_id || !$total_amount || !$scheduled_date) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be provided']);
        return;
    }
    
    if ($total_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total amount must be greater than 0']);
        return;
    }
    
    // Get current payout data
    $stmt = $pdo->prepare("SELECT * FROM payouts WHERE id = ?");
    $stmt->execute([$payout_id]);
    $current_payout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_payout) {
        echo json_encode(['success' => false, 'message' => 'Payout not found']);
        return;
    }
    
    // Validate member exists and is active
    $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ? AND is_active = 1");
    $stmt->execute([$member_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid or inactive member selected']);
        return;
    }
    
    // Check for duplicate payout (same member and date, excluding current payout)
    $stmt = $pdo->prepare("SELECT id FROM payouts WHERE member_id = ? AND scheduled_date = ? AND id != ?");
    $stmt->execute([$member_id, $scheduled_date, $payout_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payout for this member and date already exists']);
        return;
    }
    
    // Optional fields
    $payout_method = sanitize_input($_POST['payout_method'] ?? 'bank_transfer');
    $admin_fee = floatval($_POST['admin_fee'] ?? 0);
    $status = sanitize_input($_POST['status'] ?? 'scheduled');
    $payout_notes = sanitize_input($_POST['payout_notes'] ?? '');
    
    // Calculate net amount
    $net_amount = $total_amount - $admin_fee;
    
    // Handle status changes
    $actual_payout_date = null;
    $processed_by_admin_id = null;
    
    if ($status === 'completed' && $current_payout['status'] !== 'completed') {
        $actual_payout_date = date('Y-m-d');
        $processed_by_admin_id = $admin_id;
        
        // Update member's has_received_payout flag
        $stmt = $pdo->prepare("UPDATE members SET has_received_payout = 1 WHERE id = ?");
        $stmt->execute([$member_id]);
    } elseif ($current_payout['status'] === 'completed' && $status !== 'completed') {
        // If changing from completed to other status, reset member flag
        $stmt = $pdo->prepare("UPDATE members SET has_received_payout = 0 WHERE id = ?");
        $stmt->execute([$member_id]);
    } elseif ($status === 'completed') {
        // Keep existing values if already completed
        $actual_payout_date = $current_payout['actual_payout_date'];
        $processed_by_admin_id = $current_payout['processed_by_admin_id'];
    }
    
    // Update payout
    $stmt = $pdo->prepare("
        UPDATE payouts SET 
            member_id = ?, total_amount = ?, scheduled_date = ?, status = ?, 
            payout_method = ?, admin_fee = ?, net_amount = ?, payout_notes = ?,
            actual_payout_date = ?, processed_by_admin_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $member_id, $total_amount, $scheduled_date, $status, 
        $payout_method, $admin_fee, $net_amount, $payout_notes,
        $actual_payout_date, $processed_by_admin_id, $payout_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Payout updated successfully']);
}

/**
 * Delete payout
 */
function deletePayout() {
    global $pdo;
    
    $payout_id = intval($_POST['payout_id'] ?? 0);
    
    if (!$payout_id) {
        echo json_encode(['success' => false, 'message' => 'Payout ID is required']);
        return;
    }
    
    // Get payout data before deletion
    $stmt = $pdo->prepare("SELECT * FROM payouts WHERE id = ?");
    $stmt->execute([$payout_id]);
    $payout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payout) {
        echo json_encode(['success' => false, 'message' => 'Payout not found']);
        return;
    }
    
    // If payout was completed, reset member's payout flag
    if ($payout['status'] === 'completed') {
        $stmt = $pdo->prepare("UPDATE members SET has_received_payout = 0 WHERE id = ?");
        $stmt->execute([$payout['member_id']]);
    }
    
    // Delete payout
    $stmt = $pdo->prepare("DELETE FROM payouts WHERE id = ?");
    $stmt->execute([$payout_id]);
    
    echo json_encode(['success' => true, 'message' => 'Payout deleted successfully']);
}

/**
 * Process payout (mark as completed)
 */
function processPayout() {
    global $pdo, $admin_id;
    
    $payout_id = intval($_POST['payout_id'] ?? 0);
    
    if (!$payout_id) {
        echo json_encode(['success' => false, 'message' => 'Payout ID is required']);
        return;
    }
    
    // Get current payout
    $stmt = $pdo->prepare("SELECT * FROM payouts WHERE id = ?");
    $stmt->execute([$payout_id]);
    $payout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payout) {
        echo json_encode(['success' => false, 'message' => 'Payout not found']);
        return;
    }
    
    if ($payout['status'] === 'completed') {
        echo json_encode(['success' => false, 'message' => 'Payout is already processed']);
        return;
    }
    
    // Update payout status to completed
    $stmt = $pdo->prepare("
        UPDATE payouts SET 
            status = 'completed',
            actual_payout_date = CURDATE(),
            processed_by_admin_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $payout_id]);
    
    // Update member's has_received_payout flag
    $stmt = $pdo->prepare("UPDATE members SET has_received_payout = 1 WHERE id = ?");
    $stmt->execute([$payout['member_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Payout processed successfully']);
}

/**
 * List payouts with filters
 */
function listPayouts() {
    global $pdo;
    
    // Get filter parameters
    $search = sanitize_input($_GET['search'] ?? '');
    $status = sanitize_input($_GET['status'] ?? '');
    $member_id = intval($_GET['member_id'] ?? 0);
    
    // Build query
    $query = "
        SELECT p.*, 
               m.first_name, m.last_name, m.member_id as member_code, m.email,
               pa.username as processed_by_name
        FROM payouts p 
        LEFT JOIN members m ON p.member_id = m.id
        LEFT JOIN admins pa ON p.processed_by_admin_id = pa.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply filters
    if ($search) {
        $query .= " AND (
            m.first_name LIKE ? OR 
            m.last_name LIKE ? OR 
            p.payout_id LIKE ? OR 
            p.total_amount LIKE ?
        )";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if ($status) {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }
    
    if ($member_id) {
        $query .= " AND p.member_id = ?";
        $params[] = $member_id;
    }
    
    $query .= " ORDER BY p.scheduled_date DESC, p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'payouts' => $payouts]);
}

/**
 * Generate payout ID
 */
function generatePayoutId($member_code, $scheduled_date) {
    // Extract initials from member code (e.g., HEM-MW1 -> MW)
    $parts = explode('-', $member_code);
    $initials = isset($parts[1]) ? preg_replace('/\d+/', '', $parts[1]) : 'XX';
    
    // Format date as MMYYYY
    $date = date('mY', strtotime($scheduled_date));
    
    return "PAYOUT-{$initials}-{$date}";
}
?> 