<?php
/**
 * HabeshaEqub - Professional Members Directory
 * Top-tier responsive member discovery and networking page
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

// Debug: Force specific member for testing (will be session-based in production)
$current_user_id = $_SESSION['user_id'] ?? 1;

// Get all PUBLIC members (go_public = 1) with their financial data
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
               DATE_ADD('2024-06-01', INTERVAL (m.payout_position - 1) MONTH) as expected_payout_date
        FROM members m 
        LEFT JOIN payments p ON m.id = p.member_id
        WHERE m.is_active = 1 AND m.go_public = 1 AND m.is_approved = 1
        GROUP BY m.id
        ORDER BY m.payout_position ASC
    ");
    $stmt->execute();
    $public_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total member count for statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_count FROM members WHERE is_active = 1 AND is_approved = 1");
    $stmt->execute();
    $total_members = $stmt->fetch(PDO::FETCH_ASSOC)['total_count'];
    
    $public_count = count($public_members);
    $private_count = $total_members - $public_count;
    
} catch (PDOException $e) {
    die("❌ DATABASE ERROR: " . $e->getMessage());
}

// Strong cache buster for assets
$cache_buster = time() . '_' . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('members_directory.page_title'); ?> - HabeshaEqub</title>
    
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
/* === TOP-TIER PROFESSIONAL MEMBERS DIRECTORY === */

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
    padding: 50px 40px;
    margin-bottom: 45px;
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

.page-header::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(218, 165, 32, 0.05) 0%, transparent 70%);
    border-radius: 50%;
}

.page-title {
    font-size: 36px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 12px 0;
    letter-spacing: -0.8px;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    z-index: 2;
}

.page-subtitle {
    font-size: 20px;
    color: var(--palette-dark-purple);
    margin: 0;
    font-weight: 400;
    opacity: 0.85;
    position: relative;
    z-index: 2;
}

/* Statistics Bar */
.stats-bar {
    background: var(--palette-white);
    border-radius: 20px;
    padding: 25px 30px;
    margin-bottom: 35px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 8px 32px rgba(48, 25, 52, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.stat-icon.primary { 
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    box-shadow: 0 6px 20px rgba(42, 157, 143, 0.3);
}

.stat-icon.warning { 
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    box-shadow: 0 6px 20px rgba(218, 165, 32, 0.3);
}

.stat-icon.info { 
    background: linear-gradient(135deg, var(--palette-deep-purple) 0%, var(--palette-dark-purple) 100%);
    box-shadow: 0 6px 20px rgba(48, 25, 52, 0.3);
}

.stat-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0;
    line-height: 1;
}

.stat-content p {
    font-size: 14px;
    color: var(--palette-dark-purple);
    margin: 2px 0 0 0;
    opacity: 0.8;
}

/* View Toggle Controls */
.view-controls {
    background: var(--palette-white);
    border-radius: 20px;
    padding: 20px 25px;
    margin-bottom: 35px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 4px 20px rgba(48, 25, 52, 0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.view-toggle {
    display: flex;
    background: var(--palette-light-bg);
    border-radius: 15px;
    padding: 8px;
    border: 1px solid var(--palette-border);
    position: relative;
}

.view-btn {
    border: none;
    background: transparent;
    padding: 12px 20px;
    border-radius: 12px;
    color: var(--palette-dark-purple);
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    position: relative;
    z-index: 2;
}

.view-btn.active {
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(42, 157, 143, 0.3);
}

.view-btn:hover:not(.active) {
    background: var(--palette-cream);
    color: var(--palette-deep-purple);
}

/* Search and Filter */
.search-filter {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    border: 2px solid rgba(77, 64, 82, 0.15);
    border-radius: 15px;
    padding: 12px 20px;
    font-size: 15px;
    min-width: 250px;
    transition: all 0.3s ease;
    background: rgba(250, 250, 250, 0.5);
}

.search-input:focus {
    border-color: var(--palette-gold);
    box-shadow: 0 0 0 0.25rem rgba(218, 165, 32, 0.15);
    background: var(--palette-white);
    outline: none;
}

/* Member Cards - Grid View */
.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.member-card {
    background: var(--palette-white);
    border-radius: 24px;
    padding: 30px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 8px 32px rgba(48, 25, 52, 0.08);
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.member-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--palette-gold) 0%, var(--palette-success) 100%);
    transform: scaleX(0);
    transition: transform 0.5s ease;
}

.member-card::after {
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

.member-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(48, 25, 52, 0.15);
    border-color: rgba(218, 165, 32, 0.3);
}

.member-card:hover::before {
    transform: scaleX(1);
}

.member-card:hover::after {
    transform: scale(1.2);
    opacity: 0.8;
}

.member-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    position: relative;
    z-index: 2;
}

