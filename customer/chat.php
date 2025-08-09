<?php
require_once '../includes/db_connect.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'start_conversation':
            $guest_name = trim($input['guest_name'] ?? '');
            $guest_contact = trim($input['guest_contact'] ?? '');
            $seller_id = intval($input['seller_id'] ?? 0);
            $product_id = intval($input['product_id'] ?? 0);

            if (empty($guest_name) || $seller_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            try {
                // Check if conversation already exists
                $stmt = $pdo->prepare("
                    SELECT id FROM conversations 
                    WHERE guest_name = ? AND seller_id = ? AND status = 'active'
                ");
                $stmt->execute([$guest_name, $seller_id]);
                $conversation = $stmt->fetch();

                if ($conversation) {
                    $conversation_id = $conversation['id'];
                } else {
                    // Create new conversation
                    $stmt = $pdo->prepare("
                        INSERT INTO conversations (guest_name, guest_contact, seller_id) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$guest_name, $guest_contact, $seller_id]);
                    $conversation_id = $pdo->lastInsertId();
                }

                // If product_id is provided, send initial message about the product
                if ($product_id > 0) {
                    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $initial_message = "Hi! I'm interested in your product: " . $product['name'];
                        $stmt = $pdo->prepare("
                            INSERT INTO messages (conversation_id, sender_type, sender_name, message_text) 
                            VALUES (?, 'guest', ?, ?)
                        ");
                        $stmt->execute([$conversation_id, $guest_name, $initial_message]);
                    }
                }

                echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error creating conversation']);
            }
            break;

        case 'send_message':
            $conversation_id = intval($input['conversation_id'] ?? 0);
            $sender_name = trim($input['sender_name'] ?? '');
            $message = trim($input['message'] ?? '');

            if ($conversation_id <= 0 || empty($sender_name) || empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (conversation_id, sender_type, sender_name, message_text) 
                    VALUES (?, 'guest', ?, ?)
                ");
                $stmt->execute([$conversation_id, $sender_name, $message]);
                $message_id = $pdo->lastInsertId();

                // Update conversation timestamp
                $stmt = $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$conversation_id]);

                echo json_encode([
                    'success' => true, 
                    'message' => 'Message sent',
                    'message_id' => $message_id
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error sending message']);
            }
            break;

        case 'get_messages':
            $conversation_id = intval($input['conversation_id'] ?? 0);
            $after_id = intval($input['after_id'] ?? 0); // New parameter for incremental loading

            if ($conversation_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
                exit;
            }

            try {
                // Build query based on whether we want all messages or just new ones
                if ($after_id > 0) {
                    // Get only messages after the specified ID (for polling)
                    $stmt = $pdo->prepare("
                        SELECT m.*, s.first_name as seller_first_name, s.last_name as seller_last_name
                        FROM messages m
                        LEFT JOIN conversations c ON m.conversation_id = c.id
                        LEFT JOIN sellers s ON c.seller_id = s.id
                        WHERE m.conversation_id = ? AND m.id > ?
                        ORDER BY m.sent_at ASC
                    ");
                    $stmt->execute([$conversation_id, $after_id]);
                } else {
                    // Get all messages (initial load)
                    $stmt = $pdo->prepare("
                        SELECT m.*, s.first_name as seller_first_name, s.last_name as seller_last_name
                        FROM messages m
                        LEFT JOIN conversations c ON m.conversation_id = c.id
                        LEFT JOIN sellers s ON c.seller_id = s.id
                        WHERE m.conversation_id = ?
                        ORDER BY m.sent_at ASC
                    ");
                    $stmt->execute([$conversation_id]);
                }
                
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Mark messages as read only if we're getting new messages (after_id > 0)
                // and only mark seller messages as read
                if ($after_id > 0 && !empty($messages)) {
                    $stmt = $pdo->prepare("
                        UPDATE messages SET is_read = 1 
                        WHERE conversation_id = ? AND sender_type = 'seller' AND id > ? AND is_read = 0
                    ");
                    $stmt->execute([$conversation_id, $after_id]);
                }

                // Get the highest message ID for the frontend to track
                $last_message_id = 0;
                if (!empty($messages)) {
                    $last_message_id = max(array_column($messages, 'id'));
                }

                echo json_encode([
                    'success' => true, 
                    'messages' => $messages,
                    'last_message_id' => $last_message_id,
                    'is_incremental' => $after_id > 0
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
            }
            break;

        case 'get_seller_info':
            $seller_id = intval($input['seller_id'] ?? 0);

            if ($seller_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid seller']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("
                    SELECT s.*, sa.business_name, st.stall_number
                    FROM sellers s
                    LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
                    LEFT JOIN stalls st ON s.id = st.current_seller_id
                    WHERE s.id = ? AND s.status = 'approved'
                ");
                $stmt->execute([$seller_id]);
                $seller = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($seller) {
                    echo json_encode(['success' => true, 'seller' => $seller]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Seller not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching seller info']);
            }
            break;

        case 'mark_messages_read':
            // Optional: Separate endpoint to mark messages as read
            $conversation_id = intval($input['conversation_id'] ?? 0);
            $message_ids = $input['message_ids'] ?? [];

            if ($conversation_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
                exit;
            }

            try {
                if (!empty($message_ids) && is_array($message_ids)) {
                    // Mark specific messages as read
                    $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
                    $stmt = $pdo->prepare("
                        UPDATE messages SET is_read = 1 
                        WHERE conversation_id = ? AND id IN ($placeholders) AND sender_type = 'seller'
                    ");
                    $stmt->execute(array_merge([$conversation_id], $message_ids));
                } else {
                    // Mark all unread seller messages as read
                    $stmt = $pdo->prepare("
                        UPDATE messages SET is_read = 1 
                        WHERE conversation_id = ? AND sender_type = 'seller' AND is_read = 0
                    ");
                    $stmt->execute([$conversation_id]);
                }

                echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error marking messages as read']);
            }
            break;

        case 'get_conversation_status':
            // Optional: Get conversation info and unread count
            $conversation_id = intval($input['conversation_id'] ?? 0);

            if ($conversation_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
                exit;
            }

            try {
                // Get conversation info
                $stmt = $pdo->prepare("
                    SELECT c.*, s.first_name, s.last_name, sa.business_name, st.stall_number
                    FROM conversations c
                    LEFT JOIN sellers s ON c.seller_id = s.id
                    LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
                    LEFT JOIN stalls st ON s.id = st.current_seller_id
                    WHERE c.id = ?
                ");
                $stmt->execute([$conversation_id]);
                $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$conversation) {
                    echo json_encode(['success' => false, 'message' => 'Conversation not found']);
                    exit;
                }

                // Get unread message count
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as unread_count
                    FROM messages 
                    WHERE conversation_id = ? AND sender_type = 'seller' AND is_read = 0
                ");
                $stmt->execute([$conversation_id]);
                $unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $unread_count = $unread_result['unread_count'] ?? 0;

                echo json_encode([
                    'success' => true,
                    'conversation' => $conversation,
                    'unread_count' => $unread_count
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching conversation status']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}

// If not AJAX request, return error
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>