<?php
require_once 'includes/db.php';

try {
    // Get the member data with correct column names
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = 1');
    $stmt->execute();
    $member = $stmt->fetch();
    
    if ($member) {
        echo "Current member data:\n";
        foreach ($member as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
        
        // The issue is the auth code expects different columns
        // We need to add the missing columns that the auth expects
        echo "\nAdding missing columns...\n";
        
        // Add username column (based on member_id)
        $pdo->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS username VARCHAR(50) AFTER member_id");
        
        // Add full_name column (combination of first_name + last_name)
        $pdo->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) AFTER last_name");
        
        // Add status column (default 'active')
        $pdo->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active' AFTER password");
        
        // Add is_active column (default 1)
        $pdo->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER status");
        
        // Update the record with proper values
        $full_name = trim($member['first_name'] . ' ' . $member['last_name']);
        $username = strtolower($member['first_name']); // Use first name as username
        
        $stmt = $pdo->prepare('UPDATE members SET username = ?, full_name = ?, status = ?, is_active = 1 WHERE id = 1');
        $stmt->execute([$username, $full_name, 'active']);
        
        echo "Database structure updated successfully!\n";
        echo "Username set to: $username\n";
        echo "Full name set to: $full_name\n";
        echo "Status set to: active\n";
        echo "Is active set to: 1\n";
        
    } else {
        echo "No member found with ID 1\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 