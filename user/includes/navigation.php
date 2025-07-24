<?php
/**
 * HabeshaEqub - Member Navigation
 * Professional navigation with same desktop-style layout as admin
 * Adapted for member-specific functionality
 */

// Include required files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../languages/translator.php';

// Get current page for active states
$current_page = basename($_SERVER['PHP_SELF']);

// Get REAL member data from database
$user_id = $_SESSION['user_id'] ?? 1;
$member_name = 'John Doe'; // Default fallback

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM members WHERE id = ? AND is_active = 1");
    $stmt->execute([$user_id]);
    $member_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($member_data) {
        $member_name = trim($member_data['first_name'] . ' ' . $member_data['last_name']);
    }
} catch (PDOException $e) {
    // Keep default if database error
    error_log("Navigation member data error: " . $e->getMessage());
}

// Generate CSRF token for language switching
$csrf_token = generate_csrf_token();
?>

<style>
/* === DESKTOP SOFTWARE STYLE LAYOUT === */
.app-layout {
    display: flex;
    min-height: 100vh;
    background: var(--primary-bg);
}

/* === SIDEBAR (LEFT PANEL) === */
.app-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid var(--border-light);
    box-shadow: 2px 0 8px rgba(48, 25, 67, 0.05);
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 1000;
    transition: all 0.3s ease;
}

.app-sidebar.collapsed {
    width: 70px;
}

.app-sidebar.mobile-hidden {
    left: -280px;
}

/* === SIDEBAR HEADER === */
.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 70px;
}

.sidebar-logo {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    transition: all 0.3s ease;
}

.app-sidebar.collapsed .sidebar-logo {
    display: none;
}

.sidebar-toggle {
    width: 32px;
    height: 32px;
    border: none;
    background: var(--secondary-bg);
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    background: var(--color-teal);
    color: white;
}

.app-sidebar.collapsed .sidebar-toggle {
    margin: 0 auto;
}

/* Hide sidebar toggle on mobile - only show hamburger menu */
@media (max-width: 1024px) {
    .sidebar-toggle {
        display: none !important;
    }
}

/* === SIDEBAR NAVIGATION === */
.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: hidden;
}

.nav-section {
    margin-bottom: 24px;
}

.nav-section-title {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    padding: 0 20px;
    transition: all 0.3s ease;
}

.app-sidebar.collapsed .nav-section-title {
    display: none;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    margin: 2px 8px;
    border-radius: 8px;
}

.nav-item:hover {
    background: var(--secondary-bg);
    color: var(--color-teal);
    text-decoration: none;
}

.nav-item.active {
    background: var(--color-teal);
    color: white;
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--color-gold);
    border-radius: 0 2px 2px 0;
}

.nav-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.nav-text {
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s ease;
}

.app-sidebar.collapsed .nav-text {
    display: none;
}

.app-sidebar.collapsed .nav-item {
    justify-content: center;
    padding: 12px 10px;
    margin: 2px 8px;
}

.nav-badge {
    background: var(--color-coral);
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
    transition: all 0.3s ease;
}

.app-sidebar.collapsed .nav-badge {
    display: none;
}

/* === MAIN CONTENT AREA === */
.app-main {
    flex: 1;
    margin-left: 280px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    min-height: 100vh;
}

.app-main.expanded {
    margin-left: 70px;
}

.app-main.mobile-full {
    margin-left: 0;
}

/* === TOP BAR === */
.app-topbar {
    background: white;
    border-bottom: 1px solid var(--border-light);
    padding: 0 24px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(48, 25, 67, 0.05);
    position: sticky;
    top: 0;
    z-index: 999;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.mobile-menu-btn {
    display: none;
    width: 40px;
    height: 40px;
    border: none;
    background: var(--secondary-bg);
    border-radius: 8px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 3px;
}

.hamburger-line {
    width: 18px;
    height: 2px;
    background: var(--text-primary);
    border-radius: 1px;
    transition: all 0.3s ease;
}

.mobile-menu-btn.active .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(4px, 4px);
}

