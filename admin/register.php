<?php
/**
 * HabeshaEqub Admin Registration Page
 * Beautiful, mobile-first registration interface with AJAX functionality
 */

// Include database and start session
require_once '../includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id']) && $_SESSION['admin_id']) {
    header('Location: dashboard.php');
    exit;
}

// Generate CSRF token for security
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - HabeshaEqub</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/css/style.css" as="style">
    <link rel="preload" href="../assets/js/auth.js" as="script">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon and meta tags -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../Pictures/Icon/apple-icon-180x180.png">
    <meta name="description" content="Create new admin account for HabeshaEqub management system">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- iPhone/mobile specific meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HabeshaEqub Admin">
    
    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    <!-- Main authentication wrapper -->
    <div class="auth-wrapper">
        <div class="auth-card fade-in">
            
            <!-- Logo and branding section -->
            <div class="auth-logo">
                <img src="../Pictures/Main Logo.png" alt="HabeshaEqub Logo" class="logo-img">
                <p>Create Admin Account</p>
            </div>

            <!-- Alert messages (hidden by default) -->
            <div class="alert alert-success" id="successAlert"></div>
            <div class="alert alert-error" id="errorAlert"></div>
            <div class="alert alert-info" id="infoAlert"></div>

            <!-- Registration form -->
            <form id="registerForm" novalidate>
                <!-- CSRF Protection -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <!-- Username field -->
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Choose a unique username"
                        autocomplete="username"
                        required
                        maxlength="50"
                    >
                    <div class="error-message" id="usernameError"></div>
                    <small style="color: var(--text-secondary); font-size: 12px; margin-top: 4px; display: block;">
                        3-50 characters, letters, numbers, and underscores only
                    </small>
                </div>

                <!-- Password field -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control password-input" 
                            placeholder="Create a secure password"
                            autocomplete="new-password"
                            required
                            minlength="6"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span class="password-icon" id="passwordIcon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                    <div class="error-message" id="passwordError"></div>
                    <small style="color: var(--text-secondary); font-size: 12px; margin-top: 4px; display: block;">
                        At least 6 characters with letters and numbers (special characters allowed)
                    </small>
                </div>

                <!-- Confirm Password field -->
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control password-input" 
                            placeholder="Confirm your password"
                            autocomplete="new-password"
                            required
                            minlength="6"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <span class="password-icon" id="confirm_passwordIcon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>

                <!-- Terms agreement -->
                <div class="form-group">
                    <div class="d-flex" style="align-items: flex-start;">
                        <input 
                            type="checkbox" 
                            id="agree_terms" 
                            name="agree_terms" 
                            style="margin-right: 8px; margin-top: 2px;"
                            required
                        >
                        <label for="agree_terms" style="margin: 0; font-size: 14px; color: var(--text-secondary); line-height: 1.4;">
                            I agree to the terms and conditions and will use this admin account responsibly
                        </label>
                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn btn-primary btn-block">
                    Create Admin Account
                </button>
            </form>

            <!-- Additional links -->
            <div class="auth-links">
                <p style="margin-bottom: 12px;">
                    <a href="login.php">Already have an account? Login here</a>
                </p>
                <p style="margin-bottom: 0;">
                    <a href="../member/login.php">Member Login →</a>
                </p>
            </div>

        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/auth.js"></script>
    
    <!-- Additional validation for registration -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus first input on page load
            const firstInput = document.getElementById('username');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
            
            // Real-time password confirmation validation
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            
            if (passwordField && confirmPasswordField) {
                const validatePasswordMatch = () => {
                    const password = passwordField.value;
                    const confirmPassword = confirmPasswordField.value;
                    
                    if (confirmPassword && password !== confirmPassword) {
                        confirmPasswordField.classList.add('error');
                        const errorElement = confirmPasswordField.parentElement.querySelector('.error-message');
                        if (errorElement) {
                            errorElement.textContent = 'Passwords do not match';
                            errorElement.style.display = 'block';
                        }
                    } else if (confirmPassword && password === confirmPassword) {
                        confirmPasswordField.classList.remove('error');
                        confirmPasswordField.classList.add('success');
                        const errorElement = confirmPasswordField.parentElement.querySelector('.error-message');
                        if (errorElement) {
                            errorElement.style.display = 'none';
                        }
                    }
                };
                
                passwordField.addEventListener('input', validatePasswordMatch);
                confirmPasswordField.addEventListener('input', validatePasswordMatch);
            }
            
            // Terms checkbox validation
            const termsCheckbox = document.getElementById('agree_terms');
            const form = document.getElementById('registerForm');
            
            if (termsCheckbox && form) {
                form.addEventListener('submit', function(e) {
                    if (!termsCheckbox.checked) {
                        e.preventDefault();
                        
                        // Show error alert
                        const authManager = window.authManager || new AuthManager();
                        authManager.showAlert('error', 'Please agree to the terms and conditions to continue.');
                        
                        // Highlight checkbox
                        termsCheckbox.style.outline = '2px solid var(--color-coral)';
                        setTimeout(() => {
                            termsCheckbox.style.outline = '';
                        }, 3000);
                    }
                });
                
                termsCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        this.style.outline = '';
                    }
                });
            }
        });
        
        // Password strength indicator (optional enhancement)
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength += 1;
            else feedback.push('at least 8 characters');
            
            if (/[a-z]/.test(password)) strength += 1;
            else feedback.push('lowercase letters');
            
            if (/[A-Z]/.test(password)) strength += 1;
            else feedback.push('uppercase letters');
            
            if (/[0-9]/.test(password)) strength += 1;
            else feedback.push('numbers');
            
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            else feedback.push('special characters');
            
            return { strength, feedback };
        }
    </script>

    <!-- Critical CSS optimization -->
    <style>
        /* Enhanced form styling for registration */
        .form-group small {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        /* Logo styling */
        .logo-img {
            max-width: 120px;
            height: auto;
            margin: 0 auto 12px auto;
            border-radius: 8px;
            display: block;
        }
        
        /* Password toggle functionality */
        .password-input-wrapper {
            position: relative;
        }
        
        .password-input {
            padding-right: 50px !important;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--color-teal);
        }
        
        .password-icon {
            font-size: 18px;
            user-select: none;
        }
        
        /* Checkbox styling */
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--color-teal);
            cursor: pointer;
        }
        
        input[type="checkbox"]:focus {
            outline: 2px solid var(--color-teal);
            outline-offset: 2px;
        }
        
        /* Terms label styling */
        label[for="agree_terms"] {
            cursor: pointer;
            user-select: none;
        }
        
        /* Registration-specific animations */
        .auth-card {
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 480px) {
            .logo-img {
                max-width: 100px;
                margin: 0 auto 10px auto;
            }
        }
    </style>
    


</body>
</html> 