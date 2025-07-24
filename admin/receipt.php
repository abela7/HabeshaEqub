<?php
/**
 * HabeshaEqub - Receipt Generator
 * Generate professional receipts for payments and payouts
 */

require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Get receipt parameters
$type = $_GET['type'] ?? ''; // 'payment' or 'payout'
$transaction_id = intval($_GET['id'] ?? 0);

// Verify CSRF token for security
if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
    die('Invalid security token');
}

if (!in_array($type, ['payment', 'payout']) || !$transaction_id) {
    die('Invalid receipt parameters');
}

try {
    if ($type === 'payment') {
        $receipt_data = generatePaymentReceipt($transaction_id);
    } else {
        $receipt_data = generatePayoutReceipt($transaction_id);
    }
    
    if (!$receipt_data) {
        die('Transaction not found or access denied. Transaction ID: ' . $transaction_id . ', Type: ' . $type);
    }
    
    // Generate and output receipt HTML
    $receipt_html = generateReceiptHTML($receipt_data, $type);
    
    // Set headers for PDF-ready HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $receipt_data['filename'] . '"');
    
    echo $receipt_html;
    
} catch (Exception $e) {
    error_log("Receipt Error: " . $e->getMessage());
    die('Error generating receipt: ' . $e->getMessage() . '<br><br>Debug Info:<br>Type: ' . $type . '<br>ID: ' . $transaction_id);
}

/**
 * Generate payment receipt data
 */
function generatePaymentReceipt($payment_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            m.first_name,
            m.last_name,
            m.member_id,
            m.email,
            a.username as verified_by
        FROM payments p
        LEFT JOIN members m ON p.member_id = m.id
        LEFT JOIN admins a ON p.verified_by_admin_id = a.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        return false;
    }
    
    // Calculate totals and fees (if any)
    $subtotal = $payment['amount'];
    $processing_fee = 0; // Add if you have processing fees
    $total = $subtotal + $processing_fee;
    
    return [
        'type' => 'payment',
        'transaction_id' => $payment['payment_id'],
        'member_name' => $payment['first_name'] . ' ' . $payment['last_name'],
        'member_id' => $payment['member_id'],
        'member_email' => $payment['email'],
        'amount' => $payment['amount'],
        'subtotal' => $subtotal,
        'processing_fee' => $processing_fee,
        'total' => $total,
        'payment_date' => $payment['payment_date'],
        'payment_month' => $payment['payment_month'],
        'status' => $payment['status'],
        'payment_method' => $payment['payment_method'],
        'verified_by' => $payment['verified_by'] ?? 'System',
        'created_at' => $payment['created_at'],
        'filename' => 'Payment_Receipt_' . $payment['payment_id'] . '.html'
    ];
}

/**
 * Generate payout receipt data
 */
function generatePayoutReceipt($payout_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            m.first_name,
            m.last_name,
            m.member_id,
            m.email,
            a.username as processed_by
        FROM payouts p
        LEFT JOIN members m ON p.member_id = m.id
        LEFT JOIN admins a ON p.processed_by_admin_id = a.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payout_id]);
    $payout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payout) {
        return false;
    }
    
    return [
        'type' => 'payout',
        'transaction_id' => $payout['payout_id'],
        'member_name' => $payout['first_name'] . ' ' . $payout['last_name'],
        'member_id' => $payout['member_id'],
        'member_email' => $payout['email'],
        'total_amount' => $payout['total_amount'],
        'admin_fee' => $payout['admin_fee'],
        'net_amount' => $payout['net_amount'],
        'scheduled_date' => $payout['scheduled_date'],
        'actual_payout_date' => $payout['actual_payout_date'],
        'status' => $payout['status'],
        'payout_method' => $payout['payout_method'],
        'processed_by' => $payout['processed_by'] ?? 'System',
        'transaction_reference' => $payout['transaction_reference'],
        'payout_notes' => $payout['payout_notes'],
        'created_at' => $payout['created_at'],
        'filename' => 'Payout_Receipt_' . $payout['payout_id'] . '.html'
    ];
}

/**
 * Generate receipt HTML template
 */
