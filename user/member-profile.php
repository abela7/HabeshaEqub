<?php
/**
 * HabeshaEqub - Member Profile Details (AJAX)
 * Detailed member information for modal display
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
    echo '<div class="text-center py-5"><h5>' . t('members_directory.invalid_member_id') . '</h5></div>';
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
        echo '<div class="text-center py-5"><h5>' . t('members_directory.member_not_found') . '</h5></div>';
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
    echo '<div class="text-center py-5"><h5>' . t('members_directory.database_error') . '</h5><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
    exit;
}

// Calculate member data
$member_name = trim($member['first_name'] . ' ' . $member['last_name']);
$initials = substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1);
$member_since = date('M Y', strtotime($member['created_at']));
$payout_status = $member['total_payouts_received'] > 0 ? 'received' : ($member['payout_position'] == 1 ? 'current' : 'upcoming');
$expected_payout_formatted = date('M Y', strtotime($member['expected_payout_date']));
$payment_progress = $member['total_payment_records'] > 0 ? ($member['payments_made'] / $member['total_payment_records']) * 100 : 0;
?>

<style>
/* Member Profile Modal Styles */
.member-profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(135deg, var(--palette-cream) 0%, #FAF8F5 100%);
    border-radius: 20px;
    border: 1px solid var(--palette-border);
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 8px 25px rgba(42, 157, 143, 0.4);
}

.profile-info h2 {
    font-size: 28px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 8px 0;
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
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.profile-section {
    margin-bottom: 30px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--palette-deep-purple);
    margin-bottom: 20px;
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
    width: 3px;
    height: 25px;
    background: linear-gradient(180deg, var(--palette-gold) 0%, var(--palette-success) 100%);
    border-radius: 2px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: var(--palette-white);
    border: 1px solid var(--palette-border);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(48, 25, 52, 0.06);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(48, 25, 52, 0.12);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin: 0 auto 15px;
}

.stat-icon.primary { 
    background: linear-gradient(135deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
}

.stat-icon.warning { 
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
}

.stat-icon.info { 
    background: linear-gradient(135deg, var(--palette-deep-purple) 0%, var(--palette-dark-purple) 100%);
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 5px 0;
}

.stat-label {
    font-size: 13px;
    color: var(--palette-dark-purple);
    opacity: 0.8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.payment-history {
    background: var(--palette-white);
    border: 1px solid var(--palette-border);
    border-radius: 16px;
    overflow: hidden;
}

.payment-item {
    padding: 15px 20px;
    border-bottom: 1px solid rgba(77, 64, 82, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.payment-item:hover {
    background: rgba(241, 236, 226, 0.3);
}

.payment-item:last-child {
    border-bottom: none;
}

.payment-info h5 {
    font-size: 16px;
    font-weight: 600;
    color: var(--palette-deep-purple);
    margin: 0 0 3px 0;
}

.payment-date {
    font-size: 12px;
    color: var(--palette-dark-purple);
    opacity: 0.7;
}

.payment-amount {
    font-size: 18px;
    font-weight: 700;
    color: var(--palette-success);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--palette-success) 0%, var(--palette-dark-teal) 100%);
    border-radius: 10px;
    transition: width 0.8s ease;
}

.no-data {
    text-align: center;
    padding: 30px;
    color: var(--palette-dark-purple);
    opacity: 0.6;
}

@media (max-width: 768px) {
    .member-profile-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
        padding: 20px;
    }
    
    .profile-meta {
        justify-content: center;
        gap: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .payment-item {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>

<div class="member-profile-content">
    <!-- Member Header -->
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

    <!-- Financial Overview -->
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

    <!-- Payment History -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-history text-success"></i>
            <?php echo t('members_directory.recent_payment_history'); ?>
        </h3>
        
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span><strong><?php echo $member['payments_made']; ?></strong> payments made</span>
                <span><strong><?php echo number_format($payment_progress, 1); ?>%</strong> completion</span>
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
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <p>No payment history available</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payout History -->
    <?php if (count($payout_history) > 0): ?>
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
                            <div style="font-size: 11px; color: var(--palette-dark-purple); opacity: 0.6;">
                                (Total: £<?php echo number_format($payout['total_amount'], 0); ?>)
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Member Details -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-user text-info"></i>
            Member Details
        </h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $member['payments_made']; ?></div>
                <div class="stat-label">Successful Payments</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $member['total_payouts_received']; ?></div>
                <div class="stat-label">Payouts Received</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $member['payout_position']; ?> / <?php echo (int)($member['expected_payout'] / $member['monthly_payment']); ?></div>
                <div class="stat-label">Queue Position</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo $member['is_approved'] ? 'Approved' : 'Pending'; ?>
                </div>
                <div class="stat-label">Account Status</div>
            </div>
        </div>
    </div>
</div> 