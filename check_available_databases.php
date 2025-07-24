<?php
/**
 * Check Available Databases and Users
 * This helps identify the correct database credentials for your hosting
 */

echo "<h2>Available Databases and Users Check</h2>";

// Common hosting database patterns
$possible_hosts = ['localhost', '127.0.0.1'];
$possible_users = [
    'habeshjv_abel',      // Common pattern: username_username
    'habeshjv_habeshaequb', // Common pattern: username_database
    'abel',               // Original
    'habeshjv',          // Just the username
    'root'               // Sometimes available
];

$possible_databases = [
    'habeshjv_habeshaequb', // Original
    'habeshaequb',          // Without prefix
    'habeshjv_equb',        // Shortened
    'equb'                  // Very short
];

echo "<h3>Testing Common Hosting Patterns</h3>";

foreach ($possible_hosts as $host) {
    echo "<h4>Testing Host: $host</h4>";
    
    foreach ($possible_users as $user) {
        foreach ($possible_databases as $db) {
            try {
                $pdo = new PDO(
                    "mysql:host=$host;dbname=$db",
                    $user,
                    '2121@Habesha',
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo "✅ <strong>SUCCESS!</strong> Host: $host, User: $user, Database: $db<br>";
                
                // Test if we can query
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "   Tables found: " . count($tables) . "<br>";
                if (count($tables) > 0) {
                    echo "   Table names: " . implode(', ', $tables) . "<br>";
                }
                echo "<br>";
                
            } catch (PDOException $e) {
                // Don't show all failures, just note them
                if (strpos($e->getMessage(), 'Access denied') !== false) {
                    echo "❌ Access denied for $user@$host to $db<br>";
                } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                    echo "❌ Database $db doesn't exist for $user@$host<br>";
                }
            }
        }
    }
}

echo "<h3>Check Your Hosting Control Panel</h3>";
echo "<p>Please check your hosting control panel for:</p>";
echo "<ul>";
echo "<li><strong>MySQL Databases</strong> - to see what databases exist</li>";
echo "<li><strong>MySQL Users</strong> - to see what users exist</li>";
echo "<li><strong>Database Prefix</strong> - many hosts add your username as a prefix</li>";
echo "</ul>";

echo "<h3>Common Hosting Patterns</h3>";
echo "<p>Most hosting providers use these patterns:</p>";
echo "<ul>";
echo "<li><strong>Database:</strong> username_databasename (e.g., habeshjv_habeshaequb)</li>";
echo "<li><strong>User:</strong> username_username (e.g., habeshjv_abel) or just username</li>";
echo "<li><strong>Host:</strong> localhost (most common)</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Go to your hosting control panel</li>";
echo "<li>Check MySQL Databases section</li>";
echo "<li>Check MySQL Users section</li>";
echo "<li>Create the database and user if they don't exist</li>";
echo "<li>Grant the user access to the database</li>";
echo "<li>Update the database configuration in includes/db.php</li>";
echo "</ol>";
?> 