.mobile-menu-btn.active .hamburger-line:nth-child(2) {
    opacity: 0;
}

.mobile-menu-btn.active .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(4px, -4px);
}

.page-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* === LANGUAGE MENU === */
.language-menu {
    position: relative;
}

.language-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--secondary-bg);
    border: 1px solid var(--border-light);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    color: var(--text-primary);
}

.language-toggle:hover {
    background: var(--color-teal);
    color: white;
    border-color: var(--color-teal);
}

.current-lang {
    font-weight: 600;
    font-size: 12px;
    min-width: 24px;
    text-align: center;
}

.language-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    box-shadow: var(--shadow-medium);
    min-width: 140px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 8px;
}

.language-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.language-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    color: var(--text-primary);
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.language-option:hover {
    background: var(--secondary-bg);
    color: var(--color-teal);
}

/* === USER MENU === */
.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: var(--secondary-bg);
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.user-menu:hover {
    background: var(--color-teal);
    color: white;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--color-teal);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
    line-height: 1;
}

.user-role {
    font-size: 11px;
    opacity: 0.7;
    line-height: 1;
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid var(--border-light);
    border-radius: 8px;
    box-shadow: var(--shadow-medium);
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 8px;
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s ease;
    border-bottom: 1px solid var(--border-light);
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: var(--secondary-bg);
    color: var(--color-teal);
    text-decoration: none;
}

/* === MOBILE OVERLAY === */
.mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-overlay.active {
    opacity: 1;
    visibility: visible;
}

.logout-item {
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.logout-item:hover {
    background: var(--secondary-bg);
    color: #dc2626;
    text-decoration: none;
}

/* === RESPONSIVE DESIGN === */
@media (max-width: 1024px) {
    .app-sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0 !important;
        height: 100vh !important;
        width: 280px !important;
        z-index: 1100 !important;
        transition: left 0.3s ease-in-out !important;
        box-shadow: 8px 0 32px rgba(0, 0, 0, 0.3) !important;
        background: white !important;
    }
    
    /* Force sidebar to full width on mobile (never collapsed) */
    .app-sidebar.collapsed {
        width: 280px !important;
    }
    
    .app-sidebar.mobile-visible {
        left: 0 !important;
        z-index: 1110 !important;
        background: #ffffff !important;
        display: flex !important;
        visibility: visible !important;
    }
    
    /* Force mobile sidebar to show all nav elements */
    .app-sidebar.mobile-visible .nav-text {
        display: block !important;
    }
    
    .app-sidebar.mobile-visible .nav-section-title {
        display: block !important;
    }
    
    .app-sidebar.mobile-visible .sidebar-logo {
        display: block !important;
    }
    
    .app-sidebar.mobile-visible .nav-text {
        display: block !important;
    }
    
    .app-main {
        margin-left: 0 !important;
        transition: none !important;
    }
    
    .mobile-menu-btn {
        display: flex !important;
    }
    
    /* Hide user menu on mobile - profile moved to sidebar */
    .user-menu {
        display: none !important;
    }
    
    .page-title {
        font-size: 18px;
        font-weight: 600;
    }
    
    .mobile-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 1050 !important;
        background: rgba(0, 0, 0, 0.6) !important;
        transition: opacity 0.3s ease-in-out !important;
        opacity: 0 !important;
        visibility: hidden !important;
    }
    
    .mobile-overlay.active {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Clean sidebar content styling */
    .app-sidebar .sidebar-header {
        background: white !important;
        color: var(--text-primary) !important;
        border-bottom: 1px solid var(--border-light);
        justify-content: center !important;
    }
    
    .app-sidebar .sidebar-nav {
        background: white !important;
        color: var(--text-primary) !important;
    }
    
    /* Enhanced mobile typography */
    .topbar-left .page-title {
        font-size: 18px;
        font-weight: 600;
        color: #000000;
    }
    
    .hamburger-line {
        background: #000000 !important;
    }
}

