<?php
// delete_incident.php
header('Content-Type: application/json');
require 'db.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "message" => "No ID provided"]);
    exit;
}

try {
    // Permanently delete the row
    $stmt = $conn->prepare("DELETE FROM incidents WHERE id = ?");
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Incident not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>