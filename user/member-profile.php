<?php
/**
 * HabeshaEqub - Member Profile Details (Standalone Page)
 * Professional member profile page with top-tier design
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

// Get member ID from request
$member_id = (int)($_GET['id'] ?? 0);

if (!$member_id) {
    header('Location: members.php');
    exit;
}

// Get detailed member information
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               COALESCE(SUM(CASE WHEN p.status IN ('paid', 'completed') THEN p.amount ELSE 0 END), 0) as total_contributed,
               COALESCE(COUNT(CASE WHEN p.status IN ('paid', 'completed') THEN 1 END), 0) as payments_made,
               COALESCE(
                   (SELECT po.total_amount FROM payouts po WHERE po.member_id = m.id AND po.status = 'completed' ORDER BY po.actual_payout_date DESC LIMIT 1), 
                   0
               ) as last_payout_amount,
               COALESCE(
                   (SELECT po.actual_payout_date FROM payouts po WHERE po.member_id = m.id AND po.status = 'completed' ORDER BY po.actual_payout_date DESC LIMIT 1), 
                   NULL
               ) as last_payout_date,
               (SELECT COUNT(*) FROM payouts po WHERE po.member_id = m.id AND po.status = 'completed') as total_payouts_received,
               -- Calculate expected payout amount (total members * monthly payment)
               (SELECT COUNT(*) FROM members WHERE is_active = 1) * m.monthly_payment as expected_payout,
               -- Calculate next payout date based on position
               DATE_ADD('2024-06-01', INTERVAL (m.payout_position - 1) MONTH) as expected_payout_date,
               -- Get payment history count
               (SELECT COUNT(*) FROM payments p WHERE p.member_id = m.id) as total_payment_records
        FROM members m 
        LEFT JOIN payments p ON m.id = p.member_id
        WHERE m.id = ? AND m.is_active = 1 AND m.go_public = 1 AND m.is_approved = 1
        GROUP BY m.id
    ");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        header('Location: members.php');
        exit;
    }

    // Get member's recent payment history (last 6 months)
    $stmt = $pdo->prepare("
        SELECT p.*, 
               DATE_FORMAT(p.payment_date, '%M %Y') as payment_month_name,
               DATE_FORMAT(p.payment_date, '%d %M %Y') as formatted_date
        FROM payments p 
        WHERE p.member_id = ? AND p.status IN ('paid', 'completed')
        ORDER BY p.payment_date DESC 
        LIMIT 6
    ");
    $stmt->execute([$member_id]);
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get member's payout history
    $stmt = $pdo->prepare("
        SELECT po.*, 
               DATE_FORMAT(po.actual_payout_date, '%d %M %Y') as formatted_date,
               DATE_FORMAT(po.actual_payout_date, '%M %Y') as payout_month_name
        FROM payouts po 
        WHERE po.member_id = ? AND po.status = 'completed'
        ORDER BY po.actual_payout_date DESC
    ");
    $stmt->execute([$member_id]);
    $payout_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header('Location: members.php');
    exit;
}

// Calculate member data
$member_name = trim($member['first_name'] . ' ' . $member['last_name']);
$initials = substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1);
$member_since = date('M Y', strtotime($member['created_at']));
$payout_status = $member['total_payouts_received'] > 0 ? 'received' : ($member['payout_position'] == 1 ? 'current' : 'upcoming');
$expected_payout_formatted = date('M Y', strtotime($member['expected_payout_date']));
$payment_progress = $member['total_payment_records'] > 0 ? ($member['payments_made'] / $member['total_payment_records']) * 100 : 0;

// Strong cache buster for assets
$cache_buster = time() . '_' . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($member_name, ENT_QUOTES); ?> - <?php echo t('members_directory.member_profile'); ?> - HabeshaEqub</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Pictures/Icon/favicon-16x16.png">
    
    <!-- CSS with cache busting -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo $cache_buster; ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo $cache_buster; ?>" rel="stylesheet" crossorigin="anonymous">
    <link href="../assets/css/style.css?v=<?php echo $cache_buster; ?>" rel="stylesheet">
    
    <!-- Ensure Font Awesome loads properly -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>

<style>
/* === TOP-TIER PROFESSIONAL MEMBER PROFILE === */