function generateReceiptHTML($data, $type) {
    global $admin_username;
    
    $is_payment = ($type === 'payment');
    $title = $is_payment ? 'Payment Receipt' : 'Payout Receipt';
    
    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $title . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #fff;
                color: #333;
            }
            
            .receipt {
                border: 2px solid #13665C;
                border-radius: 8px;
                padding: 30px;
                background: #fff;
            }
            
            .header {
                text-align: center;
                border-bottom: 2px solid #13665C;
                padding-bottom: 20px;
                margin-bottom: 25px;
            }
            
            .company-name {
                font-size: 24px;
                font-weight: bold;
                color: #13665C;
                margin-bottom: 10px;
            }
            
            .receipt-title {
                background: #13665C;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                font-size: 18px;
                font-weight: bold;
                margin: 15px 0;
            }
            
            .details {
                margin: 20px 0;
            }
            
            .row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .row:last-child {
                border-bottom: none;
            }
            
            .label {
                font-weight: 600;
                color: #555;
            }
            
            .value {
                color: #333;
            }
            
            .amount-section {
                background: #f8f9fa;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                border-left: 4px solid #E9C46A;
            }
            
            .amount-row {
                display: flex;
                justify-content: space-between;
                margin: 5px 0;
            }
            
            .total-row {
                border-top: 2px solid #13665C;
                margin-top: 10px;
                padding-top: 10px;
                font-weight: bold;
                font-size: 18px;
                color: #13665C;
            }
            
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                font-size: 12px;
                color: #666;
            }
            
            @media print {
                body { margin: 0; padding: 10px; }
                .receipt { border: 1px solid #333; }
            }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <div class="company-name">HabeshaEqub</div>
                <div class="receipt-title">' . $title . '</div>
            </div>
            
            <div class="details">
                <div class="row">
                    <span class="label">Member:</span>
                    <span class="value">' . htmlspecialchars($data['member_name']) . ' (' . htmlspecialchars($data['member_id']) . ')</span>
                </div>
                <div class="row">
                    <span class="label">Transaction ID:</span>
                    <span class="value">' . htmlspecialchars($data['transaction_id']) . '</span>
                </div>';
    
    if ($is_payment) {
        $html .= '
                <div class="row">
                    <span class="label">Payment Date:</span>
                    <span class="value">' . date('M j, Y', strtotime($data['payment_date'])) . '</span>
                </div>
                <div class="row">
                    <span class="label">Payment Method:</span>
                    <span class="value">' . ucfirst(str_replace('_', ' ', $data['payment_method'])) . '</span>
                </div>';
    } else {
        $html .= '
                <div class="row">
                    <span class="label">Payout Date:</span>
                    <span class="value">' . ($data['actual_payout_date'] ? date('M j, Y', strtotime($data['actual_payout_date'])) : 'Pending') . '</span>
                </div>
                <div class="row">
                    <span class="label">Payout Method:</span>
                    <span class="value">' . ucfirst(str_replace('_', ' ', $data['payout_method'])) . '</span>
                </div>';
    }
    
    $html .= '
                <div class="row">
                    <span class="label">Status:</span>
                    <span class="value">' . ucfirst($data['status']) . '</span>
                </div>
            </div>
            
            <div class="amount-section">';
    
    if ($is_payment) {
        $html .= '
                <div class="amount-row">
                    <span>Monthly Contribution:</span>
                    <span>£' . number_format($data['amount'], 2) . '</span>
                </div>';
        
        if ($data['processing_fee'] > 0) {
            $html .= '
                <div class="amount-row">
                    <span>Processing Fee:</span>
                    <span>£' . number_format($data['processing_fee'], 2) . '</span>
                </div>';
        }
        
        $html .= '
                <div class="amount-row total-row">
                    <span>Total Paid:</span>
                    <span>£' . number_format($data['total'], 2) . '</span>
                </div>';
    } else {
        $html .= '
                <div class="amount-row">
                    <span>Gross Amount:</span>
                    <span>£' . number_format($data['total_amount'], 2) . '</span>
                </div>';
        
        if ($data['admin_fee'] > 0) {
            $html .= '
                <div class="amount-row">
                    <span>Admin Fee:</span>
                    <span>-£' . number_format($data['admin_fee'], 2) . '</span>
                </div>';
        }
        
        $html .= '
                <div class="amount-row total-row">
                    <span>Net Received:</span>
                    <span>£' . number_format($data['net_amount'], 2) . '</span>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="footer">
                <p>Generated on ' . date('M j, Y \a\t g:i A') . '</p>
                <p><strong>Keep this receipt for your records</strong></p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?> 