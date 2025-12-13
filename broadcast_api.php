<?php
// Simple JSON-based broadcast system
// File: broadcast.json (created automatically)

$file = 'broadcast_data.json';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADMIN: Create new broadcast
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['message'])) {
        $alert = [
            'message' => htmlspecialchars($data['message']), // Sanitize
            'timestamp' => time(),
            'active' => true
        ];
        
        // Save to file
        file_put_contents($file, json_encode($alert));
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'No message']);
    }
} else {
    // USER: Get latest broadcast
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        
        // Only return if it's less than 1 hour old (3600 seconds)
        // This prevents old alerts from causing panic later
        if (time() - $data['timestamp'] < 3600) { 
            echo json_encode($data);
        } else {
            echo json_encode(['active' => false]);
        }
    } else {
        echo json_encode(['active' => false]);
    }
}
?>
