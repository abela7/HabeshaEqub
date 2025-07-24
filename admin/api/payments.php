<?php
/**
 * HabeshaEqub - Payments API
 * Handle all payment-related CRUD operations and management
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
            addPayment();
            break;
        case 'get':
            getPayment();
            break;
        case 'update':
            updatePayment();
            break;
        case 'delete':
            deletePayment();
            break;
        case 'verify':
            verifyPayment();
            break;
        case 'list':
            listPayments();
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
    error_log("Payment API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}

/**
 * Add new payment
 */
function addPayment() {
    global $pdo, $admin_id;
    
    // Validate required fields
    $member_id = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_month = $_POST['payment_month'] ?? '';
    
    if (!$member_id || !$amount || !$payment_date || !$payment_month) {
        echo json_encode(['success' => false, 'message' => 'Member, amount, payment date, and payment month are required']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    // Validate member exists and is active
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM members WHERE id = ? AND is_active = 1");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Invalid or inactive member selected']);
        return;
    }
    
    // Check for duplicate payment (same member and month)
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE member_id = ? AND payment_month = ?");
    $stmt->execute([$member_id, $payment_month]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payment for this member and month already exists']);
        return;
    }
    
    // Optional fields
    $payment_method = sanitize_input($_POST['payment_method'] ?? 'cash');
    $status = sanitize_input($_POST['status'] ?? 'pending');
    $receipt_number = sanitize_input($_POST['receipt_number'] ?? '');
    $late_fee = floatval($_POST['late_fee'] ?? 0);
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Generate payment ID
    $payment_id = generatePaymentId();
    
    // Insert payment
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            payment_id, member_id, amount, payment_date, payment_month, 
            status, payment_method, receipt_number, late_fee, notes,
            verified_by_admin, verified_by_admin_id, verification_date,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    // Convert 'completed' to 'paid' to match ENUM values
    if ($status === 'completed') {
        $status = 'paid';
    }
    
    $verified_by_admin = ($status === 'paid') ? 1 : 0;
    $verified_by_admin_id = ($status === 'paid') ? $admin_id : null;
    $verification_date = ($status === 'paid') ? date('Y-m-d H:i:s') : null;
    
    $stmt->execute([
        $payment_id, $member_id, $amount, $payment_date, $payment_month,
        $status, $payment_method, $receipt_number, $late_fee, $notes,
        $verified_by_admin, $verified_by_admin_id, $verification_date
    ]);
    
    // Update member's total contributed if payment is paid
    if ($status === 'paid') {
        $stmt = $pdo->prepare("
            UPDATE members 
            SET total_contributed = total_contributed + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $member_id]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payment added successfully',
        'payment_id' => $payment_id
    ]);
}

/**
 * Get payment details
 */
function getPayment() {
    global $pdo;
    
    $payment_id = intval($_GET['payment_id'] ?? 0);
    
    if (!$payment_id) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, m.id as member_db_id, m.first_name, m.last_name, m.member_id as member_code
        FROM payments p
        LEFT JOIN members m ON p.member_id = m.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'payment' => $payment]);
}

/**
 * Update payment
 */
