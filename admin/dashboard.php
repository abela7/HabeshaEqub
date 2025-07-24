<?php
/**
 * HabeshaEqub - Admin Dashboard
 * Main administrative dashboard with system overview and quick access to management modules
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

// Get members data for dashboard statistics
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
    error_log("Dashboard error fetching members: " . $e->getMessage());
    $members = []; // Initialize as empty array to prevent errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('navigation.dashboard'); ?> - HabeshaEqub Admin</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Pictures/Icon/favicon-16x16.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* === TOP-TIER DASHBOARD DESIGN === */
        
        /* Welcome Header */
        .welcome-header {
            background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(48, 25, 67, 0.08);
        }

        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .welcome-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 400;
        }

        .welcome-stats {
            display: flex;
            gap: 40px;
        }

        .quick-metric {
            text-align: center;
        }

        .metric-value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            color: var(--color-teal);
            line-height: 1;
        }

        .metric-label {
            display: block;
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 4px;
            font-weight: 500;
        }

        /* Statistics Dashboard */
        .stats-dashboard {
            margin-bottom: 50px;
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
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .members-card .stat-icon { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .payments-card .stat-icon { background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%); }
        .payouts-card .stat-icon { background: linear-gradient(135deg, var(--color-light-gold) 0%, #B8941C 100%); }
        .activity-card .stat-icon { background: linear-gradient(135deg, var(--color-coral) 0%, #D63447 100%); }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .stat-trend.positive {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .stat-trend.neutral {
            background: rgba(107, 114, 128, 0.1);
            color: #6B7280;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 8px 0;
            line-height: 1;
        }

        .stat-label {
            font-size: 16px;
            color: var(--text-secondary);
            margin: 0 0 16px 0;
            font-weight: 500;
        }

        .stat-details {
            display: flex;
            gap: 16px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .detail-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .detail-dot.active { background: #059669; }
        .detail-dot.pending { background: #D97706; }
        .detail-dot.success { background: #059669; }
        .detail-dot.warning { background: #DC2626; }

        /* Management Section */
        .management-section {
            margin-bottom: 40px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 36px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
        }

        .section-title p {
            font-size: 18px;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Module Cards */
        .module-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            border: 1px solid var(--border-light);
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .module-card:hover {
            text-decoration: none;
            color: inherit;
        }

        .active-module:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(48, 25, 67, 0.15);
            border-color: var(--color-teal);
        }

        .coming-soon-module {
            opacity: 0.7;
            cursor: default;
        }

        .coming-soon-module:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(48, 25, 67, 0.08);
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .module-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .module-icon.primary { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .module-icon.secondary { background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%); }
        .module-icon.accent { background: linear-gradient(135deg, var(--color-light-gold) 0%, #B8941C 100%); }
        .module-icon.warning { background: linear-gradient(135deg, var(--color-coral) 0%, #D63447 100%); }
        .module-icon.info { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .module-icon.neutral { background: linear-gradient(135deg, var(--color-light-gold) 0%, #B8941C 100%); }

        .module-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .module-status.ready {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .module-status.coming-soon {
            background: rgba(107, 114, 128, 0.1);
            color: #6B7280;
        }

        .module-content h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 12px 0;
        }

        .module-content p {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0 0 20px 0;
        }

        .module-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }

        .module-stat {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .module-stat strong {
            color: var(--color-teal);
            font-weight: 600;
        }

        .module-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--border-light);
        }

        .module-action {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-teal);
        }

        .coming-soon-module .module-action {
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .welcome-header {
                flex-direction: column;
                text-align: center;
                gap: 30px;
            }

            .welcome-stats {
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .welcome-header {
                padding: 30px 20px;
            }

            .welcome-title {
                font-size: 28px;
            }

            .section-title h2 {
                font-size: 28px;
            }

            .stat-number {
                font-size: 28px;
            }

            .module-card {
                padding: 24px;
            }

            .module-content h3 {
                font-size: 20px;
            }

            .welcome-stats {
                flex-direction: column;
                gap: 20px;
            }

            .stat-details {
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .welcome-header {
                padding: 20px;
                margin-bottom: 30px;
            }

            .module-card {
                padding: 20px;
            }

            .metric-value {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <!-- Include Navigation -->
        <?php include 'includes/navigation.php'; ?>

        <!-- Dashboard Content -->
        <!-- Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-content">
                <h1 class="welcome-title"><?php echo t('dashboard.welcome_back', ['username' => htmlspecialchars($admin_username)]); ?></h1>
                <p class="welcome-subtitle"><?php echo t('dashboard.welcome_subtitle'); ?></p>
            </div>
            <div class="welcome-stats">
                <div class="quick-metric">
                    <span class="metric-value"><?php echo count($members); ?></span>
                    <span class="metric-label"><?php echo t('dashboard.active_members'); ?></span>
                </div>
                <div class="quick-metric">
                    <span class="metric-value">£<?php echo number_format(array_sum(array_column($members, 'monthly_payment')) * count($members), 0); ?></span>
                    <span class="metric-label"><?php echo t('dashboard.monthly_pool'); ?></span>
                </div>
            </div>
        </div>

        <!-- Key Statistics Dashboard -->
        <div class="stats-dashboard">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card members-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div class="stat-trend positive">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                    <polyline points="17 6 23 6 23 12"/>
                                </svg>
                                <span>+12%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo count($members); ?></h3>
                            <p class="stat-label"><?php echo t('dashboard.total_members'); ?></p>
                            <div class="stat-details">
                                <span class="detail-item">
                                    <span class="detail-dot active"></span>
                                    <?php echo count(array_filter($members, fn($m) => $m['is_active'])); ?> <?php echo t('dashboard.active'); ?>
                                </span>
                                <span class="detail-item">
                                    <span class="detail-dot pending"></span>
                                    <?php echo count($members) - count(array_filter($members, fn($m) => $m['is_active'])); ?> <?php echo t('dashboard.inactive'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card payments-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="stat-trend positive">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                    <polyline points="17 6 23 6 23 12"/>
                                </svg>
                                <span>+8%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number">£<?php echo number_format(array_sum(array_column($members, 'monthly_payment')) * count($members), 0); ?></h3>
                            <p class="stat-label"><?php echo t('dashboard.monthly_collection'); ?></p>
                            <div class="stat-details">
                                <span class="detail-item">
                                    <span class="detail-dot success"></span>
                                    £<?php echo number_format(array_sum(array_column($members, 'total_contributed')), 0); ?> <?php echo t('dashboard.collected'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card payouts-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M16 12l-4-4-4 4"/>
                                    <path d="M12 16V8"/>
                                </svg>
                            </div>
                            <div class="stat-trend neutral">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                <span>0%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number"><?php echo count(array_filter($members, fn($m) => $m['has_received_payout'])); ?></h3>
                            <p class="stat-label"><?php echo t('dashboard.completed_payouts'); ?></p>
                            <div class="stat-details">
                                <span class="detail-item">
                                    <span class="detail-dot warning"></span>
                                    <?php echo count(array_filter($members, fn($m) => !$m['has_received_payout'])); ?> <?php echo t('dashboard.pending'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card activity-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                                </svg>
                            </div>
                            <div class="stat-trend positive">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                    <polyline points="17 6 23 6 23 12"/>
                                </svg>
                                <span>+24%</span>
                            </div>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-number">98%</h3>
                            <p class="stat-label"><?php echo t('dashboard.collection_rate'); ?></p>
                            <div class="stat-details">
                                <span class="detail-item">
                                    <span class="detail-dot success"></span>
                                    <?php echo t('dashboard.this_month'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Modules -->
        <div class="management-section">
            <div class="section-title">
                <h2><?php echo t('dashboard.management_center'); ?></h2>
                <p><?php echo t('dashboard.management_center_desc'); ?></p>
            </div>

            <div class="modules-container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="members.php" class="module-card active-module">
                            <div class="module-header">
                                <div class="module-icon primary">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?php echo t('dashboard.members_management'); ?></h3>
                                <p><?php echo t('dashboard.members_management_desc'); ?></p>
                                <div class="module-stats">
                                    <span class="module-stat">
                                        <strong><?php echo count($members); ?></strong> <?php echo t('dashboard.members'); ?>
                                    </span>
                                    <span class="module-stat">
                                        <strong><?php echo count(array_filter($members, fn($m) => $m['is_active'])); ?></strong> <?php echo t('dashboard.active'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="module-footer">
                                <span class="module-action"><?php echo t('dashboard.manage_members'); ?></span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9,18 15,12 9,6"/>
                                </svg>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="payments.php" class="module-card active-module">
                            <div class="module-header">
                                <div class="module-icon secondary">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                        <line x1="1" y1="10" x2="23" y2="10"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?php echo t('dashboard.payment_tracking'); ?></h3>
                                <p><?php echo t('dashboard.payment_tracking_desc'); ?></p>
                                <div class="module-stats">
                                    <span class="module-stat">
                                        <strong>£<?php echo number_format(array_sum(array_column($members, 'monthly_payment')) * count($members), 0); ?></strong> <?php echo t('dashboard.monthly'); ?>
                                    </span>
                                    <span class="module-stat">
                                        <strong>98%</strong> <?php echo t('dashboard.rate'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="module-footer">
                                <span class="module-action"><?php echo t('dashboard.track_payments'); ?></span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9,18 15,12 9,6"/>
                                </svg>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="payouts.php" class="module-card active-module">
                            <div class="module-header">
                                <div class="module-icon accent">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M16 12l-4-4-4 4"/>
                                        <path d="M12 16V8"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?php echo t('dashboard.payout_management'); ?></h3>
                                <p><?php echo t('dashboard.payout_management_desc'); ?></p>
                                <div class="module-stats">
                                    <span class="module-stat">
                                        <strong><?php echo count(array_filter($members, fn($m) => !$m['has_received_payout'])); ?></strong> <?php echo t('dashboard.pending'); ?>
                                    </span>
                                    <span class="module-stat">
                                        <strong>1</strong> <?php echo t('dashboard.completed'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="module-footer">
                                <span class="module-action"><?php echo t('dashboard.manage_payouts'); ?></span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9,18 15,12 9,6"/>
                                </svg>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="module-card coming-soon-module" onclick="showComingSoon('Notifications')">
                            <div class="module-header">
                                <div class="module-icon warning">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?php echo t('dashboard.notifications'); ?></h3>
                                <p><?php echo t('dashboard.notifications_desc'); ?></p>
                                <div class="module-stats">
                                    <span class="module-stat">
                                        <strong>0</strong> <?php echo t('dashboard.sent'); ?>
                                    </span>
                                    <span class="module-stat">
                                        <strong><?php echo t('dashboard.email_sms'); ?></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="module-footer">
                                <span class="module-action"><?php echo t('dashboard.coming_soon'); ?></span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="1"/>
                                    <circle cx="12" cy="5" r="1"/>
                                    <circle cx="12" cy="19" r="1"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="reports.php" class="module-card active-module">
                            <div class="module-header">
                                <div class="module-icon info">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <polyline points="10,9 9,9 8,9"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?php echo t('dashboard.reports_analytics'); ?></h3>
                                <p><?php echo t('dashboard.reports_analytics_desc'); ?></p>
                                <div class="module-stats">
                                    <span class="module-stat">
                                        <strong><?php echo t('dashboard.financial'); ?></strong>
                                    </span>
                                    <span class="module-stat">
                                        <strong><?php echo t('dashboard.member'); ?></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="module-footer">
                                <span class="module-action"><?php echo t('dashboard.view_reports'); ?></span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9,18 15,12 9,6"/>
                                </svg>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="module-card coming-soon-module" onclick="showComingSoon('Settings')">
                            <div class="module-header">
                                <div class="module-icon neutral">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-content">
                                <h3><?php echo t('dashboard.system_settings'); ?></h3>
                                <p><?php echo t('dashboard.system_settings_desc'); ?></p>
                                <div class="module-stats">
                                    <span class="module-stat">
                                        <strong><?php echo t('dashboard.global'); ?></strong>
                                    </span>
                                    <span class="module-stat">
                                        <strong><?php echo t('dashboard.security'); ?></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="module-footer">
                                <span class="module-action"><?php echo t('dashboard.coming_soon'); ?></span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="1"/>
                                    <circle cx="12" cy="5" r="1"/>
                                    <circle cx="12" cy="19" r="1"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End app-content -->
</main> <!-- End app-main -->
</div> <!-- End app-layout -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth.js"></script>
    
    <script>
        function showComingSoon(feature) {
            alert(`${feature} module coming soon!\n\nWe're building this feature next as part of the HabeshaEqub development process.`);
        }
    </script>
</body>
</html> 