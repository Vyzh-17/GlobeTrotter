<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $section_id = $data['section_id'] ?? null;
    $trip_id = $data['trip_id'] ?? null;
    
    if ($section_id && $trip_id) {
        // Verify trip belongs to user
        $stmt = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM itinerary_sections WHERE id = ? AND trip_id = ?");
            $stmt->execute([$section_id, $trip_id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
}
?>