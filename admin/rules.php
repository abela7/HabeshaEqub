<?php
/**
 * HabeshaEqub - Equb Rules Management
 * Admin page to manage Equb rules in English and Amharic
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

// Fetch all rules from database
try {
    $stmt = $pdo->query("
        SELECT * FROM equb_rules 
        ORDER BY rule_number ASC
    ");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Rules fetch error: " . $e->getMessage());
    $rules = [];
}

// No categories needed for simplified rules

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('rules.page_title'); ?> - HabeshaEqub Admin</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* === TOP-TIER RULES PAGE DESIGN === */
        
        /* Page Header - Matching other admin pages */
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
            font-size: 20px;
        }
        
        .page-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 400;
        }
        
        .btn-modern {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(19, 102, 92, 0.15);
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(19, 102, 92, 0.25);
        }
        
        /* Statistics Cards - Matching other pages */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 12px rgba(48, 25, 67, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(48, 25, 67, 0.12);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .stat-icon.bg-primary { background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%); }
        .stat-icon.bg-success { background: linear-gradient(135deg, var(--color-gold) 0%, #D4A853 100%); }
        .stat-icon.bg-info { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, var(--color-coral) 0%, #D86142 100%); }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-purple);
            line-height: 1;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Language toggle styles removed - now in navigation bar */
        
        /* Rules Grid */
        .rules-grid {
            display: grid;
            gap: 24px;
        }
        
        .rule-card.modern-card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(48, 25, 67, 0.08);
        }
        
        .rule-card.modern-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(48, 25, 67, 0.15);
        }
        
        .rule-header {
            padding: 24px;
            background: linear-gradient(135deg, var(--secondary-bg) 0%, #FAF8F5 100%);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .rule-number-badge {
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F5147 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-indicator.active {
            background: rgba(233, 196, 106, 0.2);
            color: var(--color-gold);
        }
        
        .status-indicator.inactive {
            background: rgba(231, 111, 81, 0.2);
            color: var(--color-coral);
        }
        
        .rule-body {
            padding: 32px 24px;
        }
        
        .rule-content {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-primary);
            margin-bottom: 0;
        }
        
        .rule-actions {
            padding: 20px 24px;
            border-top: 1px solid var(--border-light);
            background: var(--secondary-bg);
            display: flex;
            gap: 12px;
        }
        
        .btn-sm.btn-modern {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        /* Modal Enhancements */
        .modal-header {
            background: var(--secondary-bg);
            border-bottom: 1px solid var(--border-light);
            border-radius: 20px 20px 0 0;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(48, 25, 67, 0.15);
        }
        
        .modal-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 20px;
        }
        
        .form-floating textarea {
            min-height: 120px;
            border-radius: 12px;
        }
        
        .form-floating input, .form-floating textarea {
            border-radius: 12px;
        }
        
        .bilingual-field {
            margin-bottom: 24px;
        }
        
        .field-label {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: block;
            font-size: 16px;
        }
        
        .language-tabs {
            display: flex;
            border-bottom: 2px solid var(--border-light);
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            color: var(--color-teal);
            border-bottom-color: var(--color-teal);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: var(--text-secondary);
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-light);
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 24px;
            color: var(--border-light);
        }
        
        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        /* COMPLETELY DISABLE ALL BACKDROP ELEMENTS */
        .modal-backdrop,
        .modal-backdrop.fade,
        .modal-backdrop.show,
        .modal-backdrop.fade.show {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        /* Modal WITHOUT any blocking elements */
        .modal {
            z-index: 1050 !important;
            background-color: transparent !important; /* COMPLETELY TRANSPARENT */
            pointer-events: none !important; /* Modal itself doesn't block */
        }
        
        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: rgba(0, 0, 0, 0.5) !important; /* Background on the modal container */
        }
        
        .modal-dialog {
            margin: 0 !important;
            width: 90% !important;
            max-width: 600px !important;
            pointer-events: auto !important; /* Only the dialog content is clickable */
            position: relative !important;
            z-index: 1051 !important;
        }
        
        .modal-content {
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
            border: none !important;
            pointer-events: auto !important; /* Ensure content is clickable */
            position: relative !important;
            z-index: 1052 !important;
        }
        
        .modal-content * {
            pointer-events: auto !important; /* All content inside is clickable */
        }
        
        .modal-header {
            border-bottom: 1px solid #e9ecef !important;
            padding: 20px !important;
            position: relative;
        }
        
        .modal-header .btn-close {
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: none !important;
            border: none !important;
            font-size: 1.2rem !important;
            opacity: 0.7 !important;
            pointer-events: auto !important;
            z-index: 1053 !important;
        }
        
        .modal-header .btn-close:hover {
            opacity: 1 !important;
        }
        
        /* Emergency cleanup button */
        .emergency-cleanup-btn {
            display: none !important;
        }
        
        /* FORCE REMOVE ANY BOOTSTRAP GENERATED BACKDROPS */
        body *[class*="backdrop"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* Ensure no overlay blocks interaction */
        body.modal-open {
            overflow: auto !important;
            position: static !important;
            padding-right: 0 !important;
        }
        
        @media (max-width: 768px) {
            .modal-dialog {
                width: 95% !important;
                max-width: 95% !important;
                margin: 20px auto !important;
            }
            
            .modal-content {
                border-radius: 16px !important;
                max-height: 90vh !important;
                overflow-y: auto !important;
            }
            
            .modal-body {
                padding: 15px !important;
            }
            
            .modal-header, .modal-footer {
                padding: 15px !important;
            }
            
            /* CRITICAL: Force no backdrop on mobile */
            .modal {
                background-color: transparent !important;
            }
            
            .modal.show {
                background-color: rgba(0, 0, 0, 0.5) !important;
            }
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 24px;
                padding: 24px;
                text-align: center;
            }
            
            .page-title-section h1 {
                font-size: 24px;
                justify-content: center;
            }
            
            .page-title-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            /* Language toggle removed - now in navigation */
            
            .rule-card.modern-card {
                margin-bottom: 16px;
            }
            
            .rule-header {
                flex-direction: column;
                gap: 16px;
                padding: 20px;
                text-align: center;
            }
            
            .rule-body {
                padding: 24px 20px;
            }
            
            .rule-content {
                font-size: 15px;
            }
            
            .rule-actions {
                flex-direction: column;
                gap: 8px;
                padding: 16px 20px;
            }
            
            .btn-sm.btn-modern {
                width: 100%;
                justify-content: center;
            }
            
            .empty-state {
                padding: 60px 24px;
            }
            
            .empty-state i {
                font-size: 48px;
            }
        }
        
        @media (max-width: 480px) {
            .page-header {
                padding: 20px;
            }
            
            .page-title-section h1 {
                font-size: 20px;
            }
            
            .rule-header {
                padding: 16px;
            }
            
            .rule-body {
                padding: 20px 16px;
            }
            
            .rule-actions {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <!-- Include Navigation -->
    <?php include 'includes/navigation.php'; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title-section">
            <h1>
                <div class="page-title-icon">
                    <i class="fas fa-list-alt"></i>
                </div>
                <?php echo t('rules.page_title'); ?>
            </h1>
            <p class="page-subtitle"><?php echo t('rules.page_subtitle'); ?></p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addRuleModal" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>
                <?php echo t('rules.add_new_rule'); ?>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($rules); ?></div>
                <div class="stat-label"><?php echo t('rules.total_rules'); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count(array_filter($rules, function($r) { return $r['is_active'] == 1; })); ?></div>
                <div class="stat-label"><?php echo t('rules.active_rules'); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-hashtag"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo !empty($rules) ? max(array_column($rules, 'rule_number')) : '0'; ?></div>
                <div class="stat-label"><?php echo t('rules.highest_rule'); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-pause-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count(array_filter($rules, function($r) { return $r['is_active'] == 0; })); ?></div>
                <div class="stat-label"><?php echo t('rules.inactive_rules'); ?></div>
            </div>
        </div>
    </div>

    <!-- Language toggle is now in the top navigation bar -->

    <!-- Alert Messages -->
    <div class="alert alert-success d-none" id="successAlert"></div>
    <div class="alert alert-danger d-none" id="errorAlert"></div>

    <!-- Rules Grid -->
    <div class="rules-grid" id="rulesGrid">
        <?php if (empty($rules)): ?>
            <div class="empty-state">
                <i class="fas fa-list-alt"></i>
                <h3><?php echo t('rules.no_rules_found'); ?></h3>
                <p><?php echo t('rules.no_rules_message'); ?></p>
                <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addRuleModal" onclick="openAddModal()">
                    <i class="fas fa-plus me-2"></i>
                    <?php echo t('rules.add_first_rule'); ?>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($rules as $rule): ?>
                <div class="rule-card modern-card" data-rule-id="<?php echo $rule['id']; ?>">
                    <div class="rule-header">
                        <div class="rule-number-badge">
                            <i class="fas fa-gavel"></i>
                            <?php echo t('rules.rule_prefix'); ?><?php echo $rule['rule_number']; ?>
                        </div>
                        <div class="rule-status-badge">
                            <span class="status-indicator <?php echo $rule['is_active'] ? 'active' : 'inactive'; ?>">
                                <i class="fas fa-<?php echo $rule['is_active'] ? 'check-circle' : 'pause-circle'; ?>"></i>
                                <?php echo $rule['is_active'] ? t('rules.active') : t('rules.inactive'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="rule-body">
                        <div class="rule-content rule-content-en">
                            <strong><?php echo nl2br(htmlspecialchars($rule['rule_en'])); ?></strong>
                        </div>
                        <div class="rule-content rule-content-am" style="display: none;">
                            <strong><?php echo nl2br(htmlspecialchars($rule['rule_am'])); ?></strong>
                        </div>
                    </div>
                    <div class="rule-actions">
                        <button class="btn btn-outline-primary btn-sm btn-modern" onclick="editRule(<?php echo $rule['id']; ?>)">
                            <i class="fas fa-edit me-1"></i>
                            <?php echo t('rules.edit'); ?>
                        </button>
                        <button class="btn btn-outline-danger btn-sm btn-modern" onclick="deleteRule(<?php echo $rule['id']; ?>)">
                            <i class="fas fa-trash me-1"></i>
                            <?php echo t('rules.delete'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Rule Modal -->
    <div class="modal fade" id="addRuleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo t('rules.add_new_rule'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addRuleForm">
                    <div class="modal-body">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="ruleNumber" name="rule_number" min="1" required>
                            <label for="ruleNumber"><?php echo t('rules.rule_number'); ?></label>
                        </div>
                        
                        <!-- Bilingual Rule Content -->
                        <div class="bilingual-field">
                            <label class="field-label"><?php echo t('rules.rule_content'); ?></label>
                            <div class="language-tabs">
                                <button type="button" class="tab-btn active" data-tab="rule-en"><?php echo t('rules.english'); ?></button>
                                <button type="button" class="tab-btn" data-tab="rule-am"><?php echo t('rules.amharic'); ?></button>
                            </div>
                            <div class="tab-content active" id="rule-en">
                                <div class="form-floating">
                                    <textarea class="form-control" name="rule_en" required></textarea>
                                    <label><?php echo t('rules.rule_content_english'); ?></label>
                                </div>
                            </div>
                            <div class="tab-content" id="rule-am">
                                <div class="form-floating">
                                    <textarea class="form-control" name="rule_am" required></textarea>
                                    <label><?php echo t('rules.rule_content_amharic'); ?></label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Options -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                            <label class="form-check-label" for="isActive">
                                <?php echo t('rules.active_rule'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('common.cancel'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo t('rules.add_rule'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Rule Modal -->
    <div class="modal fade" id="editRuleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo t('rules.edit_rule'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editRuleForm">
                    <div class="modal-body" id="editRuleContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('common.cancel'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo t('rules.update_rule'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Global functions (available immediately)
        
        // BACKDROP KILLER - Remove any backdrop immediately
        function killAllBackdrops() {
            // Remove all backdrop elements
            const backdrops = document.querySelectorAll('.modal-backdrop, [class*="backdrop"]');
            backdrops.forEach(backdrop => {
                backdrop.remove();
                console.log('KILLED BACKDROP:', backdrop);
            });
            
            // Reset body
            document.body.classList.remove('modal-open');
            document.body.style.cssText = '';
        }
        
        // Open add modal - AGGRESSIVE NO BACKDROP
        function openAddModal() {
            console.log('Opening add modal - KILLING ALL BACKDROPS...');
            
            // KILL BACKDROPS IMMEDIATELY
            killAllBackdrops();
            
            const modal = document.getElementById('addRuleModal');
            if (modal) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    // Close any existing modals first
                    const existingModals = document.querySelectorAll('.modal.show');
                    existingModals.forEach(existingModal => {
                        const instance = bootstrap.Modal.getInstance(existingModal);
                        if (instance) {
                            instance.hide();
                        }
                    });
                    
                    // KILL BACKDROPS AGAIN
                    killAllBackdrops();
                    
                    // Create and show modal without backdrop
                    const bsModal = new bootstrap.Modal(modal, {
                        backdrop: false, // NO BACKDROP AT ALL
                        keyboard: true,
                        focus: true
                    });
                    
                    // Listen for show event and kill backdrops
                    modal.addEventListener('show.bs.modal', killAllBackdrops);
                    modal.addEventListener('shown.bs.modal', function() {
                        killAllBackdrops();
                        console.log('Modal shown - all backdrops killed');
                    });
                    
                    bsModal.show();
                    
                    // Kill backdrops after show
                    setTimeout(killAllBackdrops, 10);
                    setTimeout(killAllBackdrops, 50);
                    setTimeout(killAllBackdrops, 100);
                    
                } else {
                    console.error('Bootstrap not available - modal cannot open');
                    showAlert('error', 'Modal system not available.');
                }
            } else {
                console.error('Add modal not found!');
            }
        }





        // NUCLEAR BACKDROP DESTROYER - Watch for any backdrop creation
        function initBackdropDestroyer() {
            console.log('INITIALIZING NUCLEAR BACKDROP DESTROYER...');
            
            // Create mutation observer to watch for backdrop elements
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Check if it's a backdrop
                            if (node.classList && 
                                (node.classList.contains('modal-backdrop') || 
                                 node.className.includes('backdrop'))) {
                                console.log('DETECTED BACKDROP CREATION - DESTROYING:', node);
                                node.remove();
                            }
                            
                            // Check children for backdrops
                            const childBackdrops = node.querySelectorAll && node.querySelectorAll('.modal-backdrop, [class*="backdrop"]');
                            if (childBackdrops) {
                                childBackdrops.forEach(backdrop => {
                                    console.log('DETECTED CHILD BACKDROP - DESTROYING:', backdrop);
                                    backdrop.remove();
                                });
                            }
                        }
                    });
                });
            });
            
            // Start watching for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            // Also kill backdrops every 100ms as a safety net
            setInterval(killAllBackdrops, 100);
            
            console.log('NUCLEAR BACKDROP DESTROYER IS ACTIVE');
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Rules page loaded - ACTIVATING NUCLEAR BACKDROP DESTROYER');
            
            // Initialize nuclear backdrop destroyer
            initBackdropDestroyer();
            
            // Test API connectivity
            testApiConnection();
            
            // Initialize form handlers
            initFormHandlers();
            
            // Initialize tab functionality
            initTabFunctionality();
            
            // Initialize modal background close functionality
            initModalBackgroundClose();
        });

        // Simple modal helper function
        function closeAllModals() {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) {
                    instance.hide();
                }
            });
        }

        // Add click-to-close functionality for modal background
        function initModalBackgroundClose() {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
                    const instance = bootstrap.Modal.getInstance(e.target);
                    if (instance) {
                        instance.hide();
                    }
                }
            });
        }

        // Test API connectivity
        async function testApiConnection() {
            try {
                const response = await fetch('api/rules.php?action=list');
                if (response.ok) {
                    console.log('API connection: OK');
                } else {
                    console.warn('API connection issue:', response.status);
                }
            } catch (error) {
                console.error('API connection failed:', error);
            }
        }

        // Language functionality is now handled by the navigation bar dropdown

        // Tab functionality for forms
        function initTabFunctionality() {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    const tabContainer = this.closest('.bilingual-field') || this.closest('.modal-body');
                    
                    if (tabContainer) {
                        // Remove active from all tabs in this container
                        tabContainer.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                        tabContainer.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                        
                        // Activate current tab
                        this.classList.add('active');
                        const targetElement = document.getElementById(targetTab);
                        if (targetElement) {
                            targetElement.classList.add('active');
                        }
                    }
                });
            });
        }

        // Form handlers
        function initFormHandlers() {
            // Add rule form
            const addForm = document.getElementById('addRuleForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitRuleForm(this, 'add');
                });
            }
            
            // Edit rule form
            const editForm = document.getElementById('editRuleForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitRuleForm(this, 'edit');
                });
            }
        }

        // Submit rule form
        async function submitRuleForm(form, action) {
            const formData = new FormData(form);
            
            try {
                const response = await fetch('api/rules.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('success', data.message);
                    // Close modal
                    const modal = form.closest('.modal');
                    if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    }
                    // Clear form
                    form.reset();
                    // Reload page to show changes
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('error', data.message || 'An error occurred');
                }
            } catch (error) {
                console.error('Form submission error:', error);
                showAlert('error', 'Network error. Please check your connection and try again.');
            }
        }

        // Edit rule
        async function editRule(ruleId) {
            console.log('Edit rule clicked for ID:', ruleId);
            try {
                const response = await fetch(`api/rules.php?action=get&id=${ruleId}`);
                console.log('API response:', response);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('API data:', data);
                
                if (data.success) {
                    loadEditForm(data.rule || data.data);
                    const modal = document.getElementById('editRuleModal');
                    if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        // KILL BACKDROPS BEFORE OPENING EDIT MODAL
                        killAllBackdrops();
                        
                        const bsModal = new bootstrap.Modal(modal, {
                            backdrop: false, // NO BACKDROP AT ALL
                            keyboard: true,
                            focus: true
                        });
                        
                        // Listen for show event and kill backdrops
                        modal.addEventListener('show.bs.modal', killAllBackdrops);
                        modal.addEventListener('shown.bs.modal', function() {
                            killAllBackdrops();
                            console.log('Edit modal shown - all backdrops killed');
                        });
                        
                        bsModal.show();
                        
                        // Kill backdrops after show
                        setTimeout(killAllBackdrops, 10);
                        setTimeout(killAllBackdrops, 50);
                        setTimeout(killAllBackdrops, 100);
                        
                    } else {
                        console.error('Bootstrap modal not available or modal not found');
                        showAlert('error', 'Modal system not available.');
                    }
                } else {
                    showAlert('error', data.message || 'Failed to load rule data.');
                }
            } catch (error) {
                console.error('Edit rule error:', error);
                showAlert('error', 'Network error while loading rule data.');
            }
        }

        // Load edit form
        function loadEditForm(rule) {
            const content = document.getElementById('editRuleContent');
            if (!content) return;
            
            content.innerHTML = `
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="rule_id" value="${rule.id}">
                
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" name="rule_number" value="${rule.rule_number}" min="1" required>
                    <label>Rule Number</label>
                </div>
                
                <!-- Bilingual Rule Content -->
                <div class="bilingual-field">
                    <label class="field-label">Rule Content</label>
                    <div class="language-tabs">
                        <button type="button" class="tab-btn active" data-tab="edit-rule-en">English</button>
                        <button type="button" class="tab-btn" data-tab="edit-rule-am">አማርኛ</button>
                    </div>
                    <div class="tab-content active" id="edit-rule-en">
                        <div class="form-floating">
                            <textarea class="form-control" name="rule_en" required>${rule.rule_en}</textarea>
                            <label>Rule Content (English)</label>
                        </div>
                    </div>
                    <div class="tab-content" id="edit-rule-am">
                        <div class="form-floating">
                            <textarea class="form-control" name="rule_am" required>${rule.rule_am}</textarea>
                            <label>Rule Content (አማርኛ)</label>
                        </div>
                    </div>
                </div>
                
                <!-- Options -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" ${rule.is_active ? 'checked' : ''}>
                    <label class="form-check-label">
                        Active Rule
                    </label>
                </div>
            `;
            
            // Reinitialize tab functionality for edit form
            initTabFunctionality();
        }

        // Delete rule
        async function deleteRule(ruleId) {
            if (!confirm('Are you sure you want to delete this rule? This action cannot be undone.')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('rule_id', ruleId);
                formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                
                const response = await fetch('api/rules.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('success', data.message);
                    // Remove rule card from DOM
                    const ruleCard = document.querySelector(`[data-rule-id="${ruleId}"]`);
                    if (ruleCard) {
                        ruleCard.remove();
                    }
                } else {
                    showAlert('error', data.message);
                }
            } catch (error) {
                console.error('Delete rule error:', error);
                showAlert('error', 'An error occurred while deleting the rule.');
            }
        }

        // Show alert
        function showAlert(type, message) {
            const alertId = type === 'success' ? 'successAlert' : 'errorAlert';
            const alertElement = document.getElementById(alertId);
            
            if (alertElement) {
                alertElement.textContent = message;
                alertElement.classList.remove('d-none');
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    alertElement.classList.add('d-none');
                }, 5000);
                
                // Scroll to top to show alert
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    </script>

    <!-- Close navigation -->
    </div>
    </main>

</body>
</html> 