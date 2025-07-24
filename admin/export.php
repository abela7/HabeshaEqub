<?php
/**
 * HabeshaEqub - Export Handler
 * Generate PDF and CSV reports for analytics data
 */

require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Get export parameters
$format = $_GET['format'] ?? 'pdf';
$start_date = $_GET['start_date'] ?? '2024-01-01';
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Verify CSRF token for security
if (!isset($_GET['token']) || !verify_csrf_token($_GET['token'])) {
    die('Invalid security token');
}

try {
    // Fetch all data for export
    $data = fetchReportData($start_date, $end_date);
    
    if ($format === 'pdf') {
        generatePDFReport($data, $start_date, $end_date);
    } elseif ($format === 'csv' || $format === 'excel') {
        generateCSVReport($data, $start_date, $end_date);
    } else {
        die('Invalid export format');
    }
    
} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage());
    die('Error generating report: ' . $e->getMessage());
}

/**
 * Fetch all report data
 */
function fetchReportData($start_date, $end_date) {
    global $pdo;
    
    $data = [];
    
    // Financial summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payments,
            SUM(amount) as total_collected,
            AVG(amount) as avg_payment,
            MIN(amount) as min_payment,
            MAX(amount) as max_payment
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $data['financial_summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Monthly collections
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            COUNT(*) as payment_count,
            SUM(amount) as total_amount
        FROM payments 
        WHERE payment_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $data['monthly_collections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment status breakdown
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
    $data['payment_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top contributors
    $stmt = $pdo->prepare("
        SELECT 
            m.first_name, 
            m.last_name, 
            m.member_id,
            COUNT(p.id) as payment_count,
            SUM(p.amount) as total_contributed,
            AVG(p.amount) as avg_payment
        FROM members m
        LEFT JOIN payments p ON m.id = p.member_id 
        WHERE p.payment_date BETWEEN ? AND ?
        GROUP BY m.id
        HAVING total_contributed > 0
        ORDER BY total_contributed DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $data['top_contributors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All payments detail
    $stmt = $pdo->prepare("
        SELECT 
            p.payment_id,
            m.first_name,
            m.last_name,
            m.member_id,
            p.amount,
            p.payment_date,
            p.status,
            p.payment_method
        FROM payments p
        LEFT JOIN members m ON p.member_id = m.id
        WHERE p.payment_date BETWEEN ? AND ?
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $data['payment_details'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payout summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payouts,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_distributed,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payouts,
            COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as pending_payouts
        FROM payouts 
        WHERE scheduled_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $data['payout_summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Member summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_members,
            COUNT(CASE WHEN has_received_payout = 1 THEN 1 END) as members_with_payouts
        FROM members
    ");
    $data['member_summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $data;
}

/**
 * Generate PDF Report using built-in PHP
 */
function generatePDFReport($data, $start_date, $end_date) {
    global $admin_username;
    
    // Start output buffering
    ob_start();
    
    // Generate HTML content for PDF
    $html = generateReportHTML($data, $start_date, $end_date, 'pdf');
    
    // Create PDF using DomPDF alternative or simple HTML to PDF
    // For now, we'll create a comprehensive HTML report that can be printed as PDF
    
    $filename = 'HabeshaEqub_Report_' . date('Y-m-d_H-i-s') . '.html';
    
    // Set headers for download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
    
    echo $html;
    exit;
}

/**
 * Generate CSV Report for Excel
 */
function generateCSVReport($data, $start_date, $end_date) {
    $filename = 'HabeshaEqub_Report_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
    
    // Create file handle
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report header
    fputcsv($output, ['HabeshaEqub Analytics Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['Period: ' . $start_date . ' to ' . $end_date]);
    fputcsv($output, ['']); // Empty row
    
    // Financial Summary
    fputcsv($output, ['FINANCIAL SUMMARY']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Payments', $data['financial_summary']['total_payments'] ?? 0]);
    fputcsv($output, ['Total Collected', '£' . number_format($data['financial_summary']['total_collected'] ?? 0, 2)]);
    fputcsv($output, ['Average Payment', '£' . number_format($data['financial_summary']['avg_payment'] ?? 0, 2)]);
    fputcsv($output, ['Minimum Payment', '£' . number_format($data['financial_summary']['min_payment'] ?? 0, 2)]);
    fputcsv($output, ['Maximum Payment', '£' . number_format($data['financial_summary']['max_payment'] ?? 0, 2)]);
    fputcsv($output, ['']); // Empty row
    
    // Monthly Collections
    fputcsv($output, ['MONTHLY COLLECTIONS']);
    fputcsv($output, ['Month', 'Payment Count', 'Total Amount']);
    foreach ($data['monthly_collections'] as $month) {
        fputcsv($output, [
            $month['month'],
            $month['payment_count'],
            '£' . number_format($month['total_amount'], 2)
        ]);
    }
    fputcsv($output, ['']); // Empty row
    
    // Payment Status
    fputcsv($output, ['PAYMENT STATUS BREAKDOWN']);
    fputcsv($output, ['Status', 'Count', 'Total Amount']);
    foreach ($data['payment_status'] as $status) {
        fputcsv($output, [
            ucfirst($status['status']),
            $status['count'],
            '£' . number_format($status['total_amount'], 2)
        ]);
    }
    fputcsv($output, ['']); // Empty row
    
    // Top Contributors
    fputcsv($output, ['TOP CONTRIBUTORS']);
    fputcsv($output, ['Member ID', 'Name', 'Payment Count', 'Total Contributed', 'Average Payment']);
    foreach ($data['top_contributors'] as $contributor) {
        fputcsv($output, [
            $contributor['member_id'],
            $contributor['first_name'] . ' ' . $contributor['last_name'],
            $contributor['payment_count'],
            '£' . number_format($contributor['total_contributed'], 2),
            '£' . number_format($contributor['avg_payment'], 2)
        ]);
    }
    fputcsv($output, ['']); // Empty row
    
    // Payment Details
    fputcsv($output, ['PAYMENT DETAILS']);
    fputcsv($output, ['Payment ID', 'Member ID', 'Member Name', 'Amount', 'Date', 'Status', 'Method']);
    foreach ($data['payment_details'] as $payment) {
        fputcsv($output, [
            $payment['payment_id'],
            $payment['member_id'],
            $payment['first_name'] . ' ' . $payment['last_name'],
            '£' . number_format($payment['amount'], 2),
            $payment['payment_date'],
            ucfirst($payment['status']),
            ucfirst(str_replace('_', ' ', $payment['payment_method']))
        ]);
    }
    
    // Member Summary
    fputcsv($output, ['']); // Empty row
    fputcsv($output, ['MEMBER SUMMARY']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Members', $data['member_summary']['total_members'] ?? 0]);
    fputcsv($output, ['Active Members', $data['member_summary']['active_members'] ?? 0]);
    fputcsv($output, ['Members with Payouts', $data['member_summary']['members_with_payouts'] ?? 0]);
    
    // Payout Summary
    fputcsv($output, ['']); // Empty row
    fputcsv($output, ['PAYOUT SUMMARY']);
    fputcsv($output, ['Total Payouts', $data['payout_summary']['total_payouts'] ?? 0]);
    fputcsv($output, ['Total Distributed', '£' . number_format($data['payout_summary']['total_distributed'] ?? 0, 2)]);
    fputcsv($output, ['Completed Payouts', $data['payout_summary']['completed_payouts'] ?? 0]);
    fputcsv($output, ['Pending Payouts', $data['payout_summary']['pending_payouts'] ?? 0]);
    
    fclose($output);
    exit;
}

/**
 * Generate HTML content for PDF reports
 */
function generateReportHTML($data, $start_date, $end_date, $format = 'pdf') {
    global $admin_username;
    
    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HabeshaEqub Analytics Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #13665C;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #13665C;
                margin: 0;
                font-size: 32px;
            }
            .header p {
                color: #666;
                margin: 10px 0;
                font-size: 16px;
            }
            .section {
                margin-bottom: 40px;
                page-break-inside: avoid;
            }
            .section h2 {
                color: #13665C;
                border-left: 5px solid #E9C46A;
                padding-left: 15px;
                margin-bottom: 20px;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .summary-card {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
            }
            .summary-card h3 {
                color: #13665C;
                margin: 0 0 10px 0;
                font-size: 18px;
            }
            .summary-card .value {
                font-size: 24px;
                font-weight: bold;
                color: #E9C46A;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th {
                background-color: #13665C;
                color: white;
                font-weight: bold;
            }
            tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                color: #666;
                font-size: 14px;
            }
            @media print {
                body { margin: 0; padding: 15px; }
                .section { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>HabeshaEqub Analytics Report</h1>
            <p><strong>Generated on:</strong> ' . date('F j, Y \a\t g:i A') . '</p>
            <p><strong>Report Period:</strong> ' . date('F j, Y', strtotime($start_date)) . ' to ' . date('F j, Y', strtotime($end_date)) . '</p>
            <p><strong>Generated by:</strong> ' . htmlspecialchars($admin_username) . '</p>
        </div>';
    
    // Financial Summary Section
    $html .= '<div class="section">
        <h2>Financial Summary</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Payments</h3>
                <div class="value">' . ($data['financial_summary']['total_payments'] ?? 0) . '</div>
            </div>
            <div class="summary-card">
                <h3>Total Collected</h3>
                <div class="value">£' . number_format($data['financial_summary']['total_collected'] ?? 0, 0) . '</div>
            </div>
            <div class="summary-card">
                <h3>Average Payment</h3>
                <div class="value">£' . number_format($data['financial_summary']['avg_payment'] ?? 0, 0) . '</div>
            </div>
        </div>
    </div>';
    
    // Monthly Collections
    if (!empty($data['monthly_collections'])) {
        $html .= '<div class="section">
            <h2>Monthly Collections</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Payment Count</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['monthly_collections'] as $month) {
            $html .= '<tr>
                <td>' . date('F Y', strtotime($month['month'] . '-01')) . '</td>
                <td>' . $month['payment_count'] . '</td>
                <td>£' . number_format($month['total_amount'], 0) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>';
    }
    
    // Top Contributors
    if (!empty($data['top_contributors'])) {
        $html .= '<div class="section">
            <h2>Top Contributors</h2>
            <table>
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Payment Count</th>
                        <th>Total Contributed</th>
                        <th>Average Payment</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['top_contributors'] as $contributor) {
            $html .= '<tr>
                <td>' . htmlspecialchars($contributor['member_id']) . '</td>
                <td>' . htmlspecialchars($contributor['first_name'] . ' ' . $contributor['last_name']) . '</td>
                <td>' . $contributor['payment_count'] . '</td>
                <td>£' . number_format($contributor['total_contributed'], 0) . '</td>
                <td>£' . number_format($contributor['avg_payment'], 0) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>';
    }
    
    // Payment Status Breakdown
    if (!empty($data['payment_status'])) {
        $html .= '<div class="section">
            <h2>Payment Status Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($data['payment_status'] as $status) {
            $html .= '<tr>
                <td>' . ucfirst($status['status']) . '</td>
                <td>' . $status['count'] . '</td>
                <td>£' . number_format($status['total_amount'], 0) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>';
    }
    
    // Member & Payout Summary
    $html .= '<div class="section">
        <h2>System Overview</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Members</h3>
                <div class="value">' . ($data['member_summary']['total_members'] ?? 0) . '</div>
            </div>
            <div class="summary-card">
                <h3>Active Members</h3>
                <div class="value">' . ($data['member_summary']['active_members'] ?? 0) . '</div>
            </div>
            <div class="summary-card">
                <h3>Total Distributed</h3>
                <div class="value">£' . number_format($data['payout_summary']['total_distributed'] ?? 0, 0) . '</div>
            </div>
        </div>
    </div>';
    
    $html .= '<div class="footer">
        <p><strong>HabeshaEqub Community Management System</strong></p>
        <p>This report contains confidential information. Please handle with care.</p>
    </div>
    
    </body>
    </html>';
    
    return $html;
}
?> 