@media (max-width: 768px) {
    .topbar-right {
        gap: 8px;
    }
    
    .page-title {
        font-size: 16px;
        font-weight: 600;
        color: #000000 !important;
    }
    
    .language-toggle {
        padding: 8px 12px;
        font-size: 13px;
        background: #F1ECE2;
        border: 1px solid #DAA520;
        color: #301934;
    }
    
    .language-toggle:hover {
        background: #DAA520;
        color: white;
        border-color: #DAA520;
    }
    
    .current-lang {
        font-size: 12px;
        min-width: 22px;
        font-weight: 600;
    }
    
    .language-dropdown {
        min-width: 120px;
        right: -10px;
    }
    
         /* Enhanced mobile sidebar */
     .sidebar-nav {
         padding-bottom: 20px; /* Normal spacing */
     }
    
    .nav-item {
        padding: 14px 20px;
        margin: 3px 8px;
    }
    
    .nav-text {
        font-size: 15px;
        font-weight: 500;
        color: #000000 !important;
    }
    
    .nav-section-title {
        font-size: 12px;
        font-weight: 600;
        color: #301934 !important;
        margin-bottom: 10px;
    }
    
    .nav-icon {
        width: 22px;
        height: 22px;
        stroke: #301934 !important;
    }
    
    .nav-item.active .nav-icon {
        stroke: white !important;
    }
    
    .nav-item.active .nav-text {
        color: white !important;
    }
}

@media (max-width: 576px) {
    .app-topbar {
        padding: 0 12px;
        height: 60px;
    }
    
    .page-title {
        font-size: 15px;
        font-weight: 600;
    }
    
    .language-toggle {
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .current-lang {
        font-size: 11px;
        min-width: 20px;
    }
    
    .mobile-menu-btn {
        width: 36px;
        height: 36px;
    }
    
    .hamburger-line {
        width: 16px;
    }
}

/* === PRELOADER === */
.preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
}

.preloader.hidden {
    opacity: 0;
    visibility: hidden;
}

.preloader-svg {
    width: 100px;
    height: 100px;
}

/* === APP CONTENT === */
.app-content {
    flex: 1;
    padding: 32px;
    background: var(--primary-bg);
}

@media (max-width: 768px) {
    .app-content {
        padding: 20px 16px;
    }
    
    .app-topbar {
        padding: 0 16px;
    }
}
</style>

<!-- Preloader -->
<div class="preloader" id="preloader">
    <div class="preloader-svg">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" width="100%" height="100%" style="shape-rendering: auto; display: block; background: transparent;" xmlns:xlink="http://www.w3.org/1999/xlink">
            <g>
                <circle stroke-width="25" stroke="#E9C46A" fill="none" r="0" cy="50" cx="50">
                    <animate begin="0s" calcMode="spline" keySplines="0 0.2 0.8 1" keyTimes="0;1" values="0;44" dur="2s" repeatCount="indefinite" attributeName="r"></animate>
                    <animate begin="0s" calcMode="spline" keySplines="0.2 0 0.8 1" keyTimes="0;1" values="1;0" dur="2s" repeatCount="indefinite" attributeName="opacity"></animate>
                </circle>
                <circle stroke-width="25" stroke="#E76F51" fill="none" r="0" cy="50" cx="50">
                    <animate begin="-1s" calcMode="spline" keySplines="0 0.2 0.8 1" keyTimes="0;1" values="0;44" dur="2s" repeatCount="indefinite" attributeName="r"></animate>
                    <animate begin="-1s" calcMode="spline" keySplines="0.2 0 0.8 1" keyTimes="0;1" values="1;0" dur="2s" repeatCount="indefinite" attributeName="opacity"></animate>
                </circle>
            </g>
        </svg>
    </div>
