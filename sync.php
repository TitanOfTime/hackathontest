<?php
// sync.php
header('Content-Type: application/json');
require 'db.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) { echo json_encode(["status" => "empty"]); exit; }

$stmt = $conn->prepare("INSERT IGNORE INTO incidents (client_uuid, incident_type, severity, latitude, longitude, reported_at) VALUES (?, ?, ?, ?, ?, ?)");

$count = 0;
foreach ($data as $row) {
    $stmt->execute([
        $row['uuid'], $row['type'], $row['severity'], 
        $row['lat'], $row['lng'], $row['timestamp']
    ]);
    if ($stmt->rowCount() > 0) $count++;
}

echo json_encode(["status" => "success", "synced" => $count]);
?>