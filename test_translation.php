<?php
/**
 * Simple Translation System Test
 * This file tests that the translation system is working correctly
 */

// Include the database connection to start session
require_once 'includes/db.php';
require_once 'languages/translator.php';

// Handle language switching for testing
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: test_translation.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation System Test - HabeshaEqub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2><i class="fas fa-language me-2"></i><?php echo t('rules.page_title'); ?> - Translation Test</h2>
                    </div>
                    <div class="card-body">
                        
                        <!-- Language Switcher -->
                        <div class="mb-4">
                            <h5>Current Language: <span class="badge bg-success"><?php echo getCurrentLanguage() === 'en' ? 'English' : 'አማርኛ'; ?></span></h5>
                            <div class="btn-group">
                                <a href="?lang=en" class="btn btn-outline-primary <?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>">
                                    <i class="fas fa-globe me-2"></i>English
                                </a>
                                <a href="?lang=am" class="btn btn-outline-primary <?php echo getCurrentLanguage() === 'am' ? 'active' : ''; ?>">
                                    <i class="fas fa-globe me-2"></i>አማርኛ
                                </a>
                            </div>
                        </div>

                        <!-- Translation Examples -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Navigation Terms:</h6>
                                <ul class="list-group mb-3">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo t('navigation.dashboard'); ?></span>
                                        <small class="text-muted">navigation.dashboard</small>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo t('navigation.members'); ?></span>
                                        <small class="text-muted">navigation.members</small>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo t('navigation.rules'); ?></span>
                                        <small class="text-muted">navigation.rules</small>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted">Common Terms:</h6>
                                <ul class="list-group mb-3">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo t('common.save'); ?></span>
                                        <small class="text-muted">common.save</small>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo t('common.cancel'); ?></span>
                                        <small class="text-muted">common.cancel</small>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span><?php echo t('common.active'); ?></span>
                                        <small class="text-muted">common.active</small>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Rules Terms -->
                        <div class="mb-4">
                            <h6 class="text-muted">Rules Page Terms:</h6>
                            <div class="alert alert-info">
                                <h6><?php echo t('rules.page_title'); ?></h6>
                                <p><?php echo t('rules.page_subtitle'); ?></p>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i><?php echo t('rules.add_new_rule'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>Translation System Status</h6>
                            <ul class="mb-0">
                                <li><strong>Current Language:</strong> <?php echo getCurrentLanguage(); ?></li>
                                <li><strong>Available Languages:</strong> <?php echo implode(', ', array_keys(getAvailableLanguages())); ?></li>
                                <li><strong>Translation Files:</strong> 
                                    <?php 
                                    $files = [];
                                    if (file_exists('languages/en.json')) $files[] = 'en.json ✓';
                                    if (file_exists('languages/am.json')) $files[] = 'am.json ✓';
                                    echo implode(', ', $files);
                                    ?>
                                </li>
                            </ul>
                        </div>

                        <!-- Instructions -->
                        <div class="mt-4">
                            <h6><i class="fas fa-info-circle me-2"></i>How It Works</h6>
                            <ol>
                                <li>Click the language buttons above to switch between English and Amharic</li>
                                <li>The page reloads with all text translated</li>
                                <li>Language preference is stored in session</li>
                                <li>The admin rules page now has full bilingual support</li>
                            </ol>
                        </div>

                        <div class="text-center mt-4">
                            <a href="admin/rules.php" class="btn btn-success">
                                <i class="fas fa-arrow-right me-2"></i>Go to Rules Page
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 