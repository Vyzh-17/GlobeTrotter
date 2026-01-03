<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$user_stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch();

// Fetch all users for online status
$users = $pdo->prepare("
    SELECT id, username, profile_pic, 
           (SELECT COUNT(*) FROM messages WHERE sender_id = users.id AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as is_online
    FROM users 
    ORDER BY username
");
$users->execute();
$all_users = $users->fetchAll();

// Get all messages (group chat)
$messages = $pdo->prepare("
    SELECT m.*, u.username, u.profile_pic 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    ORDER BY m.created_at ASC
    LIMIT 100
");
$messages->execute();
$messages = $messages->fetchAll();

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $message]);
        
        // Redirect to refresh page with new message
        header("Location: chat.php");
        exit();
    }
}

// Get online users count
$online_users = array_filter($all_users, fn($u) => $u['is_online'] > 0);
$online_count = count($online_users);

// Get total messages count
$total_messages = $pdo->query("SELECT COUNT(*) as count FROM messages")->fetch()['count'];

// Get today's messages count
$today_messages = $pdo->query("SELECT COUNT(*) as count FROM messages WHERE DATE(created_at) = CURDATE()")->fetch()['count'];

// Get active topics (based on message content)
$topics = $pdo->query("
    SELECT 
        CASE 
            WHEN message LIKE '%japan%' OR message LIKE '%tokyo%' OR message LIKE '%kyoto%' THEN 'Japan Travel'
            WHEN message LIKE '%europe%' OR message LIKE '%paris%' OR message LIKE '%rome%' THEN 'Europe Travel'
            WHEN message LIKE '%budget%' OR message LIKE '%cheap%' OR message LIKE '%money%' THEN 'Budget Travel'
            WHEN message LIKE '%family%' OR message LIKE '%kids%' OR message LIKE '%children%' THEN 'Family Travel'
            WHEN message LIKE '%hiking%' OR message LIKE '%adventure%' OR message LIKE '%trek%' THEN 'Adventure Travel'
            WHEN message LIKE '%food%' OR message LIKE '%restaurant%' OR message LIKE '%cuisine%' THEN 'Food & Dining'
            WHEN message LIKE '%hotel%' OR message LIKE '%accommodation%' OR message LIKE '%hostel%' THEN 'Accommodation'
            ELSE 'General Travel'
        END as topic,
        COUNT(*) as message_count
    FROM messages 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY topic
    ORDER BY message_count DESC
    LIMIT 6
")->fetchAll();
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
            grid-template-columns: 280px 1fr 280px;
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
        
        .chat-sidebar {
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
            margin-bottom: 5px;
        }
        
        .user-item:hover {
            background: #f0f2f5;
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
            justify-content: space-between;
        }
        
        .chat-header-info h2 {
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
        
        .message-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .message-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-username {
            font-weight: 600;
            font-size: 0.9rem;
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
        
        .topic-list {
            padding: 15px;
        }
        
        .topic-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .topic-item:hover {
            background: #eef2ff;
            transform: translateX(5px);
        }
        
        .topic-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .topic-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .welcome-message h3 {
            margin: 0 0 10px 0;
        }
        
        .chat-rules {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
        }
        
        .chat-rules h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .chat-rules ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        
        .chat-rules li {
            margin-bottom: 5px;
        }
        
        .typing-indicator {
            padding: 10px 20px;
            font-style: italic;
            color: #666;
            font-size: 0.9rem;
        }
        
        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .message-action {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 0.8rem;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .message-action:hover {
            opacity: 1;
        }
        
        @media (max-width: 1200px) {
            .chat-container {
                grid-template-columns: 250px 1fr;
            }
            
            .chat-sidebar {
                display: none;
            }
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
            <h1><i class="fas fa-users"></i> Travel Community Chat</h1>
            <p>Join the conversation with travelers from around the world</p>
            
            <div class="community-stats">
                <div class="stat">
                    <div class="stat-number"><?= count($all_users) ?></div>
                    <div class="stat-label">Community Members</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?= $online_count ?></div>
                    <div class="stat-label">Online Now</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?= $total_messages ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?= $today_messages ?></div>
                    <div class="stat-label">Messages Today</div>
                </div>
            </div>
        </div>

        <div class="chat-container">
            <!-- Online Users Sidebar -->
            <div class="users-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-user-friends"></i> Online Travelers</h3>
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
                    <?php foreach($all_users as $user): ?>
                        <?php if($user['id'] != $user_id): ?>
                            <div class="user-item">
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
                                            <span>Last seen recently</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Main Chat Window -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <h2><i class="fas fa-comments"></i> Global Travel Chat</h2>
                        <div class="chat-status">
                            <span style="color: #48bb78;">● <?= $online_count ?> travelers online</span>
                            • <span>Chatting about travel worldwide</span>
                        </div>
                    </div>
                    <div>
                        <button onclick="clearChat()" class="btn-secondary" style="padding: 8px 16px;">
                            <i class="fas fa-trash"></i> Clear Chat
                        </button>
                    </div>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <!-- Welcome Message -->
                    <div class="welcome-message">
                        <h3>👋 Welcome to the Travel Community!</h3>
                        <p>This is a global chat where travelers from around the world share experiences, ask questions, and connect. Be respectful and have fun!</p>
                    </div>
                    
                    <!-- Chat Rules -->
                    <div class="chat-rules">
                        <h4><i class="fas fa-info-circle"></i> Community Guidelines</h4>
                        <ul>
                            <li>Be respectful and kind to everyone</li>
                            <li>Share travel tips and experiences</li>
                            <li>Ask questions about destinations</li>
                            <li>No spam or self-promotion</li>
                            <li>Keep conversations travel-related</li>
                        </ul>
                    </div>
                    
                    <?php if(empty($messages)): ?>
                        <div class="empty-chat">
                            <i class="fas fa-comment-slash"></i>
                            <h3>No messages yet</h3>
                            <p>Be the first to start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $prev_date = null;
                        $prev_sender = null;
                        foreach($messages as $message): 
                            $current_date = date('Y-m-d', strtotime($message['created_at']));
                            $is_sent = $message['sender_id'] == $user_id;
                            $show_header = $prev_sender != $message['sender_id'];
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
                                <?php if($show_header): ?>
                                    <div class="message-header">
                                        <img src="<?= $message['profile_pic'] ? '../' . htmlspecialchars($message['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($message['username']) . '&background=667eea&color=fff' ?>" 
                                             alt="<?= htmlspecialchars($message['username']) ?>" class="message-avatar">
                                        <div class="message-username">
                                            <?= htmlspecialchars($message['username']) ?>
                                            <?php if($message['sender_id'] == $user_id): ?>
                                                <span style="font-weight: normal; font-size: 0.8rem; margin-left: 5px;">(You)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div><?= htmlspecialchars($message['message']) ?></div>
                                
                                <div class="message-time">
                                    <?= date('g:i A', strtotime($message['created_at'])) ?>
                                    <?php if(date('Y-m-d', strtotime($message['created_at'])) == date('Y-m-d')): ?>
                                        <span style="margin-left: 5px; font-size: 0.7rem;">Today</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="message-actions">
                                    <button class="message-action" onclick="replyToMessage('<?= htmlspecialchars($message['username']) ?>')">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <button class="message-action" onclick="likeMessage(<?= $message['id'] ?>)">
                                        <i class="fas fa-thumbs-up"></i> Like
                                    </button>
                                    <?php if($message['sender_id'] == $user_id): ?>
                                        <button class="message-action" onclick="deleteMessage(<?= $message['id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $prev_sender = $message['sender_id']; ?>
                        <?php endforeach; ?>
                        
                        <!-- Typing Indicator -->
                        <div class="typing-indicator" id="typingIndicator" style="display: none;">
                            <i class="fas fa-pencil-alt"></i> 
                            <span id="typingUsers"></span> typing...
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input-container">
                    <form method="POST" class="chat-input-form" id="chatForm">
                        <input type="text" name="message" class="chat-input" id="messageInput"
                               placeholder="Type your message to the community..." required autocomplete="off">
                        <button type="submit" class="send-btn">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </form>
                    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn-small" onclick="insertQuickMessage('Any recommendations for Japan in spring?')">
                            Japan Spring
                        </button>
                        <button type="button" class="btn-small" onclick="insertQuickMessage('Looking for budget accommodation tips')">
                            Budget Tips
                        </button>
                        <button type="button" class="btn-small" onclick="insertQuickMessage('Has anyone visited Bali recently?')">
                            Bali Tips
                        </button>
                        <button type="button" class="btn-small" onclick="insertQuickMessage('Share your favorite travel hack!')">
                            Travel Hack
                        </button>
                    </div>
                </div>
            </div>

            <!-- Topics & Info Sidebar -->
            <div class="chat-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-hashtag"></i> Trending Topics</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">What travelers are discussing</p>
                </div>
                
                <div class="topic-list">
                    <?php foreach($topics as $topic): ?>
                        <div class="topic-item" onclick="searchTopic('<?= htmlspecialchars($topic['topic']) ?>')">
                            <div class="topic-name">#<?= htmlspecialchars($topic['topic']) ?></div>
                            <div class="topic-stats">
                                <span><?= $topic['message_count'] ?> messages</span>
                                <span>This week</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="sidebar-header" style="border-top: 1px solid #e0e0e0; margin-top: auto;">
                    <h3><i class="fas fa-lightbulb"></i> Chat Tips</h3>
                    <ul style="font-size: 0.9rem; color: #666; margin-top: 10px; padding-left: 20px;">
                        <li>Use @username to mention someone</li>
                        <li>Share photos in your trips section</li>
                        <li>Ask specific questions for better answers</li>
                        <li>Respect different cultures and opinions</li>
                        <li>Report inappropriate behavior to admins</li>
                    </ul>
                </div>
                
                <div style="padding: 15px; background: #f8f9fa; border-top: 1px solid #e0e0e0;">
                    <h4 style="margin: 0 0 10px 0; font-size: 1rem;">
                        <i class="fas fa-globe-americas"></i> Community Map
                    </h4>
                    <div style="height: 150px; background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                        <div style="text-align: center;">
                            <i class="fas fa-globe-americas fa-2x"></i>
                            <p style="margin-top: 10px;">Travelers from<br><?= count($all_users) ?> countries</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if(messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Auto-refresh messages every 3 seconds
        let refreshInterval;
        
        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                fetch('get_messages.php')
                    .then(response => response.text())
                    .then(html => {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const newMessages = tempDiv.querySelector('#messagesContainer');
                        if(newMessages) {
                            const oldScrollHeight = messagesContainer.scrollHeight;
                            const oldScrollTop = messagesContainer.scrollTop;
                            const atBottom = oldScrollHeight - oldScrollTop - messagesContainer.clientHeight < 100;
                            
                            messagesContainer.innerHTML = newMessages.innerHTML;
                            
                            if(atBottom) {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        }
                    });
                
                // Update online users
                fetch('get_online_users.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update online count in header
                        document.querySelector('.chat-status span:first-child').textContent = 
                            `● ${data.onlineCount} travelers online`;
                    });
            }, 3000);
        }
        
        function stopAutoRefresh() {
            if(refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        // Start auto-refresh
        startAutoRefresh();
        
        // Clean up on page unload
        window.addEventListener('beforeunload', stopAutoRefresh);
        
        // Enter key to send message (Shift+Enter for new line)
        const messageInput = document.getElementById('messageInput');
        if(messageInput) {
            messageInput.addEventListener('keydown', function(e) {
                if(e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('chatForm').submit();
                }
            });
            
            // Show typing indicator
            let typingTimeout;
            messageInput.addEventListener('input', function() {
                // Send typing indicator
                fetch('typing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ typing: true })
                });
                
                // Clear previous timeout
                clearTimeout(typingTimeout);
                
                // Set timeout to stop typing indicator after 3 seconds
                typingTimeout = setTimeout(() => {
                    fetch('typing.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ typing: false })
                    });
                }, 3000);
            });
        }
        
        // Quick message buttons
        function insertQuickMessage(text) {
            messageInput.value = text;
            messageInput.focus();
        }
        
        // Search for topic
        function searchTopic(topic) {
            messageInput.value = `About ${topic}: `;
            messageInput.focus();
        }
        
        // Reply to message
        function replyToMessage(username) {
            messageInput.value = `@${username} `;
            messageInput.focus();
        }
        
        // Like message
        function likeMessage(messageId) {
            fetch('like_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message_id: messageId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Message liked!');
                }
            });
        }
        
        // Delete message (only for own messages)
        function deleteMessage(messageId) {
            if(confirm('Are you sure you want to delete this message?')) {
                fetch('delete_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message_id: messageId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting message: ' + data.message);
                    }
                });
            }
        }
        
        // Clear chat (admin function)
        function clearChat() {
            if(confirm('Are you sure you want to clear the chat? This will delete all messages.')) {
                fetch('clear_chat.php')
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            location.reload();
                        } else {
                            alert('Error clearing chat: ' + data.message);
                        }
                    });
            }
        }
        
        // Poll for typing indicators
        function pollTyping() {
            fetch('get_typing.php')
                .then(response => response.json())
                .then(data => {
                    const typingIndicator = document.getElementById('typingIndicator');
                    const typingUsersElement = document.getElementById('typingUsers');
                    
                    if(data.typingUsers.length > 0) {
                        typingUsersElement.textContent = data.typingUsers.join(', ');
                        typingIndicator.style.display = 'block';
                    } else {
                        typingIndicator.style.display = 'none';
                    }
                });
        }
        
        // Poll every 2 seconds for typing indicators
        setInterval(pollTyping, 2000);
        
        // Play sound on new message (optional)
        function playNotificationSound() {
            // Create a simple notification sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }
        
        // Check for new messages and play sound
        let lastMessageCount = <?= count($messages) ?>;
        setInterval(() => {
            fetch('get_message_count.php')
                .then(response => response.json())
                .then(data => {
                    if(data.count > lastMessageCount) {
                        // Only play sound if user is not active in chat
                        if(!document.hasFocus()) {
                            playNotificationSound();
                        }
                        lastMessageCount = data.count;
                    }
                });
        }, 3000);
    </script>
</body>
</html>