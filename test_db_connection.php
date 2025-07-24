<?php
/**
 * Database Connection Test
 * This file helps diagnose database connection issues on the server
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'habeshjv_habeshaequb');
define('DB_USER', 'habeshjv_abel');
define('DB_PASS', '2121@Habesha');

echo "<h2>Database Connection Test</h2>";
echo "<p>Testing connection to: " . DB_HOST . "</p>";
echo "<p>Database: " . DB_NAME . "</p>";
echo "<p>User: " . DB_USER . "</p>";

// Test 1: Check if PDO is available
echo "<h3>Test 1: PDO Extension</h3>";
if (extension_loaded('pdo')) {
    echo "✅ PDO extension is loaded<br>";
    if (extension_loaded('pdo_mysql')) {
        echo "✅ PDO MySQL driver is loaded<br>";
    } else {
        echo "❌ PDO MySQL driver is NOT loaded<br>";
    }
} else {
    echo "❌ PDO extension is NOT loaded<br>";
}

// Test 2: Try to connect without database name first
echo "<h3>Test 2: Basic MySQL Connection</h3>";
try {
    $pdo_test = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Basic MySQL connection successful<br>";
    
    // Test 3: Check if database exists
    echo "<h3>Test 3: Database Existence</h3>";
    $stmt = $pdo_test->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '" . DB_NAME . "' exists<br>";
    } else {
        echo "❌ Database '" . DB_NAME . "' does NOT exist<br>";
        echo "<p>Available databases:</p><ul>";
        $databases = $pdo_test->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($databases as $db) {
            echo "<li>" . htmlspecialchars($db) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "❌ Basic MySQL connection failed: " . $e->getMessage() . "<br>";
}

// Test 4: Try full connection with database
echo "<h3>Test 4: Full Database Connection</h3>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    echo "✅ Full database connection successful!<br>";
    
    // Test 5: Check if tables exist
    echo "<h3>Test 5: Database Tables</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) > 0) {
        echo "✅ Database has " . count($tables) . " tables:<br>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "⚠️ Database exists but has no tables<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Full database connection failed: " . $e->getMessage() . "<br>";
}

// Test 6: Alternative host names
echo "<h3>Test 6: Alternative Host Names</h3>";
$alternative_hosts = ['127.0.0.1', 'localhost:3306', 'localhost'];
foreach ($alternative_hosts as $host) {
    if ($host === 'localhost') continue; // Already tested
    
    try {
        $pdo_alt = new PDO(
            "mysql:host=" . $host . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Connection successful with host: " . $host . "<br>";
    } catch (PDOException $e) {
        echo "❌ Connection failed with host: " . $host . " - " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Server Information</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
?> 