</div>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">HabeshaEqub</div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12h18M3 6h18M3 18h18"/>
            </svg>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Overview Section -->
        <div class="nav-section">
            <div class="nav-section-title"><?php echo t('member_nav.overview'); ?></div>
            <a href="dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <span class="nav-text"><?php echo t('member_nav.dashboard'); ?></span>
            </a>
        </div>
        
        <!-- Financial Section -->
        <div class="nav-section">
            <div class="nav-section-title"><?php echo t('member_nav.financial'); ?></div>
            <a href="contributions.php" class="nav-item <?php echo ($current_page === 'contributions.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <span class="nav-text"><?php echo t('member_nav.contributions'); ?></span>
            </a>
            <a href="payout-info.php" class="nav-item <?php echo ($current_page === 'payout-info.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M16 12l-4-4-4 4"/>
                    <path d="M12 16V8"/>
                </svg>
                <span class="nav-text"><?php echo t('member_nav.payout_info'); ?></span>
            </a>
        </div>
        
                 <!-- Account Section -->
         <div class="nav-section">
             <div class="nav-section-title"><?php echo t('member_nav.account'); ?></div>
             <a href="members.php" class="nav-item <?php echo ($current_page === 'members.php') ? 'active' : ''; ?>">
                 <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                     <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                     <circle cx="9" cy="7" r="4"/>
                     <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                     <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                 </svg>
                 <span class="nav-text"><?php echo t('member_nav.equb_members'); ?></span>
             </a>
             <a href="profile.php" class="nav-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                 <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                     <circle cx="12" cy="12" r="3"/>
                     <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 -1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                 </svg>
                 <span class="nav-text"><?php echo t('member_nav.my_profile'); ?></span>
             </a>
             <button class="nav-item logout-item" onclick="handleLogout()">
                 <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                     <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                     <polyline points="16,17 21,12 16,7"/>
                     <line x1="21" y1="12" x2="9" y2="12"/>
                 </svg>
                 <span class="nav-text"><?php echo t('member_nav.logout'); ?></span>
             </button>
         </div>
        
        <!-- Support Section -->
        <div class="nav-section">
            <div class="nav-section-title"><?php echo t('member_nav.support'); ?></div>
            <a href="tel:07360436171" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <point x="12" y="17"/>
                </svg>
                <span class="nav-text"><?php echo t('member_nav.help'); ?></span>
            </a>
            <a href="tel:07360436171" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <span class="nav-text"><?php echo t('member_nav.contact'); ?></span>
            </a>
        </div>
         </nav>
</aside>

<!-- Main Content Area -->
<main class="app-main" id="appMain">
    <!-- Top Bar -->
    <header class="app-topbar">
        <div class="topbar-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            <h1 class="page-title" id="pageTitle">
                <?php 
                $page_titles = [
                    'dashboard.php' => t('member_nav.dashboard'),
                    'contributions.php' => t('member_nav.contributions'),
                    'payout-info.php' => t('member_nav.payout_info'),
                    'members.php' => t('member_nav.equb_members'),
                    'member-profile.php' => t('navigation.member_profile'),
                    'profile.php' => t('member_nav.profile')
                ];
                echo $page_titles[$current_page] ?? 'HabeshaEqub Member';
                ?>
            </h1>
        </div>
        
        <div class="topbar-right">
            <!-- Language Selector -->
            <div class="language-menu" id="languageMenu">
                <button class="language-toggle" id="languageToggle">
                    <i class="fas fa-globe"></i>
                    <span class="current-lang"><?php echo getCurrentLanguage() === 'am' ? 'አማ' : 'EN'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                
                <div class="language-dropdown" id="languageDropdown">
                    <button class="dropdown-item language-option" data-lang="en">
                        <i class="fas fa-flag"></i>
                        English
                    </button>
                    <button class="dropdown-item language-option" data-lang="am">
                        <i class="fas fa-flag"></i>
                        አማረኛ
                    </button>
                </div>
            </div>
            
            <div class="user-menu" id="userMenu">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($member_name, 0, 2)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($member_name); ?></div>
                    <div class="user-role"><?php echo t('member_nav.member'); ?></div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
                
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo t('member_nav.my_profile'); ?>
                    </a>
                    <a href="members.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo t('member_nav.equb_members'); ?>
                    </a>
                    <button class="dropdown-item logout" onclick="handleLogout()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16,17 21,12 16,7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <?php echo t('member_nav.logout'); ?>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Content Area -->
    <div class="app-content" id="appContent">

