<?php
/**
 * HabeshaEqub Main Index Page
 * Redirects users to the user dashboard!
 */

// Include database for session handling
require_once 'includes/db.php';

// Redirect to user login page
header("Location: user/login.php");
exit();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabeshaEqub - Ethiopian Traditional Savings Group</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon and meta tags -->
    <link rel="icon" type="image/x-icon" href="Pictures/Icon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="Pictures/Icon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="Pictures/Icon/apple-icon-180x180.png">
    <meta name="description" content="HabeshaEqub - Modern Ethiopian traditional savings group management system">
    
    <!-- iPhone/mobile specific meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HabeshaEqub">
    
    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    
    <!-- Main landing wrapper -->
    <div class="landing-wrapper">
        
        <!-- Header Section -->
        <header class="landing-header">
            <div class="container">
                <div class="header-content">
                    <img src="Pictures/Main Logo.png" alt="HabeshaEqub Logo" class="landing-logo">
                    <h1 class="landing-title">HabeshaEqub</h1>
                    <p class="landing-subtitle">Modern Ethiopian Traditional Savings Group Management</p>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="landing-main">
            <div class="container">
                
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <div class="welcome-content">
                        <h2>Welcome to HabeshaEqub</h2>
                        <p>
                            Experience the power of traditional Ethiopian savings with modern technology. 
                            Our platform helps communities manage their Equb groups with ease, transparency, and security.
                        </p>
                    </div>
                </section>

                <!-- Access Portal Section -->
                <section class="portal-section">
                    <h3>Access Your Portal</h3>
                    
                    <div class="portal-grid">
                        <!-- Admin Portal -->
                        <div class="portal-card admin-portal">
                            <div class="portal-icon">⚙️</div>
                            <h4>Admin Portal</h4>
                            <p>Manage members, track payments, handle payouts, and oversee all Equb operations.</p>
                            <div class="portal-actions">
                                <a href="admin/login.php" class="btn btn-primary">Admin Login</a>
                                <a href="admin/register.php" class="portal-link">Register as Admin</a>
                            </div>
                        </div>
                        
                        <!-- Member Portal -->
                        <div class="portal-card member-portal">
                            <div class="portal-icon">👥</div>
                            <h4>Member Portal</h4>
                            <p>View your contributions, check payout schedule, and stay updated with your group.</p>
                            <div class="portal-actions">
                                <button class="btn btn-primary" onclick="showComingSoon('Member Portal')">Member Login</button>
                                <button class="portal-link" onclick="showComingSoon('Member Registration')">Join as Member</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Features Section -->
                <section class="features-section">
                    <h3>Why Choose HabeshaEqub?</h3>
                    
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-icon">🔒</span>
                            <h4>Secure & Trusted</h4>
                            <p>Bank-level security with encrypted data and secure authentication.</p>
                        </div>
                        
                        <div class="feature-item">
                            <span class="feature-icon">📱</span>
                            <h4>Mobile-First</h4>
                            <p>Optimized for smartphones with beautiful, responsive design.</p>
                        </div>
                        
                        <div class="feature-item">
                            <span class="feature-icon">💰</span>
                            <h4>Smart Tracking</h4>
                            <p>Automated payment tracking and payout scheduling.</p>
                        </div>
                        
                        <div class="feature-item">
                            <span class="feature-icon">🌍</span>
                            <h4>Ethiopian Culture</h4>
                            <p>Built for Ethiopian communities with cultural understanding.</p>
                        </div>
                    </div>
                </section>

            </div>
        </main>

        <!-- Footer -->
        <footer class="landing-footer">
            <div class="container">
                <p>&copy; 2024 HabeshaEqub. Building stronger Ethiopian communities through technology.</p>
            </div>
        </footer>
    </div>

    <!-- JavaScript -->
    <script>
        // Show coming soon alert for unimplemented features
        function showComingSoon(feature) {
            alert(`${feature} is coming soon! 🚀\n\nCurrently, only the Admin Portal is available for testing.\nThe Member Portal will be built next.`);
        }
        
        // Add scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, observerOptions);
            
            // Observe all portal cards and feature items
            document.querySelectorAll('.portal-card, .feature-item').forEach(el => {
                observer.observe(el);
            });
        });
    </script>

    <!-- Landing page specific styles -->
    <style>
        /* Landing page layout */
        .landing-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .landing-header {
            background: linear-gradient(135deg, var(--color-cream) 0%, #F5F2EC 100%);
            padding: 60px 0;
            text-align: center;
        }
        
        .landing-logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 24px;
            border-radius: 12px;
        }
        
        .landing-title {
            font-size: 48px;
            color: var(--color-teal);
            margin: 0 0 16px 0;
            font-weight: 700;
            letter-spacing: -1px;
        }
        
        .landing-subtitle {
            font-size: 20px;
            color: var(--text-secondary);
            margin: 0;
            font-weight: 400;
        }
        
        .landing-main {
            flex: 1;
            padding: 60px 0;
        }
        
        /* Welcome section */
        .welcome-section {
            text-align: center;
            margin-bottom: 80px;
        }
        
        .welcome-content h2 {
            font-size: 36px;
            color: var(--color-purple);
            margin-bottom: 24px;
        }
        
        .welcome-content p {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* Portal section */
        .portal-section {
            margin-bottom: 80px;
        }
        
        .portal-section h3 {
            text-align: center;
            font-size: 32px;
            color: var(--color-purple);
            margin-bottom: 48px;
        }
        
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .portal-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 40px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(30px);
        }
        
        .portal-card.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .portal-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        
        .portal-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .portal-card h4 {
            font-size: 24px;
            color: var(--color-teal);
            margin-bottom: 16px;
        }
        
        .portal-card p {
            color: var(--text-secondary);
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .portal-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .portal-link {
            color: var(--color-teal);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .portal-link:hover {
            color: var(--btn-primary-hover);
            text-decoration: underline;
        }
        
        /* Features section */
        .features-section {
            margin-bottom: 60px;
        }
        
        .features-section h3 {
            text-align: center;
            font-size: 32px;
            color: var(--color-purple);
            margin-bottom: 48px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
        }
        
        .feature-item {
            text-align: center;
            padding: 20px;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .feature-item.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .feature-icon {
            font-size: 40px;
            margin-bottom: 16px;
            display: block;
        }
        
        .feature-item h4 {
            font-size: 20px;
            color: var(--color-teal);
            margin-bottom: 12px;
        }
        
        .feature-item p {
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        /* Footer */
        .landing-footer {
            background: var(--color-purple);
            color: var(--color-cream);
            text-align: center;
            padding: 32px 0;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .landing-title {
                font-size: 36px;
            }
            
            .landing-subtitle {
                font-size: 18px;
            }
            
            .landing-logo {
                max-width: 150px;
            }
            
            .welcome-content h2 {
                font-size: 28px;
            }
            
            .portal-section h3,
            .features-section h3 {
                font-size: 24px;
            }
            
            .portal-grid {
                grid-template-columns: 1fr;
            }
            
            .portal-card {
                padding: 32px 24px;
            }
        }
    </style>

</body>
</html> 