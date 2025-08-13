<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'oroquieta_marketplace';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX request for complaint details
if (isset($_GET['action']) && $_GET['action'] === 'get_complaint_details') {
    // Check if complaint_id is provided
    if (!isset($_GET['complaint_id']) || empty($_GET['complaint_id'])) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID is required']);
        exit;
    }
    
    $complaint_id = intval($_GET['complaint_id']); // Ensure it's an integer
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   CONCAT(s.first_name, ' ', s.last_name) as seller_name,
                   s.username as seller_username,
                   s.email as seller_email,
                   s.phone as seller_phone
            FROM complaints c 
            LEFT JOIN sellers s ON c.seller_id = s.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$complaint_id]);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($complaint) {
            echo json_encode(['success' => true, 'complaint' => $complaint]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Complaint not found with ID: ' . $complaint_id]);
        }
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle complaint status updates
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!isset($_POST['complaint_id']) || !isset($_POST['status'])) {
        $message = "Missing required parameters for status update";
        $message_type = "error";
    } else {
        $complaint_id = intval($_POST['complaint_id']);
        $new_status = $_POST['status'];
        
        // Validate status
        if (!in_array($new_status, ['pending', 'resolved'])) {
            $message = "Invalid status value";
            $message_type = "error";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
                $result = $stmt->execute([$new_status, $complaint_id]);
                
                if ($stmt->rowCount() > 0) {
                    $message = "Complaint status updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Complaint not found or no changes made";
                    $message_type = "error";
                }
            } catch(PDOException $e) {
                $message = "Error updating complaint: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
}

// Handle complaint deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['complaint_id'])) {
        $message = "Missing complaint ID for deletion";
        $message_type = "error";
    } else {
        $complaint_id = intval($_POST['complaint_id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
            $result = $stmt->execute([$complaint_id]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Complaint deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Complaint not found";
                $message_type = "error";
            }
        } catch(PDOException $e) {
            $message = "Error deleting complaint: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Handle sending message to seller
if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    // Validate required fields
    if (!isset($_POST['seller_id']) || !isset($_POST['message_text'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields (seller_id, message_text)']);
        exit;
    }
    
    $seller_id = intval($_POST['seller_id']);
    $message_text = trim($_POST['message_text']);
    $admin_name = $_POST['admin_name'] ?? 'Admin';
    
    // Validate inputs
    if ($seller_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid seller ID']);
        exit;
    }
    
    if (empty($message_text)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    try {
        // First, check if seller exists
        $stmt = $pdo->prepare("SELECT id FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Seller not found']);
            exit;
        }
        
        // Check if conversation already exists
        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE guest_name = ? AND seller_id = ?");
        $stmt->execute([$admin_name, $seller_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            // Create new conversation
            $stmt = $pdo->prepare("INSERT INTO conversations (guest_name, guest_contact, seller_id, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
            $stmt->execute([$admin_name, 'admin@oroquieta-marketplace.com', $seller_id]);
            $conversation_id = $pdo->lastInsertId();
        } else {
            $conversation_id = $conversation['id'];
        }
        
        // Insert message
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_type, sender_name, message_text, sent_at) VALUES (?, 'guest', ?, ?, NOW())");
        $stmt->execute([$conversation_id, $admin_name, $message_text]);
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle fetching chat messages
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    if (!isset($_GET['seller_id'])) {
        echo json_encode(['success' => false, 'message' => 'Seller ID is required']);
        exit;
    }
    
    $seller_id = intval($_GET['seller_id']);
    $admin_name = $_GET['admin_name'] ?? 'Admin';
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, c.id as conversation_id 
            FROM conversations c 
            LEFT JOIN messages m ON c.id = m.conversation_id 
            WHERE c.guest_name = ? AND c.seller_id = ? 
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute([$admin_name, $seller_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch complaints with seller information
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT c.*, 
               CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as seller_name,
               s.username as seller_username,
               s.email as seller_email,
               s.phone as seller_phone
        FROM complaints c 
        LEFT JOIN sellers s ON c.seller_id = s.id 
        WHERE 1=1";

$params = [];

if ($filter_status !== 'all') {
    $sql .= " AND c.status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $sql .= " AND (c.title LIKE ? OR c.complainant_name LIKE ? OR c.complainant_email LIKE ? OR CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$sql .= " ORDER BY c.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $complaints = [];
    $error = "Error fetching complaints: " . $e->getMessage();
}

// Get complaint statistics
try {
    $stats_sql = "SELECT 
                    COUNT(*) as total_complaints,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints,
                    COUNT(DISTINCT seller_id) as sellers_with_complaints
                  FROM complaints";
    $stats_stmt = $pdo->query($stats_sql);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = ['total_complaints' => 0, 'pending_complaints' => 0, 'resolved_complaints' => 0, 'sellers_with_complaints' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Manager - Oroquieta Marketplace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/complaints.css">
</head>
<body>
    

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-exclamation-triangle"></i> Complaint Manager</h1>
            <p>Manage and resolve customer complaints efficiently</p>
        </div>

        <!-- Display messages -->
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Display error if any -->
        <?php if (isset($error)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-number total"><?php echo $stats['total_complaints']; ?></div>
                <div class="stat-label">Total Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                <div class="stat-number pending"><?php echo $stats['pending_complaints']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon resolved"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number resolved"><?php echo $stats['resolved_complaints']; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon sellers"><i class="fas fa-store"></i></div>
                <div class="stat-number sellers"><?php echo $stats['sellers_with_complaints']; ?></div>
                <div class="stat-label">Sellers with Complaints</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <form method="GET" class="controls-row">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search complaints..." class="search-box">
                
                <select name="status" class="filter-select">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
                
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Filter
                </button>
                
                <a href="complaint_manager.php" class="btn">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </form>
        </div>

        <!-- Complaints Table -->
        <div class="complaints-table">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Complaints List</h2>
            </div>
            
            <?php if (empty($complaints)): ?>
                <div class="no-complaints">
                    <i class="fas fa-inbox" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                    <p>No complaints found matching your criteria.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Complainant</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><?php echo $complaint['id']; ?></td>
                                <td>
                                    <div class="complaint-details" title="<?php echo htmlspecialchars($complaint['title']); ?>">
                                        <strong><?php echo htmlspecialchars($complaint['title']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($complaint['complainant_name']); ?></strong><br>
                                    <small class="seller-info"><?php echo htmlspecialchars($complaint['complainant_email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(trim($complaint['seller_name']) ?: 'Unknown'); ?></strong><br>
                                    <small class="seller-info"><?php echo htmlspecialchars($complaint['seller_username'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $complaint['status']; ?>">
                                        <?php echo ucfirst($complaint['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button onclick="viewComplaint(<?php echo $complaint['id']; ?>)" 
                                                class="btn btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($complaint['seller_id']): ?>
                                            <button onclick="openChat(<?php echo $complaint['seller_id']; ?>, '<?php echo htmlspecialchars(trim($complaint['seller_name']) ?: 'Unknown Seller'); ?>')" 
                                                    class="btn btn-sm btn-info" title="Chat with Seller">
                                                <i class="fas fa-comments"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($complaint['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                <input type="hidden" name="status" value="resolved">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        title="Mark as Resolved"
                                                        onclick="return confirm('Mark this complaint as resolved?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="btn btn-sm" 
                                                        title="Mark as Pending"
                                                        onclick="return confirm('Mark this complaint as pending?')">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    title="Delete Complaint"
                                                    onclick="return confirm('Are you sure you want to delete this complaint? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for viewing complaint details -->
    <div id="complaintModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Complaint Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading complaint details...
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chatModal" class="chat-modal">
        <div class="chat-header">
            <h3 id="chatSellerName">Chat with Seller</h3>
            <button class="chat-close" onclick="closeChat()">&times;</button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="chat-loading">Loading conversation...</div>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type your message..." maxlength="500">
            <button class="chat-send" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        let currentSellerId = null;
        let chatUpdateInterval = null;
        let currentComplaintData = null;
        const adminName = 'Admin'; // You can make this dynamic based on logged-in admin

        function viewComplaint(complaintId) {
            // Validate complaint ID
            if (!complaintId || complaintId <= 0) {
                alert('Invalid complaint ID');
                return;
            }
            
            document.getElementById('complaintModal').style.display = 'block';
            document.getElementById('modalBody').innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading complaint details...
                </div>
            `;
            
            // Fetch complaint details via AJAX
            fetch(`?action=get_complaint_details&complaint_id=${encodeURIComponent(complaintId)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        currentComplaintData = data.complaint;
                        displayComplaintDetails(data.complaint);
                    } else {
                        document.getElementById('modalBody').innerHTML = `
                            <div style="text-align: center; color: #e74c3c;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                                <p>Error loading complaint details: ${escapeHtml(data.message)}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalBody').innerHTML = `
                        <div style="text-align: center; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                            <p>Error loading complaint details. Please check the console for more information.</p>
                        </div>
                    `;
                });
        }

        function displayComplaintDetails(complaint) {
            const modalBody = document.getElementById('modalBody');
            
            let chatButton = '';
            if (complaint.seller_id) {
                chatButton = `
                    <div class="modal-actions">
                        <button onclick="openChatFromModal(${complaint.seller_id}, '${escapeHtml(complaint.seller_name || 'Unknown Seller')}')" 
                                class="btn btn-info">
                            <i class="fas fa-comments"></i> Chat with Seller
                        </button>
                    </div>
                `;
            }
            
            modalBody.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h3>${escapeHtml(complaint.title)}</h3>
                    <p><strong>Status:</strong> <span class="status-badge status-${complaint.status}">${complaint.status.charAt(0).toUpperCase() + complaint.status.slice(1)}</span></p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4><i class="fas fa-user"></i> Complainant Information</h4>
                    <p><strong>Name:</strong> ${escapeHtml(complaint.complainant_name)}</p>
                    <p><strong>Email:</strong> ${escapeHtml(complaint.complainant_email)}</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4><i class="fas fa-store"></i> Seller Information</h4>
                    <p><strong>Name:</strong> ${escapeHtml(complaint.seller_name || 'Unknown')}</p>
                    <p><strong>Username:</strong> ${escapeHtml(complaint.seller_username || 'N/A')}</p>
                    <p><strong>Email:</strong> ${escapeHtml(complaint.seller_email || 'N/A')}</p>
                    <p><strong>Phone:</strong> ${escapeHtml(complaint.seller_phone || 'N/A')}</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4><i class="fas fa-file-alt"></i> Complaint Description</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; white-space: pre-wrap; border-left: 4px solid #667eea;">
                        ${escapeHtml(complaint.description)}
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4><i class="fas fa-calendar"></i> Additional Information</h4>
                    <p><strong>Date Filed:</strong> ${new Date(complaint.created_at).toLocaleString()}</p>
                    <p><strong>Complaint ID:</strong> #${complaint.id}</p>
                </div>
                
                ${chatButton}
            `;
        }
        
        function closeModal() {
            document.getElementById('complaintModal').style.display = 'none';
            currentComplaintData = null;
        }

        function openChatFromModal(sellerId, sellerName) {
            closeModal(); // Close the complaint modal first
            openChat(sellerId, sellerName);
        }

        function openChat(sellerId, sellerName) {
            // Validate seller ID
            if (!sellerId || sellerId <= 0) {
                alert('Invalid seller ID');
                return;
            }
            
            currentSellerId = sellerId;
            document.getElementById('chatSellerName').textContent = `Chat with ${sellerName}`;
            document.getElementById('chatModal').style.display = 'block';
            
            // Load messages
            loadChatMessages();
            
            // Start auto-refresh
            if (chatUpdateInterval) {
                clearInterval(chatUpdateInterval);
            }
            chatUpdateInterval = setInterval(loadChatMessages, 3000); // Refresh every 3 seconds
        }

        function closeChat() {
            document.getElementById('chatModal').style.display = 'none';
            currentSellerId = null;
            
            // Stop auto-refresh
            if (chatUpdateInterval) {
                clearInterval(chatUpdateInterval);
                chatUpdateInterval = null;
            }
        }

        function loadChatMessages() {
            if (!currentSellerId) return;
            
            fetch(`?action=get_messages&seller_id=${encodeURIComponent(currentSellerId)}&admin_name=${encodeURIComponent(adminName)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    } else {
                        console.error('Error loading messages:', data.message);
                        document.getElementById('chatMessages').innerHTML = `
                            <div style="text-align: center; padding: 20px; color: #e74c3c;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Error loading messages: ${escapeHtml(data.message)}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('chatMessages').innerHTML = `
                        <div style="text-align: center; padding: 20px; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error loading messages. Please try again.</p>
                        </div>
                    `;
                });
        }

        function displayMessages(messages) {
            const chatMessages = document.getElementById('chatMessages');
            
            if (messages.length === 0 || !messages[0].id) {
                chatMessages.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <i class="fas fa-comments" style="font-size: 2em; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                `;
                return;
            }
            
            let messagesHtml = '';
            messages.forEach(message => {
                if (message.message_text) {
                    const isAdmin = message.sender_type === 'guest';
                    const messageClass = isAdmin ? 'message-admin' : 'message-seller';
                    const senderName = isAdmin ? 'Admin' : message.sender_name;
                    const messageTime = new Date(message.sent_at).toLocaleString();
                    
                    messagesHtml += `
                        <div class="message-bubble ${messageClass}">
                            <div><strong>${escapeHtml(senderName)}</strong></div>
                            <div>${escapeHtml(message.message_text)}</div>
                            <div class="message-time">${messageTime}</div>
                        </div>
                    `;
                }
            });
            
            chatMessages.innerHTML = messagesHtml;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const messageText = input.value.trim();
            
            if (!messageText) {
                alert('Please enter a message');
                return;
            }
            
            if (!currentSellerId) {
                alert('No seller selected');
                return;
            }
            
            // Disable input while sending
            input.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('seller_id', currentSellerId);
            formData.append('message_text', messageText);
            formData.append('admin_name', adminName);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadChatMessages(); // Refresh messages
                } else {
                    alert('Error sending message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            })
            .finally(() => {
                input.disabled = false; // Re-enable input
                input.focus();
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const complaintModal = document.getElementById('complaintModal');
            const chatModal = document.getElementById('chatModal');
            
            if (event.target == complaintModal) {
                closeModal();
            }
            if (event.target == chatModal) {
                closeChat();
            }
        }

        // Handle Enter key in chat input
        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.getElementById('chatInput');
            if (chatInput) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
        });

        // Add some basic error handling for missing elements
        function handleError(message) {
            console.error(message);
            alert(message);
        }

        // Validate that required elements exist
        document.addEventListener('DOMContentLoaded', function() {
            const requiredElements = [
                'complaintModal',
                'chatModal', 
                'modalBody',
                'chatMessages',
                'chatInput',
                'chatSellerName'
            ];
            
            requiredElements.forEach(id => {
                if (!document.getElementById(id)) {
                    console.error(`Required element with ID '${id}' not found`);
                }
            });
        });
    </script>
</body>
</html>