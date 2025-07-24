<?php
/**
 * HabeshaEqub - Reports API
 * Handle reports data and export functionality
 */

require_once '../../includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF token verification for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'get_data') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid security token. Please refresh the page and try again.'
        ]);
        exit;
    }
}

try {
    switch ($action) {
        case 'get_chart_data':
            getChartData();
            break;
        case 'get_financial_summary':
            getFinancialSummary();
            break;
        case 'get_member_analytics':
            getMemberAnalytics();
            break;
        case 'export_pdf':
            exportPDF();
            break;
        case 'export_excel':
            exportExcel();
            break;
        case 'get_performance_metrics':
            getPerformanceMetrics();
            break;
        case 'get_csrf_token':
            echo json_encode([
                'success' => true, 
                'csrf_token' => generate_csrf_token()
            ]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred processing your request']);
}

/**
 * Get chart data for various visualizations
 */
function getChartData() {
    global $pdo;
    
    $chart_type = $_GET['type'] ?? '';
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    switch ($chart_type) {
        case 'monthly_collections':
            $stmt = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM payments 
                WHERE payment_date BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute([$start_date, $end_date]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'payment_status':
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM payments 
                WHERE payment_date BETWEEN ? AND ?
                GROUP BY status
            ");
            $stmt->execute([$start_date, $end_date]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'member_growth':
            $stmt = $pdo->prepare("
                SELECT 
                    DATE_FORMAT(date_joined, '%Y-%m') as month,
                    COUNT(*) as new_members
                FROM members 
                WHERE date_joined BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(date_joined, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute([$start_date, $end_date]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'payout_distribution':
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_amount) as total_amount,
                    AVG(total_amount) as avg_amount
                FROM payouts 
                WHERE scheduled_date BETWEEN ? AND ?
                GROUP BY status
            ");
            $stmt->execute([$start_date, $end_date]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid chart type']);
            return;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Get comprehensive financial summary
 */
function getFinancialSummary() {
    global $pdo;
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    // Overall financial metrics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payments,
            SUM(amount) as total_collected,
            AVG(amount) as avg_payment,
            MIN(amount) as min_payment,
            MAX(amount) as max_payment,
            COUNT(DISTINCT member_id) as paying_members
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $financial_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Monthly trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            SUM(amount) as monthly_total,
            COUNT(*) as monthly_count,
            COUNT(DISTINCT member_id) as unique_payers
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute([$start_date, $end_date]);
    $monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment method analysis
    $stmt = $pdo->prepare("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'financial_summary' => $financial_summary,
        'monthly_trends' => $monthly_trends,
        'payment_methods' => $payment_methods
    ]);
}

/**
 * Get member analytics and insights
 */
function getMemberAnalytics() {
    global $pdo;
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    // Member status overview
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_members,
            COUNT(CASE WHEN has_received_payout = 1 THEN 1 END) as members_with_payouts,
            AVG(monthly_payment) as avg_monthly_payment
        FROM members
    ");
    $member_overview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Member activity analysis
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.first_name,
            m.last_name,
            m.member_id,
            m.monthly_payment,
            COUNT(p.id) as payment_count,
            SUM(p.amount) as total_contributed,
            MAX(p.payment_date) as last_payment_date,
            CASE 
                WHEN COUNT(p.id) = 0 THEN 'No Payments'
                WHEN MAX(p.payment_date) < DATE_SUB(CURDATE(), INTERVAL 2 MONTH) THEN 'Inactive'
                WHEN COUNT(p.id) >= 10 THEN 'High Activity'
                WHEN COUNT(p.id) >= 5 THEN 'Medium Activity'
                ELSE 'Low Activity'
            END as activity_level
        FROM members m
        LEFT JOIN payments p ON m.id = p.member_id 
            AND p.payment_date BETWEEN ? AND ?
        GROUP BY m.id
        ORDER BY total_contributed DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $member_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Registration trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_joined, '%Y-%m') as month,
            COUNT(*) as new_registrations
        FROM members 
        WHERE date_joined BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date_joined, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $registration_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'member_overview' => $member_overview,
        'member_activity' => $member_activity,
        'registration_trends' => $registration_trends
    ]);
}

/**
 * Get performance metrics and KPIs
 */
function getPerformanceMetrics() {
    global $pdo;
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    // Collection rate metrics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT m.id) as total_active_members,
            COUNT(DISTINCT p.member_id) as paying_members,
            ROUND((COUNT(DISTINCT p.member_id) / COUNT(DISTINCT m.id)) * 100, 2) as collection_rate
        FROM members m
        LEFT JOIN payments p ON m.id = p.member_id 
            AND p.payment_date BETWEEN ? AND ?
        WHERE m.is_active = 1
    ");
    $stmt->execute([$start_date, $end_date]);
    $collection_metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Average time between payments
    $stmt = $pdo->prepare("
        SELECT 
            member_id,
            AVG(DATEDIFF(payment_date, LAG(payment_date) OVER (PARTITION BY member_id ORDER BY payment_date))) as avg_payment_interval
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
        GROUP BY member_id
    ");
    $stmt->execute([$start_date, $end_date]);
    $payment_intervals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payout efficiency
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payouts,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payouts,
            AVG(DATEDIFF(actual_payout_date, scheduled_date)) as avg_processing_days
        FROM payouts 
        WHERE scheduled_date BETWEEN ? AND ?
        AND status = 'completed'
    ");
    $stmt->execute([$start_date, $end_date]);
    $payout_efficiency = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'collection_metrics' => $collection_metrics,
        'payment_intervals' => $payment_intervals,
        'payout_efficiency' => $payout_efficiency
    ]);
}

/**
 * Export report as PDF
 */
function exportPDF() {
    global $admin_id;
    
    $start_date = $_POST['start_date'] ?? '2024-01-01';
    $end_date = $_POST['end_date'] ?? date('Y-m-d');
    
    // Generate secure token for export
    $export_token = generate_csrf_token();
    
    // Create export URL with parameters
    $export_url = 'export.php?' . http_build_query([
        'format' => 'pdf',
        'start_date' => $start_date,
        'end_date' => $end_date,
        'token' => $export_token
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Generating PDF report...',
        'redirect_url' => $export_url,
        'parameters' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'format' => 'pdf'
        ]
    ]);
}

/**
 * Export report as Excel/CSV
 */
function exportExcel() {
    global $admin_id;
    
    $start_date = $_POST['start_date'] ?? '2024-01-01';
    $end_date = $_POST['end_date'] ?? date('Y-m-d');
    
    // Generate secure token for export
    $export_token = generate_csrf_token();
    
    // Create export URL with parameters  
    $export_url = 'export.php?' . http_build_query([
        'format' => 'csv',
        'start_date' => $start_date,
        'end_date' => $end_date,
        'token' => $export_token
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Generating Excel report...',
        'redirect_url' => $export_url,
        'parameters' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'format' => 'csv'
        ]
    ]);
}

/**
 * Helper function to format currency
 */
function formatCurrency($amount) {
    return '£' . number_format($amount, 2);
}

/**
 * Helper function to calculate percentage change
 */
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
}
?> 