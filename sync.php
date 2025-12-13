<?php
// sync.php - DIAGNOSTIC VERSION
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow from any domain (fixes some Railway issues)
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Load Database
if (!file_exists('db.php')) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "db.php file not found"]);
    exit;
}
require 'db.php';

// 2. Error Handling (Prevent HTML Errors messing up JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 3. Get Data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(["status" => "error", "message" => "No JSON data received"]);
        exit;
    }

    // 4. THE INSERT COMMAND
    // We map your Javascript variable names (left) to Database Column names (right)
    // Javascript: uuid, username, type, severity, lat, lng, timestamp, image
    // Database:   client_uuid, username, incident_type, severity, latitude, longitude, reported_at, image_data, status
    
    $sql = "INSERT INTO incidents (
                client_uuid, 
                username, 
                incident_type, 
                severity, 
                latitude, 
                longitude, 
                reported_at, 
                image_data, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";

    $stmt = $conn->prepare($sql);
    $savedCount = 0;

    foreach ($data as $row) {
        // Fallbacks for missing data
        $uuid = $row['uuid'] ?? uniqid();
        $user = $row['username'] ?? 'Unknown';
        $type = $row['type'] ?? 'General';
        $sev  = $row['severity'] ?? 3;
        $lat  = $row['lat'] ?? 0.0;
        $lng  = $row['lng'] ?? 0.0;
        $time = $row['timestamp'] ?? date('Y-m-d H:i:s');
        $img  = $row['image'] ?? null;

        $stmt->execute([$uuid, $user, $type, $sev, $lat, $lng, $time, $img]);
        if ($stmt->rowCount() > 0) $savedCount++;
    }

    echo json_encode(["status" => "success", "synced" => $savedCount]);

} catch (PDOException $e) {
    // CAPTURE SQL ERROR
    // This will tell us exactly which column is missing
    http_response_code(200); // Send as 200 OK so Javascript can read the error message
    echo json_encode([
        "status" => "sql_error",
        "message" => $e->getMessage()
    ]);
} catch (Exception $e) {
    // CAPTURE GENERIC ERROR
    http_response_code(200);
    echo json_encode([
        "status" => "php_error",
        "message" => $e->getMessage()
    ]);
}
?>