<?php
// my_status.php
header('Content-Type: application/json');
require 'db.php';

$user = $_GET['user'] ?? '';

if ($user) {
    // Fetch active reports for this user
    $stmt = $conn->prepare("SELECT id, incident_type, status FROM incidents WHERE username = ? AND status != 'resolved' ORDER BY id DESC");
    $stmt->execute([$user]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode([]);
}
?>