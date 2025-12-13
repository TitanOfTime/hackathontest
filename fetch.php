<?php
// fetch.php
header('Content-Type: application/json');
require 'db.php';

$stmt = $conn->query("SELECT * FROM incidents ORDER BY reported_at DESC LIMIT 100");
echo json_encode($stmt->fetchAll());
?>