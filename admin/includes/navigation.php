<?php
/**
 * HabeshaEqub - Professional Desktop-Style Navigation
 * Modern sidebar + top bar layout like desktop software
 */

// Include required files
require_once '../includes/db.php';
require_once '../languages/translator.php';

// Get current page for active states
$current_page = basename($_SERVER['PHP_SELF']);
$admin_username = $_SESSION['admin_username'] ?? 'Admin';

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
    overflow-y: auto;
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
    border-bottom: 1px solid var(--border-light);
    transition: all 0.3s ease;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: var(--secondary-bg);
    color: var(--color-teal);
    text-decoration: none;
}

.dropdown-item.logout {
    color: var(--color-coral);
}

.dropdown-item.logout:hover {
    background: var(--color-coral);
    color: white;
}

/* === CONTENT AREA === */
.app-content {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
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
    
    .app-main {
        margin-left: 0 !important;
        transition: none !important;
    }
    
    .mobile-menu-btn {
        display: flex !important;
    }
    
    .user-info {
        display: none;
    }
    
    .page-title {
        font-size: 20px;
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
        /* Ensure header shows properly on mobile */
        justify-content: center !important;
    }
    
    .app-sidebar .sidebar-nav {
        background: white !important;
        color: var(--text-primary) !important;
    }
    
    .app-sidebar .nav-item {
        color: var(--text-primary) !important;
        background: transparent !important;
        /* Full nav items on mobile */
        padding: 12px 20px !important;
        justify-content: flex-start !important;
    }
    
    .app-sidebar .nav-item:hover {
        background: var(--secondary-bg) !important;
        color: var(--color-teal) !important;
    }
    
    .app-sidebar .nav-item.active {
        background: var(--color-teal) !important;
        color: white !important;
    }
    
    .app-sidebar .sidebar-logo {
        color: var(--text-primary) !important;
        font-weight: 700 !important;
    }
    
    .app-sidebar .nav-text {
        color: inherit !important;
        font-weight: 500 !important;
    }
    
    .app-sidebar .nav-section-title {
        color: var(--text-secondary) !important;
    }
}

@media (max-width: 768px) {
    .app-content {
        padding: 16px;
    }
    
    .app-topbar {
        padding: 0 16px;
        height: 60px;
    }
    
    .page-title {
        font-size: 18px;
    }
    
    .topbar-left {
        gap: 12px;
    }
    
    .user-avatar {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    
    .mobile-menu-btn {
        width: 36px;
        height: 36px;
    }
    
    .hamburger-line {
        width: 16px;
    }
}

@media (max-width: 480px) {
    .app-topbar {
        padding: 0 12px;
    }
    
    .app-content {
        padding: 12px;
    }
    
    .page-title {
        font-size: 16px;
    }
}

/* === SCROLL BARS === */
.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: var(--border-light);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}

/* Add this for body no-scroll */
body.no-scroll {
  overflow: hidden !important;
  height: 100vh !important;
  touch-action: none !important; /* Prevent touch scrolling on mobile */
}

/* Enhance mobile off-canvas to slide-over (no push) */
@media (max-width: 1024px) {
  .app-main {
    transition: transform 0.3s ease !important;
    will-change: transform; /* Optimize performance */
  }

  .app-main.mobile-pushed {
    transform: translateX(280px) !important;
    box-shadow: -4px 0 16px rgba(0,0,0,0.2) !important; /* Add shadow for depth */
  }

  .app-main.mobile-pushed .app-content {
    padding: 0 !important; /* Remove all padding to eliminate extra space */
  }

  .app-sidebar {
    transition: left 0.3s ease !important;
    width: 280px !important;
    box-shadow: 4px 0 16px rgba(0,0,0,0.2) !important;
  }

  .mobile-overlay {
    transition: opacity 0.3s ease !important;
    background: rgba(0,0,0,0.6) !important; /* Higher opacity for better dimming */
  }

  .app-content {
    padding: 0 12px !important; /* Reduce padding to eliminate space */
    transition: padding 0.3s ease; /* Smooth padding change */
  }
}

