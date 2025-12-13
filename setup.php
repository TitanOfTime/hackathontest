<?php
// setup.php - Runs once to upgrade the DB
require 'db.php';

try {
    // The SQL Command to add the column
    $sql = "ALTER TABLE incidents ADD COLUMN image_data LONGTEXT";
    
    $conn->exec($sql);
    echo "<h1 style='color:green'>✅ SUCCESS: 'image_data' column added.</h1>";
    echo "<p>You can now delete this file.</p>";

} catch(PDOException $e) {
    // If it fails, tell us why
    echo "<h1 style='color:red'>❌ ERROR</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Check if column already exists
    if(strpos($e->getMessage(), "Duplicate column") !== false) {
        echo "<p style='color:blue'>Good news: The column already exists!</p>";
    }
}
?>