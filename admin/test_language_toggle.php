<?php
/**
 * Language Toggle Test Page
 * Simple test to verify the navigation language toggle is working
 */

require_once '../includes/db.php';
require_once '../languages/translator.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'];
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('navigation.dashboard'); ?> Test - HabeshaEqub Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #264653;
            --primary-light: #2A9D8F;
            --accent: #E76F51;
            --accent-light: #E9C46A;
            --text-dark: #301934;
            --text-light: #F1ECE2;
            --surface-bg: #ffffff;
            --primary-bg: #f8f9fa;
            --border-light: #e9ecef;
            --text-secondary: #6c757d;
        }
        
        body {
            background: var(--primary-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>

<?php include 'includes/navigation.php'; ?>

<!-- Page content -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-language me-2"></i>Language Toggle Test</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Testing Language Toggle</h5>
                        <p>This page tests the language toggle functionality in the top navigation bar.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Current Language Info:</h6>
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Current Language:</strong></span>
                                    <span class="badge bg-success"><?php echo getCurrentLanguage(); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Dashboard:</strong></span>
                                    <span><?php echo t('navigation.dashboard'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Members:</strong></span>
                                    <span><?php echo t('navigation.members'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Save:</strong></span>
                                    <span><?php echo t('common.save'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Cancel:</strong></span>
                                    <span><?php echo t('common.cancel'); ?></span>
                                </li>
                            </ul>
                        </div>
                        
                                                 <div class="col-md-6">
                            <h6 class="text-muted">Instructions:</h6>
                            <ol>
                                <li>Look at the <strong>sidebar navigation</strong> in the "System" section</li>
                                <li>You should see a <strong>"Language"</strong> item with globe icon and current language</li>
                                <li>Click the language item to open the dropdown</li>
                                <li>Select "English" or "አማርኛ" from the dropdown with flags</li>
                                <li>The page should reload with translated content</li>
                                <li>No "Invalid CSRF token" error should appear</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mt-3">
                        <h6><i class="fas fa-check-circle me-2"></i>What Should Happen:</h6>
                        <ul class="mb-0">
                            <li>Click "Language" in sidebar → dropdown opens with flags and language names</li>
                            <li>Hover effects with gold accent and slide animations</li>
                            <li>Click language option → see loading state with hourglass</li>
                            <li>Dropdown closes → page reloads in selected language</li>
                            <li>Selected language highlighted with gold background</li>
                            <li>Sidebar shows current language (EN/አማ) next to "Language"</li>
                            <li>All text above changes language</li>
                            <li>No error messages</li>
                        </ul>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <a href="rules.php" class="btn btn-success">
                            <i class="fas fa-list-alt me-2"></i>Test Rules Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Close app-content from navigation -->
</main> <!-- Close app-main from navigation -->
</div> <!-- Close app-layout from navigation -->

</body>
</html> 