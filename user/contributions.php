<?php
/**
 * HabeshaEqub - Member Contributions/Payments Page
 * Professional financial dashboard showing payment history and contribution tracking
 */

// FORCE NO CACHING
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../languages/translator.php';

// Get current member data
$user_id = $_SESSION['user_id'] ?? 1; // Michael Werkneh = ID 1

// Get member information
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               COUNT(p.id) as total_payments,
               COALESCE(SUM(CASE WHEN p.status IN ('paid', 'completed') THEN p.amount ELSE 0 END), 0) as total_contributed,
               COALESCE(SUM(p.late_fee), 0) as total_late_fees,
               MAX(p.payment_date) as last_payment_date,
               (SELECT COUNT(*) FROM members WHERE is_active = 1) as total_active_members
        FROM members m 
        LEFT JOIN payments p ON m.id = p.member_id
        WHERE m.id = ? AND m.is_active = 1
        GROUP BY m.id
    ");
    $stmt->execute([$user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        die("❌ ERROR: No member found with ID $user_id");
    }
} catch (PDOException $e) {
    die("❌ DATABASE ERROR: " . $e->getMessage());
}

// Calculate financial statistics
$monthly_contribution = (float)$member['monthly_payment'];
$total_contributed = (float)$member['total_contributed']; 
$total_members = (int)$member['total_active_members'];
$expected_total = $total_members * $monthly_contribution;
$progress_percentage = $expected_total > 0 ? ($total_contributed / $expected_total) * 100 : 0;

// Calculate current month payment status
$current_month = date('Y-m');
$current_month_start = $current_month . '-01';

try {
    $stmt = $pdo->prepare("
        SELECT * FROM payments 
        WHERE member_id = ? AND payment_month = ? 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $current_month_start]);
    $current_payment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $current_payment = null;
}

// Calculate next payment due date
$next_due_date = date('Y-m-01', strtotime('first day of next month'));
$days_until_due = max(0, floor((strtotime($next_due_date) - time()) / (60 * 60 * 24)));

// Get comprehensive payment history
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               CASE 
                   WHEN p.payment_date IS NOT NULL AND p.payment_date != '0000-00-00' 
                   THEN DATE_FORMAT(p.payment_date, '%b %d, %Y') 
                   ELSE NULL 
               END as formatted_date,
               CASE 
                   WHEN p.payment_month IS NOT NULL AND p.payment_month != '0000-00-00' 
                   THEN DATE_FORMAT(p.payment_month, '%M %Y') 
                   ELSE 'UNKNOWN_MONTH' 
               END as payment_month_name,
               CASE 
                   WHEN p.verified_by_admin = 1 THEN 'verified'
                   WHEN p.verified_by_admin = 0 AND p.status = 'paid' THEN 'pending_verification'
                   ELSE 'not_verified'
               END as verification_status
        FROM payments p 
        WHERE p.member_id = ?
        ORDER BY p.payment_month DESC, p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $payments = [];
}

// Calculate payment statistics
$total_payments = count($payments);
$on_time_payments = 0;
$late_payments = 0;
$consecutive_payments = 0;

foreach ($payments as $payment) {
    if ($payment['status'] === 'paid' && $payment['late_fee'] == 0) {
        $on_time_payments++;
    } elseif ($payment['status'] === 'late') {
        $late_payments++;
    }
}

$on_time_rate = $total_payments > 0 ? ($on_time_payments / $total_payments) * 100 : 0;

// Cache buster
$cache_buster = time() . '_' . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('contributions.page_title'); ?> - HabeshaEqub</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Pictures/Icon/favicon-16x16.png">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo $cache_buster; ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo $cache_buster; ?>" rel="stylesheet" crossorigin="anonymous">
    <link href="../assets/css/style.css?v=<?php echo $cache_buster; ?>" rel="stylesheet">

<style>
/* === PROFESSIONAL CONTRIBUTIONS PAGE DESIGN === */

/* Professional 6-Color Palette - Clean & Consistent */
:root {
    --color-cream: #F1ECE2;
    --color-dark-purple: #4D4052;
    --color-deep-purple: #301934;
    --color-gold: #DAA520;
    --color-light-gold: #CDAF56;
    --color-brown: #5D4225;
    --color-white: #FFFFFF;
    --color-light-bg: #F1ECE2;
    --color-border: rgba(77, 64, 82, 0.15);
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 20px rgba(48, 25, 52, 0.06);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
}

