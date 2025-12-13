<?php
require 'db.php';

$new_pass = 'admin2025';
$new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

try {
    // 1. Force update the Admin password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE role = 'admin'");
    $stmt->execute([$new_hash]);
    
    echo "<h1>✅ Admin Password Reset!</h1>";
    echo "<p>New Hash Generated: <strong>$new_hash</strong></p>";
    echo "<p>You can now login with: <strong>admin2025</strong></p>";
    echo "<a href='dashboard.php'>Go to Dashboard</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>