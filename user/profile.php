<?php
/**
 * HabeshaEqub - Professional Profile Management Page
 * Account settings and financial overview for members
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
$user_id = $_SESSION['user_id'] ?? 1; // Michael Werkneh = ID 1

// Get member data and financial information
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               (SELECT COUNT(*) FROM members WHERE is_active = 1) as total_active_members,
               COALESCE(SUM(CASE WHEN p.status IN ('paid', 'completed') THEN p.amount ELSE 0 END), 0) as total_contributed,
               COALESCE(COUNT(CASE WHEN p.status IN ('paid', 'completed') THEN 1 END), 0) as payments_made,
               COALESCE(
                   (SELECT total_amount FROM payouts po WHERE po.member_id = m.id ORDER BY po.actual_payout_date DESC LIMIT 1), 
                   0
               ) as last_payout_amount,
               (SELECT COUNT(*) FROM payouts po WHERE po.member_id = m.id) as total_payouts_received
        FROM members m 
        LEFT JOIN payments p ON m.id = p.member_id
        WHERE m.id = ? AND m.is_active = 1
        GROUP BY m.id
    ");
    $stmt->execute([$user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        die("❌ ERROR: No member found with ID $user_id. Please check database.");
    }
} catch (PDOException $e) {
    die("❌ DATABASE ERROR: " . $e->getMessage());
}

// Calculate financial data
$monthly_contribution = (float)$member['monthly_payment'];
$total_members = (int)$member['total_active_members'];
$expected_payout = $total_members * $monthly_contribution;
$payout_position = (int)$member['payout_position'];
$total_contributed = (float)$member['total_contributed'];
$payments_made = (int)$member['payments_made'];
$last_payout_amount = (float)$member['last_payout_amount'];
$total_payouts_received = (int)$member['total_payouts_received'];

// Calculate member status
$member_since = date('M Y', strtotime($member['created_at']));
$payout_status = $total_payouts_received > 0 ? 'received' : 'pending';

// Strong cache buster for assets
$cache_buster = time() . '_' . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('user_profile.page_title'); ?> - HabeshaEqub</title>
    
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
/* === PROFESSIONAL PROFILE MANAGEMENT DESIGN === */

/* Custom Color Palette */
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
}

/* Enhanced Page Header */
.page-header {
    background: linear-gradient(135deg, var(--palette-cream) 0%, #FAF8F5 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 40px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 8px 32px rgba(48, 25, 52, 0.08);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-subtitle {
    font-size: 18px;
    color: var(--palette-dark-purple);
    margin: 0;
    font-weight: 400;
    opacity: 0.8;
}

/* Section Styling */
.section-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--palette-deep-purple);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Profile Cards */
.profile-card {
    background: var(--palette-white);
    border-radius: 20px;
    padding: 28px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 4px 20px rgba(48, 25, 52, 0.06);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    height: 100%;
    position: relative;
    overflow: hidden;
    margin-bottom: 30px;
}

.profile-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.profile-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(48, 25, 52, 0.15);
    border-color: rgba(218, 165, 32, 0.2);
}

.profile-card:hover::before {
    transform: scaleX(1);
}

/* Financial Summary Cards */
.financial-card {
    background: var(--palette-white);
    border-radius: 16px;
    padding: 24px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 4px 16px rgba(48, 25, 52, 0.05);
    transition: all 0.3s ease;
    height: 100%;
    margin-bottom: 20px;
}

.financial-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(48, 25, 52, 0.1);
}

.financial-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.financial-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex-shrink: 0;
}

.financial-icon.primary { 
    background: linear-gradient(135deg, var(--palette-success) 0%, #0F766E 100%);
    box-shadow: 0 6px 20px rgba(42, 157, 143, 0.3);
}

.financial-icon.warning { 
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    box-shadow: 0 6px 20px rgba(218, 165, 32, 0.3);
}

.financial-icon.info { 
    background: linear-gradient(135deg, var(--palette-deep-purple) 0%, var(--palette-dark-purple) 100%);
    box-shadow: 0 6px 20px rgba(48, 25, 52, 0.3);
}

.financial-title h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--palette-dark-purple);
    margin: 0;
    line-height: 1.2;
}

.financial-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--palette-deep-purple);
    margin: 12px 0 8px 0;
    line-height: 1;
}

.financial-detail {
    font-size: 13px;
    color: var(--palette-dark-purple);
    margin: 0;
    opacity: 0.7;
    font-weight: 400;
}

/* Form Styling */
.form-section {
    background: var(--palette-white);
    border-radius: 20px;
    padding: 30px;
    border: 1px solid var(--palette-border);
    box-shadow: 0 4px 20px rgba(48, 25, 52, 0.06);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: var(--palette-deep-purple);
    margin-bottom: 8px;
    font-size: 14px;
}

