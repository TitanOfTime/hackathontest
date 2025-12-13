<?php
// FORCE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<body style="background:#111; color:#fff; font-family:sans-serif; text-align:center; padding-top:50px;">
    <h1>Debug Mode</h1>
    
    <h3>Step 1: Checking files...</h3>
    <?php
    if (file_exists('db.php')) {
        echo "<p style='color:green'>✅ db.php found.</p>";
    } else {
        echo "<p style='color:red'>❌ FATAL: db.php is missing from the root folder.</p>";
        exit; // Stop here
    }
    ?>

    <h3>Step 2: Testing Connection...</h3>
    <?php
    // Try to include and catch syntax errors
    try {
        require_once 'db.php'; 
        // If db.php is successful, $conn variable should exist
        if (isset($conn)) {
            echo "<h1 style='color:lime; font-size:40px;'>✅ CONNECTED!</h1>";
            echo "<p>Host: " . getenv('MYSQLHOST') . "</p>"; // Verify var is read
        } else {
            echo "<p style='color:red'>❌ db.php loaded, but \$conn is missing.</p>";
        }
    } catch (Throwable $e) {
        // Catch ANY crash (Syntax error, PDO error, etc)
        echo "<div style='background:red; color:white; padding:20px;'>";
        echo "<strong>CRASH REPORT:</strong><br>";
        echo $e->getMessage();
        echo "</div>";
    }
    ?>
</body>