/* === SIDEBAR LANGUAGE SELECTOR === */
.sidebar-language {
    position: relative;
    margin: 8px 0;
}

.sidebar-language .language-btn {
    background: transparent;
    border: none;
    width: 100%;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
    padding: 12px 16px;
    border-radius: 8px;
    position: relative;
    justify-content: space-between;
}

.sidebar-language .language-btn:hover {
    background: var(--nav-hover-bg);
    color: var(--color-gold);
    transform: translateX(4px);
}

.sidebar-language .language-btn.open {
    background: var(--nav-active-bg);
    color: var(--color-gold);
}

.sidebar-language .language-btn::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--color-gold);
    border-radius: 0 2px 2px 0;
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.sidebar-language .language-btn:hover::before,
.sidebar-language .language-btn.open::before {
    transform: scaleY(1);
}

.sidebar-language .lang-icon {
    font-size: 16px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: inherit;
}

.sidebar-language .nav-text {
    flex: 1;
    text-align: left;
}

.sidebar-language .current-lang {
    font-size: 12px;
    opacity: 0.8;
    font-weight: 600;
}

.sidebar-language .dropdown-arrow {
    font-size: 10px;
    transition: transform 0.3s ease;
    opacity: 0.7;
    color: inherit;
}

.sidebar-language .language-btn.open .dropdown-arrow {
    transform: rotate(180deg);
}

.sidebar-language .language-menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: var(--surface-bg);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(38, 70, 83, 0.15);
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    overflow: hidden;
}

