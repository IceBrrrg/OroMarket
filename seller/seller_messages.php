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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #ff6b35;
            --primary-dark: #f7931e;
            --secondary: #64748b;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #2d3436;
            --text-primary: #2d3436;
            --text-secondary: #636e72;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fff5f2 0%, #ffd4c2 100%);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 80px;
        }

        .container-fluid {
            max-width: 1400px;
            transition: all 0.3s ease;
        }

        .messages-container {
            display: flex;
            height: calc(100vh - 200px);
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        .conversations-sidebar {
            width: 350px;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            background: white;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .sidebar-header h3 i {
            margin-right: 0.5rem;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .conversation-item:hover {
            background-color: var(--light);
        }

        .conversation-item.active {
            background-color: rgba(255, 107, 53, 0.1);
            border-left: 4px solid var(--primary);
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .guest-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .last-message {
            color: var(--text-secondary);
            font-size: 0.8rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }

        .unread-badge {
            position: absolute;
            top: 0.8rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1.5rem;
            background: var(--light);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-guest-info h4 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
        }

        .chat-guest-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 1rem;
        }

        .message-content {
            max-width: 70%;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            word-wrap: break-word;
        }

        .guest-message .message-content {
            background: white;
            margin-left: 0;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .seller-message {
            text-align: right;
        }

        .seller-message .message-content {
            background: white;
            margin-left: auto;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.3rem;
        }

        .sender-name {
            font-weight: 600;
            font-size: 0.75rem;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
        }

        .message-text {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .chat-input-container {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid var(--border);
        }

        .chat-input-wrapper {
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }

        #messageInput {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border);
            border-radius: 25px;
            outline: none;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        #messageInput:focus {
            border-color: var(--primary);
        }

        .send-btn {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #22C55E 0%, #16a34a 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        .empty-state h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.2);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
        }

        .page-header h1 i {
            margin-right: 0.8rem;
        }

        .page-header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            margin: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 0;
            }

            .messages-container {
                height: calc(100vh - 160px);
            }

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

            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="bi bi-chat-dots"></i>Messages & Customer Inquiries</h1>
                <p>Connect with your customers and provide excellent support through real-time messaging</p>
            </div>

            <div class="messages-container">
                <div class="conversations-sidebar" id="conversationsSidebar">
                    <div class="sidebar-header">
                        <h3><i class="bi bi-chat-left-text"></i>Conversations</h3>
                    </div>
                    <div class="conversations-list" id="conversationsList">
                        <?php if (empty($conversations)): ?>
                            <div class="empty-state">
                                <div>
                                    <i class="bi bi-chat-dots"></i>
                                    <p>No conversations yet</p>
                                    <small class="text-muted">Customer inquiries will appear here</small>
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
                            <i class="bi bi-chat-square-dots"></i>
                            <h4>Select a conversation</h4>
                            <p>Choose a conversation from the sidebar to start messaging with your customers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
                        <h4><i class="bi bi-person-circle me-2"></i>${guestName}</h4>
                        <p>${guestContact || 'No contact information provided'}</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="showConversations()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-container">
                    <div class="chat-input-wrapper">
                        <input type="text" id="messageInput" placeholder="Type your message...">
                        <button class="send-btn" onclick="sendMessage()">
                            <i class="bi bi-send"></i>
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

            // Disable input while sending
            messageInput.disabled = true;
            const sendBtn = document.querySelector('.send-btn');
            sendBtn.disabled = true;

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
                } else {
                    alert('Failed to send message: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message. Please try again.');
            } finally {
                // Re-enable input
                messageInput.disabled = false;
                sendBtn.disabled = false;
                messageInput.focus();
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
            // Update unread counts without losing current selection
            const currentActive = document.querySelector('.conversation-item.active');
            const currentActiveId = currentActive ? currentActive.onclick.toString().match(/\d+/)[0] : null;
            
            conversations.forEach(conv => {
                const convElement = document.querySelector(`[onclick*="${conv.id}"]`);
                if (convElement && conv.unread_count > 0) {
                    let badge = convElement.querySelector('.unread-badge');
                    if (!badge) {
                        badge = document.createElement('div');
                        badge.className = 'unread-badge';
                        convElement.appendChild(badge);
                    }
                    badge.textContent = conv.unread_count;
                } else if (convElement) {
                    const badge = convElement.querySelector('.unread-badge');
                    if (badge && conv.unread_count === 0) {
                        badge.remove();
                    }
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-select first conversation if available
            const firstConv = document.querySelector('.conversation-item');
            if (firstConv) {
                firstConv.click();
            }
        });
    </script>
</body>
</html>