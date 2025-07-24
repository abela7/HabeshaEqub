<?php
require_once 'includes/db.php';

try {
    // Get table structure
    $stmt = $pdo->prepare('DESCRIBE members');
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "Members table structure:\n";
    echo "=========================\n";
    foreach ($columns as $column) {
        echo "Column: " . $column['Field'] . " | Type: " . $column['Type'] . " | Null: " . $column['Null'] . " | Default: " . $column['Default'] . "\n";
    }
    
    echo "\nActual member data for ID 1:\n";
    echo "============================\n";
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = 1');
    $stmt->execute();
    $member = $stmt->fetch();
    
    if ($member) {
        foreach ($member as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "No member found with ID 1\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 