<?php
// fix_db.php - Adds missing columns for the new features
require 'db.php';

function addCol($conn, $table, $col, $def) {
    try {
        $conn->exec("ALTER TABLE $table ADD COLUMN $col $def");
        echo "<p style='color:green'>✅ Added column '$col' to '$table'</p>";
    } catch (PDOException $e) {
        if(strpos($e->getMessage(), "Duplicate column") !== false) {
            echo "<p style='color:gray'>ℹ️ Column '$col' already exists in '$table'</p>";
        } else {
            echo "<p style='color:red'>❌ Error adding '$col': " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h1>Database Repair Tool</h1>";

// 1. Add 'status' (active / resolved)
addCol($conn, 'incidents', 'status', "VARCHAR(20) NOT NULL DEFAULT 'active'");

// 2. Add 'username' (who reported it)
addCol($conn, 'incidents', 'username', "VARCHAR(100) NOT NULL DEFAULT 'Unknown'");

// 3. Add 'client_uuid' (to avoid duplicate uploads)
addCol($conn, 'incidents', 'client_uuid', "VARCHAR(100) NOT NULL DEFAULT '0'");

echo "<hr><p><b>Done!</b> You can now try uploading from the app again.</p>";
?>
