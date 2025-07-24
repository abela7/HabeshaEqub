<?php
/**
 * HabeshaEqub - Admin Profile Management
 * Edit admin profile information and change password
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

// Fetch current admin data
try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_data) {
        die('Admin data not found');
    }
} catch (PDOException $e) {
    error_log("Error fetching admin data: " . $e->getMessage());
    die('Error loading profile data');
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('profile.page_title'); ?> - HabeshaEqub Admin</title>
    
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
        /* === TOP-TIER PROFILE PAGE DESIGN === */
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--color-cream) 0%, #FAF8F5 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid var(--border-light);
            box-shadow: 0 8px 32px rgba(48, 25, 67, 0.08);
            text-align: center;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-purple);
            margin: 0 0 8px 0;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            margin: 0;
            font-size: 16px;
        }
        
        .admin-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F766E 100%);
            color: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: 0 8px 32px rgba(19, 102, 92, 0.2);
        }

        /* Profile Cards */
        .profile-section {
            margin-bottom: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(48, 25, 67, 0.06);
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(48, 25, 67, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-light);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            color: var(--color-purple);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--color-purple);
            margin: 0;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 4px 0 0 0;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--color-purple);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            border: 2px solid var(--border-light);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--color-cream);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-gold);
            box-shadow: 0 0 0 3px rgba(233, 196, 106, 0.1);
            background: white;
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            background: var(--color-cream);
            border: 2px solid var(--border-light);
            border-right: none;
            color: var(--text-secondary);
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--color-gold);
            background: white;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--color-teal);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--color-teal) 0%, #0F766E 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(19, 102, 92, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(19, 102, 92, 0.4);
            background: linear-gradient(135deg, #0F766E 0%, var(--color-teal) 100%);
        }

        .btn-secondary {
            background: var(--color-cream);
            border: 2px solid var(--border-light);
            color: var(--color-purple);
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--color-light-gold);
            border-color: var(--color-gold);
            color: var(--color-purple);
        }

        /* Alert Styling */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
            border-left: 4px solid #059669;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
            border-left: 4px solid #DC2626;
        }

        /* Password Requirements */
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-size: 14px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            color: var(--text-secondary);
        }

        .requirement.valid {
            color: #059669;
        }

        .requirement i {
            font-size: 12px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
            }

            .profile-card {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .admin-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
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
            <div class="admin-avatar">
                <?php echo strtoupper(substr($admin_username, 0, 2)); ?>
            </div>
                            <h1 class="page-title"><?php echo t('profile.page_title'); ?></h1>
            <p class="page-subtitle"><?php echo t('profile.page_subtitle'); ?></p>
        </div>

        <!-- Profile Information Card -->
        <div class="profile-section">
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h3 class="card-title"><?php echo t('profile.personal_info'); ?></h3>
                        <p class="card-subtitle"><?php echo t('profile.personal_info_subtitle'); ?></p>
                    </div>
                </div>

                <form id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="username">
                                    <i class="fas fa-user me-2"></i><?php echo t('profile.username'); ?>
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($admin_data['username']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="email">
                                    <i class="fas fa-envelope me-2"></i><?php echo t('profile.email_address'); ?>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="phone">
                                    <i class="fas fa-phone me-2"></i><?php echo t('profile.phone_number'); ?>
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($admin_data['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar me-2"></i><?php echo t('profile.member_since'); ?>
                                </label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y', strtotime($admin_data['created_at'])); ?>" 
                                       readonly>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?php echo t('profile.update_profile'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Change Card -->
        <div class="profile-section">
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h3 class="card-title"><?php echo t('profile.change_password'); ?></h3>
                        <p class="card-subtitle"><?php echo t('profile.change_password_subtitle'); ?></p>
                    </div>
                </div>

                <form id="passwordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label" for="current_password">
                                    <i class="fas fa-key me-2"></i><?php echo t('profile.current_password'); ?>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="new_password">
                                    <i class="fas fa-lock me-2"></i><?php echo t('profile.new_password'); ?>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">
                                    <i class="fas fa-check-circle me-2"></i><?php echo t('profile.confirm_new_password'); ?>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <strong><?php echo t('profile.password_requirements'); ?></strong>
                        <div class="requirement" id="req-length">
                            <i class="fas fa-times"></i>
                            <span><?php echo t('profile.req_length'); ?></span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <i class="fas fa-times"></i>
                            <span><?php echo t('profile.req_upper'); ?></span>
                        </div>
                        <div class="requirement" id="req-lower">
                            <i class="fas fa-times"></i>
                            <span><?php echo t('profile.req_lower'); ?></span>
                        </div>
                        <div class="requirement" id="req-number">
                            <i class="fas fa-times"></i>
                            <span><?php echo t('profile.req_number'); ?></span>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-shield-alt me-2"></i><?php echo t('profile.change_password'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div> <!-- End app-content -->
</main> <!-- End app-main -->
</div> <!-- End app-layout -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="../assets/js/auth.js"></script>

<script>
    // Handle logout
    async function handleLogout() {
        if (confirm('<?php echo t('profile.logout_confirm'); ?>')) {
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

    // Profile form submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('api/profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                // Update session username if changed
                if (data.new_username) {
                    location.reload(); // Reload to update navigation
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('<?php echo t('profile.profile_error'); ?>', 'danger');
        });
    });

    // Password form submission
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            showAlert('<?php echo t('profile.passwords_no_match'); ?>', 'danger');
            return;
        }
        
        if (!validatePassword(newPassword)) {
            showAlert('<?php echo t('profile.password_no_requirements'); ?>', 'danger');
            return;
        }
        
        const formData = new FormData(this);
        
        fetch('api/profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                this.reset();
                updatePasswordRequirements('');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('<?php echo t('profile.password_error'); ?>', 'danger');
        });
    });

    // Password validation
    document.getElementById('new_password').addEventListener('input', function() {
        updatePasswordRequirements(this.value);
    });

    function validatePassword(password) {
        return password.length >= 8 &&
               /[A-Z]/.test(password) &&
               /[a-z]/.test(password) &&
               /\d/.test(password);
    }

    function updatePasswordRequirements(password) {
        const requirements = [
            { id: 'req-length', test: password.length >= 8 },
            { id: 'req-upper', test: /[A-Z]/.test(password) },
            { id: 'req-lower', test: /[a-z]/.test(password) },
            { id: 'req-number', test: /\d/.test(password) }
        ];

        requirements.forEach(req => {
            const element = document.getElementById(req.id);
            const icon = element.querySelector('i');
            
            if (req.test) {
                element.classList.add('valid');
                icon.className = 'fas fa-check';
            } else {
                element.classList.remove('valid');
                icon.className = 'fas fa-times';
            }
        });
    }

    // Show alert function
    function showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
        `;
        
        // Insert after page header
        const pageHeader = document.querySelector('.page-header');
        pageHeader.insertAdjacentElement('afterend', alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Initialize password requirements
    updatePasswordRequirements('');
</script>
</body>
</html> 