.page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-deep-purple);
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header p {
    font-size: 16px;
    color: var(--color-dark-purple);
    margin: 0;
    opacity: 0.8;
}

/* Section Styling */
.section-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--color-deep-purple);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-subtitle {
    font-size: 15px;
    color: var(--color-dark-purple);
    margin-bottom: 16px;
    opacity: 0.8;
}

/* Financial Summary Cards */
.financial-card {
    background: var(--color-white);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 20px rgba(48, 25, 52, 0.06);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    height: 100%;
    position: relative;
    overflow: hidden;
}

.financial-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.financial-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(48, 25, 52, 0.15);
    border-color: rgba(218, 165, 32, 0.2);
}

.financial-card:hover::before {
    transform: scaleX(1);
}

.financial-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.financial-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex-shrink: 0;
}

.financial-icon.success { 
    background: linear-gradient(135deg, var(--color-deep-purple) 0%, var(--color-dark-purple) 100%);
    box-shadow: 0 8px 24px rgba(48, 25, 52, 0.3);
}

.financial-icon.warning { 
    background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    box-shadow: 0 8px 24px rgba(218, 165, 32, 0.3);
}

.financial-icon.primary { 
    background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    box-shadow: 0 8px 24px rgba(218, 165, 32, 0.3);
}

.financial-icon.info { 
    background: linear-gradient(135deg, var(--color-deep-purple) 0%, var(--color-dark-purple) 100%);
    box-shadow: 0 8px 24px rgba(48, 25, 52, 0.3);
}

 .financial-title h3 {
     font-size: 18px;
     font-weight: 700;
     color: var(--color-deep-purple);
     margin: 0;
     line-height: 1.3;
 }
 
 .financial-title .description {
     font-size: 13px;
     color: var(--color-dark-purple);
     margin: 0;
     opacity: 0.7;
 }
 
 .financial-value {
     font-size: 24px;
     font-weight: 700;
     color: var(--color-deep-purple);
     margin: 12px 0 6px 0;
     line-height: 1;
 }

.financial-detail {
    font-size: 13px;
    color: var(--color-dark-purple);
    margin: 6px 0 0 0;
    opacity: 0.8;
}

/* Progress Bar */
.progress-container {
    margin-top: 12px;
}

