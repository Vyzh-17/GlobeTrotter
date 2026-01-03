<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$message_id = $data['message_id'] ?? null;

if ($message_id) {
    // Check if message belongs to user
    $stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    if ($message && $message['sender_id'] == $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own messages']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
}
?>