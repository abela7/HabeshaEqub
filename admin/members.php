<?php
/**
 * HabeshaEqub - Members Management Page
 * Admin interface for managing equib members
 */

require_once '../includes/db.php';
require_once '../languages/translator.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Get members data
try {
    $stmt = $pdo->query("
        SELECT m.*, 
               COUNT(p.id) as total_payments,
               COALESCE(SUM(p.amount), 0) as total_paid
        FROM members m 
        LEFT JOIN payments p ON m.id = p.member_id AND p.status = 'completed'
        GROUP BY m.id 
        ORDER BY m.payout_position ASC, m.created_at DESC
    ");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching members: " . $e->getMessage());
    $members = [];
}

$total_members = count($members);
$active_members = count(array_filter($members, fn($m) => $m['is_active']));
$completed_payouts = count(array_filter($members, fn($m) => $m['has_received_payout']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('members.page_title'); ?> - HabeshaEqub Admin</title>
    
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
        /* === TOP-TIER MEMBERS PAGE DESIGN === */
        
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
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%);
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

        .btn-add-member {
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%);
            color: white;
            font-size: 16px;
        }

        .btn-add-member:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(48, 25, 67, 0.25);
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

        .total-members .stat-icon { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .active-members .stat-icon { background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%); }
        .completed-payouts .stat-icon { background: linear-gradient(135deg, var(--color-light-gold) 0%, #B8941C 100%); }

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
            border-color: var(--color-teal);
            box-shadow: 0 0 0 3px rgba(19, 102, 92, 0.1);
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
            border-color: var(--color-teal);
            box-shadow: 0 0 0 3px rgba(19, 102, 92, 0.1);
            background: white;
        }

        /* Members Table */
        .members-table-container {
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

        .members-table {
            width: 100%;
            margin: 0;
        }

        .members-table thead th {
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

        .members-table tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }

        .members-table tbody tr:hover {
            background: rgba(233, 196, 106, 0.05);
        }

        .members-table tbody td {
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

        /* Contact Info */
        .contact-info .contact-email {
            font-weight: 500;
            color: var(--color-purple);
            margin: 0 0 4px 0;
            font-size: 14px;
        }

        .contact-phone {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Payment Info */
        .payment-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-teal);
            margin: 0 0 4px 0;
        }

        .payment-status {
            font-size: 14px;
            margin: 0;
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

        .status-active {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
        }

        .payout-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payout-received {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .payout-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #D97706;
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

        .btn-toggle {
            background: rgba(251, 191, 36, 0.1);
            color: #D97706;
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        .btn-toggle:hover {
            background: rgba(251, 191, 36, 0.2);
            transform: scale(1.1);
            border-color: rgba(251, 191, 36, 0.3);
        }

        .btn-toggle i {
            color: #D97706;
        }

        .btn-toggle:hover i {
            color: #B45309;
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

            .members-table-container {
                overflow-x: auto;
            }

            .members-table {
                min-width: 800px;
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

            .members-table tbody td {
                padding: 16px 12px;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <!-- Include Navigation -->
        <?php include 'includes/navigation.php'; ?>

            <!-- Members Page Content -->
            <div class="page-header">
                <div class="page-title-section">
                    <h1>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <?php echo t('members.page_title'); ?>
                    </h1>
                    <p class="page-subtitle"><?php echo t('members.page_subtitle'); ?></p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-add-member" onclick="showAddMemberModal()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M20 8v6M23 11h-6"/>
                        </svg>
                        <?php echo t('members.add_new_member'); ?>
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row stats-dashboard">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon total-members">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <span class="stat-trend"><?php echo t('members.total_members'); ?></span>
                        </div>
                        <h3 class="stat-number"><?php echo $total_members; ?></h3>
                        <p class="stat-label"><?php echo t('members.total_members'); ?></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon active-members">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                                </svg>
                            </div>
                            <span class="stat-trend"><?php echo t('members.active_members'); ?></span>
                        </div>
                        <h3 class="stat-number"><?php echo $active_members; ?></h3>
                        <p class="stat-label"><?php echo t('members.active_members'); ?></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon completed-payouts">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M16 12l-4-4-4 4"/>
                                    <path d="M12 16V8"/>
                                </svg>
                            </div>
                            <span class="stat-trend"><?php echo t('members.completed_payouts'); ?></span>
                        </div>
                        <h3 class="stat-number"><?php echo $completed_payouts; ?></h3>
                        <p class="stat-label"><?php echo t('members.completed_payouts'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Members Management Section -->
            <div class="search-filter-section">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="search-bar">
                            <input type="text" class="search-input" id="memberSearch" placeholder="<?php echo t('members.search_placeholder'); ?>" oninput="searchMembers()">
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
                            <select id="statusFilter" class="filter-select" onchange="filterMembers()">
                                <option value=""><?php echo t('members.all_status'); ?></option>
                                <option value="active"><?php echo t('members.active'); ?></option>
                                <option value="inactive"><?php echo t('members.inactive'); ?></option>
                            </select>
                            <select id="payoutFilter" class="filter-select" onchange="filterMembers()">
                                <option value=""><?php echo t('members.all_payouts'); ?></option>
                                <option value="completed"><?php echo t('members.received_payout'); ?></option>
                                <option value="pending"><?php echo t('members.pending_payout'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Members Table -->
                <div class="members-table-container">
                    <div class="table-header">
                        <h3 class="table-title"><?php echo t('members.all_members'); ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table class="members-table">
                            <thead>
                                <tr>
                                    <th><?php echo t('members.member'); ?></th>
                                    <th><?php echo t('members.contact'); ?></th>
                                    <th><?php echo t('members.payment_details'); ?></th>
                                    <th><?php echo t('members.payout_status'); ?></th>
                                    <th><?php echo t('members.status'); ?></th>
                                    <th><?php echo t('members.actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="membersTableBody">
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="member-info">
                                                <div class="member-avatar">
                                                    <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="member-details">
                                                    <div class="member-name">
                                                        <a href="member-profile.php?id=<?php echo $member['id']; ?>" class="member-name-link">
                                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                                        </a>
                                                    </div>
                                                    <div class="member-id"><?php echo htmlspecialchars($member['member_id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="contact-info">
                                                <div class="contact-email"><?php echo htmlspecialchars($member['email']); ?></div>
                                                <div class="contact-phone"><?php echo htmlspecialchars($member['phone']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="payment-info">
                                                <div class="payment-amount">£<?php echo number_format($member['monthly_payment'], 0); ?>/<?php echo t('members.month'); ?></div>
                                                <div class="payment-status"><?php echo t('members.paid'); ?>: £<?php echo number_format($member['total_paid'], 0); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($member['has_received_payout']): ?>
                                                <span class="payout-badge payout-received"><?php echo t('members.received'); ?></span>
                                            <?php else: ?>
                                                <span class="payout-badge payout-pending"><?php echo t('members.pending'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($member['is_active']): ?>
                                                <span class="status-badge status-active"><?php echo t('members.active'); ?></span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive"><?php echo t('members.inactive'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-action btn-edit" onclick="editMember(<?php echo $member['id']; ?>)" title="<?php echo t('members.edit_member'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-action btn-toggle" 
                                                        onclick="toggleMemberStatus(<?php echo $member['id']; ?>, <?php echo $member['is_active'] ? 0 : 1; ?>)" 
                                                        title="<?php echo $member['is_active'] ? t('members.deactivate') : t('members.activate'); ?>">
                                                    <?php if ($member['is_active']): ?>
                                                        <i class="fas fa-toggle-on"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-toggle-off"></i>
                                                    <?php endif; ?>
                                                </button>
                                                <button class="btn btn-action btn-delete" 
                                                        onclick="deleteMember(<?php echo $member['id']; ?>)" title="<?php echo t('members.delete_member'); ?>">
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

        </div> <!-- End app-content -->
    </main> <!-- End app-main -->
</div> <!-- End app-layout -->

    <!-- Add/Edit Member Modal -->
    <div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberModalLabel"><?php echo t('members.add_member_title'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="memberForm">
                    <div class="modal-body">
                        <input type="hidden" id="memberId" name="member_id">
                        
                        <!-- Personal Information -->
                        <h6 class="text-primary mb-3"><?php echo t('members.personal_information'); ?></h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label"><?php echo t('members.first_name'); ?> *</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label"><?php echo t('members.last_name'); ?> *</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label"><?php echo t('members.email'); ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label"><?php echo t('members.phone'); ?> *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Equib Information -->
                        <h6 class="text-primary mb-3 mt-4"><?php echo t('members.equib_information'); ?></h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="monthlyPayment" class="form-label"><?php echo t('members.monthly_payment'); ?> *</label>
                                    <input type="number" class="form-control" id="monthlyPayment" name="monthly_payment" min="1" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payoutPosition" class="form-label"><?php echo t('members.payout_position'); ?> *</label>
                                    <input type="number" class="form-control" id="payoutPosition" name="payout_position" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Guarantor Information -->
                        <h6 class="text-primary mb-3 mt-4"><?php echo t('members.guarantor_information'); ?></h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="guarantorFirstName" class="form-label"><?php echo t('members.guarantor_first_name'); ?></label>
                                    <input type="text" class="form-control" id="guarantorFirstName" name="guarantor_first_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="guarantorLastName" class="form-label"><?php echo t('members.guarantor_last_name'); ?></label>
                                    <input type="text" class="form-control" id="guarantorLastName" name="guarantor_last_name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="guarantorPhone" class="form-label"><?php echo t('members.guarantor_phone'); ?></label>
                                    <input type="tel" class="form-control" id="guarantorPhone" name="guarantor_phone">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="guarantorEmail" class="form-label"><?php echo t('members.guarantor_email'); ?></label>
                                    <input type="email" class="form-control" id="guarantorEmail" name="guarantor_email">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="guarantorRelationship" class="form-label"><?php echo t('members.relationship'); ?></label>
                                    <select class="form-select" id="guarantorRelationship" name="guarantor_relationship">
                                        <option value=""><?php echo t('members.select'); ?></option>
                                        <option value="Father"><?php echo t('members.father'); ?></option>
                                        <option value="Mother"><?php echo t('members.mother'); ?></option>
                                        <option value="Brother"><?php echo t('members.brother'); ?></option>
                                        <option value="Sister"><?php echo t('members.sister'); ?></option>
                                        <option value="Husband"><?php echo t('members.husband'); ?></option>
                                        <option value="Wife"><?php echo t('members.wife'); ?></option>
                                        <option value="Friend"><?php echo t('members.friend'); ?></option>
                                        <option value="Other"><?php echo t('members.other'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label"><?php echo t('members.notes'); ?></label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('members.cancel'); ?></button>
                        <button type="submit" class="btn btn-primary" id="submitBtn"><?php echo t('members.add_member_btn'); ?></button>
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
                    <h5 class="modal-title" id="successModalLabel"><?php echo t('members.member_added_success'); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="successMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal"><?php echo t('members.ok'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/auth.js"></script>
    
    <script>
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

        // Search functionality
        document.getElementById('memberSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#membersTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Member management functions
        let isEditMode = false;
        let currentMemberId = null;



        function showAddMemberModal() {
            isEditMode = false;
            currentMemberId = null;
            document.getElementById('memberModalLabel').textContent = 'Add New Member';
            document.getElementById('submitBtn').textContent = 'Add Member';
            document.getElementById('memberForm').reset();
            document.getElementById('memberId').value = '';
            new bootstrap.Modal(document.getElementById('memberModal')).show();
        }

        function editMember(id) {
            isEditMode = true;
            currentMemberId = id;
            document.getElementById('memberModalLabel').textContent = 'Edit Member';
            document.getElementById('submitBtn').textContent = 'Update Member';
            
            // Fetch member data
            fetch('api/members.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_member&member_id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const member = data.member;
                    document.getElementById('memberId').value = member.id;
                    document.getElementById('firstName').value = member.first_name;
                    document.getElementById('lastName').value = member.last_name;
                    document.getElementById('email').value = member.email;
                    document.getElementById('phone').value = member.phone;
                    document.getElementById('monthlyPayment').value = member.monthly_payment;
                    document.getElementById('payoutPosition').value = member.payout_position;
                    document.getElementById('guarantorFirstName').value = member.guarantor_first_name || '';
                    document.getElementById('guarantorLastName').value = member.guarantor_last_name || '';
                    document.getElementById('guarantorPhone').value = member.guarantor_phone || '';
                    document.getElementById('guarantorEmail').value = member.guarantor_email || '';
                    document.getElementById('guarantorRelationship').value = member.guarantor_relationship || '';
                    document.getElementById('notes').value = member.notes || '';
                    
                    new bootstrap.Modal(document.getElementById('memberModal')).show();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching member data');
            });
        }

        function toggleMemberStatus(id, status) {
            const action = status ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this member?`)) {
                fetch('api/members.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=toggle_status&member_id=${id}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadMembers();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating member status');
                });
            }
        }

        function deleteMember(id) {
            if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
                fetch('api/members.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&member_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadMembers();
                        updateStats();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting member');
                });
            }
        }

        // Form submission
        document.getElementById('memberForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = isEditMode ? 'update' : 'add';
            formData.append('action', action);
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = isEditMode ? 'Updating...' : 'Adding...';
            
            fetch('api/members.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
                    
                    if (!isEditMode && data.member_id && data.password) {
                        // Show success modal with member credentials
                        document.getElementById('successMessage').innerHTML = `
                            <div class="alert alert-info">
                                <h6>New member created successfully!</h6>
                                <p><strong>Member ID:</strong> ${data.member_id}</p>
                                <p><strong>Password:</strong> ${data.password}</p>
                                <small class="text-muted">Please save these credentials and share them with the member.</small>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('successModal')).show();
                    } else {
                        showToast(data.message, 'success');
                    }
                    
                    loadMembers();
                    updateStats();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving member data');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // Load members with filters
        function loadMembers() {
            const searchTerm = document.getElementById('memberSearch').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const payoutFilter = document.getElementById('payoutFilter').value;
            
            const formData = new FormData();
            formData.append('action', 'list');
            if (searchTerm) formData.append('search', searchTerm);
            if (statusFilter !== '') formData.append('status', statusFilter);
            if (payoutFilter !== '') formData.append('payout_status', payoutFilter);
            
            fetch('api/members.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMembersTable(data.members);
                }
            })
            .catch(error => {
                console.error('Error loading members:', error);
            });
        }

        // Update members table
        function updateMembersTable(members) {
            const tbody = document.getElementById('membersTableBody');
            tbody.innerHTML = '';
            
            members.forEach(member => {
                const initials = (member.first_name.charAt(0) + member.last_name.charAt(0)).toUpperCase();
                const statusBadge = member.is_active ? 
                    '<span class="status-badge status-active">Active</span>' : 
                    '<span class="status-badge status-inactive">Inactive</span>';
                const payoutBadge = member.has_received_payout ? 
                    '<span class="payout-badge payout-received">Received</span>' : 
                    '<span class="payout-badge payout-pending">Pending</span>';
                
                const row = `
                    <tr>
                        <td>
                            <div class="member-info">
                                <div class="member-avatar">${initials}</div>
                                <div class="member-details">
                                    <div class="member-name">
                                        <a href="member-profile.php?id=${member.id}" class="member-name-link">
                                            ${member.first_name} ${member.last_name}
                                        </a>
                                    </div>
                                    <div class="member-id">${member.member_id}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="contact-info">
                                <div class="contact-email">${member.email}</div>
                                <div class="contact-phone">${member.phone}</div>
                            </div>
                        </td>
                        <td>
                            <div class="payment-info">
                                <div class="payment-amount">£${parseFloat(member.monthly_payment).toLocaleString()}/month</div>
                                <div class="payment-status">Paid: £${parseFloat(member.total_paid).toLocaleString()}</div>
                            </div>
                        </td>
                        <td>${payoutBadge}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-action btn-edit" onclick="editMember(${member.id})" title="Edit Member">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-action btn-toggle" onclick="toggleMemberStatus(${member.id}, ${member.is_active ? 0 : 1})" title="${member.is_active ? 'Deactivate' : 'Activate'}">
                                    ${member.is_active ? 
                                        '<i class="fas fa-toggle-on"></i>' :
                                        '<i class="fas fa-toggle-off"></i>'
                                    }
                                </button>
                                <button class="btn btn-action btn-delete" onclick="deleteMember(${member.id})" title="Delete Member">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Update statistics
        function updateStats() {
            // You can implement this to refresh stats after member changes
            setTimeout(() => location.reload(), 1000);
        }

        // Show toast notifications
        function showToast(message, type = 'info') {
            // Simple toast implementation
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Filter event listeners
        document.getElementById('statusFilter').addEventListener('change', loadMembers);
        document.getElementById('payoutFilter').addEventListener('change', loadMembers);

        // Enhanced search with debounce
        let searchTimeout;
        document.getElementById('memberSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadMembers();
            }, 300);
        });

        // Close mobile menu on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // This function is no longer needed as mobile menu is handled by navigation.php
                // Keeping it for now in case it's used elsewhere or for future updates.
            }
        });
    </script>
</body>
</html> 