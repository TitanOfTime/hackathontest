<?php
// fetch_analytics.php
header('Content-Type: application/json');
require 'db.php';

// Fetch ALL incidents for analytics (No LIMIT)
// We want to see trends over time, so we need everything.
try {
    $stmt = $conn->query("SELECT * FROM incidents ORDER BY reported_at DESC");
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