function updatePayment() {
    global $pdo, $admin_id;
    

    
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_month = $_POST['payment_month'] ?? '';
    
    // Ensure payment_month is in correct format (YYYY-MM)
    if ($payment_month && !preg_match('/^\d{4}-\d{2}$/', $payment_month)) {
        echo json_encode(['success' => false, 'message' => 'Payment month must be in YYYY-MM format']);
        return;
    }
    
    if (!$payment_id || !$member_id || !$amount || !$payment_date || !$payment_month) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be provided']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    // Get current payment data
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $current_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }
    
    // Validate member exists and is active
    $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ? AND is_active = 1");
    $stmt->execute([$member_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid or inactive member selected']);
        return;
    }
    
    // Check for duplicate payment (same member and month, excluding current payment)
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE member_id = ? AND payment_month = ? AND id != ?");
    $stmt->execute([$member_id, $payment_month, $payment_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Payment for this member and month already exists']);
        return;
    }
    
    // Optional fields
    $payment_method = sanitize_input($_POST['payment_method'] ?? 'cash');
    $status = sanitize_input($_POST['status'] ?? 'pending');
    $receipt_number = sanitize_input($_POST['receipt_number'] ?? '');
    $late_fee = floatval($_POST['late_fee'] ?? 0);
    $notes = sanitize_input($_POST['notes'] ?? '');
    

    
    // Handle verification status changes  
    // Convert 'completed' to 'paid' to match ENUM values
    if ($status === 'completed') {
        $status = 'paid';
    }
    
    $verified_by_admin = ($status === 'paid') ? 1 : 0;
    $verified_by_admin_id = ($status === 'paid') ? $admin_id : null;
    $verification_date = ($status === 'paid') ? date('Y-m-d H:i:s') : null;
    

    
    // Update payment
    $stmt = $pdo->prepare("
        UPDATE payments SET 
            member_id = ?, amount = ?, payment_date = ?, payment_month = ?,
            status = ?, payment_method = ?, receipt_number = ?, late_fee = ?, notes = ?,
            verified_by_admin = ?, verified_by_admin_id = ?, verification_date = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $member_id, $amount, $payment_date, $payment_month,
        $status, $payment_method, $receipt_number, $late_fee, $notes,
        $verified_by_admin, $verified_by_admin_id, $verification_date,
        $payment_id
    ]);
    
    // Update member's total contributed based on status changes
    $old_amount = ($current_payment['status'] === 'paid') ? $current_payment['amount'] : 0;
    $new_amount = ($status === 'paid') ? $amount : 0;
    $contribution_change = $new_amount - $old_amount;
    
    if ($contribution_change != 0) {
        $stmt = $pdo->prepare("
            UPDATE members 
            SET total_contributed = total_contributed + ? 
            WHERE id = ?
        ");
        $stmt->execute([$contribution_change, $member_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Payment updated successfully']);
}

/**
 * Delete payment
 */
function deletePayment() {
    global $pdo;
    
    $payment_id = intval($_POST['payment_id'] ?? 0);
    
    if (!$payment_id) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    // Get payment details before deletion
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }
    
    // Delete payment
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    
    // Update member's total contributed if payment was paid
    if ($payment['status'] === 'paid') {
        $stmt = $pdo->prepare("
            UPDATE members 
            SET total_contributed = total_contributed - ? 
            WHERE id = ?
        ");
        $stmt->execute([$payment['amount'], $payment['member_id']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Payment deleted successfully']);
}

/**
 * Verify payment (mark as completed)
 */
function verifyPayment() {
    global $pdo, $admin_id;
    
    $payment_id = intval($_POST['payment_id'] ?? 0);
    
    if (!$payment_id) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    // Get current payment
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }
    
    if ($payment['status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Payment is already verified']);
        return;
    }
    
    // Update payment status to paid
    $stmt = $pdo->prepare("
        UPDATE payments SET 
            status = 'paid',
            verified_by_admin = 1,
            verified_by_admin_id = ?,
            verification_date = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $payment_id]);
    
    // Update member's total contributed
    $stmt = $pdo->prepare("
        UPDATE members 
        SET total_contributed = total_contributed + ? 
        WHERE id = ?
    ");
    $stmt->execute([$payment['amount'], $payment['member_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
}

/**
 * List payments with filters
 */
function listPayments() {
    global $pdo;
    
    // Get filter parameters
    $search = sanitize_input($_GET['search'] ?? '');
    $status = sanitize_input($_GET['status'] ?? '');
    $member_id = intval($_GET['member_id'] ?? 0);
    $month = sanitize_input($_GET['month'] ?? '');
    
    // Build query
    $query = "
        SELECT p.*, 
               m.id as member_db_id, m.first_name, m.last_name, m.member_id as member_code, m.email,
               va.username as verified_by_name
        FROM payments p 
        LEFT JOIN members m ON p.member_id = m.id
        LEFT JOIN admins va ON p.verified_by_admin_id = va.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply filters
    if ($search) {
        $query .= " AND (
            m.first_name LIKE ? OR 
            m.last_name LIKE ? OR 
            p.payment_id LIKE ? OR 
            p.amount LIKE ?
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
    
    if ($month) {
        $query .= " AND p.payment_month LIKE ?";
        $params[] = "$month%";
    }
    
    $query .= " ORDER BY p.payment_date DESC, p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    
    echo json_encode(['success' => true, 'payments' => $payments]);
}

/**
 * Generate unique payment ID
 */
function generatePaymentId() {
    global $pdo;
    
    do {
        // Format: HEP-YYYYMMDD-XXX (HabeshaEqub Payment)
        $payment_id = 'HEP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Check if ID already exists
        $stmt = $pdo->prepare("SELECT id FROM payments WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        $exists = $stmt->fetch();
    } while ($exists);
    
    return $payment_id;
}
?> 