.sidebar-language .language-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.sidebar-language .language-option {
    width: 100%;
    background: none;
    border: none;
    padding: 12px 16px;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.sidebar-language .language-option:hover {
    background: var(--nav-hover-bg);
    color: var(--color-gold);
}

.sidebar-language .language-option.active {
    background: var(--nav-active-bg);
    color: var(--color-gold);
    font-weight: 600;
}

.sidebar-language .language-option .flag {
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.sidebar-language .language-option .lang-text {
    flex: 1;
    font-size: 13px;
}

.sidebar-language .language-option .lang-code {
    font-size: 11px;
    font-weight: 600;
    opacity: 0.7;
    background: rgba(233, 196, 106, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
}

.sidebar-language .language-option:hover .lang-code,
.sidebar-language .language-option.active .lang-code {
    background: rgba(233, 196, 106, 0.2);
    opacity: 1;
}

.sidebar-language .language-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Mobile sidebar language selector */
@media (max-width: 768px) {
    .sidebar-language .language-btn {
        padding: 10px 12px;
        gap: 10px;
    }
    
    .sidebar-language .lang-icon {
        font-size: 16px;
    }
    
    .sidebar-language .current-lang {
        font-size: 11px;
    }
    
    .sidebar-language .language-option {
        padding: 10px 12px;
        gap: 8px;
    }
    
    .sidebar-language .language-option .flag {
        font-size: 14px;
    }
    
    .sidebar-language .language-option .lang-text {
        font-size: 12px;
  }
}
</style>

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
        <!-- Main Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>
        
        <!-- Member Management -->
        <div class="nav-section">
            <div class="nav-section-title">Member Management</div>
            <a href="members.php" class="nav-item <?php echo ($current_page === 'members.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span class="nav-text">All Members</span>
            </a>
        </div>
        
        <!-- Financial Management -->
        <div class="nav-section">
            <div class="nav-section-title">Financial</div>
            <a href="payments.php" class="nav-item <?php echo ($current_page === 'payments.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <span class="nav-text">Payments</span>
            </a>
            <a href="payouts.php" class="nav-item <?php echo ($current_page === 'payouts.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M16 12l-4-4-4 4"/>
                    <path d="M12 16V8"/>
                </svg>
                <span class="nav-text">Payouts</span>
            </a>
        </div>
        
        <!-- Communication -->
        <div class="nav-section">
            <div class="nav-section-title">Communication</div>
            <a href="notifications.php" class="nav-item <?php echo ($current_page === 'notifications.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="nav-text">Notifications</span>
            </a>
        </div>
        
        <!-- System -->
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <a href="rules.php" class="nav-item <?php echo ($current_page === 'rules.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10,9 9,9 8,9"/>
                </svg>
                <span class="nav-text">Equb Rules</span>
            </a>
            <a href="settings.php" class="nav-item <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <span class="nav-text">Settings</span>
            </a>
            
            <!-- Language Selector -->
            <div class="language-dropdown sidebar-language" data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">
                <button class="nav-item language-btn" id="languageBtn" title="Switch Language">
                    <i class="fas fa-language lang-icon"></i>
                    <span class="nav-text">Language <span class="current-lang">(<?php echo getCurrentLanguage() === 'en' ? 'EN' : 'አማ'; ?>)</span></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                
                <div class="language-menu" id="languageMenu">
                    <button class="language-option <?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>" 
                            data-lang="en">
                        <span class="flag">🇺🇸</span>
                        <span class="lang-text">English</span>
                        <span class="lang-code">EN</span>
                    </button>
                    <button class="language-option <?php echo getCurrentLanguage() === 'am' ? 'active' : ''; ?>" 
                            data-lang="am">
                        <span class="flag">🇪🇹</span>
                        <span class="lang-text">አማርኛ</span>
                        <span class="lang-code">አማ</span>
                    </button>
                </div>
            </div>
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
                    'dashboard.php' => t('navigation.dashboard'),
                    'members.php' => t('navigation.members'),
                    'member-profile.php' => t('navigation.member_profile'),
                    'payments.php' => t('navigation.payments'),
                    'payouts.php' => t('navigation.payouts'),
                    'reports.php' => t('navigation.reports'),
                    'profile.php' => t('navigation.profile'),
                    'notifications.php' => t('navigation.notifications'),
                    'rules.php' => t('navigation.rules'),
                    'settings.php' => t('navigation.settings')
                ];
                echo $page_titles[$current_page] ?? 'HabeshaEqub Admin';
                ?>
            </h1>
        </div>
        
        <div class="topbar-right">

            
            <div class="user-menu" id="userMenu">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_username, 0, 2)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($admin_username); ?></div>
                    <div class="user-role">Administrator</div>
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
                        Profile Settings
                    </a>
                    <button class="dropdown-item logout" onclick="handleLogout()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16,17 21,12 16,7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Content Area -->
    <div class="app-content" id="appContent">

<script>
// Desktop Software Style Navigation System
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('appSidebar');
    const mainContent = document.getElementById('appMain');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const userMenu = document.getElementById('userMenu');
    const userDropdown = document.getElementById('userDropdown');

    // Restore sidebar state for desktop (default to collapsed if not set)
    let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (localStorage.getItem('sidebarCollapsed') === null) {
        isCollapsed = true; // Default to collapsed to prevent auto-opening
        localStorage.setItem('sidebarCollapsed', 'true');
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
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    });

    // Mobile menu toggle (no push, just slide over)
    mobileMenuBtn.addEventListener('click', function() {
        const isOpening = !sidebar.classList.contains('mobile-visible');

        sidebar.classList.toggle('mobile-visible');
        mobileOverlay.classList.toggle('active');
        this.classList.toggle('active');
        // Removed: mainContent.classList.toggle('mobile-pushed');

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
        // Removed: mainContent.classList.remove('mobile-pushed');
        document.body.classList.remove('no-scroll');
    });

    // User dropdown
    userMenu.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        userDropdown.classList.remove('active');
    });

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('mobile-visible');
            mobileOverlay.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            // Removed: mainContent.classList.remove('mobile-pushed');
            document.body.classList.remove('no-scroll');
            userDropdown.classList.remove('active');
        }
    });

    // Handle window resize (close mobile menu and remove no-scroll)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('mobile-visible');
            mobileOverlay.classList.remove('active');
            mobileMenuBtn.classList.remove('active');
            // Removed: mainContent.classList.remove('mobile-pushed');
            document.body.classList.remove('no-scroll');
        } else {
            // Ensure sidebar is hidden on mobile resize
            if (!sidebar.classList.contains('mobile-visible')) {
                sidebar.style.left = '-280px';
            }
        }
    });
});

