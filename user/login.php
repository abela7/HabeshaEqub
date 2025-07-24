<?php
/**
 * HabeshaEqub - Member Login Page
 */

// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../languages/translator.php';

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($phone) || empty($password)) {
        $error_message = 'Please enter both phone number and password.';
    } else {
        try {
            // Check if member exists with this phone number
            $stmt = $pdo->prepare("
                SELECT id, first_name, last_name, phone, password_hash, is_active 
                FROM members 
                WHERE phone = ? AND is_active = 1
            ");
            $stmt->execute([$phone]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member && password_verify($password, $member['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $member['id'];
                $_SESSION['user_name'] = trim($member['first_name'] . ' ' . $member['last_name']);
                $_SESSION['user_phone'] = $member['phone'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = 'Invalid phone number or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login - HabeshaEqub</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../Pictures/Icon/favicon.ico">
    
    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Logo and Header -->
            <div class="auth-header">
                <img src="../Pictures/Main Logo.png" alt="HabeshaEqub Logo" class="auth-logo">
                <h1>Member Login</h1>
                <p>Access your Equb dashboard</p>
            </div>

            <!-- Error Message -->
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required 
                           placeholder="Enter your phone number"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Login to Dashboard
                </button>
            </form>

            <!-- Links -->
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register as Member</a></p>
                <p><a href="../admin/login.php">Admin Login</a></p>
            </div>
        </div>
    </div>

    <!-- Auth page specific styles -->
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-cream) 0%, #F5F2EC 100%);
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            max-width: 400px;
            width: 100%;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .auth-logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 24px;
            border-radius: 8px;
        }
        
        .auth-header h1 {
            font-size: 28px;
            color: var(--color-teal);
            margin: 0 0 8px 0;
        }
        
        .auth-header p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        .auth-form {
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--color-teal);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
        }
        
        .btn-full {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .auth-links {
            text-align: center;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }
        
        .auth-links p {
            margin: 8px 0;
            color: var(--text-secondary);
        }
        
        .auth-links a {
            color: var(--color-teal);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        @media (max-width: 480px) {
            .auth-card {
                padding: 32px 24px;
            }
            
            .auth-header h1 {
                font-size: 24px;
            }
        }
    </style>
</body>
</html> 