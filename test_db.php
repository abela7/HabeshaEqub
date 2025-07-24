<?php
// Simple database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test database connection
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=habeshaequb;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = ['admins', 'members', 'payments', 'payouts'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "<p>✅ Table '$table' exists with {$result['count']} records</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Table '$table' error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Database 'habeshaequb' exists</li>";
    echo "<li>Import the habeshaequb.sql file</li>";
    echo "</ul>";
}

// Test session
echo "<h3>Session Test</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>✅ Session started successfully</p>";

// Test file includes
echo "<h3>File Include Test</h3>";
$files_to_test = [
    'includes/db.php',
    'languages/translator.php',
    'languages/en.json',
    'assets/css/style.css'
];

foreach ($files_to_test as $file) {
    if (file_exists($file)) {
        echo "<p>✅ File '$file' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ File '$file' missing</p>";
    }
}
?> 