// Logout function (unchanged)
async function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=logout'
            });

            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'login.php';
            } else {
                alert('Logout failed. Please try again.');
            }
        } catch (error) {
            console.error('Logout error:', error);
            alert('An error occurred during logout.');
        }
    }
}

// Language Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    initLanguageToggle();
});

function initLanguageToggle() {
    const languageBtn = document.getElementById('languageBtn');
    const languageMenu = document.getElementById('languageMenu');
    const languageOptions = document.querySelectorAll('.language-option');
    
    if (!languageBtn || !languageMenu) return;
    
    // Toggle dropdown open/close
    languageBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = languageMenu.classList.contains('show');
        
        if (isOpen) {
            closeLanguageDropdown();
        } else {
            openLanguageDropdown();
        }
    });
    
    // Handle language option clicks
    languageOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();
            const newLang = this.dataset.lang;
            
            // Don't switch if already active
            if (this.classList.contains('active')) return;
            
            // Show loading state
            showLanguageLoading(this);
            
            // Switch language via AJAX
            switchLanguage(newLang);
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sidebar-language')) {
            closeLanguageDropdown();
        }
    });
    
    // Close dropdown on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLanguageDropdown();
        }
    });
}

function openLanguageDropdown() {
    const languageBtn = document.getElementById('languageBtn');
    const languageMenu = document.getElementById('languageMenu');
    
    languageBtn.classList.add('open');
    languageMenu.classList.add('show');
}

function closeLanguageDropdown() {
    const languageBtn = document.getElementById('languageBtn');
    const languageMenu = document.getElementById('languageMenu');
    
    languageBtn.classList.remove('open');
    languageMenu.classList.remove('show');
}

function showLanguageLoading(option) {
    const languageOptions = document.querySelectorAll('.language-option');
    
    // Disable all options
    languageOptions.forEach(opt => opt.disabled = true);
    
    // Show loading in clicked option
    option.innerHTML = `
        <span class="flag">⏳</span>
        <span class="lang-text">Loading...</span>
        <span class="lang-code">...</span>
    `;
    
    // Close dropdown
    closeLanguageDropdown();
}

// Switch language and reload page
function switchLanguage(language) {
    // Get CSRF token from language dropdown container
    const languageDropdown = document.querySelector('.sidebar-language');
    const csrfToken = languageDropdown ? languageDropdown.dataset.csrfToken : '';
    
    if (!csrfToken) {
        console.error('CSRF token not found');
        alert('Security token missing. Please refresh the page.');
        resetLanguageDropdown();
        return;
    }
    
    const formData = new FormData();
    formData.append('language', language);
    formData.append('csrf_token', csrfToken);
    
    fetch('api/language.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Language switched successfully, reload page
            console.log('Language switched to:', data.current_language);
            window.location.reload();
        } else {
            console.error('Language switch failed:', data.message);
            alert(data.message || 'Failed to switch language');
            
            // Reset dropdown
            resetLanguageDropdown();
        }
    })
    .catch(error => {
        console.error('Language switch error:', error);
        alert('Network error occurred while switching language');
        
        // Reset dropdown
        resetLanguageDropdown();
    });
}

function resetLanguageDropdown() {
    const languageOptions = document.querySelectorAll('.language-option');
    
    languageOptions.forEach(option => {
        option.disabled = false;
        
        // Reset content based on language
        const lang = option.dataset.lang;
        if (lang === 'en') {
            option.innerHTML = `
                <span class="flag">🇺🇸</span>
                <span class="lang-text">English</span>
                <span class="lang-code">EN</span>
            `;
        } else if (lang === 'am') {
            option.innerHTML = `
                <span class="flag">🇪🇹</span>
                <span class="lang-text">አማርኛ</span>
                <span class="lang-code">አማ</span>
            `;
        }
    });
    
    // Ensure dropdown is closed
    closeLanguageDropdown();
}
</script> 