<script>
// Desktop Software Style Navigation System (Same as Admin)
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('appSidebar');
    const mainContent = document.getElementById('appMain');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const userMenu = document.getElementById('userMenu');
    const userDropdown = document.getElementById('userDropdown');
    const languageMenu = document.getElementById('languageMenu');
    const languageToggle = document.getElementById('languageToggle');
    const languageDropdown = document.getElementById('languageDropdown');
    const preloader = document.getElementById('preloader');

    // Restore sidebar state for desktop (default to collapsed if not set)
    let isCollapsed = localStorage.getItem('memberSidebarCollapsed') === 'true';
    if (localStorage.getItem('memberSidebarCollapsed') === null) {
        isCollapsed = true; // Default to collapsed to prevent auto-opening
        localStorage.setItem('memberSidebarCollapsed', 'true');
    }
    if (isCollapsed && window.innerWidth > 1024) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }

    // Desktop sidebar toggle (only works on desktop)
    sidebarToggle.addEventListener('click', function() {
        // Only allow toggle on desktop
        if (window.innerWidth > 1024) {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            // Save state
            localStorage.setItem('memberSidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    });

    // Mobile menu toggle (no push, just slide over)
    mobileMenuBtn.addEventListener('click', function() {
        const isOpening = !sidebar.classList.contains('mobile-visible');

        sidebar.classList.toggle('mobile-visible');
        mobileOverlay.classList.toggle('active');
        this.classList.toggle('active');

        if (isOpening) {
            document.body.classList.add('no-scroll');
        } else {
            document.body.classList.remove('no-scroll');
        }
    });

    // Close mobile menu
    mobileOverlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-visible');
        mobileOverlay.classList.remove('active');
        mobileMenuBtn.classList.remove('active');
        document.body.classList.remove('no-scroll');
    });

    // User dropdown
    userMenu.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
        languageDropdown.classList.remove('active'); // Close language dropdown
    });

    // Language dropdown
    languageToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        languageDropdown.classList.toggle('active');
        userDropdown.classList.remove('active'); // Close user dropdown
    });

    // Language selection
    document.querySelectorAll('.language-option').forEach(option => {
        option.addEventListener('click', function() {
            const selectedLang = this.dataset.lang;
            changeLanguage(selectedLang);
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        userDropdown.classList.remove('active');
        languageDropdown.classList.remove('active');
    });

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('mobile-visible');
            mobileOverlay.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            document.body.classList.remove('no-scroll');
            userDropdown.classList.remove('active');
            languageDropdown.classList.remove('active');
        }
    });

    // Handle window resize (close mobile menu and remove no-scroll)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('mobile-visible');
            mobileOverlay.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            document.body.classList.remove('no-scroll');
        } else {
            // Ensure sidebar is hidden on mobile resize
            if (!sidebar.classList.contains('mobile-visible')) {
                sidebar.style.left = '-280px';
            }
        }
    });

    // Hide preloader once page is fully loaded
    function hidePreloader() {
        if (preloader) {
            preloader.classList.add('hidden');
            // Remove preloader from DOM after animation completes
            setTimeout(() => {
                if (preloader && preloader.parentNode) {
                    preloader.parentNode.removeChild(preloader);
                }
            }, 500);
        }
    }

    // Hide preloader when everything is loaded
    if (document.readyState === 'complete') {
        hidePreloader();
    } else {
        window.addEventListener('load', hidePreloader);
    }
});

// Language switching function
async function changeLanguage(language) {
    try {
        const response = await fetch('../languages/switch_language.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `language=${language}&csrf_token=<?php echo $csrf_token; ?>`
        });

        const data = await response.json();
        
        if (data.success) {
            // Reload page to apply new language
            window.location.reload();
        } else {
            console.error('Language switch failed:', data.message);
        }
    } catch (error) {
        console.error('Language switch error:', error);
    }
}

// Logout function
function handleLogout() {
    if (confirm('<?php echo t('member_nav.logout_confirm'); ?>')) {
        window.location.href = 'logout.php';
    }
}
</script> 