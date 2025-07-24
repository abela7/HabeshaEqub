<?php
/**
 * HabeshaEqub - Payout Management
 * Comprehensive payout management and tracking system
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

// Get payouts data with member information
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               m.first_name, m.last_name, m.member_id as member_code, m.email,
               pa.username as processed_by_name
        FROM payouts p 
        LEFT JOIN members m ON p.member_id = m.id
        LEFT JOIN admins pa ON p.processed_by_admin_id = pa.id
        ORDER BY p.scheduled_date DESC, p.created_at DESC
    ");
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payouts: " . $e->getMessage());
    $payouts = [];
}

// Get members for dropdown
try {
    $stmt = $pdo->query("
        SELECT id, member_id, first_name, last_name, monthly_payment, 
               payout_position, has_received_payout
        FROM members 
        WHERE is_active = 1 
        ORDER BY payout_position ASC, first_name ASC
    ");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching members: " . $e->getMessage());
    $members = [];
}

// Calculate payout statistics
$total_payouts = count($payouts);
$completed_payouts = count(array_filter($payouts, fn($p) => $p['status'] === 'completed'));
$scheduled_payouts = count(array_filter($payouts, fn($p) => $p['status'] === 'scheduled'));
$total_amount = array_sum(array_column(array_filter($payouts, fn($p) => $p['status'] === 'completed'), 'total_amount'));

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('payouts.page_title'); ?> - HabeshaEqub Admin</title>
    
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
        /* === TOP-TIER PAYOUTS PAGE DESIGN === */
        
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
        }
        
        .page-title-section p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 16px;
        }
        
        .add-payout-btn {
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F766E 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(19, 102, 92, 0.3);
        }
        
        .add-payout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(19, 102, 92, 0.4);
            color: white;
        }

        /* Statistics Cards */
        .stats-container {
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(48, 25, 67, 0.12);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 20px;
        }

        .stat-icon.total { background: rgba(233, 196, 106, 0.1); color: var(--color-gold); }
        .stat-icon.completed { background: rgba(34, 197, 94, 0.1); color: #059669; }
        .stat-icon.scheduled { background: rgba(59, 130, 246, 0.1); color: #2563EB; }
        .stat-icon.amount { background: rgba(139, 92, 246, 0.1); color: #7C3AED; }

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
            font-size: 16px;
        }

        .filter-group {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .filter-select {
            min-width: 150px;
            padding: 10px 16px;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(233, 196, 106, 0.1);
        }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: var(--color-cream);
            border-bottom: 2px solid var(--border-light);
            color: var(--color-purple);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 20px 16px;
            border-top: none;
        }

        .table tbody td {
            padding: 20px 16px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(233, 196, 106, 0.02);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Member Info */
        .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F766E 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
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
            font-family: 'Courier New', monospace;
        }

        /* Payout Info */
        .payout-id {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: var(--color-purple);
            font-weight: 600;
        }

        .payout-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-gold);
            margin: 0 0 4px 0;
        }

        .payout-method {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
        }

        .payout-date {
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

        .status-completed {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .status-scheduled {
            background: rgba(59, 130, 246, 0.1);
            color: #2563EB;
        }

        .status-processing {
            background: rgba(251, 191, 36, 0.1);
            color: #D97706;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
        }

        .status-on_hold {
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

        .btn-process {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .btn-process:hover {
            background: rgba(34, 197, 94, 0.2);
            transform: scale(1.1);
            border-color: rgba(34, 197, 94, 0.3);
        }

        .btn-process i {
            color: #059669;
        }

        .btn-process:hover i {
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

            .search-filter-section {
                padding: 20px 16px;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .stat-card {
                padding: 20px;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 800px;
            }

            .action-buttons {
                gap: 4px;
            }

            .btn-action {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <!-- Include Navigation -->
        <?php include 'includes/navigation.php'; ?>

        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title-section">
                <h1><?php echo t('payouts.page_title'); ?></h1>
                <p><?php echo t('payouts.page_subtitle'); ?></p>
            </div>
            <button class="add-payout-btn" onclick="showAddPayoutModal()">
                <i class="fas fa-plus me-2"></i>
                <?php echo t('payouts.schedule_payout'); ?>
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="row">
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_payouts; ?></div>
                        <div class="stat-label"><?php echo t('payouts.total_payouts'); ?></div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $completed_payouts; ?></div>
                        <div class="stat-label"><?php echo t('payouts.completed'); ?></div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon scheduled">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $scheduled_payouts; ?></div>
                        <div class="stat-label"><?php echo t('payouts.scheduled'); ?></div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon amount">
                            <i class="fas fa-pound-sign"></i>
                        </div>
                        <div class="stat-number">£<?php echo number_format($total_amount, 0); ?></div>
                        <div class="stat-label"><?php echo t('payouts.total_distributed'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="payoutSearch" placeholder="<?php echo t('payouts.search_placeholder_payouts'); ?>">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter">
                            <option value=""><?php echo t('payouts.all_status'); ?></option>
                            <option value="scheduled"><?php echo t('payouts.scheduled'); ?></option>
                            <option value="processing"><?php echo t('payouts.processing'); ?></option>
                            <option value="completed"><?php echo t('payouts.completed'); ?></option>
                            <option value="cancelled"><?php echo t('payouts.cancelled'); ?></option>
                            <option value="on_hold"><?php echo t('payouts.on_hold'); ?></option>
                        </select>
                        <select class="filter-select" id="memberFilter">
                            <option value=""><?php echo t('payouts.all_members'); ?></option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payouts Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo t('payouts.member'); ?></th>
                        <th><?php echo t('payouts.payout_details'); ?></th>
                        <th><?php echo t('payouts.amount'); ?></th>
                        <th><?php echo t('payouts.scheduled_date'); ?></th>
                        <th><?php echo t('payouts.actual_date'); ?></th>
                        <th><?php echo t('payouts.status'); ?></th>
                        <th><?php echo t('payouts.actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="payoutsTableBody">
                    <?php foreach ($payouts as $payout): ?>
                        <tr>
                            <td>
                                <div class="member-info">
                                    <div class="member-avatar">
                                        <?php echo strtoupper(substr($payout['first_name'], 0, 1) . substr($payout['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="member-details">
                                        <div class="member-name">
                                            <a href="member-profile.php?id=<?php echo $payout['member_id']; ?>" class="member-name-link">
                                                <?php echo htmlspecialchars($payout['first_name'] . ' ' . $payout['last_name']); ?>
                                            </a>
                                        </div>
                                        <div class="member-id"><?php echo htmlspecialchars($payout['member_code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="payout-id"><?php echo htmlspecialchars($payout['payout_id']); ?></div>
                                <div class="payout-method"><?php echo ucfirst(str_replace('_', ' ', $payout['payout_method'] ?? 'bank_transfer')); ?></div>
                            </td>
                            <td>
                                <div class="payout-amount">£<?php echo number_format($payout['total_amount'], 0); ?></div>
                                <?php if ($payout['admin_fee'] > 0): ?>
                                    <div class="payout-method">Fee: £<?php echo number_format($payout['admin_fee'], 0); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="payout-date"><?php echo date('M d, Y', strtotime($payout['scheduled_date'])); ?></div>
                            </td>
                            <td>
                                <div class="payout-date">
                                    <?php echo $payout['actual_payout_date'] ? date('M d, Y', strtotime($payout['actual_payout_date'])) : t('payouts.not_processed'); ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    $status = $payout['status'] ?: 'scheduled';
                                ?>
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo t('payouts.' . $status); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-action btn-edit" onclick="editPayout(<?php echo $payout['id']; ?>)" title="<?php echo t('payouts.edit_payout'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-action btn-receipt" onclick="generateReceipt('payout', <?php echo $payout['id']; ?>)" title="<?php echo t('payouts.generate_receipt'); ?>">
                                        <i class="fas fa-receipt"></i>
                                    </button>
                                    <?php if ($payout['status'] === 'scheduled' || $payout['status'] === 'processing'): ?>
                                        <button class="btn btn-action btn-process" onclick="processPayout(<?php echo $payout['id']; ?>)" title="<?php echo t('payouts.process_payout'); ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-action btn-delete" onclick="deletePayout(<?php echo $payout['id']; ?>)" title="<?php echo t('payouts.delete_payout'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($payouts)): ?>
                <div class="text-center py-5">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="1" class="mb-3">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <path d="M12 17h.01"/>
                    </svg>
                    <h4 style="color: var(--text-secondary);">No Payouts Found</h4>
                    <p style="color: var(--text-secondary);">Start by scheduling your first payout.</p>
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- End app-content -->
</main> <!-- End app-main -->
</div> <!-- End app-layout -->

<!-- Add/Edit Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1" aria-labelledby="payoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                                    <h5 class="modal-title" id="payoutModalLabel"><?php echo t('payouts.schedule_payout'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="payoutForm">
                <input type="hidden" id="payoutId" name="payout_id">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="memberId" class="form-label">Member *</label>
                                <select class="form-select" id="memberId" name="member_id" required>
                                    <option value="">Select Member</option>
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?php echo $member['id']; ?>" 
                                                data-payment="<?php echo $member['monthly_payment']; ?>"
                                                data-position="<?php echo $member['payout_position']; ?>"
                                                data-received="<?php echo $member['has_received_payout']; ?>">
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['member_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="totalAmount" class="form-label">Total Amount (£) *</label>
                                <input type="number" class="form-control" id="totalAmount" name="total_amount" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="scheduledDate" class="form-label">Scheduled Date *</label>
                                <input type="date" class="form-control" id="scheduledDate" name="scheduled_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payoutMethod" class="form-label">Payout Method</label>
                                <select class="form-select" id="payoutMethod" name="payout_method">
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="check">Check</option>
                                    <option value="mixed">Mixed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="adminFee" class="form-label">Admin Fee (£)</label>
                                <input type="number" class="form-control" id="adminFee" name="admin_fee" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label"><?php echo t('payouts.status'); ?></label>
                                <select class="form-select" id="status" name="status">
                                    <option value="scheduled"><?php echo t('payouts.scheduled'); ?></option>
                                    <option value="processing"><?php echo t('payouts.processing'); ?></option>
                                    <option value="completed"><?php echo t('payouts.completed'); ?></option>
                                    <option value="on_hold"><?php echo t('payouts.on_hold'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="payoutNotes" class="form-label">Notes</label>
                                <textarea class="form-control" id="payoutNotes" name="payout_notes" rows="3" placeholder="Additional notes about this payout..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitText">Schedule Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Message Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Payout Scheduled Successfully</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="successMessage"></div>
                <div id="successDetails" class="mt-2 text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="../assets/js/auth.js"></script>

<script>
    // Global variables
    let isEditMode = false;
    let currentPayoutId = null;

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        loadPayouts();
        setupFilters();
    });

    // Handle logout
    async function handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=logout'
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                window.location.href = 'login.php';
            }
        }
    }

    // Setup filters
    function setupFilters() {
        document.getElementById('payoutSearch').addEventListener('input', loadPayouts);
        document.getElementById('statusFilter').addEventListener('change', loadPayouts);
        document.getElementById('memberFilter').addEventListener('change', loadPayouts);
    }

    // Payout status translation function
    function getPayoutStatusTranslation(status) {
        const statusTranslations = {
            'scheduled': '<?php echo t('payouts.scheduled'); ?>',
            'processing': '<?php echo t('payouts.processing'); ?>',
            'completed': '<?php echo t('payouts.completed'); ?>',
            'cancelled': '<?php echo t('payouts.cancelled'); ?>',
            'on_hold': '<?php echo t('payouts.on_hold'); ?>'
        };
        return statusTranslations[status] || status.charAt(0).toUpperCase() + status.slice(1);
    }

    // Payment method translation function
    function getPaymentMethodTranslation(method) {
        const methodTranslations = {
            'bank_transfer': '<?php echo t('payouts.bank_transfer'); ?>',
            'cash': '<?php echo t('payments.cash'); ?>',
            'mobile_money': '<?php echo t('payments.mobile_money'); ?>',
            'check': '<?php echo t('payments.check'); ?>'
        };
        return methodTranslations[method] || method.charAt(0).toUpperCase() + method.slice(1);
    }

    // Show add payout modal
    function showAddPayoutModal() {
        isEditMode = false;
        currentPayoutId = null;
                    document.getElementById('payoutModalLabel').textContent = '<?php echo t('payouts.schedule_payout'); ?>';
        document.getElementById('submitText').textContent = '<?php echo t('payouts.schedule_payout'); ?>';
        document.getElementById('payoutForm').reset();
        document.getElementById('payoutId').value = '';
        
        // Set default date to today
        document.getElementById('scheduledDate').value = new Date().toISOString().split('T')[0];
        
        // Refresh CSRF token
        refreshCSRFToken();
        
        new bootstrap.Modal(document.getElementById('payoutModal')).show();
    }

    // Refresh CSRF token to prevent expiry issues
    function refreshCSRFToken() {
        fetch('api/payouts.php?action=get_csrf_token')
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

    // Edit payout
    function editPayout(id) {
        isEditMode = true;
        currentPayoutId = id;
        document.getElementById('payoutModalLabel').textContent = '<?php echo t('payouts.edit_payout'); ?>';
        document.getElementById('submitText').textContent = '<?php echo t('payouts.update_payout'); ?>';
        
        // Refresh CSRF token
        refreshCSRFToken();
        
        // Fetch payout data
        fetch(`api/payouts.php?action=get&payout_id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const payout = data.payout;
                    document.getElementById('payoutId').value = payout.id;
                    document.getElementById('memberId').value = payout.member_id;
                    document.getElementById('totalAmount').value = payout.total_amount;
                    document.getElementById('scheduledDate').value = payout.scheduled_date;
                    document.getElementById('payoutMethod').value = payout.payout_method || 'bank_transfer';
                    document.getElementById('adminFee').value = payout.admin_fee || 0;
                    document.getElementById('status').value = payout.status || 'scheduled';
                    document.getElementById('payoutNotes').value = payout.payout_notes || '';
                    
                    new bootstrap.Modal(document.getElementById('payoutModal')).show();
                } else {
                    alert('Error fetching payout data: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching payout data');
            });
    }

    // Process payout
    function processPayout(id) {
        if (confirm('Are you sure you want to process this payout? This will mark it as completed.')) {
            const csrfToken = getCSRFToken();
            if (!csrfToken) return;
            
            fetch('api/payouts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=process&payout_id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Payout processed successfully!', 'success');
                    loadPayouts();
                } else {
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
                alert('An error occurred while processing payout');
            });
        }
    }

    // Delete payout
    function deletePayout(id) {
        if (confirm('Are you sure you want to delete this payout? This action cannot be undone.')) {
            const csrfToken = getCSRFToken();
            if (!csrfToken) return;
            
            fetch('api/payouts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&payout_id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Payout deleted successfully!', 'success');
                    loadPayouts();
                } else {
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
                alert('An error occurred while deleting payout');
            });
        }
    }

    // Load payouts with filters
    function loadPayouts() {
        const search = document.getElementById('payoutSearch').value;
        const status = document.getElementById('statusFilter').value;
        const member = document.getElementById('memberFilter').value;

        const params = new URLSearchParams({
            action: 'list',
            search: search,
            status: status,
            member_id: member,
            _t: Date.now()
        });

        fetch(`api/payouts.php?${params}`, {
            cache: 'no-cache'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePayoutsTable(data.payouts);
            }
        })
        .catch(error => {
            console.error('Error loading payouts:', error);
        });
    }

    // Update payouts table
    function updatePayoutsTable(payouts) {
        const tbody = document.getElementById('payoutsTableBody');
        tbody.innerHTML = '';
        
        if (payouts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <span style="color: var(--text-secondary);">No payouts found matching the current filters.</span>
                    </td>
                </tr>
            `;
            return;
        }
        
        payouts.forEach(payout => {
            const initials = payout.first_name.charAt(0) + payout.last_name.charAt(0);
            const scheduledDate = new Date(payout.scheduled_date).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
            const actualDate = payout.actual_payout_date 
                ? new Date(payout.actual_payout_date).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'short', day: 'numeric'
                })
                : '<?php echo t('payouts.not_processed'); ?>';
                
            const processButton = (payout.status === 'scheduled' || payout.status === 'processing') ? 
                `<button class="btn btn-action btn-process" onclick="processPayout(${payout.id})" title="<?php echo t('payouts.process_payout'); ?>">
                    <i class="fas fa-check"></i>
                </button>` : '';
            
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="member-info">
                            <div class="member-avatar">${initials}</div>
                            <div class="member-details">
                                <div class="member-name">
                                    <a href="member-profile.php?id=${payout.member_id}" class="member-name-link">
                                        ${payout.first_name} ${payout.last_name}
                                    </a>
                                </div>
                                <div class="member-id">${payout.member_code}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="payout-id">${payout.payout_id}</div>
                        <div class="payout-method">${payout.payout_method ? getPaymentMethodTranslation(payout.payout_method) : '<?php echo t('payouts.bank_transfer'); ?>'}</div>
                    </td>
                    <td>
                        <div class="payout-amount">£${parseFloat(payout.total_amount).toLocaleString()}</div>
                        ${payout.admin_fee > 0 ? `<div class="payout-method">Fee: £${parseFloat(payout.admin_fee).toLocaleString()}</div>` : ''}
                    </td>
                    <td>
                        <div class="payout-date">${scheduledDate}</div>
                    </td>
                    <td>
                        <div class="payout-date">${actualDate}</div>
                    </td>
                    <td><span class="status-badge status-${payout.status || 'scheduled'}">${getPayoutStatusTranslation(payout.status || 'scheduled')}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-action btn-edit" onclick="editPayout(${payout.id})" title="<?php echo t('payouts.edit_payout'); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-action btn-receipt" onclick="generateReceipt('payout', ${payout.id})" title="<?php echo t('payouts.generate_receipt'); ?>">
                                <i class="fas fa-receipt"></i>
                            </button>
                            ${processButton}
                            <button class="btn btn-action btn-delete" onclick="deletePayout(${payout.id})" title="<?php echo t('payouts.delete_payout'); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    // Form submission
    document.getElementById('payoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const action = isEditMode ? 'update' : 'add';
        formData.append('action', action);
        
        fetch('api/payouts.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('payoutModal')).hide();
                
                // Show success modal with payout ID
                document.getElementById('successMessage').textContent = 
                    isEditMode ? 'Payout updated successfully!' : 'Payout scheduled successfully!';
                document.getElementById('successDetails').textContent = 
                    isEditMode ? '' : `Payout ID: ${data.payout_id}`;
                
                new bootstrap.Modal(document.getElementById('successModal')).show();
                
                // Reload payouts
                loadPayouts();
            } else {
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
            alert('An error occurred while saving payout');
        });
    });

    // Auto-calculate total amount when member is selected
    document.getElementById('memberId').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const monthlyPayment = parseFloat(selectedOption.dataset.payment || 0);
            // Calculate total pool amount (assuming all active members contribute)
            const totalMembers = <?php echo count($members); ?>;
            const suggestedAmount = monthlyPayment * totalMembers;
            
            if (!isEditMode) {
                document.getElementById('totalAmount').value = suggestedAmount.toFixed(2);
            }
        }
    });

    // Toast notification function
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
</script>
</body>
</html> 