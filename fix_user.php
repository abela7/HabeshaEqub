<?php
require_once 'includes/db.php';

try {
    // Update the member record with the missing fields
    $stmt = $pdo->prepare('UPDATE members SET username = ?, full_name = ?, status = ? WHERE id = 1');
    $result = $stmt->execute(['michael', 'Michael Werkneh', 'active']);
    
    if ($result) {
        echo "Database updated successfully!\n";
        
        // Verify the update
        $stmt = $pdo->prepare('SELECT * FROM members WHERE id = 1');
        $stmt->execute();
        $member = $stmt->fetch();
        
        echo "\nUpdated member data:\n";
        echo "ID: " . $member['id'] . "\n";
        echo "Username: " . $member['username'] . "\n";
        echo "Email: " . $member['email'] . "\n";
        echo "Full Name: " . $member['full_name'] . "\n";
        echo "Status: " . $member['status'] . "\n";
        echo "Is Active: " . $member['is_active'] . "\n";
        
    } else {
        echo "Failed to update database\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 