/* Custom Color Palette - Consistent with other pages */
:root {
    --palette-cream: #F1ECE2;
    --palette-dark-purple: #4D4052;
    --palette-deep-purple: #301934;
    --palette-gold: #DAA520;
    --palette-light-gold: #CDAF56;
    --palette-brown: #5D4225;
    --palette-white: #FFFFFF;
    --palette-success: #2A9D8F;
    --palette-light-bg: #FAFAFA;
    --palette-border: rgba(77, 64, 82, 0.1);
    --palette-red-orange: #E76F51;
    --palette-teal: #2A9D8F;
    --palette-dark-teal: #0F766E;
}

/* Enhanced Page Header - Top Tier Design */
.page-header {
    background: var(--palette-cream);
    border-radius: 24px;
    padding: 40px 35px;
    margin-bottom: 40px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 12px 40px rgba(48, 25, 52, 0.1);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--palette-gold) 0%, var(--palette-light-gold) 50%, var(--palette-success) 100%);
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 12px 0;
    letter-spacing: -0.8px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-subtitle {
    font-size: 18px;
    color: var(--palette-dark-purple);
    margin: 0;
    font-weight: 400;
    opacity: 0.85;
}

.back-button {
    background: linear-gradient(135deg, var(--palette-dark-purple) 0%, var(--palette-deep-purple) 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 15px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.back-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(48, 25, 52, 0.3);
    color: white;
}

/* Member Profile Header */
.member-profile-header {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-bottom: 35px;
    padding: 30px;
    background: var(--palette-white);
    border-radius: 24px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 8px 32px rgba(48, 25, 52, 0.08);
    position: relative;
    overflow: hidden;
}

.member-profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--palette-success) 0%, var(--palette-gold) 100%);
}

.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 24px;
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 10px 30px rgba(42, 157, 143, 0.4);
}

.profile-info h2 {
    font-size: 32px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 12px 0;
    line-height: 1.2;
}

.profile-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--palette-dark-purple);
    background: rgba(218, 165, 32, 0.1);
    padding: 8px 16px;
    border-radius: 25px;
    font-weight: 600;
    border: 1px solid rgba(218, 165, 32, 0.2);
}

.profile-section {
    margin-bottom: 35px;
}

.section-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding-left: 15px;
    position: relative;
}

.section-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 30px;
    background: linear-gradient(180deg, var(--palette-gold) 0%, var(--palette-success) 100%);
    border-radius: 2px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--palette-white);
    border: 1px solid var(--palette-border);
    border-radius: 20px;
    padding: 25px;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 6px 25px rgba(48, 25, 52, 0.08);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(42, 157, 143, 0.03) 0%, transparent 70%);
    border-radius: 50%;
    transition: all 0.5s ease;
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 40px rgba(48, 25, 52, 0.15);
    border-color: rgba(218, 165, 32, 0.3);
}

.stat-card:hover::before {
    transform: scale(1.2);
    opacity: 0.8;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    margin: 0 auto 20px;
    position: relative;
    z-index: 2;
}

.stat-icon.primary { 
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    box-shadow: 0 8px 25px rgba(42, 157, 143, 0.3);
}

.stat-icon.warning { 
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    box-shadow: 0 8px 25px rgba(218, 165, 32, 0.3);
}

.stat-icon.info { 
    background: linear-gradient(135deg, var(--palette-deep-purple) 0%, var(--palette-dark-purple) 100%);
    box-shadow: 0 8px 25px rgba(48, 25, 52, 0.3);
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 8px 0;
    position: relative;
    z-index: 2;
}

