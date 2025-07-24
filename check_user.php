<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = 1');
    $stmt->execute();
    $member = $stmt->fetch();
    
    if ($member) {
        echo "Member found:\n";
        echo "ID: " . $member['id'] . "\n";
        echo "Username: " . ($member['username'] ?? 'NULL') . "\n";
        echo "Email: " . ($member['email'] ?? 'NULL') . "\n";
        echo "Full Name: " . ($member['full_name'] ?? 'NULL') . "\n";
        echo "Status: " . ($member['status'] ?? 'NULL') . "\n";
        echo "Is Active: " . ($member['is_active'] ?? 'NULL') . "\n";
        echo "Password length: " . strlen($member['password'] ?? '') . "\n";
        echo "Password starts with: " . substr($member['password'] ?? '', 0, 10) . "\n";
    } else {
        echo "No member found with ID 1\n";
    }
    
    // Test password verification
    echo "\nTesting password verification:\n";
    $test_password = 'MW123A';
    $stored_hash = $member['password'] ?? '';
    
    if (!empty($stored_hash)) {
        $verify_result = password_verify($test_password, $stored_hash);
        echo "Password verification result: " . ($verify_result ? 'SUCCESS' : 'FAILED') . "\n";
        
        // Check if it's a bcrypt hash
        $hash_info = password_get_info($stored_hash);
        echo "Hash algorithm: " . $hash_info['algoName'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 