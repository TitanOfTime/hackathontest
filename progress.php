<?php
// progress.php
header('Content-Type: application/json');
require 'db.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['id'])) {
    // Update status to 'in_progress'
    $stmt = $conn->prepare("UPDATE incidents SET status = 'in_progress' WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(["status" => "success"]);
}
?>