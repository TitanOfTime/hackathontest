<?php
// db.php - CLEAN VERSION
header("Access-Control-Allow-Origin: *"); 

try {
    $conn = new PDO(
        "mysql:host=" . getenv('MYSQLHOST') . 
        ";port=" . getenv('MYSQLPORT') . 
        ";dbname=" . getenv('MYSQL_DATABASE'), 
        getenv('MYSQLUSER'), 
        getenv('MYSQLPASSWORD')
    );
    
    // Set Error Mode to Exception (This replaces the line that crashed)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // If this prints, your variables are wrong.
    die("DB Connection Failed: " . $e->getMessage());
}
?>