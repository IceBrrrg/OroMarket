<?php
require_once 'seller_auth_check.php';
require_once '../includes/db_connect.php';

$seller_id = $_SESSION['seller_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'get_conversations':
            try {
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           COUNT(m.id) as message_count,
                           COUNT(CASE WHEN m.sender_type = 'guest' AND m.is_read = 0 THEN 1 END) as unread_count,
                           MAX(m.sent_at) as last_message_time,
                           (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY sent_at DESC LIMIT 1) as last_message
                    FROM conversations c
                    LEFT JOIN messages m ON c.id = m.conversation_id
                    WHERE c.seller_id = ? AND c.status = 'active'
                    GROUP BY c.id
                    ORDER BY c.updated_at DESC
                ");
                $stmt->execute([$seller_id]);
                $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'conversations' => $conversations]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching conversations']);
            }
            break;

        case 'get_messages':
            $conversation_id = intval($input['conversation_id'] ?? 0);

            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM messages 
                    WHERE conversation_id = ? 
                    ORDER BY sent_at ASC
                ");
                $stmt->execute([$conversation_id]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Mark guest messages as read
                $stmt = $pdo->prepare("
                    UPDATE messages SET is_read = 1 
                    WHERE conversation_id = ? AND sender_type = 'guest'
                ");
                $stmt->execute([$conversation_id]);

                echo json_encode(['success' => true, 'messages' => $messages]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
            }
            break;

        case 'send_message':
            $conversation_id = intval($input['conversation_id'] ?? 0);
            $message = trim($input['message'] ?? '');

            if ($conversation_id <= 0 || empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            try {
                // Get seller name
                $stmt = $pdo->prepare("SELECT first_name, last_name FROM sellers WHERE id = ?");
                $stmt->execute([$seller_id]);
                $seller = $stmt->fetch(PDO::FETCH_ASSOC);
                $sender_name = $seller['first_name'] . ' ' . $seller['last_name'];

                $stmt = $pdo->prepare("
                    INSERT INTO messages (conversation_id, sender_type, sender_name, message_text) 
                    VALUES (?, 'seller', ?, ?)
                ");
                $stmt->execute([$conversation_id, $sender_name, $message]);

                // Update conversation timestamp
                $stmt = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$conversation_id]);

                echo json_encode(['success' => true, 'message' => 'Message sent']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error sending message']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}

// Get conversations for initial load
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(m.id) as message_count,
               COUNT(CASE WHEN m.sender_type = 'guest' AND m.is_read = 0 THEN 1 END) as unread_count,
               MAX(m.sent_at) as last_message_time,
               (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY sent_at DESC LIMIT 1) as last_message
        FROM conversations c
        LEFT JOIN messages m ON c.id = m.conversation_id
        WHERE c.seller_id = ? AND c.status = 'active'
        GROUP BY c.id
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$seller_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $conversations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Seller Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <style>
        .messages-container {
            display: flex;
            height: calc(100vh - 120px);
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .conversations-sidebar {
            width: 350px;
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .sidebar-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            position: relative;
        }

        .conversation-item:hover {
            background-color: #f8f9fa;
        }

        .conversation-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid #81c408;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .guest-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .conversation-time {
            font-size: 12px;
            color: #666;
        }

        .last-message {
            color: #666;
            font-size: 13px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .unread-badge {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #81c408;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-guest-info h4 {
            margin: 0;
            color: #333;
            font-size: 16px;
        }

        .chat-guest-info p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
        }

        .message-content {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 12px;
            word-wrap: break-word;
        }

        .guest-message .message-content {
            background: white;
            margin-left: 0;
            border: 1px solid #e0e0e0;
        }

        .seller-message {
            text-align: right;
        }

        .seller-message .message-content {
            background: #81c408;
            color: white;
            margin-left: auto;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .sender-name {
            font-weight: 600;
            font-size: 12px;
        }

        .message-time {
            font-size: 11px;
            opacity: 0.7;
        }

        .message-text {
            font-size: 14px;
            line-height: 1.4;
        }

        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        #messageInput {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }

        #messageInput:focus {
            border-color: #81c408;
        }

        .send-btn {
            width: 45px;
            height: 45px;
            background: #81c408;
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            background: #72ac07;
        }

        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            text-align: center;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .conversations-sidebar {
                width: 100%;
                display: none;
            }

            .conversations-sidebar.mobile-show {
                display: flex;
            }

            .chat-area {
                display: none;
            }

            .chat-area.mobile-show {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content flex-grow-1">
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Messages</h2>
                    <button class="btn btn-primary d-md-none" onclick="toggleSidebar()">
                        <i class="fas fa-list"></i> Conversations
                    </button>
                </div>

                <div class="messages-container">
                    <div class="conversations-sidebar" id="conversationsSidebar">
                        <div class="sidebar-header">
                            <h3>Conversations</h3>
                        </div>
                        <div class="conversations-list" id="conversationsList">
                            <?php if (empty($conversations)): ?>
                                <div class="empty-state">
                                    <div>
                                        <i class="fas fa-comments"></i>
                                        <p>No conversations yet</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="conversation-item" onclick="selectConversation(<?php echo $conv['id']; ?>, '<?php echo htmlspecialchars($conv['guest_name']); ?>', '<?php echo htmlspecialchars($conv['guest_contact']); ?>')">
                                        <div class="conversation-header">
                                            <span class="guest-name"><?php echo htmlspecialchars($conv['guest_name']); ?></span>
                                            <span class="conversation-time">
                                                <?php echo $conv['last_message_time'] ? date('M j, g:i A', strtotime($conv['last_message_time'])) : ''; ?>
                                            </span>
                                        </div>
                                        <div class="last-message">
                                            <?php echo htmlspecialchars($conv['last_message'] ?? 'No messages yet'); ?>
                                        </div>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <div class="unread-badge"><?php echo $conv['unread_count']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chat-area" id="chatArea">
                        <div class="empty-state">
                            <div>
                                <i class="fas fa-comment-dots"></i>
                                <h4>Select a conversation</h4>
                                <p>Choose a conversation from the sidebar to start messaging</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let messagePolling = null;

        function selectConversation(conversationId, guestName, guestContact) {
            currentConversationId = conversationId;
            
            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Load chat interface
            loadChatInterface(guestName, guestContact);
            loadMessages();
            startMessagePolling();

            // Hide sidebar on mobile
            if (window.innerWidth <= 768) {
                document.getElementById('conversationsSidebar').classList.remove('mobile-show');
                document.getElementById('chatArea').classList.add('mobile-show');
            }
        }

        function loadChatInterface(guestName, guestContact) {
            const chatArea = document.getElementById('chatArea');
            chatArea.innerHTML = `
                <div class="chat-header">
                    <div class="chat-guest-info">
                        <h4>${guestName}</h4>
                        <p>${guestContact || 'No contact info'}</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="showConversations()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-container">
                    <div class="chat-input-wrapper">
                        <input type="text" id="messageInput" placeholder="Type your message...">
                        <button class="send-btn" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            `;

            // Add enter key listener
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }

        async function loadMessages() {
            if (!currentConversationId) return;

            try {
                const response = await fetch('seller_messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'get_messages',
                        conversation_id: currentConversationId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    displayMessages(data.messages);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function displayMessages(messages) {
            const chatMessages = document.getElementById('chatMessages');
            if (!chatMessages) return;

            let html = '';
            messages.forEach(message => {
                const isSeller = message.sender_type === 'seller';
                const messageTime = new Date(message.sent_at).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="message ${isSeller ? 'seller-message' : 'guest-message'}">
                        <div class="message-content">
                            <div class="message-header">
                                <span class="sender-name">${message.sender_name}</span>
                                <span class="message-time">${messageTime}</span>
                            </div>
                            <div class="message-text">${escapeHtml(message.message_text)}</div>
                        </div>
                    </div>
                `;
            });

            chatMessages.innerHTML = html;
            scrollToBottom();
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message || !currentConversationId) return;

            try {
                const response = await fetch('seller_messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'send_message',
                        conversation_id: currentConversationId,
                        message: message
                    })
                });

                const data = await response.json();
                if (data.success) {
                    messageInput.value = '';
                    loadMessages();
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        }

        function startMessagePolling() {
            if (messagePolling) {
                clearInterval(messagePolling);
            }

            messagePolling = setInterval(() => {
                loadMessages();
            }, 3000);
        }

        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function toggleSidebar() {
            document.getElementById('conversationsSidebar').classList.toggle('mobile-show');
        }

        function showConversations() {
            document.getElementById('conversationsSidebar').classList.add('mobile-show');
            document.getElementById('chatArea').classList.remove('mobile-show');
        }

        // Auto-refresh conversations every 30 seconds
        setInterval(async function() {
            try {
                const response = await fetch('seller_messages.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'get_conversations'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    updateConversationsList(data.conversations);
                }
            } catch (error) {
                console.error('Error refreshing conversations:', error);
            }
        }, 30000);

        function updateConversationsList(conversations) {
            // Implementation for updating conversations list without losing current state
            // You can implement this based on your needs
        }
    </script>
</body>
</html>