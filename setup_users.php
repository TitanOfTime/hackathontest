<?php
require 'db.php';

try {
    // 1. Create Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'responder') NOT NULL DEFAULT 'responder'
    )";
    $conn->exec($sql);
    echo "✅ Table 'users' checked/created.<br>";

    // 2. Define Default Users (Password will be hashed)
    $users = [
        ['responder', 'hero', 'responder'],      // User for App
        ['admin', 'admin2025', 'admin']          // User for Dashboard
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, ?)");

    foreach ($users as $user) {
        $hash = password_hash($user[1], PASSWORD_DEFAULT); // Secure Hash
        $stmt->execute([$user[0], $hash, $user[2]]);
        echo "👤 User '{$user[0]}' ready.<br>";
    }

    echo "<h3 style='color:green'>System Security Upgraded. Delete this file now.</h3>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>