.member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 6px 20px rgba(42, 157, 143, 0.3);
}

.member-info h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 5px 0;
    line-height: 1.2;
}

.member-position {
    font-size: 14px;
    color: var(--palette-gold);
    font-weight: 600;
    background: rgba(218, 165, 32, 0.1);
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
}

.member-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.stat-block {
    text-align: center;
    padding: 15px;
    background: rgba(241, 236, 226, 0.3);
    border-radius: 16px;
    border: 1px solid rgba(77, 64, 82, 0.08);
    transition: all 0.3s ease;
}

.stat-block:hover {
    background: rgba(241, 236, 226, 0.5);
    transform: translateY(-2px);
}

.stat-value {
    font-size: 14px;
    color: var(--palette-dark-purple);
    margin: 0 0 5px 0;
    font-weight: 500;
}

.stat-label {
    font-size: 16px;
    color: var(--palette-deep-purple);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.member-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid var(--palette-border);
}

.payout-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-received { background: var(--palette-success); }
.status-pending { background: var(--palette-gold); }
.status-upcoming { background: var(--palette-dark-purple); }

.view-profile-btn {
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    color: var(--palette-deep-purple);
    border: none;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.view-profile-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4);
}

/* Member List - List View */
.members-list {
    display: none;
    background: var(--palette-white);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--palette-border);
    box-shadow: 0 8px 32px rgba(48, 25, 52, 0.08);
}

.members-list.active {
    display: block;
}

.list-header {
    background: linear-gradient(135deg, var(--palette-cream) 0%, #FAF8F5 100%);
    padding: 20px 25px;
    font-weight: 600;
    color: var(--palette-deep-purple);
    border-bottom: 1px solid var(--palette-border);
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 120px;
    gap: 20px;
    align-items: center;
}

.list-item {
    padding: 20px 25px;
    border-bottom: 1px solid rgba(77, 64, 82, 0.05);
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 120px;
    gap: 20px;
    align-items: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.list-item:hover {
    background: rgba(241, 236, 226, 0.3);
    transform: translateX(5px);
}

.list-item:last-child {
    border-bottom: none;
}

.list-member-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.list-avatar {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    font-weight: 700;
}

.list-member-details h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--palette-deep-purple);
    margin: 0 0 3px 0;
}

.list-member-details span {
    font-size: 12px;
    color: var(--palette-gold);
    font-weight: 600;
}

.list-stat {
    font-size: 15px;
    font-weight: 600;
    color: var(--palette-deep-purple);
}

.list-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.list-status.received {
    background: rgba(42, 157, 143, 0.1);
    color: var(--palette-success);
}

.list-status.pending {
    background: rgba(218, 165, 32, 0.1);
    color: var(--palette-gold);
}

.list-actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.action-btn.primary {
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    color: white;
}

.action-btn.primary:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(42, 157, 143, 0.4);
}

    /* Mobile Responsive Design - Creative & Perfect */
