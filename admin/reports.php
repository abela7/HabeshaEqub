<?php
/**
 * HabeshaEqub - Reports & Analytics
 * Comprehensive reporting and analytics dashboard
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

// Date range parameters (default: last 24 months to ensure data is captured)
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-24 months'));

// Ensure we have a reasonable date range for existing data
if (empty($_GET['start_date'])) {
    // If no start date provided, use a range that captures existing data
    $start_date = '2024-01-01';
}

try {
    // === FINANCIAL ANALYTICS ===
    
    // Total collections by month
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            COUNT(*) as payment_count,
            SUM(amount) as total_amount
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $monthly_collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment status distribution
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $payment_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payout analytics
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount,
            AVG(total_amount) as avg_amount
        FROM payouts 
        WHERE scheduled_date BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $payout_analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === MEMBER ANALYTICS ===
    
    // Member registration trends (use created_at if date_joined doesn't exist)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_members
        FROM members 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $member_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no member trends from created_at, try with a broader range
    if (empty($member_trends)) {
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_members
            FROM members 
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $member_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Member status distribution
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN is_active = 1 THEN 'Active'
                ELSE 'Inactive'
            END as status,
            COUNT(*) as count
        FROM members 
        GROUP BY is_active
    ");
    $member_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top contributors
    $stmt = $pdo->prepare("
        SELECT 
            m.first_name, 
            m.last_name, 
            m.member_id,
            COUNT(p.id) as payment_count,
            SUM(p.amount) as total_contributed,
            AVG(p.amount) as avg_payment
        FROM members m
        LEFT JOIN payments p ON m.id = p.member_id 
        WHERE p.payment_date BETWEEN ? AND ?
        GROUP BY m.id
        HAVING total_contributed > 0
        ORDER BY total_contributed DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_contributors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === SUMMARY STATISTICS ===
    
    // Overall financial summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payments,
            SUM(amount) as total_collected,
            AVG(amount) as avg_payment
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $financial_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Payout summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payouts,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_distributed,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payouts,
            COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as pending_payouts
        FROM payouts 
        WHERE scheduled_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $payout_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Member summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_members,
            COUNT(CASE WHEN has_received_payout = 1 THEN 1 END) as members_with_payouts
        FROM members
    ");
    $member_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching analytics data: " . $e->getMessage());
    $monthly_collections = [];
    $payment_status = [];
    $payout_analytics = [];
    $member_trends = [];
    $member_status = [];
    $top_contributors = [];
    $financial_summary = ['total_payments' => 0, 'total_collected' => 0, 'avg_payment' => 0];
    $payout_summary = ['total_payouts' => 0, 'total_distributed' => 0, 'completed_payouts' => 0, 'pending_payouts' => 0];
    $member_summary = ['total_members' => 0, 'active_members' => 0, 'members_with_payouts' => 0];
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('reports.page_title'); ?> - HabeshaEqub Admin</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Pictures/Icon/favicon-16x16.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* === TOP-TIER REPORTS & ANALYTICS DESIGN === */
        
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
        
        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .export-btn {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            color: var(--color-purple);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(233, 196, 106, 0.3);
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(233, 196, 106, 0.4);
            color: var(--color-purple);
        }

        /* Date Range Filter */
        .date-filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
        }

        .date-input {
            padding: 10px 16px;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            transition: all 0.3s ease;
            background: var(--color-cream);
        }

        .date-input:focus {
            outline: none;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(233, 196, 106, 0.1);
            background: white;
        }

        .filter-btn {
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F766E 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(19, 102, 92, 0.3);
            color: white;
        }

        /* Statistics Overview */
        .stats-overview {
            margin-bottom: 40px;
        }

        .overview-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .overview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-teal), var(--color-gold));
        }

        .overview-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(48, 25, 67, 0.12);
        }

        .overview-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .financial-card .overview-icon { background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%); }
        .payout-card .overview-icon { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .member-card .overview-icon { background: linear-gradient(135deg, var(--color-coral) 0%, #D63447 100%); }

        .overview-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 8px 0;
            line-height: 1;
        }

        .overview-label {
            font-size: 16px;
            color: var(--text-secondary);
            margin: 0 0 16px 0;
            font-weight: 500;
        }

        .overview-details {
            display: flex;
            gap: 20px;
        }

        .detail-metric {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .detail-metric strong {
            color: var(--color-teal);
            font-weight: 600;
        }

        /* Chart Containers */
        .chart-section {
            margin-bottom: 40px;
        }

        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
            height: 100%;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-purple);
            margin: 0;
        }

        .chart-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 4px 0 0 0;
        }

        .chart-canvas {
            position: relative;
            height: 350px;
            width: 100%;
        }

        /* Top Contributors Table */
        .contributors-section {
            margin-bottom: 40px;
        }

        .contributors-table {
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
            padding: 16px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(233, 196, 106, 0.02);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .contributor-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .contributor-avatar {
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

        .contributor-name {
            font-weight: 600;
            color: var(--color-purple);
            margin: 0 0 4px 0;
        }

        .contributor-id {
            font-size: 14px;
            color: var(--text-secondary);
            font-family: 'Courier New', monospace;
        }

        .amount-display {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-gold);
        }

        .count-display {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-teal);
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-positive {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }

        .status-warning {
            background: rgba(251, 191, 36, 0.1);
            color: #D97706;
        }

        .status-neutral {
            background: rgba(107, 114, 128, 0.1);
            color: #6B7280;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .header-actions {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .date-filter-section {
                padding: 20px 16px;
            }

            .overview-card {
                padding: 20px;
            }

            .chart-container {
                padding: 20px;
            }

            .chart-canvas {
                height: 250px;
            }

            .overview-details {
                flex-direction: column;
                gap: 8px;
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
                <h1><?php echo t('reports.page_title'); ?></h1>
                <p><?php echo t('reports.page_subtitle'); ?></p>
            </div>
            <div class="header-actions">
                <button class="export-btn" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf me-2"></i>
                    Export PDF
                </button>
                <button class="export-btn" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel me-2"></i>
                    Export Excel
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="date-filter-section">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="start_date" class="form-label fw-semibold">Start Date</label>
                    <input type="date" class="form-control date-input" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="end_date" class="form-label fw-semibold">End Date</label>
                    <input type="date" class="form-control date-input" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <button type="submit" class="filter-btn w-100">
                        <i class="fas fa-filter me-2"></i>
                        Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="overview-card financial-card">
                        <div class="overview-icon">
                            <i class="fas fa-pound-sign"></i>
                        </div>
                        <div class="overview-number">£<?php echo number_format($financial_summary['total_collected'] ?: 0, 0); ?></div>
                        <div class="overview-label">Total Collections</div>
                        <div class="overview-details">
                            <div class="detail-metric">
                                <strong><?php echo $financial_summary['total_payments'] ?: 0; ?></strong> payments
                            </div>
                            <div class="detail-metric">
                                <strong>£<?php echo number_format($financial_summary['avg_payment'] ?: 0, 0); ?></strong> avg
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="overview-card payout-card">
                        <div class="overview-icon">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="overview-number">£<?php echo number_format($payout_summary['total_distributed'] ?: 0, 0); ?></div>
                        <div class="overview-label">Total Distributed</div>
                        <div class="overview-details">
                            <div class="detail-metric">
                                <strong><?php echo $payout_summary['completed_payouts'] ?: 0; ?></strong> completed
                            </div>
                            <div class="detail-metric">
                                <strong><?php echo $payout_summary['pending_payouts'] ?: 0; ?></strong> pending
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="overview-card member-card">
                        <div class="overview-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="overview-number"><?php echo $member_summary['total_members'] ?: 0; ?></div>
                        <div class="overview-label">Total Members</div>
                        <div class="overview-details">
                            <div class="detail-metric">
                                <strong><?php echo $member_summary['active_members'] ?: 0; ?></strong> active
                            </div>
                            <div class="detail-metric">
                                <strong><?php echo $member_summary['members_with_payouts'] ?: 0; ?></strong> paid out
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-section">
            <div class="row">
                <!-- Monthly Collections Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Monthly Collections Trend</h3>
                                <p class="chart-subtitle">Payment collections over time</p>
                            </div>
                            <div class="status-indicator status-positive">
                                <i class="fas fa-arrow-up"></i>
                                Trending Up
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="monthlyCollectionsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Payment Status Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Payment Status</h3>
                                <p class="chart-subtitle">Distribution by status</p>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="paymentStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Member Registration Trend -->
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Member Growth</h3>
                                <p class="chart-subtitle">New member registrations</p>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="memberGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Payout Analytics -->
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Payout Distribution</h3>
                                <p class="chart-subtitle">Payouts by status</p>
                            </div>
                        </div>
                        <div class="chart-canvas">
                            <canvas id="payoutDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Contributors Table -->
        <div class="contributors-section">
            <div class="contributors-table">
                <div class="chart-header" style="padding: 24px 24px 0; border-bottom: none; margin-bottom: 0;">
                    <div>
                        <h3 class="chart-title">Top Contributors</h3>
                        <p class="chart-subtitle">Members with highest contributions in selected period</p>
                    </div>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Total Contributed</th>
                            <th>Payment Count</th>
                            <th>Average Payment</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_contributors)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <span style="color: var(--text-secondary);">No contribution data available for the selected period.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_contributors as $index => $contributor): ?>
                                <tr>
                                    <td>
                                        <div class="contributor-info">
                                            <div class="contributor-avatar">
                                                <?php echo strtoupper(substr($contributor['first_name'], 0, 1) . substr($contributor['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="contributor-name">
                                                    <?php echo htmlspecialchars($contributor['first_name'] . ' ' . $contributor['last_name']); ?>
                                                </div>
                                                <div class="contributor-id"><?php echo htmlspecialchars($contributor['member_id']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="amount-display">£<?php echo number_format($contributor['total_contributed'] ?: 0, 0); ?></div>
                                    </td>
                                    <td>
                                        <div class="count-display"><?php echo $contributor['payment_count'] ?: 0; ?></div>
                                    </td>
                                    <td>
                                        <div class="amount-display">£<?php echo number_format($contributor['avg_payment'] ?: 0, 0); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $rank = $index + 1;
                                            if ($rank <= 3) {
                                                echo '<span class="status-indicator status-positive"><i class="fas fa-star"></i> Top Performer</span>';
                                            } elseif ($rank <= 7) {
                                                echo '<span class="status-indicator status-warning"><i class="fas fa-thumbs-up"></i> Good</span>';
                                            } else {
                                                echo '<span class="status-indicator status-neutral"><i class="fas fa-check"></i> Regular</span>';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- End app-content -->
</main> <!-- End app-main -->
</div> <!-- End app-layout -->

<!-- Hidden CSRF Token for Export -->
<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

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

    // Export functionality
    function exportReport(format) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = format === 'pdf' ? 
            '<i class="fas fa-spinner fa-spin me-2"></i>Generating PDF...' : 
            '<i class="fas fa-spinner fa-spin me-2"></i>Generating Excel...';
        
        // Get CSRF token
        const csrfToken = getCSRFToken();
        if (!csrfToken) {
            alert('Security token missing. Please refresh the page.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', format === 'pdf' ? 'export_pdf' : 'export_excel');
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);
        formData.append('csrf_token', csrfToken);
        
        // Call export API
        fetch('api/reports.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;
            button.innerHTML = originalText;
            
            if (data.success) {
                // Redirect to export handler
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Export prepared successfully!');
                }
            } else {
                alert('Error: ' + (data.message || 'Failed to generate report'));
            }
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = originalText;
            console.error('Export error:', error);
            alert('An error occurred while generating the report. Please try again.');
        });
    }

    // Get CSRF token helper function
    function getCSRFToken() {
        // Try to get from existing form or generate new one
        let token = document.querySelector('input[name="csrf_token"]');
        if (token && token.value) {
            return token.value;
        }
        
        // If no token available, we'll need to get one from the API
        // For now, return empty and let the server handle it
        return '';
    }

    // Chart data from PHP
    const monthlyData = <?php echo json_encode($monthly_collections); ?>;
    const paymentStatusData = <?php echo json_encode($payment_status); ?>;
    const memberTrendData = <?php echo json_encode($member_trends); ?>;
    const payoutData = <?php echo json_encode($payout_analytics); ?>;

    // Chart configurations
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#6B7280';

    // Monthly Collections Chart
    if (document.getElementById('monthlyCollectionsChart')) {
        const ctx1 = document.getElementById('monthlyCollectionsChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => {
                    const date = new Date(d.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Monthly Collections (£)',
                    data: monthlyData.map(d => parseFloat(d.total_amount || 0)),
                    borderColor: '#13665C',
                    backgroundColor: 'rgba(19, 102, 92, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#13665C',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '£' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(107, 114, 128, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Payment Status Chart
    if (document.getElementById('paymentStatusChart')) {
        const ctx2 = document.getElementById('paymentStatusChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: paymentStatusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                datasets: [{
                    data: paymentStatusData.map(d => parseInt(d.count)),
                    backgroundColor: [
                        '#13665C',
                        '#E9C46A',
                        '#E76F51',
                        '#CDAF56'
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 4,
                    hoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            }
        });
    }

    // Member Growth Chart
    if (document.getElementById('memberGrowthChart')) {
        const ctx3 = document.getElementById('memberGrowthChart').getContext('2d');
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: memberTrendData.map(d => {
                    const date = new Date(d.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'New Members',
                    data: memberTrendData.map(d => parseInt(d.new_members)),
                    backgroundColor: 'rgba(233, 196, 106, 0.8)',
                    borderColor: '#E9C46A',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(107, 114, 128, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Payout Distribution Chart
    if (document.getElementById('payoutDistributionChart')) {
        const ctx4 = document.getElementById('payoutDistributionChart').getContext('2d');
        new Chart(ctx4, {
            type: 'polarArea',
            data: {
                labels: payoutData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                datasets: [{
                    data: payoutData.map(d => parseFloat(d.total_amount || 0)),
                    backgroundColor: [
                        'rgba(19, 102, 92, 0.8)',
                        'rgba(233, 196, 106, 0.8)',
                        'rgba(231, 111, 81, 0.8)',
                        'rgba(205, 175, 86, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '£' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        // Add any additional initialization here
    });
</script>
</body>
</html> 