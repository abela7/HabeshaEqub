<?php
/**
 * HabeshaEqub - Member Profile
 * Comprehensive member details and management page
 */

require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Get member ID from URL
$member_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$member_id) {
    header('Location: members.php');
    exit;
}

// Get member data
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               COUNT(p.id) as total_payments,
               COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as total_paid,
               MAX(p.payment_date) as last_payment_date
        FROM members m 
        LEFT JOIN payments p ON m.id = p.member_id
        WHERE m.id = ?
        GROUP BY m.id
    ");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        header('Location: members.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching member: " . $e->getMessage());
    header('Location: members.php');
    exit;
}

// Get member's payment history
try {
    $stmt = $pdo->prepare("
        SELECT p.*, a.username as verified_by_name
        FROM payments p 
        LEFT JOIN admins a ON p.verified_by_admin_id = a.id
        WHERE p.member_id = ? 
        ORDER BY p.payment_date DESC, p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$member_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    $payments = [];
}

// Get member's payout history
try {
    $stmt = $pdo->prepare("
        SELECT po.*, a.username as processed_by_name
        FROM payouts po 
        LEFT JOIN admins a ON po.processed_by_admin_id = a.id
        WHERE po.member_id = ? 
        ORDER BY po.scheduled_date DESC, po.created_at DESC
    ");
    $stmt->execute([$member_id]);
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payouts: " . $e->getMessage());
    $payouts = [];
}

// Calculate next payment date (assuming monthly payments)
$next_payment_date = null;
if ($member['last_payment_date']) {
    $last_payment = new DateTime($member['last_payment_date']);
    $next_payment_date = $last_payment->add(new DateInterval('P1M'));
} else {
    $join_date = new DateTime($member['join_date']);
    $next_payment_date = $join_date->add(new DateInterval('P1M'));
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> - Member Profile - HabeshaEqub</title>
    
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
        /* Navigation styles are handled by includes/navigation.php */

        /* === BACK NAVIGATION === */
        .back-navigation {
            margin-bottom: 30px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--secondary-bg);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--color-teal);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* === MEMBER HEADER === */
        .member-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-light);
        }

        .member-header-content {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .member-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--color-teal);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 28px;
            flex-shrink: 0;
        }

        .member-header-info {
            flex: 1;
        }

        .member-full-name {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .member-id-badge {
            display: inline-block;
            background: var(--color-light-gold);
            color: var(--text-primary);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .member-status-badges {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active { background: var(--color-gold); color: white; }
        .status-inactive { background: var(--color-coral); color: white; }
        .payout-received { background: var(--color-teal); color: white; }
        .payout-pending { background: var(--secondary-bg); color: var(--text-secondary); }

        .member-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }

        .action-btn.primary { background: var(--color-teal); color: white; }
        .action-btn.secondary { background: var(--secondary-bg); color: var(--text-primary); }
        .action-btn.danger { background: var(--color-coral); color: white; }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-medium);
            text-decoration: none;
            filter: brightness(110%);
        }

        /* === INFO CARDS === */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            text-align: right;
        }

        .info-value.success { color: var(--color-teal); }
        .info-value.warning { color: var(--color-gold); }
        .info-value.danger { color: var(--color-coral); }

        /* === PAYMENT HISTORY === */
        .payment-history-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-light);
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: var(--secondary-bg);
            border: none;
            font-weight: 600;
            color: var(--text-primary);
            padding: 16px;
            font-size: 14px;
        }

        .table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
            border-color: var(--border-light);
            font-size: 14px;
        }

        .payment-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed { background: var(--color-gold); color: white; }
        .status-pending { background: var(--secondary-bg); color: var(--text-secondary); }
        .status-failed { background: var(--color-coral); color: white; }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: flex;
            }

            .dashboard-nav {
                display: none;
            }
            
            .member-header-content {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
            
            .member-full-name {
                font-size: 24px;
            }
            
            .member-actions {
                justify-content: center;
                width: 100%;
            }
            
            .action-btn {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
            
            .info-value {
                text-align: left;
            }
            
            .table-responsive {
                font-size: 12px;
            }
        }

        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none;
            }
            
            .desktop-nav {
                display: flex;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <!-- Include Navigation -->
        <?php include 'includes/navigation.php'; ?>
                <!-- Back Navigation -->
                <div class="back-navigation">
                    <a href="members.php" class="back-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Back to Members
                    </a>
                </div>

                <!-- Member Header -->
                <div class="member-header">
                    <div class="member-header-content">
                        <div class="member-avatar-large">
                            <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                        </div>
                        <div class="member-header-info">
                            <h1 class="member-full-name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></h1>
                            <div class="member-id-badge"><?php echo htmlspecialchars($member['member_id']); ?></div>
                            <div class="member-status-badges">
                                <?php if ($member['is_active']): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                                
                                <?php if ($member['has_received_payout']): ?>
                                    <span class="status-badge payout-received">Payout Received</span>
                                <?php else: ?>
                                    <span class="status-badge payout-pending">Pending Payout</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="member-actions">
                            <button class="action-btn primary" onclick="editMember(<?php echo $member['id']; ?>)">
                                <i class="fas fa-edit"></i>
                                Edit
                            </button>
                            <button class="action-btn secondary" onclick="toggleMemberStatus(<?php echo $member['id']; ?>, <?php echo $member['is_active'] ? 0 : 1; ?>)">
                                <?php if ($member['is_active']): ?>
                                    <i class="fas fa-toggle-off"></i>
                                    Deactivate
                                <?php else: ?>
                                    <i class="fas fa-toggle-on"></i>
                                    Activate
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-lg-6">
                        <div class="info-card">
                            <div class="card-header">
                                <div class="card-icon" style="background: var(--color-teal);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <h3 class="card-title">Personal Information</h3>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Full Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['phone']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Join Date</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($member['join_date'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Last Login</span>
                                <span class="info-value">
                                    <?php echo $member['last_login'] ? date('M d, Y g:i A', strtotime($member['last_login'])) : 'Never logged in'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="col-lg-6">
                        <div class="info-card">
                            <div class="card-header">
                                <div class="card-icon" style="background: var(--color-gold);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                </div>
                                <h3 class="card-title">Financial Information</h3>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Monthly Payment</span>
                                <span class="info-value success">£<?php echo number_format($member['monthly_payment'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Contributed</span>
                                <span class="info-value success">£<?php echo number_format($member['total_paid'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payout Position</span>
                                <span class="info-value">#<?php echo $member['payout_position']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payout Month</span>
                                <span class="info-value"><?php echo date('M Y', strtotime($member['payout_month'] . '-01')); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Next Payment Due</span>
                                <span class="info-value warning"><?php echo $next_payment_date ? $next_payment_date->format('M d, Y') : 'Not calculated'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Guarantor Information -->
                    <div class="col-lg-6">
                        <div class="info-card">
                            <div class="card-header">
                                <div class="card-icon" style="background: var(--color-light-gold);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <path d="M20 8v6M23 11h-6"/>
                                    </svg>
                                </div>
                                <h3 class="card-title">Guarantor Information</h3>
                            </div>
                            <?php if ($member['guarantor_first_name']): ?>
                                <div class="info-row">
                                    <span class="info-label">Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($member['guarantor_first_name'] . ' ' . $member['guarantor_last_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Phone</span>
                                    <span class="info-value"><?php echo htmlspecialchars($member['guarantor_phone']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?php echo htmlspecialchars($member['guarantor_email']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Relationship</span>
                                    <span class="info-value"><?php echo htmlspecialchars($member['guarantor_relationship']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="info-row">
                                    <span class="info-label">Status</span>
                                    <span class="info-value">No guarantor information provided</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div class="col-lg-6">
                        <div class="info-card">
                            <div class="card-header">
                                <div class="card-icon" style="background: var(--color-coral);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"/>
                                        <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                                    </svg>
                                </div>
                                <h3 class="card-title">Account Status</h3>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account Status</span>
                                <span class="info-value <?php echo $member['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Approval Status</span>
                                <span class="info-value <?php echo $member['is_approved'] ? 'success' : 'warning'; ?>">
                                    <?php echo $member['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email Verified</span>
                                <span class="info-value <?php echo $member['email_verified'] ? 'success' : 'warning'; ?>">
                                    <?php echo $member['email_verified'] ? 'Verified' : 'Not Verified'; ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Payments</span>
                                <span class="info-value"><?php echo $member['total_payments']; ?> payments</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Notifications</span>
                                <span class="info-value"><?php echo htmlspecialchars($member['notification_preferences']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="info-card">
                            <div class="card-header">
                                <div class="card-icon" style="background: var(--color-teal);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                        <line x1="1" y1="10" x2="23" y2="10"/>
                                    </svg>
                                </div>
                                <h3 class="card-title">Recent Payment History</h3>
                            </div>
                            
                            <?php if (!empty($payments)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Amount</th>
                                                <th>Payment Date</th>
                                                <th>Month</th>
                                                <th>Status</th>
                                                <th>Verified By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                                    <td>£<?php echo number_format($payment['amount'], 2); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                    <td><?php echo date('M Y', strtotime($payment['payment_month'] . '-01')); ?></td>
                                                    <td>
                                                        <span class="payment-status status-<?php echo $payment['status']; ?>">
                                                            <?php echo ucfirst($payment['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $payment['verified_by_name'] ?: 'System'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No payment history available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payout History -->
                <?php if (!empty($payouts)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="info-card">
                                <div class="card-header">
                                    <div class="card-icon" style="background: var(--color-gold);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M16 12l-4-4-4 4"/>
                                            <path d="M12 16V8"/>
                                        </svg>
                                    </div>
                                    <h3 class="card-title">Payout History</h3>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Payout ID</th>
                                                <th>Total Amount</th>
                                                <th>Net Amount</th>
                                                <th>Scheduled Date</th>
                                                <th>Status</th>
                                                <th>Processed By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payouts as $payout): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($payout['payout_id']); ?></td>
                                                    <td>£<?php echo number_format($payout['total_amount'], 2); ?></td>
                                                    <td>£<?php echo number_format($payout['net_amount'], 2); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($payout['scheduled_date'])); ?></td>
                                                    <td>
                                                        <span class="payment-status status-<?php echo $payout['status']; ?>">
                                                            <?php echo ucfirst($payout['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $payout['processed_by_name'] ?: 'System'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if ($member['notes']): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="info-card">
                                <div class="card-header">
                                    <div class="card-icon" style="background: var(--color-light-gold);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                    </div>
                                    <h3 class="card-title">Notes</h3>
                                </div>
                                <p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($member['notes'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth.js"></script>
    
    <script>
        // Navigation functions are handled by includes/navigation.php

        // Member management functions
        function editMember(id) {
            // Show the edit modal
            new bootstrap.Modal(document.getElementById('memberModal')).show();
        }

        // Handle member form submission
        document.getElementById('memberForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_member');
            
            fetch('api/members.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
                    
                    // Refresh the page to show updated data
                    showToast('Member updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the member');
            });
        });

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
                        // Reload page to update status
                        setTimeout(() => location.reload(), 1000);
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
    </script>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberModalLabel">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="memberForm">
                    <div class="modal-body">
                        <input type="hidden" id="memberId" name="member_id" value="<?php echo $member['id']; ?>">
                        
                        <!-- Personal Information -->
                        <h6 class="text-primary mb-3">Personal Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" value="<?php echo htmlspecialchars($member['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Equib Information -->
                        <h6 class="text-primary mb-3 mt-4">Equib Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="monthlyPayment" class="form-label">Monthly Payment (£) *</label>
                                    <input type="number" class="form-control" id="monthlyPayment" name="monthly_payment" value="<?php echo $member['monthly_payment']; ?>" min="1" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payoutPosition" class="form-label">Payout Position *</label>
                                    <input type="number" class="form-control" id="payoutPosition" name="payout_position" value="<?php echo $member['payout_position']; ?>" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Guarantor Information -->
                        <h6 class="text-primary mb-3 mt-4">Guarantor Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="guarantorFirstName" class="form-label">Guarantor First Name</label>
                                    <input type="text" class="form-control" id="guarantorFirstName" name="guarantor_first_name" value="<?php echo htmlspecialchars($member['guarantor_first_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="guarantorLastName" class="form-label">Guarantor Last Name</label>
                                    <input type="text" class="form-control" id="guarantorLastName" name="guarantor_last_name" value="<?php echo htmlspecialchars($member['guarantor_last_name'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="guarantorPhone" class="form-label">Guarantor Phone</label>
                                    <input type="tel" class="form-control" id="guarantorPhone" name="guarantor_phone" value="<?php echo htmlspecialchars($member['guarantor_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="guarantorEmail" class="form-label">Guarantor Email</label>
                                    <input type="email" class="form-control" id="guarantorEmail" name="guarantor_email" value="<?php echo htmlspecialchars($member['guarantor_email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="guarantorRelationship" class="form-label">Relationship</label>
                                    <select class="form-select" id="guarantorRelationship" name="guarantor_relationship">
                                        <option value="">Select...</option>
                                        <option value="Father" <?php echo ($member['guarantor_relationship'] ?? '') === 'Father' ? 'selected' : ''; ?>>Father</option>
                                        <option value="Mother" <?php echo ($member['guarantor_relationship'] ?? '') === 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                        <option value="Brother" <?php echo ($member['guarantor_relationship'] ?? '') === 'Brother' ? 'selected' : ''; ?>>Brother</option>
                                        <option value="Sister" <?php echo ($member['guarantor_relationship'] ?? '') === 'Sister' ? 'selected' : ''; ?>>Sister</option>
                                        <option value="Husband" <?php echo ($member['guarantor_relationship'] ?? '') === 'Husband' ? 'selected' : ''; ?>>Husband</option>
                                        <option value="Wife" <?php echo ($member['guarantor_relationship'] ?? '') === 'Wife' ? 'selected' : ''; ?>>Wife</option>
                                        <option value="Friend" <?php echo ($member['guarantor_relationship'] ?? '') === 'Friend' ? 'selected' : ''; ?>>Friend</option>
                                        <option value="Other" <?php echo ($member['guarantor_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($member['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html> 