.progress {
    height: 6px;
    border-radius: 8px;
    background: rgba(218, 165, 32, 0.1);
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    background: linear-gradient(90deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    box-shadow: 0 2px 8px rgba(218, 165, 32, 0.3);
}

/* Payment History Table */
.table-section {
    margin-bottom: 24px;
}

.table-container {
    background: var(--color-white);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(48, 25, 52, 0.06);
    border: 1px solid var(--color-border);
    max-width: 100%;
    margin: 0;
}

/* Compact table layout */
.table {
    margin: 0;
    table-layout: fixed;
    width: 100%;
}

.table-controls {
    padding: 12px;
    background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
    border-bottom: 1px solid var(--color-border);
}

.search-box {
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-box:focus {
    border-color: var(--color-gold);
    box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
    outline: none;
}

.filter-select {
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    background: white;
}



.table th {
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
    border: none;
    padding: 12px 8px;
    color: var(--color-deep-purple);
    text-align: center;
}

.table td {
    padding: 10px 8px;
    border-color: rgba(77, 64, 82, 0.05);
    font-size: 13px;
    color: var(--color-dark-purple);
    vertical-align: middle;
    text-align: center;
}

/* Compact Table Column Widths */
.table th:nth-child(1), .table td:nth-child(1) { width: 18%; } /* Payment ID */
.table th:nth-child(2), .table td:nth-child(2) { width: 32%; } /* Payment Month */
.table th:nth-child(3), .table td:nth-child(3) { width: 20%; } /* Amount */
.table th:nth-child(4), .table td:nth-child(4) { width: 18%; } /* Status */
.table th:nth-child(5), .table td:nth-child(5) { width: 12%; } /* Actions */

/* Compact Code and Badge Styling */
.table code {
    font-size: 11px;
    padding: 2px 6px;
}

.table .badge {
    font-size: 9px;
    padding: 4px 8px;
    min-width: auto;
}

.table tbody tr:hover {
    background: rgba(218, 165, 32, 0.02);
}

/* Status Badges */
.badge {
    border-radius: 8px;
    font-weight: 600;
    font-size: 11px;
    padding: 8px 14px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
}

.badge.status-paid {
    background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    color: var(--color-deep-purple);
    box-shadow: 0 2px 8px rgba(218, 165, 32, 0.3);
}

.badge.status-pending {
    background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    color: var(--color-deep-purple);
    box-shadow: 0 2px 8px rgba(218, 165, 32, 0.3);
}

.badge.status-late {
    background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

.badge.status-missed {
    background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
}

/* Action Buttons */
.btn {
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    padding: 10px 20px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    letter-spacing: 0.02em;
}

.btn-primary {
    background: linear-gradient(135deg, var(--color-deep-purple) 0%, var(--color-dark-purple) 100%);
    border: none;
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--color-dark-purple) 0%, var(--color-deep-purple) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(48, 25, 52, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
    border: none;
    color: var(--color-deep-purple);
}

.btn-warning:hover {
    background: linear-gradient(135deg, var(--color-light-gold) 0%, var(--color-gold) 100%);
    color: var(--color-deep-purple);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 11px;
}

/* Extra compact buttons in table */
.table .btn-sm {
    padding: 6px 8px;
    font-size: 11px;
    min-width: 32px;
    border-radius: 6px;
}

/* Center and compact the actions column */
.table th:nth-child(5), 
.table td:nth-child(5) { 
    width: 12%; 
    text-align: center !important;
    padding: 8px 4px !important;
}



/* Payment Details Modal */
.payment-modal .modal-dialog {
    max-width: 600px;
}

.payment-modal .modal-header {
    background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
    border-bottom: 1px solid var(--color-border);
    border-radius: 16px 16px 0 0;
}

.payment-modal .modal-content {
    border-radius: 16px;
    border: 1px solid var(--color-border);
    box-shadow: 0 8px 32px rgba(48, 25, 52, 0.15);
}

.payment-modal .modal-title {
    color: var(--color-deep-purple);
    font-weight: 700;
    font-size: 20px;
}

.payment-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(77, 64, 82, 0.05);
}

.payment-detail-row:last-child {
    border-bottom: none;
}

.payment-detail-label {
    font-weight: 600;
    color: var(--color-dark-purple);
    font-size: 14px;
}

.payment-detail-value {
    color: var(--color-deep-purple);
    font-weight: 500;
    font-size: 14px;
}

.verification-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.verification-status.verified {
    color: var(--color-gold);
}

.verification-status.pending {
    color: var(--color-gold);
}

.verification-status.not-verified {
    color: #6B7280;
}

/* Print Receipt Styles */
@media print {
    body * {
        visibility: hidden !important;
    }
    
    .receipt-print, .receipt-print * {
        visibility: visible !important;
    }
    
    .receipt-print {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        background: white !important;
        padding: 20px !important;
        font-family: 'Courier New', monospace !important;
        color: black !important;
        font-size: 14px !important;
        line-height: 1.4 !important;
        display: block !important;
    }
    
    .receipt-header {
        text-align: center !important;
        margin-bottom: 20px !important;
        border-bottom: 2px solid #000 !important;
        padding-bottom: 10px !important;
    }
    
    .receipt-title {
        font-size: 20px !important;
        font-weight: bold !important;
        margin-bottom: 5px !important;
        color: black !important;
    }
    
    .receipt-subtitle {
        font-size: 16px !important;
        margin-bottom: 10px !important;
        color: black !important;
    }
    
    .receipt-date {
        font-size: 12px !important;
        color: black !important;
    }
    
    .receipt-details {
        margin: 20px 0 !important;
    }
    
    .receipt-row {
        display: flex !important;
        justify-content: space-between !important;
        margin: 8px 0 !important;
        padding: 4px 0 !important;
        border-bottom: 1px dotted #ccc !important;
    }
    
    .receipt-row span {
        color: black !important;
        font-size: 14px !important;
    }
    
    .receipt-footer {
        margin-top: 30px !important;
        text-align: center !important;
        border-top: 2px solid #000 !important;
        padding-top: 10px !important;
        font-size: 12px !important;
    }
    
    .receipt-footer p {
        color: black !important;
        margin: 5px 0 !important;
    }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--color-dark-purple);
}

.empty-state i {
    font-size: 64px;
    color: var(--color-dark-purple);
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-state h4 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 12px;
    color: var(--color-deep-purple);
}

.empty-state p {
    font-size: 16px;
    opacity: 0.7;
    margin-bottom: 24px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        padding: 18px 16px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .page-header h1 {
        font-size: 22px;
        justify-content: center;
    }
    
    .financial-card {
        padding: 16px;
        margin-bottom: 12px;
    }
    
         .financial-header {
         flex-direction: row;
         text-align: left;
         gap: 10px;
         margin-bottom: 12px;
     }
     
     .financial-title h3 {
         font-size: 15px;
         line-height: 1.2;
     }
     
     .financial-value {
         font-size: 20px;
         margin: 10px 0 5px 0;
     }
    
    .table-container {
        overflow-x: auto;
        max-width: 100%;
        margin: 0;
    }
    
    .table {
        font-size: 12px;
        min-width: 400px;
    }
    
    .table th, .table td {
        padding: 8px 6px;
    }
    
    .table code {
        font-size: 10px;
        padding: 1px 4px;
    }
    
    .table .badge {
        font-size: 8px;
        padding: 3px 6px;
    }
    
    .section-title {
        font-size: 18px;
        text-align: center;
        margin-bottom: 8px;
    }
    
    .section-subtitle {
        text-align: center;
        font-size: 14px;
        margin-bottom: 12px;
    }
    
    .table-controls {
        padding: 8px;
    }
    
    .payment-modal .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
}

@media (max-width: 576px) {
    .page-header {
        padding: 16px 14px;
        border-radius: 12px;
        margin-bottom: 16px;
    }
    
    .page-header h1 {
        font-size: 20px;
    }
    
    .page-header p {
        font-size: 14px;
    }
    
    .financial-card {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 10px;
    }
    
    .financial-icon {
        width: 42px;
        height: 42px;
        font-size: 16px;
    }
    
    .financial-header {
        gap: 8px;
        margin-bottom: 10px;
    }
    
         .financial-title h3 {
         font-size: 13px;
         line-height: 1.1;
     }
     
     .financial-title .description {
         font-size: 12px;
     }
     
     .financial-value {
         font-size: 18px;
         margin: 8px 0 4px 0;
     }
    
    .financial-detail {
        font-size: 12px;
        margin: 4px 0 0 0;
    }
    
    .section-title {
        font-size: 16px;
        margin-bottom: 6px;
    }
    
    .section-subtitle {
        font-size: 13px;
        margin-bottom: 10px;
    }
    
    .table-controls {
        padding: 8px;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 13px;
    }
    
    /* Ensure badges are readable on mobile */
    .badge {
        font-size: 10px;
        padding: 5px 8px;
        font-weight: 700;
    }
}

/* Text Color Fixes */
.page-header h1,
.section-title,
.financial-title h3,
.financial-value,
.table th {
    color: var(--color-deep-purple) !important;
}

.page-header p,
.section-subtitle,
.financial-title .description,
.financial-detail,
.table td {
    color: var(--color-dark-purple) !important;
}
</style>

</head>
<body>
    <!-- Include Member Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Page Content -->
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-header">
                    <h1>
                        <i class="fas fa-credit-card text-warning"></i>
                        <?php echo t('contributions.page_title'); ?>
                    </h1>
                </div>
            </div>
        </div>

        <!-- Financial Summary Section -->
        <div class="row mb-3">
            <div class="col-12">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar text-primary"></i>
                    <?php echo t('contributions.financial_summary'); ?>
                </h2>
            </div>
        </div>

        <div class="row g-3 mb-4">
                         <!-- Total Paid -->
             <div class="col-lg-3 col-md-6">
                 <div class="financial-card">
                     <div class="financial-header">
                         <div class="financial-icon success">
                             <i class="fas fa-piggy-bank"></i>
                         </div>
                         <div class="financial-title">
                             <h3><?php echo t('contributions.total_paid_desc'); ?> £<?php echo number_format($total_contributed, 2); ?></h3>
                         </div>
                     </div>
                     <div class="financial-detail">
                         <i class="fas fa-calendar me-1"></i>
                         <?php echo $total_payments; ?> <?php echo t('contributions.payments_made'); ?>
                     </div>
                     <?php if ($member['total_late_fees'] > 0): ?>
                     <div class="financial-detail text-warning">
                         <i class="fas fa-exclamation-triangle me-1"></i>
                         £<?php echo number_format($member['total_late_fees'], 2); ?> <?php echo t('contributions.total_late_fees'); ?>
                     </div>
                     <?php endif; ?>
                 </div>
             </div>

                         <!-- Payment Progress -->
             <div class="col-lg-3 col-md-6">
                 <div class="financial-card">
                     <div class="financial-header">
                         <div class="financial-icon primary">
                             <i class="fas fa-chart-line"></i>
                         </div>
                         <div class="financial-title">
                             <h3><?php echo t('contributions.payment_progress_desc'); ?> <?php echo number_format($progress_percentage, 1); ?>%</h3>
                         </div>
                     </div>
                     <div class="financial-detail">
                         £<?php echo number_format($total_contributed, 2); ?> of £<?php echo number_format($expected_total, 2); ?>
                     </div>
                     <div class="progress-container">
                         <div class="progress">
                             <div class="progress-bar" style="width: <?php echo min($progress_percentage, 100); ?>%"></div>
                         </div>
                     </div>
                 </div>
             </div>

                         <!-- Current Month Payment -->
             <div class="col-lg-3 col-md-6">
                 <div class="financial-card">
                     <div class="financial-header">
                         <div class="financial-icon <?php echo $current_payment && $current_payment['status'] === 'paid' ? 'success' : 'warning'; ?>">
                             <i class="fas fa-<?php echo $current_payment && $current_payment['status'] === 'paid' ? 'check-circle' : 'clock'; ?>"></i>
                         </div>
                         <div class="financial-title">
                             <?php if ($current_payment && $current_payment['status'] === 'paid'): ?>
                                 <h3><?php echo t('contributions.payment_complete'); ?> <?php echo date('F Y'); ?></h3>
                             <?php else: ?>
                                 <h3>£<?php echo number_format($monthly_contribution, 2); ?> <?php echo sprintf(t('contributions.due_in_days'), $days_until_due); ?></h3>
                             <?php endif; ?>
                         </div>
                     </div>
                     <div class="financial-detail">
                         <?php if ($current_payment && $current_payment['status'] === 'paid'): ?>
                             <i class="fas fa-check text-success me-1"></i>
                             <?php echo t('contributions.paid'); ?> £<?php echo number_format($current_payment['amount'], 2); ?> <?php echo t('common.on'); ?> <?php echo date('M d', strtotime($current_payment['payment_date'])); ?>
                         <?php else: ?>
                             <i class="fas fa-clock text-warning me-1"></i>
                             <?php echo t('contributions.payment_due'); ?> <?php echo date('F Y'); ?>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>

                         <!-- Payment Performance -->
             <div class="col-lg-3 col-md-6">
                 <div class="financial-card">
                     <div class="financial-header">
                         <div class="financial-icon info">
                             <i class="fas fa-trophy"></i>
                         </div>
                         <div class="financial-title">
                             <h3><?php echo t('contributions.payment_performance'); ?> <?php echo number_format($on_time_rate, 1); ?>% - 
                             <?php 
                             if ($on_time_rate >= 95) {
                                 echo t('contributions.excellent');
                             } elseif ($on_time_rate >= 80) {
                                 echo t('contributions.good');
                             } else {
                                 echo t('contributions.needs_improvement');
                             }
                             ?></h3>
                         </div>
                     </div>
                     <div class="financial-detail">
                         <?php 
                         if ($on_time_rate >= 95) {
                             echo '<i class="fas fa-star text-warning me-1"></i>';
                         } elseif ($on_time_rate >= 80) {
                             echo '<i class="fas fa-thumbs-up text-success me-1"></i>';
                         } else {
                             echo '<i class="fas fa-exclamation-triangle text-warning me-1"></i>';
                         }
                         ?>
                         <?php echo $on_time_payments; ?> <?php echo t('common.of'); ?> <?php echo $total_payments; ?> <?php echo t('contributions.payments_made'); ?> <?php echo t('contributions.on_time_rate'); ?>
                     </div>
                 </div>
             </div>
        </div>

        <!-- Payment History Section -->
        <div class="table-section">
            <div class="row mb-3">
                <div class="col-12 text-center">
                    <h2 class="section-title" style="justify-content: center;">
                        <i class="fas fa-history text-primary"></i>
                        <?php echo t('contributions.payment_history'); ?>
                    </h2>
                </div>
            </div>

            <div class="table-container">
                <!-- Search and Filter Controls -->
                <div class="table-controls">
                    <div class="row g-2 justify-content-center">
                        <div class="col-md-5">
                            <input type="text" class="form-control search-box" 
                                   placeholder="<?php echo t('contributions.search_payments'); ?>" 
                                   id="searchPayments">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select filter-select" id="statusFilter">
                                <option value=""><?php echo t('contributions.filter_by_status'); ?></option>
                                <option value="paid"><?php echo t('contributions.paid'); ?></option>
                                <option value="pending"><?php echo t('contributions.pending'); ?></option>
                                <option value="late"><?php echo t('contributions.late'); ?></option>
                                <option value="missed"><?php echo t('contributions.missed'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select filter-select" id="periodFilter">
                                <option value=""><?php echo t('contributions.filter_by_period'); ?></option>
                                <option value="recent"><?php echo t('contributions.recent_payments'); ?></option>
                                <option value="this_year"><?php echo t('contributions.this_year'); ?></option>
                                <option value="last_6_months"><?php echo t('contributions.last_6_months'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Table -->
                <?php if (!empty($payments)): ?>
                <table class="table table-hover mb-0" id="paymentsTable">
                    <thead>
                        <tr>
                            <th><?php echo t('contributions.payment_id'); ?></th>
                            <th><?php echo t('contributions.payment_month'); ?></th>
                            <th><?php echo t('contributions.amount'); ?></th>
                            <th><?php echo t('contributions.status'); ?></th>
                            <th><?php echo t('contributions.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <code class="small"><?php echo htmlspecialchars($payment['payment_id'] ?? ''); ?></code>
                            </td>
                            <td class="fw-semibold">
                                <?php 
                                $month_name = $payment['payment_month_name'] ?? '';
                                echo htmlspecialchars($month_name === 'UNKNOWN_MONTH' ? t('common.unknown_month') : $month_name); 
                                ?>
                            </td>
                            <td class="fw-semibold text-success">
                                £<?php echo number_format($payment['amount'], 2); ?>
                                <?php if ($payment['late_fee'] > 0): ?>
                                    <small class="text-warning d-block">
                                        +£<?php echo number_format($payment['late_fee'], 2); ?> <?php echo t('contributions.late_fee'); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge status-<?php echo $payment['status']; ?>">
                                    <?php echo t('contributions.' . $payment['status']); ?>
                                </span>
                            </td>
                                                         <td>
                                 <button class="btn btn-sm btn-outline-secondary payment-details-btn" 
                                         title="<?php echo t('contributions.payment_details'); ?>"
                                         data-payment-id="<?php echo htmlspecialchars($payment['payment_id'] ?? ''); ?>"
                                         data-payment-month="<?php echo htmlspecialchars($payment['payment_month_name'] ?? ''); ?>"
                                         data-payment-date="<?php echo htmlspecialchars($payment['formatted_date'] ?? ''); ?>"
                                         data-amount="<?php echo number_format($payment['amount'], 2); ?>"
                                         data-late-fee="<?php echo number_format($payment['late_fee'], 2); ?>"
                                         data-method="<?php echo t('contributions.' . $payment['payment_method']); ?>"
                                         data-status="<?php echo t('contributions.' . $payment['status']); ?>"
                                         data-verification="<?php echo $payment['verification_status']; ?>"
                                         data-receipt="<?php echo $payment['receipt_number'] ?? ''; ?>"
                                         data-created="<?php echo $payment['created_at'] ?? ''; ?>"
                                         data-member-name="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>">
                                     <i class="fas fa-eye"></i>
                                 </button>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <h4><?php echo t('contributions.no_payments_found'); ?></h4>
                    <p><?php echo t('contributions.no_payments_message'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Details Modal -->
    <div class="modal fade payment-modal" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentDetailsLabel">
                        <i class="fas fa-receipt me-2"></i>
                        <?php echo t('contributions.payment_details'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="payment-details-content">
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.payment_id'); ?>:</span>
                            <span class="payment-detail-value" id="modal-payment-id">-</span>
                        </div>
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.payment_month'); ?>:</span>
                            <span class="payment-detail-value" id="modal-payment-month">-</span>
                        </div>
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.payment_date'); ?>:</span>
                            <span class="payment-detail-value" id="modal-payment-date">-</span>
                        </div>
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.payment_method'); ?>:</span>
                            <span class="payment-detail-value" id="modal-payment-method">-</span>
                        </div>
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.amount'); ?>:</span>
                            <span class="payment-detail-value" id="modal-amount">-</span>
                        </div>
                        <div class="payment-detail-row" id="late-fee-row" style="display: none;">
                            <span class="payment-detail-label"><?php echo t('contributions.late_fee'); ?>:</span>
                            <span class="payment-detail-value text-warning" id="modal-late-fee">-</span>
                        </div>
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.status'); ?>:</span>
                            <span class="payment-detail-value" id="modal-status">-</span>
                        </div>
                        <div class="payment-detail-row">
                            <span class="payment-detail-label"><?php echo t('contributions.verification'); ?>:</span>
                            <span class="payment-detail-value verification-status" id="modal-verification">-</span>
                        </div>
                        <div class="payment-detail-row" id="receipt-row" style="display: none;">
                            <span class="payment-detail-label"><?php echo t('contributions.receipt_number'); ?>:</span>
                            <span class="payment-detail-value" id="modal-receipt">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        <?php echo t('common.cancel'); ?>
                    </button>
                                                             <button type="button" class="btn btn-warning" id="printReceiptBtn" style="background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%); border: 2px solid var(--color-light-gold); color: var(--color-deep-purple);">
                        <i class="fas fa-print me-2"></i>
                        <?php echo t('contributions.print_statement'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Print Receipt Template -->
    <div class="receipt-print" id="receiptTemplate" style="display: none;">
        <div class="receipt-header">
            <div class="receipt-title">HABESHA EQUB</div>
            <div class="receipt-subtitle"><?php echo t('contributions.payment_details'); ?></div>
            <div class="receipt-date" id="receipt-print-date"></div>
        </div>
        
                 <div class="receipt-details">
                              <div class="receipt-row">
                     <span><strong><?php echo t('common.member'); ?>:</strong></span>
                     <span id="receipt-member-name"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.payment_id'); ?>:</strong></span>
                     <span id="receipt-payment-id"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.payment_month'); ?>:</strong></span>
                     <span id="receipt-payment-month"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.payment_date'); ?>:</strong></span>
                     <span id="receipt-payment-date"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.payment_method'); ?>:</strong></span>
                     <span id="receipt-payment-method"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.amount'); ?>:</strong></span>
                     <span id="receipt-amount"></span>
                 </div>
                 <div class="receipt-row" id="receipt-late-fee-row" style="display: none;">
                     <span><strong><?php echo t('contributions.late_fee'); ?>:</strong></span>
                     <span id="receipt-late-fee"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.status'); ?>:</strong></span>
                     <span id="receipt-status"></span>
                 </div>
                 <div class="receipt-row">
                     <span><strong><?php echo t('contributions.verification'); ?>:</strong></span>
                     <span id="receipt-verification"></span>
                 </div>
         </div>
        
        <div class="receipt-footer">
            <p><?php echo t('contributions.thank_you_payment'); ?></p>
            <p><?php echo t('contributions.automated_receipt'); ?> <span id="receipt-generated-date"></span></p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo $cache_buster; ?>"></script>
    
    <script>
    // Payment History Search and Filter
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchPayments');
        const statusFilter = document.getElementById('statusFilter');
        const periodFilter = document.getElementById('periodFilter');
        const table = document.getElementById('paymentsTable');
        
        if (table) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const periodValue = periodFilter.value;
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const statusBadge = row.querySelector('.badge[class*="status-"]');
                    
                    let visible = true;
                    
                    // Search filter
                    if (searchTerm && !text.includes(searchTerm)) {
                        visible = false;
                    }
                    
                    // Status filter
                    if (statusValue && statusBadge && !statusBadge.className.includes('status-' + statusValue)) {
                        visible = false;
                    }
                    
                    row.style.display = visible ? '' : 'none';
                });
            }
            
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
            periodFilter.addEventListener('change', filterTable);
        }

        // Payment Details Modal
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
        const detailButtons = document.querySelectorAll('.payment-details-btn');
        const printBtn = document.getElementById('printReceiptBtn');
        
        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Get data from button attributes
                const paymentId = this.dataset.paymentId;
                const paymentMonth = this.dataset.paymentMonth;
                const paymentDate = this.dataset.paymentDate;
                const amount = this.dataset.amount;
                const lateFee = this.dataset.lateFee;
                const method = this.dataset.method;
                const status = this.dataset.status;
                const verification = this.dataset.verification;
                const receipt = this.dataset.receipt;
                const memberName = this.dataset.memberName;
                
                // Populate modal fields
                document.getElementById('modal-payment-id').textContent = paymentId || '-';
                document.getElementById('modal-payment-month').textContent = paymentMonth || '-';
                document.getElementById('modal-payment-date').textContent = paymentDate || '-';
                document.getElementById('modal-payment-method').textContent = method || '-';
                document.getElementById('modal-amount').textContent = '£' + amount || '-';
                document.getElementById('modal-status').textContent = status || '-';
                
                // Handle late fee
                const lateFeeRow = document.getElementById('late-fee-row');
                if (lateFee && parseFloat(lateFee) > 0) {
                    document.getElementById('modal-late-fee').textContent = '£' + lateFee;
                    lateFeeRow.style.display = 'flex';
                } else {
                    lateFeeRow.style.display = 'none';
                }
                
                // Handle verification status
                const verificationElement = document.getElementById('modal-verification');
                verificationElement.className = 'payment-detail-value verification-status ' + verification;
                
                let verificationText = '';
                let verificationIcon = '';
                
                switch(verification) {
                    case 'verified':
                        verificationText = '<?php echo t("contributions.verified_by_admin"); ?>';
                        verificationIcon = '<i class="fas fa-check-circle me-1"></i>';
                        break;
                    case 'pending_verification':
                        verificationText = '<?php echo t("contributions.pending_verification"); ?>';
                        verificationIcon = '<i class="fas fa-clock me-1"></i>';
                        break;
                    default:
                        verificationText = '<?php echo t("contributions.not_verified"); ?>';
                        verificationIcon = '<i class="fas fa-minus-circle me-1"></i>';
                }
                
                verificationElement.innerHTML = verificationIcon + verificationText;
                
                                 // Handle receipt number
                 const receiptRow = document.getElementById('receipt-row');
                 if (receipt && receipt.trim() !== '') {
                     document.getElementById('modal-receipt').textContent = receipt;
                     receiptRow.style.display = 'flex';
                 } else {
                     receiptRow.style.display = 'none';
                 }
                
                // Store data for printing
                printBtn.dataset.paymentData = JSON.stringify({
                    paymentId, paymentMonth, paymentDate, amount, lateFee, 
                    method, status, verification, receipt, memberName
                });
                
                // Show modal
                paymentModal.show();
            });
        });
        
                 // Print Receipt Functionality
         printBtn.addEventListener('click', function() {
             const paymentData = JSON.parse(this.dataset.paymentData);
             
             // Populate print template
             document.getElementById('receipt-member-name').textContent = paymentData.memberName;
             document.getElementById('receipt-payment-id').textContent = paymentData.paymentId;
             document.getElementById('receipt-payment-month').textContent = paymentData.paymentMonth;
             document.getElementById('receipt-payment-date').textContent = paymentData.paymentDate || '<?php echo t("contributions.not_specified"); ?>';
             document.getElementById('receipt-payment-method').textContent = paymentData.method;
             document.getElementById('receipt-amount').textContent = '£' + paymentData.amount;
             
             // Handle late fee in receipt
             const receiptLateFeeRow = document.getElementById('receipt-late-fee-row');
             if (paymentData.lateFee && parseFloat(paymentData.lateFee) > 0) {
                 document.getElementById('receipt-late-fee').textContent = '£' + paymentData.lateFee;
                 receiptLateFeeRow.style.display = 'block';
             } else {
                 receiptLateFeeRow.style.display = 'none';
             }
             
             // Set status based on verification
             let receiptStatus = '';
             if (paymentData.verification === 'verified') {
                 receiptStatus = '<?php echo t("contributions.paid"); ?> - <?php echo t("contributions.verified"); ?>';
             } else if (paymentData.verification === 'pending_verification') {
                 receiptStatus = '<?php echo t("contributions.paid"); ?> - <?php echo t("contributions.pending_verification"); ?>';
             } else {
                 receiptStatus = '<?php echo t("contributions.not_paid"); ?>';
             }
             
             document.getElementById('receipt-status').textContent = receiptStatus;
             document.getElementById('receipt-verification').textContent = 
                 paymentData.verification === 'verified' ? '<?php echo t("contributions.verified"); ?>' : 
                 paymentData.verification === 'pending_verification' ? '<?php echo t("contributions.pending"); ?>' : '<?php echo t("contributions.not_verified"); ?>';
             
             // Set dates
             const now = new Date();
             document.getElementById('receipt-print-date').textContent = now.toLocaleDateString();
             document.getElementById('receipt-generated-date').textContent = now.toLocaleString();
             
             // Show receipt temporarily for printing
             const receiptTemplate = document.getElementById('receiptTemplate');
             receiptTemplate.style.display = 'block';
             
             // Hide modal and print
             paymentModal.hide();
             
             // Small delay to ensure modal is hidden and receipt is visible
             setTimeout(() => {
                 window.print();
                 // Hide receipt again after printing
                 receiptTemplate.style.display = 'none';
             }, 500);
         });
    });
    </script>
</body>
</html> 