.stat-label {
    font-size: 14px;
    color: var(--palette-dark-purple);
    opacity: 0.8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    position: relative;
    z-index: 2;
}

.payment-history {
    background: var(--palette-white);
    border: 1px solid var(--palette-border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 6px 25px rgba(48, 25, 52, 0.08);
}

.payment-item {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(77, 64, 82, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.payment-item:hover {
    background: rgba(241, 236, 226, 0.3);
    transform: translateX(5px);
}

.payment-item:last-child {
    border-bottom: none;
}

.payment-info h5 {
    font-size: 18px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 5px 0;
}

.payment-date {
    font-size: 13px;
    color: var(--palette-dark-purple);
    opacity: 0.7;
    font-weight: 500;
}

.payment-amount {
    font-size: 22px;
    font-weight: 700;
    color: var(--palette-success);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.status-received {
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    color: white;
}

.status-current {
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    color: var(--palette-deep-purple);
}

.status-upcoming {
    background: linear-gradient(135deg, var(--palette-deep-purple) 0%, var(--palette-dark-purple) 100%);
    color: white;
}

.progress-bar-custom {
    background: var(--palette-light-bg);
    border-radius: 12px;
    height: 12px;
    overflow: hidden;
    margin: 15px 0;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    border-radius: 12px;
    transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.no-data {
    text-align: center;
    padding: 50px;
    color: var(--palette-dark-purple);
    opacity: 0.6;
}

.no-data i {
    color: var(--palette-gold);
    margin-bottom: 15px;
}

/* Mobile Responsive Design */
@media (max-width: 768px) {
    .container-fluid {
        padding: 0 15px;
    }
    
    .page-header {
        padding: 30px 25px;
        border-radius: 20px;
    }
    
    .page-title {
        font-size: 26px;
        text-align: center;
        justify-content: center;
        flex-direction: column;
        gap: 10px;
    }
    
    .page-subtitle {
        font-size: 16px;
        text-align: center;
    }
    
    .member-profile-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
        padding: 25px 20px;
        border-radius: 20px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }
    
    .profile-info h2 {
        font-size: 28px;
    }
    
    .profile-meta {
        justify-content: center;
        gap: 12px;
    }
    
    .meta-item {
        font-size: 13px;
        padding: 6px 12px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .stat-card {
        padding: 20px;
        border-radius: 18px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-value {
        font-size: 24px;
    }
    
    .payment-item {
        flex-direction: column;
        text-align: center;
        gap: 12px;
        padding: 18px 20px;
    }
    
    .section-title {
        font-size: 20px;
        justify-content: center;
        padding-left: 0;
    }
    
    .section-title::before {
        display: none;
    }
    
    .back-button {
        width: 100%;
        justify-content: center;
        padding: 15px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 12px;
    }
    
    .page-header {
        padding: 25px 18px;
        border-radius: 18px;
    }
    
    .member-profile-header {
        padding: 20px 16px;
        border-radius: 18px;
    }
    
    .stat-card {
        padding: 18px 15px;
        border-radius: 16px;
    }
}

/* Loading animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card, .payment-item, .member-profile-header {
    animation: fadeInUp 0.8s ease-out;
}

.stat-card:nth-child(2) { animation-delay: 0.1s; }
.stat-card:nth-child(3) { animation-delay: 0.2s; }
.stat-card:nth-child(4) { animation-delay: 0.3s; }

/* Performance optimizations */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.stat-card, .payment-item {
    will-change: transform;
}
</style>

</head>

<body>
    <!-- Include Member Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Page Content -->
    <div class="container-fluid">
        <!-- Back Button -->
        <a href="members.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Members
        </a>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-user-circle text-primary"></i>
                        <?php echo t('members_directory.member_profile'); ?>
                    </h1>
                    <p class="page-subtitle">Detailed member information and financial overview</p>
                </div>
            </div>
        </div>

        <!-- Member Profile Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="member-profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper($initials); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($member_name, ENT_QUOTES); ?></h2>
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-trophy"></i>
                                Position #<?php echo $member['payout_position']; ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                Member since <?php echo $member_since; ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-check-circle"></i>
                                <span class="status-badge status-<?php echo $payout_status; ?>">
                                    <?php 
                                    echo $payout_status === 'received' ? 'Payout Received' : 
                                         ($payout_status === 'current' ? 'Current Turn' : 'Upcoming'); 
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-line text-primary"></i>
                        <?php echo t('members_directory.financial_overview'); ?>
                    </h3>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-value">£<?php echo number_format($member['monthly_payment'], 0); ?></div>
                            <div class="stat-label">Monthly Payment</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <div class="stat-value">£<?php echo number_format($member['total_contributed'], 0); ?></div>
                            <div class="stat-label">Total Contributed</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="stat-value">£<?php echo number_format($member['expected_payout'], 0); ?></div>
                            <div class="stat-label">Expected Payout</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-value"><?php echo $expected_payout_formatted; ?></div>
                            <div class="stat-label">Payout Date</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-history text-success"></i>
                        <?php echo t('members_directory.recent_payment_history'); ?>
                    </h3>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold fs-5 text-dark"><strong><?php echo $member['payments_made']; ?></strong> payments made</span>
                            <span class="fw-bold fs-5 text-primary"><strong><?php echo number_format($payment_progress, 1); ?>%</strong> completion</span>
                        </div>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: <?php echo $payment_progress; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if (count($recent_payments) > 0): ?>
                        <div class="payment-history">
                            <?php foreach ($recent_payments as $payment): ?>
                                <div class="payment-item">
                                    <div class="payment-info">
                                        <h5><?php echo $payment['payment_month_name']; ?></h5>
                                        <div class="payment-date">
                                            Paid on <?php echo $payment['formatted_date']; ?>
                                        </div>
                                    </div>
                                    <div class="payment-amount">
                                        £<?php echo number_format($payment['amount'], 0); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No payment history available</h5>
                            <p>This member hasn't made any payments yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payout History -->
        <?php if (count($payout_history) > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="profile-section">
                        <h3 class="section-title">
                            <i class="fas fa-trophy text-warning"></i>
                            Payout History
                        </h3>
                        
                        <div class="payment-history">
                            <?php foreach ($payout_history as $payout): ?>
                                <div class="payment-item">
                                    <div class="payment-info">
                                        <h5><?php echo $payout['payout_month_name']; ?> Payout</h5>
                                        <div class="payment-date">
                                            Received on <?php echo $payout['formatted_date']; ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="payment-amount" style="color: var(--palette-gold);">
                                            £<?php echo number_format($payout['net_amount'], 0); ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--palette-dark-purple); opacity: 0.6;">
                                            (Total: £<?php echo number_format($payout['total_amount'], 0); ?>)
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Member Details -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-user text-info"></i>
                        Member Details
                    </h3>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo $member['payments_made']; ?></div>
                            <div class="stat-label">Successful Payments</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="stat-value"><?php echo $member['total_payouts_received']; ?></div>
                            <div class="stat-label">Payouts Received</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-list-ol"></i>
                            </div>
                            <div class="stat-value"><?php echo $member['payout_position']; ?> / <?php echo (int)($member['expected_payout'] / $member['monthly_payment']); ?></div>
                            <div class="stat-label">Queue Position</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-value">
                                <?php echo $member['is_approved'] ? 'Approved' : 'Pending'; ?>
                            </div>
                            <div class="stat-label">Account Status</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo $cache_buster; ?>"></script>
    
    <script>
    // Enhanced profile page functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Animate progress bar on load
        const progressFill = document.querySelector('.progress-fill');
        if (progressFill) {
            const targetWidth = progressFill.style.width;
            progressFill.style.width = '0%';
            setTimeout(() => {
                progressFill.style.width = targetWidth;
            }, 500);
        }
        
        // Add smooth hover effects
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    </script>
</body>
</html> 