.form-control {
    border: 2px solid var(--palette-border);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: var(--palette-light-bg);
}

.form-control:focus {
    border-color: var(--palette-gold);
    box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
    background: var(--palette-white);
}

.btn {
    border-radius: 12px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--palette-success) 0%, #0F766E 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(42, 157, 143, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(42, 157, 143, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    color: var(--palette-deep-purple);
    box-shadow: 0 4px 16px rgba(218, 165, 32, 0.3);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4);
    color: var(--palette-deep-purple);
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active {
    background: linear-gradient(135deg, var(--palette-success) 0%, #0F766E 100%);
    color: white;
}

.status-badge.pending {
    background: linear-gradient(135deg, var(--palette-gold) 0%, var(--palette-light-gold) 100%);
    color: var(--palette-deep-purple);
}

/* Enhanced Mobile Responsive Design */
@media (max-width: 768px) {
    .container-fluid {
        padding: 0 15px;
    }
    
    .page-header {
        padding: 30px 20px;
        margin-bottom: 30px;
        border-radius: 16px;
    }
    
    .page-title {
        font-size: 26px;
        text-align: center;
        justify-content: center;
    }
    
    .page-subtitle {
        font-size: 16px;
        text-align: center;
    }
    
    .profile-card {
        padding: 24px 20px;
        margin-bottom: 20px;
        border-radius: 16px;
    }
    
    .financial-card {
        padding: 20px 16px;
        margin-bottom: 16px;
        border-radius: 14px;
    }
    
    .form-section {
        padding: 24px 20px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 22px;
        text-align: center;
        margin-bottom: 24px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 12px;
    }
    
    .page-header {
        padding: 24px 16px;
        margin-bottom: 24px;
    }
    
    .page-title {
        font-size: 24px;
        line-height: 1.2;
    }
    
    .profile-card {
        padding: 20px 16px;
        margin-bottom: 16px;
    }
    
    .financial-card {
        padding: 18px 14px;
        margin-bottom: 14px;
    }
    
    .form-section {
        padding: 20px 16px;
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 20px;
        margin-bottom: 20px;
    }
}

/* Performance optimizations */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.profile-card, .financial-card, .form-section {
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
                        <i class="fas fa-user-circle text-warning"></i>
                        <?php echo t('user_profile.page_title'); ?>
                    </h1>
                    <p class="page-subtitle"><?php echo t('user_profile.page_subtitle'); ?></p>
                </div>
            </div>
        </div>

        <!-- Financial Overview Section -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar text-primary"></i>
                    <?php echo t('user_profile.financial_overview'); ?>
                </h2>
            </div>
            
            <!-- Payment Amount -->
            <div class="col-xl-3 col-md-6">
                <div class="financial-card">
                    <div class="financial-header">
                        <div class="financial-icon primary">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="financial-title">
                            <h4><?php echo t('user_profile.monthly_payment'); ?></h4>
                        </div>
                    </div>
                    <div class="financial-value">£<?php echo number_format($monthly_contribution, 2); ?></div>
                    <div class="financial-detail">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo t('user_profile.per_month'); ?>
                    </div>
                </div>
            </div>

            <!-- Total Contributed -->
            <div class="col-xl-3 col-md-6">
                <div class="financial-card">
                    <div class="financial-header">
                        <div class="financial-icon warning">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div class="financial-title">
                            <h4><?php echo t('user_profile.total_contributed'); ?></h4>
                        </div>
                    </div>
                    <div class="financial-value">£<?php echo number_format($total_contributed, 2); ?></div>
                    <div class="financial-detail">
                        <i class="fas fa-check-circle me-1"></i>
                        <?php echo $payments_made; ?> <?php echo t('user_profile.payments_made'); ?>
                    </div>
                </div>
            </div>

            <!-- Expected Payout -->
            <div class="col-xl-3 col-md-6">
                <div class="financial-card">
                    <div class="financial-header">
                        <div class="financial-icon info">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="financial-title">
                            <h4><?php echo t('user_profile.expected_payout'); ?></h4>
                        </div>
                    </div>
                    <div class="financial-value">£<?php echo number_format($expected_payout, 2); ?></div>
                    <div class="financial-detail">
                        <i class="fas fa-users me-1"></i>
                        <?php echo t('user_profile.position'); ?> #<?php echo $payout_position; ?>
                    </div>
                </div>
            </div>

            <!-- Payout Status -->
            <div class="col-xl-3 col-md-6">
                <div class="financial-card">
                    <div class="financial-header">
                        <div class="financial-icon <?php echo $payout_status === 'received' ? 'primary' : 'warning'; ?>">
                            <i class="fas fa-<?php echo $payout_status === 'received' ? 'check-circle' : 'clock'; ?>"></i>
                        </div>
                        <div class="financial-title">
                            <h4><?php echo t('user_profile.payout_status'); ?></h4>
                        </div>
                    </div>
                    <div class="financial-value">
                        <span class="status-badge <?php echo $payout_status === 'received' ? 'active' : 'pending'; ?>">
                            <?php echo $payout_status === 'received' ? t('user_profile.received') : t('user_profile.pending'); ?>
                        </span>
                    </div>
                    <div class="financial-detail">
                        <?php if ($payout_status === 'received'): ?>
                            <i class="fas fa-pound-sign me-1"></i>
                            <?php echo t('user_profile.last_payout'); ?>: £<?php echo number_format($last_payout_amount, 2); ?>
                        <?php else: ?>
                            <i class="fas fa-hourglass-half me-1"></i>
                            <?php echo t('user_profile.awaiting_turn'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Settings Section -->
        <div class="row">
            <!-- Personal Information Form -->
            <div class="col-lg-8">
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-user-edit text-primary"></i>
                        <?php echo t('user_profile.personal_info'); ?>
                    </h2>
                    
                    <form id="profileForm" method="POST" action="update-profile.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name" class="form-label"><?php echo t('user_profile.first_name'); ?></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($member['first_name'] ?? '', ENT_QUOTES); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name" class="form-label"><?php echo t('user_profile.last_name'); ?></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($member['last_name'] ?? '', ENT_QUOTES); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label"><?php echo t('user_profile.email'); ?></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member['email'] ?? '', ENT_QUOTES); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label"><?php echo t('user_profile.phone'); ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? '', ENT_QUOTES); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?php echo t('user_profile.update_profile'); ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-warning">
                                <i class="fas fa-arrow-left me-2"></i>
                                <?php echo t('user_profile.back_dashboard'); ?>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-lock text-primary"></i>
                        <?php echo t('user_profile.change_password'); ?>
                    </h2>
                    
                    <form id="passwordForm" method="POST" action="change-password.php">
                        <div class="form-group">
                            <label for="current_password" class="form-label"><?php echo t('user_profile.current_password'); ?></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="new_password" class="form-label"><?php echo t('user_profile.new_password'); ?></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label"><?php echo t('user_profile.confirm_password'); ?></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning mt-3">
                            <i class="fas fa-key me-2"></i>
                            <?php echo t('user_profile.change_password'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Summary Sidebar -->
            <div class="col-lg-4">
                <div class="profile-card">
                    <h2 class="section-title">
                        <i class="fas fa-user-shield text-primary"></i>
                        <?php echo t('user_profile.account_summary'); ?>
                    </h2>
                    
                    <div class="mb-3">
                        <strong><?php echo t('user_profile.member_since'); ?>:</strong><br>
                        <span class="text-muted"><?php echo $member_since; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong><?php echo t('user_profile.member_id'); ?>:</strong><br>
                        <code><?php echo str_pad($member['id'], 6, '0', STR_PAD_LEFT); ?></code>
                    </div>
                    
                    <div class="mb-3">
                        <strong><?php echo t('user_profile.account_status'); ?>:</strong><br>
                        <span class="status-badge active">
                            <i class="fas fa-check me-1"></i>
                            <?php echo t('user_profile.active'); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <strong><?php echo t('user_profile.total_payouts'); ?>:</strong><br>
                        <span class="text-success"><?php echo $total_payouts_received; ?> <?php echo t('user_profile.received'); ?></span>
                    </div>

                    <hr>
                    
                    <div class="text-center">
                        <a href="contributions.php" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-credit-card me-2"></i>
                            <?php echo t('user_profile.view_payments'); ?>
                        </a>
                        <a href="payout-info.php" class="btn btn-warning w-100">
                            <i class="fas fa-chart-line me-2"></i>
                            <?php echo t('user_profile.payout_info'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo $cache_buster; ?>"></script>
    
    <script>
    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
        // Password confirmation validation
        const passwordForm = document.getElementById('passwordForm');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                                         alert('<?php echo t('user_profile.passwords_no_match'); ?>');
                    return false;
                }
                
                if (newPassword.value.length < 6) {
                    e.preventDefault();
                                         alert('<?php echo t('user_profile.password_min_length'); ?>');
                    return false;
                }
            });
        }
        
        // Profile form enhancement
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                const email = document.getElementById('email');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!emailRegex.test(email.value)) {
                    e.preventDefault();
                                         alert('<?php echo t('user_profile.email_invalid'); ?>');
                    return false;
                }
            });
        }
    });
    </script>
</body>
</html> 