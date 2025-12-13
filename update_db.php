<?php
require 'db.php';

try {
    // Change 'role' column to basic TEXT so it accepts 'user', 'admin', 'responder' etc.
    $sql = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'";
    $conn->exec($sql);
    echo "✅ Database updated! You can now use role 'user'.";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>