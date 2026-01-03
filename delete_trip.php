<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id = $_POST['trip_id'] ?? null;
    
    if ($trip_id) {
        // Delete itinerary sections first
        $stmt = $pdo->prepare("DELETE FROM itinerary_sections WHERE trip_id = ?");
        $stmt->execute([$trip_id]);
        
        // Delete trip
        $stmt = $pdo->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Trip not found or access denied']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid trip ID']);
    }
}
?>