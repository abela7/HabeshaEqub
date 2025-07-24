<?php
/**
 * HabeshaEqub Admin Login Page
 * Beautiful, mobile-first login interface with AJAX functionality
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
    <title>Admin Login - HabeshaEqub</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/css/style.css" as="style">
    <link rel="preload" href="../assets/js/auth.js" as="script">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon and meta tags -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../Pictures/Icon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../Pictures/Icon/apple-icon-180x180.png">
    <meta name="description" content="Secure admin login for HabeshaEqub management system">
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
                <p>Admin Panel Login</p>
            </div>

            <!-- Alert messages (hidden by default) -->
            <div class="alert alert-success" id="successAlert"></div>
            <div class="alert alert-error" id="errorAlert"></div>
            <div class="alert alert-info" id="infoAlert"></div>

            <!-- Login form -->
            <form id="loginForm" novalidate>
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
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                        maxlength="50"
                    >
                    <div class="error-message" id="usernameError"></div>
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
                            placeholder="Enter your password"
                            autocomplete="current-password"
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
                </div>

                <!-- Remember me option -->
                <div class="form-group">
                    <div class="d-flex" style="align-items: center;">
                        <input 
                            type="checkbox" 
                            id="remember_me" 
                            name="remember_me" 
                            style="margin-right: 8px;"
                        >
                        <label for="remember_me" style="margin: 0; font-size: 14px; color: var(--text-secondary);">
                            Keep me logged in
                        </label>
                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn btn-primary btn-block">
                    Sign In to Admin Panel
                </button>
            </form>

            <!-- Additional links -->
            <div class="auth-links">
                <p style="margin-bottom: 12px;">
                    <a href="register.php">Need an admin account? Register here</a>
                </p>
                <p style="margin-bottom: 0;">
                    <a href="../member/login.php">Member Login →</a>
                </p>
            </div>

        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/auth.js"></script>
    
    <!-- Optional: Add loading indicator for slow connections -->
    <script>
        // Show loading indicator if page takes too long
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });
        
        // Focus first input on page load (accessibility)
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.getElementById('username');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        });
    </script>

    <!-- Inline critical CSS for faster loading (optional optimization) -->
    <style>
        /* Critical above-the-fold styles */
        body:not(.loaded) {
            opacity: 0.9;
        }
        
        body.loaded {
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        /* Prevent flash of unstyled content */
        .auth-wrapper {
            visibility: visible;
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
        
        @media (max-width: 480px) {
            .logo-img {
                max-width: 100px;
                margin: 0 auto 10px auto;
            }
        }
    </style>
    


</body>
</html> 