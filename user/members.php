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
/* === CLEAN PROFESSIONAL MEMBERS DIRECTORY === */

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

/* Typography - Consistent Font Sizes */
.page-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-deep-purple);
    margin: 0 0 8px 0;
}

.page-subtitle {
    font-size: 14px;
    color: var(--color-dark-purple);
    margin: 0;
}

/* Statistics Bar - Clean & Simple */
.stats-bar {
    background: var(--color-white);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.stat-icon.primary { 
    background: var(--color-cream);
    color: var(--color-deep-purple);
}

.stat-icon.warning { 
    background: rgba(218, 165, 32, 0.15);
    color: var(--color-gold);
}

.stat-icon.info { 
    background: rgba(77, 64, 82, 0.1);
    color: var(--color-dark-purple);
}

.stat-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-deep-purple);
    margin: 0;
    line-height: 1;
}

.stat-content p {
    font-size: 13px;
    color: var(--color-dark-purple);
    margin: 2px 0 0 0;
}

/* View Controls - Professional Design */
.view-controls {
    background: var(--color-white);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    border: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.view-toggle {
    display: flex;
    background: var(--color-cream);
    border-radius: 8px;
    padding: 4px;
    border: 1px solid var(--color-border);
}

.view-btn {
    border: none;
    background: transparent;
    padding: 8px 16px;
    border-radius: 6px;
    color: var(--color-dark-purple);
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.view-btn.active {
    background: var(--color-deep-purple);
    color: var(--color-cream);
}

.view-btn:hover:not(.active) {
    background: rgba(77, 64, 82, 0.1);
    color: var(--color-deep-purple);
}

/* Search Input */
.search-input {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    min-width: 200px;
    transition: border-color 0.2s ease;
    background: var(--color-white);
}

.search-input:focus {
    border-color: var(--color-gold);
    outline: none;
    box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.15);
}

/* Member Cards - Clean Grid */
.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.member-card {
    background: var(--color-white);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--color-border);
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.member-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
}

.member-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(77, 64, 82, 0.15);
    border-color: var(--color-gold);
}

.member-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--color-deep-purple);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-cream);
    font-size: 16px;
    font-weight: 600;
    flex-shrink: 0;
}

.member-info h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-deep-purple);
    margin: 0 0 4px 0;
}

.member-position {
    font-size: 12px;
    color: var(--color-gold);
    background: rgba(218, 165, 32, 0.15);
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.member-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.stat-block {
    text-align: center;
    padding: 12px;
    background: var(--color-cream);
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.stat-block:hover {
    background: rgba(205, 175, 86, 0.2);
}

.stat-value {
    font-size: 14px;
    color: var(--color-dark-purple);
    margin: 0 0 4px 0;
    font-weight: 500;
}

.stat-label {
    font-size: 12px;
    color: var(--color-deep-purple);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.member-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--color-border);
}

.payout-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    color: var(--color-dark-purple);
}

.status-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.status-received { background: var(--color-gold); }
.status-pending { background: var(--color-gold); }
.status-upcoming { background: var(--color-dark-purple); }

.view-profile-btn {
    background: var(--color-gold);
    color: var(--color-deep-purple);
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.view-profile-btn:hover {
    background: var(--color-light-gold);
}

/* Member List - Clean Design */
.members-list {
    display: none;
    background: var(--color-white);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--color-border);
}

.members-list.active {
    display: block;
}

.list-header {
    background: var(--color-cream);
    padding: 16px 20px;
    font-weight: 600;
    color: var(--color-deep-purple);
    font-size: 14px;
    border-bottom: 1px solid var(--color-border);
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 100px;
    gap: 16px;
    align-items: center;
}

.list-item {
    padding: 16px 20px;
    border-bottom: 1px solid var(--color-border);
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 100px;
    gap: 16px;
    align-items: center;
    transition: background-color 0.2s ease;
    cursor: pointer;
}

.list-item:hover {
    background: var(--color-cream);
}

.list-item:last-child {
    border-bottom: none;
}

.list-member-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.list-avatar {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: var(--color-deep-purple);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-cream);
    font-size: 14px;
    font-weight: 600;
}

.list-member-details h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-deep-purple);
    margin: 0 0 2px 0;
}

.list-member-details span {
    font-size: 12px;
    color: var(--color-gold);
    font-weight: 500;
}

.list-stat {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-deep-purple);
}

.list-actions {
    display: flex;
    justify-content: flex-end;
}

.action-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease;
    cursor: pointer;
    background: var(--color-gold);
    color: var(--color-deep-purple);
    font-size: 12px;
}

.action-btn:hover {
    background: var(--color-light-gold);
}

/* Mobile Responsive Design - Professional & Clean */
@media (max-width: 768px) {
    .container-fluid {
        padding: 0 16px;
    }
    
    .page-title {
        font-size: 20px;
        text-align: center;
    }
    
    .page-subtitle {
        text-align: center;
    }
    
    .stats-bar {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .view-controls {
        flex-direction: column;
        gap: 12px;
    }
    
    /* Professional Mobile Toggle */
    .view-toggle {
        width: 100%;
        background: var(--color-white);
        border: 2px solid var(--color-border);
        border-radius: 10px;
        padding: 6px;
    }
    
    .view-btn {
        flex: 1;
        justify-content: center;
        padding: 12px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 6px;
    }
    
    .view-btn.active {
        background: var(--color-gold);
        color: var(--color-deep-purple);
        box-shadow: 0 2px 8px rgba(218, 165, 32, 0.2);
    }
    
    .search-input {
        width: 100%;
        min-width: 100%;
    }
    
    .members-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .member-card {
        padding: 16px;
    }
    
    /* Clean Mobile List View */
    .members-list .list-header {
        display: none;
    }
    
    .members-list .list-item {
        display: block;
        padding: 16px;
        margin-bottom: 12px;
        border-radius: 8px;
        border: 1px solid var(--color-border);
        background: var(--color-white);
    }
    
    .members-list .list-item:last-child {
        margin-bottom: 0;
    }
    
    .list-member-info {
        margin-bottom: 12px;
    }
    
    .list-member-details h4 {
        font-size: 16px;
        margin-bottom: 4px;
    }
    
    .list-member-details span {
        font-size: 13px;
    }
    
    .mobile-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin: 12px 0;
    }
    
    .mobile-stat {
        text-align: center;
        padding: 8px;
        background: var(--color-cream);
        border-radius: 6px;
    }
    
    .mobile-stat-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--color-deep-purple);
        margin-bottom: 2px;
    }
    
    .mobile-stat-label {
        font-size: 11px;
        color: var(--color-dark-purple);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 500;
    }
    
    .list-actions {
        justify-content: center;
        margin-top: 12px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 12px;
    }
    
    .stats-bar,
    .view-controls,
    .member-card {
        padding: 12px;
    }
}

/* Animations - Subtle */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.member-card, .list-item {
    animation: fadeIn 0.3s ease-out;
}
</style>

</head>

<body>
    <!-- Include Member Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Page Content -->
    <div class="container-fluid">


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
                    card.style.animation = 'fadeIn 0.6s ease-out';
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