<?php
/**
 * HabeshaEqub - Payment Tracking
 * Comprehensive payment management and tracking system
 */

require_once '../includes/db.php';
require_once '../languages/translator.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Get payments data with member information
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               m.id as member_db_id, m.first_name, m.last_name, m.member_id, m.email,
               va.username as verified_by_name
        FROM payments p 
        LEFT JOIN members m ON p.member_id = m.id
        LEFT JOIN admins va ON p.verified_by_admin_id = va.id
        ORDER BY p.payment_date DESC, p.created_at DESC
    ");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    $payments = [];
}

// Get members for dropdown
try {
    $stmt = $pdo->query("
        SELECT id, member_id, first_name, last_name, monthly_payment 
        FROM members 
        WHERE is_active = 1 
        ORDER BY first_name ASC, last_name ASC
    ");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching members: " . $e->getMessage());
    $members = [];
}

// Calculate payment statistics
$total_payments = count($payments);
$completed_payments = count(array_filter($payments, fn($p) => $p['status'] === 'completed'));
$pending_payments = count(array_filter($payments, fn($p) => $p['status'] === 'pending'));
$total_amount = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'completed'), 'amount'));

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('payments.page_title'); ?> - HabeshaEqub Admin</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Pictures/Icon/favicon-16x16.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* === TOP-TIER PAYMENTS PAGE DESIGN === */
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid var(--border-light);
            box-shadow: 0 8px 32px rgba(48, 25, 67, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title-section h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .page-title-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .page-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 400;
        }

        .page-actions .btn {
            padding: 12px 24px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            box-shadow: 0 4px 12px rgba(48, 25, 67, 0.15);
        }

        .btn-add-payment {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            color: white;
            font-size: 16px;
        }

        .btn-add-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 196, 106, 0.4);
            color: white;
        }

        /* Statistics Dashboard */
        .stats-dashboard {
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(48, 25, 67, 0.12);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .total-payments .stat-icon { background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%); }
        .completed-payments .stat-icon { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .pending-payments .stat-icon { background: linear-gradient(135deg, var(--color-coral) 0%, #D63447 100%); }
        .total-amount .stat-icon { background: linear-gradient(135deg, var(--color-light-gold) 0%, #B8941C 100%); }

        .stat-trend {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 4px 0;
            line-height: 1;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 500;
        }

        /* Search and Filter Section */
        .search-filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
        }

        .search-bar {
            position: relative;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--color-cream);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(233, 196, 106, 0.1);
            background: white;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .filter-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid var(--border-light);
            border-radius: 12px;
            background: var(--color-cream);
            color: var(--color-purple);
            font-weight: 500;
            min-width: 140px;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(233, 196, 106, 0.1);
            background: white;
        }

        /* Payments Table */
        .payments-table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
        }

        .table-header {
            background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
        }

        .table-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0;
        }

        .payments-table {
            width: 100%;
            margin: 0;
        }

        .payments-table thead th {
            background: var(--color-cream);
            color: var(--color-purple);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px 20px;
            border: none;
            border-bottom: 2px solid var(--border-light);
        }

        .payments-table tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }

        .payments-table tbody tr:hover {
            background: rgba(233, 196, 106, 0.05);
        }

        .payments-table tbody td {
            padding: 20px;
            vertical-align: middle;
            border: none;
        }

        /* Member Info Cell */
        .member-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .member-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .member-details .member-name {
            font-weight: 600;
            color: var(--color-purple);
            margin: 0 0 4px 0;
            font-size: 16px;
        }

        .member-name-link {
            color: var(--color-teal);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .member-name-link:hover {
            color: var(--color-gold);
            text-decoration: underline;
        }

        .member-id {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
            font-family: 'Courier New', monospace;
        }

        /* Payment Info */
        .payment-id {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: var(--color-purple);
            font-weight: 600;
        }

        .payment-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-gold);
            margin: 0 0 4px 0;
        }

        .payment-method {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
        }

        .payment-date {
            font-size: 14px;
            color: var(--color-purple);
            font-weight: 500;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #D97706;
        }

        .status-late {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
        }

        .status-missed {
            background: rgba(107, 114, 128, 0.1);
            color: #6B7280;
        }

        .verified-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .verified-yes {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .verified-no {
            background: rgba(107, 114, 128, 0.1);
            color: #6B7280;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .btn-action i {
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #2563EB;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: scale(1.1);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .btn-edit i {
            color: #2563EB;
        }

        .btn-edit:hover i {
            color: #1D4ED8;
        }

        .btn-verify {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .btn-verify:hover {
            background: rgba(34, 197, 94, 0.2);
            transform: scale(1.1);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .btn-verify i {
            color: #059669;
        }

        .btn-verify:hover i {
            color: #047857;
        }

        .btn-receipt {
            background: rgba(139, 92, 246, 0.1);
            color: #7C3AED;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .btn-receipt:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: scale(1.1);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .btn-receipt i {
            color: #7C3AED;
        }

        .btn-receipt:hover i {
            color: #6D28D9;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: scale(1.1);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .btn-delete i {
            color: #DC2626;
        }

        .btn-delete:hover i {
            color: #B91C1C;
        }

        /* Receipt Number Generator */
        #generateReceiptBtn {
            border-radius: 0 8px 8px 0 !important;
            border-left: none !important;
            transition: all 0.3s ease;
        }

        #generateReceiptBtn:hover {
            background-color: var(--color-teal) !important;
            border-color: var(--color-teal) !important;
            color: white !important;
            transform: scale(1.02);
        }

        #generateReceiptBtn i {
            margin-right: 4px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .page-title-section h1 {
                font-size: 28px;
            }

            .search-filter-section {
                padding: 20px;
            }

            .filter-group {
                flex-direction: column;
                justify-content: flex-start;
                margin-top: 16px;
            }

            .search-filter-section .row {
                flex-direction: column;
            }

            .search-filter-section .col-lg-6 {
                width: 100%;
                margin-bottom: 16px;
            }

            .payments-table-container {
                overflow-x: auto;
            }

            .payments-table {
                min-width: 900px;
            }

            .stat-number {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                padding: 20px;
                margin-bottom: 30px;
            }

            .search-filter-section {
                padding: 16px;
            }

            .payments-table tbody td {
                padding: 16px 12px;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <!-- Include Navigation -->
        <?php include 'includes/navigation.php'; ?>

            <!-- Payment Tracking Page Content -->
            <div class="page-header">
                <div class="page-title-section">
                    <h1>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                        <?php echo t('payments.page_title'); ?>
                    </h1>
                    <p class="page-subtitle"><?php echo t('payments.page_subtitle'); ?></p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-add-payment" onclick="showAddPaymentModal()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                            <path d="M12 8v8M8 12h8"/>
                        </svg>
                        <?php echo t('payments.add_new_payment'); ?>
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row stats-dashboard">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <h3 class="stat-number"><?php echo count($payments); ?></h3>
                            <div class="stat-icon total-payments">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-label"><?php echo t('payments.total_payments'); ?></p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <h3 class="stat-number"><?php echo count(array_filter($payments, fn($p) => $p['status'] === 'completed')); ?></h3>
                            <div class="stat-icon completed-payments">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-label"><?php echo t('payments.completed'); ?></p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <h3 class="stat-number"><?php echo count(array_filter($payments, fn($p) => $p['status'] === 'pending')); ?></h3>
                            <div class="stat-icon pending-payments">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-label"><?php echo t('payments.pending'); ?></p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <h3 class="stat-number"><?php echo count(array_filter($payments, fn($p) => $p['verified_by_admin'] === 1)); ?></h3>
                            <div class="stat-icon total-amount">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18l-2 13H5L3 6z"/>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-label"><?php echo t('payments.verified'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="search-bar">
                            <input type="text" class="search-input" id="paymentSearch" placeholder="<?php echo t('payments.search_placeholder'); ?>" oninput="searchPayments()">
                            <span class="search-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 21l-6-6"/>
                                    <circle cx="11" cy="11" r="6"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="filter-group">
                            <select id="statusFilter" class="filter-select" onchange="filterPayments()">
                                <option value=""><?php echo t('payments.all_status'); ?></option>
                                <option value="completed"><?php echo t('payments.completed'); ?></option>
                                <option value="pending"><?php echo t('payments.pending'); ?></option>
                                <option value="failed"><?php echo t('payments.failed'); ?></option>
                            </select>
                            <select id="memberFilter" class="filter-select" onchange="filterPayments()">
                                <option value=""><?php echo t('payments.all_members'); ?></option>
                                <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <select id="monthFilter" class="filter-select" onchange="filterPayments()">
                                <option value=""><?php echo t('payments.all_months'); ?></option>
                                <?php
                                $months = ['2024-01', '2024-02', '2024-03', '2024-04', '2024-05', '2024-06', 
                                          '2024-07', '2024-08', '2024-09', '2024-10', '2024-11', '2024-12'];
                                foreach ($months as $month) {
                                    $formatted = date('F Y', strtotime($month . '-01'));
                                    echo "<option value=\"{$month}\">{$formatted}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Payments Table -->
                <div class="payments-table-container">
                    <div class="table-header">
                        <h3 class="table-title"><?php echo t('payments.all_payments'); ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table class="payments-table">
                            <thead>
                                <tr>
                                    <th><?php echo t('payments.member'); ?></th>
                                    <th><?php echo t('payments.payment_details'); ?></th>
                                    <th><?php echo t('payments.amount'); ?></th>
                                    <th><?php echo t('payments.date_month'); ?></th>
                                    <th><?php echo t('payments.status'); ?></th>
                                    <th><?php echo t('payments.verification'); ?></th>
                                    <th><?php echo t('payments.actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTableBody">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <div class="member-info">
                                                <div class="member-avatar">
                                                    <?php echo strtoupper(substr($payment['first_name'], 0, 1) . substr($payment['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="member-details">
                                                    <div class="member-name">
                                                        <a href="member-profile.php?id=<?php echo $payment['member_db_id']; ?>" class="member-name-link">
                                                            <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                                        </a>
                                                    </div>
                                                    <div class="member-id"><?php echo htmlspecialchars($payment['member_id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="payment-id"><?php echo htmlspecialchars($payment['payment_id']); ?></div>
                                            <div class="payment-method"><?php echo ucfirst($payment['payment_method'] ?? 'bank_transfer'); ?></div>
                                        </td>
                                        <td>
                                            <div class="payment-amount">£<?php echo number_format($payment['amount'], 0); ?></div>
                                            <div class="payment-date">
                                                <?php 
                                                    if ($payment['payment_month'] && $payment['payment_month'] !== '0000-00-00') {
                                                        echo date('M Y', strtotime($payment['payment_month'] . '-01'));
                                                    } else {
                                                        echo t('payments.not_set');
                                                    }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="payment-date"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                                        </td>
                                                                <td>
                            <?php 
                                $status = $payment['status'] ?: 'pending'; // Default to 'pending' if empty
                            ?>
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo t('payments.' . $status); ?>
                            </span>
                        </td>
                                        <td>
                                            <?php if ($payment['verified_by_admin']): ?>
                                                <span class="verified-badge verified-yes"><?php echo t('payments.verified'); ?></span>
                                            <?php else: ?>
                                                <span class="verified-badge verified-no"><?php echo t('payments.unverified'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-action btn-edit" onclick="editPayment(<?php echo $payment['id']; ?>)" title="<?php echo t('payments.edit_payment'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-action btn-receipt" onclick="generateReceipt('payment', <?php echo $payment['id']; ?>)" title="<?php echo t('payments.generate_receipt'); ?>">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <button class="btn btn-action btn-verify" onclick="verifyPayment(<?php echo $payment['id']; ?>)" title="<?php echo t('payments.verify_payment'); ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-action btn-delete" onclick="deletePayment(<?php echo $payment['id']; ?>)" title="<?php echo t('payments.delete_payment'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (empty($payments)): ?>
                    <div class="text-center py-5">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="1" class="mb-3">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        <h4 style="color: var(--text-secondary);"><?php echo t('payments.no_payments_found'); ?></h4>
                        <p style="color: var(--text-secondary);"><?php echo t('payments.start_adding_payments'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

        </div> <!-- End app-content -->
    </main> <!-- End app-main -->
</div> <!-- End app-layout -->

    <!-- Add/Edit Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel"><?php echo t('payments.add_payment_title'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="paymentForm">
                    <input type="hidden" id="paymentId" name="payment_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="memberId" class="form-label"><?php echo t('payments.member'); ?> *</label>
                                    <select class="form-select" id="memberId" name="member_id" required>
                                        <option value=""><?php echo t('payments.select_member'); ?></option>
                                        <?php foreach ($members as $member): ?>
                                            <option value="<?php echo $member['id']; ?>" data-payment="<?php echo $member['monthly_payment']; ?>">
                                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['member_id'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label"><?php echo t('payments.payment_amount'); ?> *</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentDate" class="form-label"><?php echo t('payments.payment_date_label'); ?> *</label>
                                    <input type="date" class="form-control" id="paymentDate" name="payment_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentMonth" class="form-label"><?php echo t('payments.payment_month'); ?> *</label>
                                    <input type="month" class="form-control" id="paymentMonth" name="payment_month" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="paymentMethod" class="form-label"><?php echo t('payments.payment_method'); ?></label>
                                    <select class="form-select" id="paymentMethod" name="payment_method">
                                        <option value="cash"><?php echo t('payments.cash'); ?></option>
                                        <option value="bank_transfer"><?php echo t('payments.bank_transfer'); ?></option>
                                        <option value="mobile_money"><?php echo t('payments.mobile_money'); ?></option>
                                        <option value="check"><?php echo t('payments.check'); ?></option>
                                        <option value="other"><?php echo t('payments.other'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label"><?php echo t('payments.status'); ?></label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending"><?php echo t('payments.pending'); ?></option>
                                        <option value="paid"><?php echo t('payments.paid'); ?></option>
                                        <option value="late"><?php echo t('payments.late'); ?></option>
                                        <option value="missed"><?php echo t('payments.missed'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="receiptNumber" class="form-label"><?php echo t('payments.receipt_number'); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="receiptNumber" name="receipt_number">
                                        <button class="btn btn-outline-primary" type="button" id="generateReceiptBtn" onclick="generateReceiptNumber()" title="<?php echo t('payments.generate'); ?> <?php echo t('payments.receipt_number'); ?>">
                                            <i class="fas fa-refresh"></i> <?php echo t('payments.generate'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lateFee" class="form-label"><?php echo t('payments.late_fee'); ?></label>
                                    <input type="number" class="form-control" id="lateFee" name="late_fee" step="0.01" min="0" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label"><?php echo t('payments.notes'); ?></label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="<?php echo t('payments.notes_placeholder'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('payments.cancel'); ?></button>
                        <button type="submit" class="btn btn-primary" style="background: var(--color-teal); border-color: var(--color-teal);">
                            <span id="submitText"><?php echo t('payments.add_payment_btn'); ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Message Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--color-gold); color: white;">
                    <h5 class="modal-title" id="successModalLabel"><?php echo t('payments.success'); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-gold)" stroke-width="2" class="mb-3">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                        <h4 id="successMessage" style="color: var(--text-primary);"><?php echo t('payments.payment_added_success'); ?></h4>
                        <p id="successDetails" style="color: var(--text-secondary);"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" style="background: var(--color-gold); border-color: var(--color-gold);" data-bs-dismiss="modal">
                        <?php echo t('payments.continue'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth.js"></script>
    
    <script>
        // Navigation functions are handled by includes/navigation.php

        // Payment management functions
        let isEditMode = false;
        let currentPaymentId = null;

        // Status translation function
        function getStatusTranslation(status) {
            const statusTranslations = {
                'pending': '<?php echo t('payments.pending'); ?>',
                'paid': '<?php echo t('payments.paid'); ?>',
                'completed': '<?php echo t('payments.completed'); ?>',
                'failed': '<?php echo t('payments.failed'); ?>',
                'late': '<?php echo t('payments.late'); ?>',
                'missed': '<?php echo t('payments.missed'); ?>'
            };
            return statusTranslations[status] || status.charAt(0).toUpperCase() + status.slice(1);
        }

        function showAddPaymentModal() {
            isEditMode = false;
            currentPaymentId = null;
            document.getElementById('paymentModalLabel').textContent = '<?php echo t('payments.add_payment_title'); ?>';
            document.getElementById('submitText').textContent = '<?php echo t('payments.add_payment_btn'); ?>';
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentId').value = '';
            
            // Set default date to today
            document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];
            // Set default month to current month
            document.getElementById('paymentMonth').value = new Date().toISOString().slice(0, 7);
            
            // Make receipt number readonly for new payments and auto-generate
            document.getElementById('receiptNumber').readOnly = true;
            document.getElementById('generateReceiptBtn').style.display = 'block';
            generateReceiptNumber();
            
            // Refresh CSRF token
            refreshCSRFToken();
            
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        // Refresh CSRF token to prevent expiry issues
        function refreshCSRFToken() {
            fetch('api/payments.php?action=get_csrf_token')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.csrf_token) {
                        document.querySelector('input[name="csrf_token"]').value = data.csrf_token;
                    }
                })
                .catch(error => {
                    // Silent fail for CSRF token refresh
                });
        }

        // Generate unique receipt number
        function generateReceiptNumber() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            // Format: HER-YYYYMMDD-HHMMSS (HER = HabeshaEqub Receipt)
            const receiptNumber = `HER-${year}${month}${day}-${hours}${minutes}${seconds}`;
            
            document.getElementById('receiptNumber').value = receiptNumber;
        }

        function editPayment(id) {

            isEditMode = true;
            currentPaymentId = id;
            document.getElementById('paymentModalLabel').textContent = 'Edit Payment';
            document.getElementById('submitText').textContent = 'Update Payment';
            
            // Make receipt number editable for editing
            document.getElementById('receiptNumber').readOnly = false;
            document.getElementById('generateReceiptBtn').style.display = 'none';
            
            // Refresh CSRF token
            refreshCSRFToken();
            
            // Fetch payment data
            fetch(`api/payments.php?action=get&payment_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const payment = data.payment;
                        document.getElementById('paymentId').value = payment.id;
                        document.getElementById('memberId').value = payment.member_db_id;
                        document.getElementById('amount').value = payment.amount;
                        document.getElementById('paymentDate').value = payment.payment_date;
                        document.getElementById('paymentMonth').value = payment.payment_month;
                        document.getElementById('paymentMethod').value = payment.payment_method || 'bank_transfer';
                        document.getElementById('status').value = payment.status || 'pending';
                        document.getElementById('receiptNumber').value = payment.receipt_number || '';
                        document.getElementById('lateFee').value = payment.late_fee || 0;
                        document.getElementById('notes').value = payment.notes || '';
                        
                        new bootstrap.Modal(document.getElementById('paymentModal')).show();
                    } else {

                        alert('Error fetching payment data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching payment data');
                });
        }

        function verifyPayment(id) {
            if (confirm('Are you sure you want to verify this payment?')) {
                // Get CSRF token with fallback
                const csrfToken = getCSRFToken();
                if (!csrfToken) return; // Exit if no token available
                
                fetch('api/payments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verify&payment_id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Payment verified successfully!', 'success');
                        loadPayments();
                    } else {
                        // Check if it's a CSRF token error
                        if (data.message && data.message.includes('security token')) {
                            if (confirm('Security token expired. Would you like to refresh the page and try again?')) {
                                window.location.reload();
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while verifying payment');
                });
            }
        }

        function deletePayment(id) {
            if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
                // Get CSRF token with fallback
                const csrfToken = getCSRFToken();
                if (!csrfToken) return; // Exit if no token available
                
                fetch('api/payments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&payment_id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Payment deleted successfully!', 'success');
                        loadPayments();
                    } else {
                        // Check if it's a CSRF token error
                        if (data.message && data.message.includes('security token')) {
                            if (confirm('Security token expired. Would you like to refresh the page and try again?')) {
                                window.location.reload();
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting payment');
                });
            }
        }

        // Form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = isEditMode ? 'update' : 'add';
            formData.append('action', action);
            

            
            fetch('api/payments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    
                    // Show success modal with payment ID
                    document.getElementById('successMessage').textContent = 
                        isEditMode ? 'Payment updated successfully!' : 'Payment added successfully!';
                    document.getElementById('successDetails').textContent = 
                        isEditMode ? '' : `Payment ID: ${data.payment_id}`;
                    
                    new bootstrap.Modal(document.getElementById('successModal')).show();
                    
                    // Reload payments
                    loadPayments();
                } else {
                    // Check if it's a CSRF token error
                    if (data.message && data.message.includes('security token')) {
                        if (confirm('Security token expired. Would you like to refresh the page and try again?')) {
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving payment');
            });
        });

        // Auto-fill amount when member is selected
        document.getElementById('memberId').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const monthlyPayment = selectedOption.getAttribute('data-payment');
            if (monthlyPayment && !isEditMode) {
                document.getElementById('amount').value = monthlyPayment;
            }
        });

        // Load payments with filters
        function loadPayments() {
            const search = document.getElementById('paymentSearch').value;
            const status = document.getElementById('statusFilter').value;
            const member = document.getElementById('memberFilter').value;
            const month = document.getElementById('monthFilter').value;
            
            const params = new URLSearchParams({
                action: 'list',
                search: search,
                status: status,
                member_id: member,
                month: month,
                _t: Date.now() // Prevent caching
            });
            
            fetch(`api/payments.php?${params}`, {
                cache: 'no-cache' // Ensure no caching
            })
                .then(response => response.json())
                            .then(data => {
                if (data.success) {

                    updatePaymentsTable(data.payments);
                }
            })
                .catch(error => {
                    console.error('Error loading payments:', error);
                });
        }

        // Update payments table
        function updatePaymentsTable(payments) {
            const tbody = document.getElementById('paymentsTableBody');
            tbody.innerHTML = '';
            
            if (payments.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <span style="color: var(--text-secondary);">No payments found matching the current filters.</span>
                        </td>
                    </tr>
                `;
                return;
            }
            
            payments.forEach(payment => {

                const initials = payment.first_name.charAt(0) + payment.last_name.charAt(0);
                const paymentDate = new Date(payment.payment_date).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'short', day: 'numeric'
                });
                const paymentMonth = (payment.payment_month && payment.payment_month !== '0000-00-00') 
                    ? new Date(payment.payment_month + '-01').toLocaleDateString('en-US', {
                        year: 'numeric', month: 'short'
                    })
                    : 'Not Set';
                
                const verifyButton = payment.status === 'pending' ? 
                    `<button class="btn btn-action btn-verify" onclick="verifyPayment(${payment.id})" title="Verify Payment">
                        <i class="fas fa-check"></i>
                    </button>` : '';

                const verifiedBadge = payment.verified_by_admin ? 
                    '<span class="verified-badge verified-yes">Verified</span>' : 
                    '<span class="verified-badge verified-no">Unverified</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="member-info">
                                <div class="member-avatar">${initials}</div>
                                <div class="member-details">
                                    <div class="member-name">
                                        <a href="member-profile.php?id=${payment.member_db_id}" class="member-name-link">
                                            ${payment.first_name} ${payment.last_name}
                                        </a>
                                    </div>
                                    <div class="member-id">${payment.member_code}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="payment-id">${payment.payment_id}</div>
                            <div class="payment-method">${payment.payment_method ? payment.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Bank Transfer'}</div>
                        </td>
                        <td>
                            <div class="payment-amount">£${parseFloat(payment.amount).toLocaleString()}</div>
                            <div class="payment-date">${paymentMonth}</div>
                        </td>
                        <td>
                            <div class="payment-date">${paymentDate}</div>
                        </td>
                        <td><span class="status-badge status-${payment.status || 'pending'}">${getStatusTranslation(payment.status || 'pending')}</span></td>
                        <td>${verifiedBadge}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-action btn-edit" onclick="editPayment(${payment.id})" title="Edit Payment">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-action btn-receipt" onclick="generateReceipt('payment', ${payment.id})" title="Generate Receipt">
                                    <i class="fas fa-receipt"></i>
                                </button>
                                ${verifyButton}
                                <button class="btn btn-action btn-delete" onclick="deletePayment(${payment.id})" title="Delete Payment">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        // Filter payments
        function filterPayments() {
            loadPayments();
        }

        // Also call filterPayments from searchPayments for consistency
        function searchPayments() {
            filterPayments();
        }

        // Show toast notifications
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Generate receipt function
        function generateReceipt(type, transactionId) {
            // Get CSRF token
            const csrfToken = getCSRFToken();
            if (!csrfToken) {
                if (confirm('Security token missing. Would you like to refresh the page and try again?')) {
                    window.location.reload();
                }
                return;
            }
            
            // Create receipt URL with parameters
            const receiptUrl = 'receipt.php?' + new URLSearchParams({
                type: type,
                id: transactionId,
                token: csrfToken
            });
            
            // Open receipt in new window/tab
            const receiptWindow = window.open(receiptUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
            
            if (!receiptWindow) {
                alert('Please allow popups for this site to generate receipts.');
            } else {
                showToast('Receipt generated successfully!', 'success');
            }
        }

        // Helper function to get CSRF token
        function getCSRFToken() {
            const token = document.querySelector('input[name="csrf_token"]');
            return token ? token.value : '';
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set up event listeners for real-time search
            document.getElementById('paymentSearch').addEventListener('input', debounce(filterPayments, 300));
        });

        // Debounce function for search
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>
</html> 