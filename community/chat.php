<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch();

// Fetch all users (except current)
$users = $pdo->prepare("
    SELECT id, username, profile_pic, 
           (SELECT COUNT(*) FROM messages WHERE sender_id = users.id AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online
    FROM users 
    WHERE id != ? 
    ORDER BY username
");
$users->execute([$user_id]);
$users = $users->fetchAll();

// Get selected chat user
$selected_user_id = $_GET['user_id'] ?? null;
$selected_user = null;
$messages = [];

if ($selected_user_id) {
    // Get user info
    $user_stmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE id = ?");
    $user_stmt->execute([$selected_user_id]);
    $selected_user = $user_stmt->fetch();
    
    if ($selected_user) {
        // Get messages between users
        $msg_stmt = $pdo->prepare("
            SELECT m.*, u.username, u.profile_pic 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE (m.sender_id = ? AND u.id = ?) 
               OR (m.sender_id = ? AND u.id = ?)
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $msg_stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
        $messages = $msg_stmt->fetchAll();
    }
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $selected_user_id) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $message]);
        
        // Redirect to refresh page with new message
        header("Location: chat.php?user_id=$selected_user_id");
        exit();
    }
}

// Get recent messages for sidebar
$recent_messages = $pdo->prepare("
    SELECT m.*, u.username, u.profile_pic, 
           (SELECT COUNT(*) FROM messages m2 WHERE m2.sender_id = u.id AND m2.id > m.id AND m2.sender_id != ?) as unread
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE u.id != ?
    ORDER BY m.created_at DESC
    LIMIT 20
");
$recent_messages->execute([$user_id, $user_id]);
$recent_messages = $recent_messages->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Chat - Globetrotter</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: 80vh;
            margin-top: 20px;
        }
        
        .users-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .current-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .current-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .users-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 5px;
        }
        
        .user-item:hover {
            background: #f0f2f5;
        }
        
        .user-item.active {
            background: #eef2ff;
            border-left: 4px solid #667eea;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            position: relative;
        }
        
        .online-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: #48bb78;
            border: 2px solid white;
            border-radius: 50%;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
        }
        
        .user-status {
            font-size: 0.85rem;
            color: #666;
        }
        
        .unread-count {
            background: #e53e3e;
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
        
        .chat-window {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header-info h3 {
            margin: 0;
            color: #333;
        }
        
        .chat-status {
            font-size: 0.9rem;
            color: #666;
        }
        
        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f8f9fa;
        }
        
        .message {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.sent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        
        .message.received {
            background: white;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }
        
        .message.received .message-time {
            text-align: left;
            color: #666;
        }
        
        .chat-input-container {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .chat-input-form {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 24px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .chat-input:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .send-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s;
        }
        
        .send-btn:hover {
            transform: translateY(-2px);
        }
        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
        }
        
        .empty-chat i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e0;
        }
        
        .community-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .community-header h1 {
            margin-bottom: 10px;
        }
        
        .community-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .users-sidebar {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Community Header -->
        <div class="community-header">
            <h1><i class="fas fa-users"></i> Travel Community</h1>
            <p>Connect with fellow travelers, share experiences, and get inspired</p>
            
            <div class="community-stats">
                <div class="stat">
                    <div class="stat-number"><?= count($users) + 1 ?></div>
                    <div class="stat-label">Total Members</div>
                </div>
                <div class="stat">
                    <div class="stat-number">
                        <?= count(array_filter($users, fn($u) => $u['is_online'] > 0)) ?>
                    </div>
                    <div class="stat-label">Online Now</div>
                </div>
                <div class="stat">
                    <div class="stat-number">
                        <?= $pdo->query("SELECT COUNT(*) as count FROM messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetch()['count'] ?>
                    </div>
                    <div class="stat-label">Messages Today</div>
                </div>
            </div>
        </div>

        <div class="chat-container">
            <!-- Users Sidebar -->
            <div class="users-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-comments"></i> Direct Messages</h3>
                    <div class="current-user">
                        <img src="<?= $current_user['profile_pic'] ? '../' . htmlspecialchars($current_user['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['username']) . '&background=667eea&color=fff' ?>" 
                             alt="<?= htmlspecialchars($current_user['username']) ?>">
                        <div>
                            <div class="user-name"><?= htmlspecialchars($current_user['username']) ?></div>
                            <div class="user-status" style="color: #48bb78;">● Online</div>
                        </div>
                    </div>
                </div>
                
                <div class="users-list">
                    <?php foreach($users as $user): ?>
                        <a href="chat.php?user_id=<?= $user['id'] ?>" class="user-item <?= $selected_user_id == $user['id'] ? 'active' : '' ?>">
                            <div class="user-avatar">
                                <img src="<?= $user['profile_pic'] ? '../' . htmlspecialchars($user['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=667eea&color=fff' ?>" 
                                     alt="<?= htmlspecialchars($user['username']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php if($user['is_online'] > 0): ?>
                                    <div class="online-indicator"></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                                <div class="user-status">
                                    <?php if($user['is_online'] > 0): ?>
                                        <span style="color: #48bb78;">Online</span>
                                    <?php else: ?>
                                        Offline
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php 
                            // Count unread messages
                            $unread_stmt = $pdo->prepare("
                                SELECT COUNT(*) as unread 
                                FROM messages 
                                WHERE sender_id = ? 
                                AND created_at > (SELECT COALESCE(MAX(created_at), '1970-01-01') FROM messages WHERE sender_id = ?)
                            ");
                            $unread_stmt->execute([$user['id'], $user_id]);
                            $unread = $unread_stmt->fetch()['unread'];
                            ?>
                            
                            <?php if($unread > 0): ?>
                                <div class="unread-count"><?= $unread ?></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Chat Window -->
            <div class="chat-window">
                <?php if($selected_user): ?>
                    <div class="chat-header">
                        <img src="<?= $selected_user['profile_pic'] ? '../' . htmlspecialchars($selected_user['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($selected_user['username']) . '&background=667eea&color=fff' ?>" 
                             alt="<?= htmlspecialchars($selected_user['username']) ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        <div class="chat-header-info">
                            <h3><?= htmlspecialchars($selected_user['username']) ?></h3>
                            <div class="chat-status">
                                <?php 
                                $online = $pdo->prepare("SELECT COUNT(*) as is_online FROM messages WHERE sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
                                $online->execute([$selected_user_id]);
                                $is_online = $online->fetch()['is_online'] > 0;
                                ?>
                                <?php if($is_online): ?>
                                    <span style="color: #48bb78;">● Online</span>
                                <?php else: ?>
                                    <span>Last seen recently</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="messages-container" id="messagesContainer">
                        <?php if(empty($messages)): ?>
                            <div class="empty-chat">
                                <i class="fas fa-comment-slash"></i>
                                <h3>No messages yet</h3>
                                <p>Start a conversation with <?= htmlspecialchars($selected_user['username']) ?></p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $prev_date = null;
                            foreach($messages as $message): 
                                $current_date = date('Y-m-d', strtotime($message['created_at']));
                                $is_sent = $message['sender_id'] == $user_id;
                            ?>
                                <?php if($current_date != $prev_date): ?>
                                    <div style="text-align: center; margin: 20px 0;">
                                        <span style="background: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; color: #666; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                            <?= date('F j, Y', strtotime($message['created_at'])) ?>
                                        </span>
                                    </div>
                                    <?php $prev_date = $current_date; ?>
                                <?php endif; ?>
                                
                                <div class="message <?= $is_sent ? 'sent' : 'received' ?>">
                                    <?php if(!$is_sent): ?>
                                        <div style="font-size: 0.85rem; margin-bottom: 5px; color: #666; font-weight: bold;">
                                            <?= htmlspecialchars($message['username']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div><?= htmlspecialchars($message['message']) ?></div>
                                    
                                    <div class="message-time">
                                        <?= date('g:i A', strtotime($message['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input-container">
                        <form method="POST" class="chat-input-form">
                            <input type="text" name="message" class="chat-input" 
                                   placeholder="Type your message here..." required autocomplete="off">
                            <button type="submit" class="send-btn">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-chat" style="height: 100%;">
                        <i class="fas fa-comments"></i>
                        <h3>Select a conversation</h3>
                        <p>Choose a user from the sidebar to start chatting</p>
                        <div style="margin-top: 20px; text-align: center; max-width: 400px;">
                            <h4><i class="fas fa-lightbulb"></i> Tips for great conversations:</h4>
                            <ul style="text-align: left; margin-top: 10px; color: #666;">
                                <li>Share your travel experiences</li>
                                <li>Ask for destination recommendations</li>
                                <li>Share travel tips and hacks</li>
                                <li>Discuss itinerary ideas</li>
                                <li>Connect with travelers heading to the same destinations</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if(messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Auto-refresh messages every 5 seconds
        let refreshInterval;
        
        function startAutoRefresh() {
            if(<?= $selected_user_id ? 'true' : 'false' ?>) {
                refreshInterval = setInterval(() => {
                    fetch(`get_messages.php?user_id=<?= $selected_user_id ?>`)
                        .then(response => response.text())
                        .then(html => {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            const newMessages = tempDiv.querySelector('#messagesContainer');
                            if(newMessages) {
                                messagesContainer.innerHTML = newMessages.innerHTML;
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        });
                }, 5000);
            }
        }
        
        function stopAutoRefresh() {
            if(refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        // Start auto-refresh when on chat page
        if(<?= $selected_user_id ? 'true' : 'false' ?>) {
            startAutoRefresh();
        }
        
        // Clean up on page unload
        window.addEventListener('beforeunload', stopAutoRefresh);
        
        // Enter key to send message
        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.querySelector('.chat-input');
            if(chatInput) {
                chatInput.addEventListener('keypress', function(e) {
                    if(e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.closest('form').submit();
                    }
                });
            }
        });
        
        // Mark messages as read
        function markAsRead(userId) {
            fetch('mark_as_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            });
        }
        
        // Mark as read when opening chat
        if(<?= $selected_user_id ? 'true' : 'false' ?>) {
            markAsRead(<?= $selected_user_id ?>);
        }
    </script>
</body>
</html>