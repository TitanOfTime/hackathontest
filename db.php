<?php
// db.php
header("Access-Control-Allow-Origin: *"); 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new PDO(
        "mysql:host=" . getenv('MYSQLHOST') . 
        ";port=" . getenv('MYSQLPORT') . 
        ";dbname=" . getenv('MYSQL_DATABASE'), 
        getenv('MYSQLUSER'), 
        getenv('MYSQLPASSWORD')
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("DB Connection Error. Check Railway Variables.");
}
?>