@media (max-width: 768px) {
    .container-fluid {
        padding: 0 15px;
    }
    
    .page-header {
        padding: 35px 25px;
        margin-bottom: 35px;
        border-radius: 20px;
    }
    
    .page-title {
        font-size: 28px;
        text-align: center;
        justify-content: center;
        flex-direction: column;
        gap: 10px;
    }
    
    .page-subtitle {
        font-size: 16px;
        text-align: center;
    }
    
    .stats-bar {
        flex-direction: column;
        padding: 20px;
        gap: 15px;
        text-align: center;
    }
    
    .view-controls {
        flex-direction: column;
        gap: 15px;
        padding: 20px;
    }
    
    .view-toggle {
        width: 100%;
        position: relative;
        background: var(--palette-white);
        border: 2px solid var(--palette-border);
        border-radius: 18px;
        padding: 6px;
        box-shadow: 0 4px 15px rgba(48, 25, 52, 0.08);
    }
    
    .view-btn {
        flex: 1;
        justify-content: center;
        padding: 16px 12px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 14px;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .view-btn.active {
        background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
        transform: scale(1.02);
        box-shadow: 0 6px 20px rgba(42, 157, 143, 0.4);
    }
    
    .search-filter {
        width: 100%;
        justify-content: center;
    }
    
    .search-input {
        min-width: 100%;
        max-width: 100%;
    }
    
    .members-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .member-card {
        padding: 25px 20px;
        border-radius: 20px;
    }
    
    .member-header {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .member-stats {
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    /* Enhanced Mobile List View */
    .members-list {
        border-radius: 18px;
        background: var(--palette-white);
        box-shadow: 0 8px 25px rgba(48, 25, 52, 0.1);
    }
    
    .members-list .list-header {
        display: none; /* Hide header on mobile */
    }
    
    .members-list .list-item {
        display: block;
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 16px;
        border: 1px solid var(--palette-border);
        background: var(--palette-white);
        box-shadow: 0 4px 15px rgba(48, 25, 52, 0.06);
    }
    
    .members-list .list-item:last-child {
        margin-bottom: 0;
    }
    
    .list-member-info {
        justify-content: flex-start;
        margin-bottom: 15px;
    }
    
    .list-member-details h4 {
        font-size: 18px;
        margin-bottom: 5px;
    }
    
    .list-member-details span {
        font-size: 14px;
    }
    
    .mobile-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 15px 0;
    }
    
    .mobile-stat {
        text-align: center;
        padding: 12px;
        background: rgba(241, 236, 226, 0.3);
        border-radius: 12px;
        border: 1px solid rgba(77, 64, 82, 0.08);
    }
    
    .mobile-stat-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--palette-deep-purple);
        margin-bottom: 3px;
    }
    
    .mobile-stat-label {
        font-size: 11px;
        color: var(--palette-dark-purple);
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .list-actions {
        justify-content: center;
        margin-top: 15px;
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
    
    .page-title {
        font-size: 24px;
    }
    
    .member-card {
        padding: 20px 16px;
        border-radius: 18px;
    }
    
    .member-avatar {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .view-toggle {
        width: 100%;
    }
    
    .view-btn {
        flex: 1;
        justify-content: center;
    }
}

@media (max-width: 400px) {
    .page-header {
        padding: 20px 15px;
    }
    
    .member-card {
        padding: 18px 14px;
    }
    
    .stats-bar {
        padding: 15px;
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

.member-card, .list-item {
    animation: fadeInUp 0.6s ease-out;
}

.member-card:nth-child(2) { animation-delay: 0.1s; }
.member-card:nth-child(3) { animation-delay: 0.2s; }
.member-card:nth-child(4) { animation-delay: 0.3s; }

/* Performance optimizations */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.member-card, .list-item {
    will-change: transform;
}
</style>

</head>

<body>
    <!-- Include Member Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Page Content -->
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-users text-primary"></i>
                        <?php echo t('members_directory.page_title'); ?>
                    </h1>
                    <p class="page-subtitle"><?php echo t('members_directory.page_subtitle'); ?></p>
                </div>
            </div>
        </div>

        <!-- Statistics Bar -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-bar">
                    <div class="stat-item">
                        <div class="stat-icon primary">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $public_count; ?></h3>
                            <p><?php echo t('members_directory.public_members'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_members; ?></h3>
                            <p><?php echo t('members_directory.total_members'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon info">
                            <i class="fas fa-user-secret"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $private_count; ?></h3>
                            <p><?php echo t('members_directory.private_members'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Controls -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="view-controls">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid">
                            <i class="fas fa-th"></i>
                            <?php echo t('members_directory.grid_view'); ?>
                        </button>
                        <button class="view-btn" data-view="list">
                            <i class="fas fa-list"></i>
                            <?php echo t('members_directory.list_view'); ?>
                        </button>
                    </div>
                    
                    <div class="search-filter">
                        <input type="text" class="search-input" placeholder="<?php echo t('members_directory.search_members'); ?>" id="memberSearch">
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Grid View -->
        <div class="row">
            <div class="col-12">
                <div class="members-grid" id="gridView">
                    <?php foreach ($public_members as $member): ?>
                        <?php
                        $member_name = trim($member['first_name'] . ' ' . $member['last_name']);
                        $initials = substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1);
                        $payout_status = $member['total_payouts_received'] > 0 ? 'received' : ($member['payout_position'] == 1 ? 'pending' : 'upcoming');
                        $expected_payout_formatted = date('M Y', strtotime($member['expected_payout_date']));
                        ?>
                        <div class="member-card" data-member-id="<?php echo $member['id']; ?>" data-name="<?php echo strtolower($member_name); ?>" data-position="<?php echo $member['payout_position']; ?>">
                            <div class="member-header">
                                <div class="member-avatar">
                                    <?php echo strtoupper($initials); ?>
                                </div>
                                <div class="member-info">
                                    <h3><?php echo htmlspecialchars($member_name, ENT_QUOTES); ?></h3>
                                    <div class="member-position"><?php echo t('members_directory.position'); ?> #<?php echo $member['payout_position']; ?></div>
                                </div>
                            </div>
                            
                            <div class="member-stats">
                                <div class="stat-block">
                                    <div class="stat-label"><?php echo t('members_directory.monthly'); ?></div>
                                    <div class="stat-value">£<?php echo number_format($member['monthly_payment'], 0); ?></div>
                                </div>
                                <div class="stat-block">
                                    <div class="stat-label"><?php echo t('members_directory.paid_total'); ?></div>
                                    <div class="stat-value">£<?php echo number_format($member['total_contributed'], 0); ?></div>
                                </div>
                                <div class="stat-block">
                                    <div class="stat-label"><?php echo t('members_directory.expected'); ?></div>
                                    <div class="stat-value">£<?php echo number_format($member['expected_payout'], 0); ?></div>
                                </div>
                                <div class="stat-block">
                                    <div class="stat-label"><?php echo t('members_directory.payout_date'); ?></div>
                                    <div class="stat-value"><?php echo $expected_payout_formatted; ?></div>
                                </div>
                            </div>
                            
                            <div class="member-footer">
                                <div class="payout-status">
                                    <div class="status-indicator status-<?php echo $payout_status; ?>"></div>
                                    <?php 
                                    echo $payout_status === 'received' ? t('members_directory.received') : 
                                         ($payout_status === 'pending' ? t('members_directory.current') : t('members_directory.upcoming')); 
                                    ?>
                                </div>
                                <button class="view-profile-btn" onclick="openMemberProfile(<?php echo $member['id']; ?>)">
                                    <?php echo t('members_directory.view_profile'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Members List View -->
                <div class="members-list" id="listView">
                    <div class="list-header">
                        <div><?php echo t('members_directory.member'); ?></div>
                        <div><?php echo t('members_directory.monthly'); ?></div>
                        <div><?php echo t('members_directory.total_paid'); ?></div>
                        <div><?php echo t('members_directory.expected'); ?></div>
                        <div><?php echo t('members_directory.actions'); ?></div>
                    </div>
                    
                                            <?php foreach ($public_members as $member): ?>
                        <?php
                        $member_name = trim($member['first_name'] . ' ' . $member['last_name']);
                        $initials = substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1);
                        $payout_status = $member['total_payouts_received'] > 0 ? 'received' : 'pending';
                        $expected_payout_formatted = date('M Y', strtotime($member['expected_payout_date']));
                        ?>
                        <div class="list-item" data-member-id="<?php echo $member['id']; ?>" data-name="<?php echo strtolower($member_name); ?>" data-position="<?php echo $member['payout_position']; ?>">
                            <div class="list-member-info">
                                <div class="list-avatar">
                                    <?php echo strtoupper($initials); ?>
                                </div>
                                <div class="list-member-details">
                                    <h4><?php echo htmlspecialchars($member_name, ENT_QUOTES); ?></h4>
                                    <span><?php echo t('members_directory.position'); ?> #<?php echo $member['payout_position']; ?></span>
                                </div>
                            </div>
                            
                            <!-- Mobile Stats Grid for Mobile View -->
                            <div class="mobile-stats-grid d-md-none">
                                <div class="mobile-stat">
                                    <div class="mobile-stat-value">£<?php echo number_format($member['monthly_payment'], 0); ?></div>
                                    <div class="mobile-stat-label"><?php echo t('members_directory.monthly'); ?></div>
                                </div>
                                <div class="mobile-stat">
                                    <div class="mobile-stat-value">£<?php echo number_format($member['total_contributed'], 0); ?></div>
                                    <div class="mobile-stat-label"><?php echo t('members_directory.paid_total'); ?></div>
                                </div>
                                <div class="mobile-stat">
                                    <div class="mobile-stat-value">£<?php echo number_format($member['expected_payout'], 0); ?></div>
                                    <div class="mobile-stat-label"><?php echo t('members_directory.expected'); ?></div>
                                </div>
                                <div class="mobile-stat">
                                    <div class="mobile-stat-value"><?php echo $expected_payout_formatted; ?></div>
                                    <div class="mobile-stat-label"><?php echo t('members_directory.payout_date'); ?></div>
                                </div>
                            </div>
                            
                            <!-- Desktop Stats for Desktop View -->
                            <div class="list-stat d-none d-md-block">£<?php echo number_format($member['monthly_payment'], 0); ?></div>
                            <div class="list-stat d-none d-md-block">£<?php echo number_format($member['total_contributed'], 0); ?></div>
                            <div class="list-stat d-none d-md-block">£<?php echo number_format($member['expected_payout'], 0); ?></div>
                            <div class="list-actions">
                                <button class="action-btn primary" onclick="openMemberProfile(<?php echo $member['id']; ?>)" title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>



    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo $cache_buster; ?>"></script>
    
    <script>
    // Enhanced member directory functionality
    document.addEventListener('DOMContentLoaded', function() {
        // View toggle functionality
        const viewButtons = document.querySelectorAll('.view-btn');
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                
                // Update active button
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Switch views
                if (view === 'grid') {
                    gridView.style.display = 'grid';
                    listView.classList.remove('active');
                } else {
                    gridView.style.display = 'none';
                    listView.classList.add('active');
                }
                
                // Save preference
                localStorage.setItem('memberViewPreference', view);
            });
        });
        
        // Load saved view preference - default to grid on mobile
        const isMobile = window.innerWidth <= 768;
        const savedView = localStorage.getItem('memberViewPreference') || (isMobile ? 'grid' : 'grid');
        if (savedView === 'list') {
            document.querySelector('[data-view="list"]').click();
        }
        
        // Search functionality
        const searchInput = document.getElementById('memberSearch');
        const positionFilter = document.getElementById('positionFilter');
        
        function filterMembers() {
            const searchTerm = searchInput.value.toLowerCase();
            const memberCards = document.querySelectorAll('[data-member-id]');
            
            memberCards.forEach(card => {
                const name = card.dataset.name;
                
                const matchesSearch = !searchTerm || name.includes(searchTerm);
                
                if (matchesSearch) {
                    card.style.display = '';
                    card.style.animation = 'fadeInUp 0.6s ease-out';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        searchInput.addEventListener('input', filterMembers);
        
        // Enhanced card interactions
        const memberCards = document.querySelectorAll('.member-card, .list-item');
        memberCards.forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('.view-profile-btn') && !e.target.closest('.action-btn')) {
                    const memberId = this.dataset.memberId;
                    openMemberProfile(memberId);
                }
            });
        });
    });

    // Open member profile page
    function openMemberProfile(memberId) {
        // Navigate to standalone member profile page
        window.location.href = `member-profile.php?id=${memberId}`;
    }
    </script>
</body>
</html> 