<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Get chat statistics and recent conversations
try {
    // Total conversations
    $query = "SELECT COUNT(*) as total FROM conversations";
    $stmt = $pdo->query($query);
    $total_conversations = $stmt->fetchColumn();

    // Active conversations
    $query = "SELECT COUNT(*) as total FROM conversations WHERE status = 'active'";
    $stmt = $pdo->query($query);
    $active_conversations = $stmt->fetchColumn();

    // Unread messages count
    $query = "SELECT COUNT(*) as total FROM messages WHERE sender_type = 'guest' AND is_read = 0";
    $stmt = $pdo->query($query);
    $unread_messages = $stmt->fetchColumn();

    // Recent conversations with seller info and message counts
    $query = "SELECT 
                c.id,
                c.guest_name,
                c.guest_contact,
                c.status,
                c.created_at,
                c.updated_at,
                c.last_message_preview,
                s.username as seller_username,
                s.first_name as seller_first_name,
                s.last_name as seller_last_name,
                COUNT(m.id) as total_messages,
                SUM(CASE WHEN m.sender_type = 'guest' AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
              FROM conversations c
              LEFT JOIN sellers s ON c.seller_id = s.id
              LEFT JOIN messages m ON c.id = m.conversation_id
              GROUP BY c.id
              ORDER BY c.updated_at DESC
              LIMIT 20";
    $stmt = $pdo->query($query);
    $recent_conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Message statistics by seller
    $query = "SELECT 
                s.id,
                s.username,
                s.first_name,
                s.last_name,
                COUNT(DISTINCT c.id) as conversation_count,
                COUNT(m.id) as total_messages,
                SUM(CASE WHEN m.sender_type = 'guest' AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_messages,
                MAX(m.sent_at) as last_message_time
              FROM sellers s
              LEFT JOIN conversations c ON s.id = c.seller_id
              LEFT JOIN messages m ON c.id = m.conversation_id
              WHERE s.status = 'approved'
              GROUP BY s.id
              HAVING conversation_count > 0
              ORDER BY unread_messages DESC, last_message_time DESC
              LIMIT 15";
    $stmt = $pdo->query($query);
    $seller_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Chat overview query error: " . $e->getMessage());
    $total_conversations = $active_conversations = $unread_messages = 0;
    $recent_conversations = $seller_stats = [];
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 15px;
        }

        .stat-icon.conversations { background: linear-gradient(45deg, #3498db, #2980b9); }
        .stat-icon.active { background: linear-gradient(45deg, #27ae60, #229954); }
        .stat-icon.unread { background: linear-gradient(45deg, #e74c3c, #c0392b); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .conversation-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 15px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .conversation-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .conversation-card.has-unread {
            border-left-color: var(--danger-color);
            background: linear-gradient(90deg, #fff5f5 0%, white 10%);
        }

        .conversation-card.active {
            border-left-color: var(--success-color);
        }

        .seller-stats-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .seller-stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { background-color: #d4edda; color: #155724; }
        .status-archived { background-color: #e2e3e5; color: #6c757d; }
        .status-blocked { background-color: #f8d7da; color: #721c24; }

        .unread-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .time-ago {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .message-preview {
            color: #6c757d;
            font-style: italic;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1"><i class="bi bi-chat-dots me-2"></i>Chat Overview</h1>
                        <p class="mb-0">Monitor customer conversations and seller messaging activity</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-light" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon conversations">
                            <i class="bi bi-chat-square"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_conversations); ?></div>
                        <div class="text-muted">Total Conversations</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="bi bi-chat-square-dots"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($active_conversations); ?></div>
                        <div class="text-muted">Active Conversations</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon unread">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($unread_messages); ?></div>
                        <div class="text-muted">Unread Messages</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Conversations -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Conversations</h5>
                        </div>
                        <div class="card-body p-0">
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php if (empty($recent_conversations)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-chat-square text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">No conversations yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_conversations as $conversation): ?>
                                        <div class="conversation-card p-3 <?php echo $conversation['unread_count'] > 0 ? 'has-unread' : ($conversation['status'] === 'active' ? 'active' : ''); ?>">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <h6 class="mb-0 me-3">
                                                            <i class="bi bi-person me-1"></i>
                                                            <?php echo htmlspecialchars($conversation['guest_name']); ?>
                                                        </h6>
                                                        <?php if ($conversation['guest_contact']): ?>
                                                            <small class="text-muted">
                                                                <i class="bi bi-telephone me-1"></i>
                                                                <?php echo htmlspecialchars($conversation['guest_contact']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="text-muted me-3">
                                                            <i class="bi bi-shop me-1"></i>
                                                            Seller: <?php echo htmlspecialchars($conversation['seller_first_name'] . ' ' . $conversation['seller_last_name']); ?>
                                                            <small>(<?php echo htmlspecialchars($conversation['seller_username']); ?>)</small>
                                                        </span>
                                                    </div>

                                                    <?php if ($conversation['last_message_preview']): ?>
                                                        <div class="message-preview mb-2">
                                                            "<?php echo htmlspecialchars($conversation['last_message_preview']); ?>"
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="d-flex align-items-center">
                                                        <span class="status-badge status-<?php echo $conversation['status']; ?> me-3">
                                                            <?php echo ucfirst($conversation['status']); ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            <i class="bi bi-chat me-1"></i>
                                                            <?php echo $conversation['total_messages']; ?> messages
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-end">
                                                    <?php if ($conversation['unread_count'] > 0): ?>
                                                        <div class="unread-badge mb-2">
                                                            <?php echo $conversation['unread_count']; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="time-ago">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?php 
                                                            $time_diff = time() - strtotime($conversation['updated_at']);
                                                            if ($time_diff < 3600) {
                                                                echo ceil($time_diff / 60) . 'm ago';
                                                            } elseif ($time_diff < 86400) {
                                                                echo ceil($time_diff / 3600) . 'h ago';
                                                            } else {
                                                                echo date('M j', strtotime($conversation['updated_at']));
                                                            }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seller Message Statistics -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Seller Activity</h5>
                        </div>
                        <div class="card-body p-0">
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php if (empty($seller_stats)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">No seller activity yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($seller_stats as $seller): ?>
                                        <div class="seller-stats-card p-3">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h6 class="mb-0">
                                                    <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?>
                                                </h6>
                                                <?php if ($seller['unread_messages'] > 0): ?>
                                                    <div class="unread-badge">
                                                        <?php echo $seller['unread_messages']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="text-muted mb-2">
                                                <small>@<?php echo htmlspecialchars($seller['username']); ?></small>
                                            </div>

                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="fw-bold text-primary"><?php echo $seller['conversation_count']; ?></div>
                                                    <small class="text-muted">Chats</small>
                                                </div>
                                                <div class="col-4">
                                                    <div class="fw-bold text-success"><?php echo $seller['total_messages']; ?></div>
                                                    <small class="text-muted">Messages</small>
                                                </div>
                                                <div class="col-4">
                                                    <div class="fw-bold text-danger"><?php echo $seller['unread_messages']; ?></div>
                                                    <small class="text-muted">Unread</small>
                                                </div>
                                            </div>

                                            <?php if ($seller['last_message_time']): ?>
                                                <div class="text-center mt-2">
                                                    <small class="time-ago">
                                                        Last: <?php 
                                                            $time_diff = time() - strtotime($seller['last_message_time']);
                                                            if ($time_diff < 3600) {
                                                                echo ceil($time_diff / 60) . 'm ago';
                                                            } elseif ($time_diff < 86400) {
                                                                echo ceil($time_diff / 3600) . 'h ago';
                                                            } else {
                                                                echo date('M j, g:i A', strtotime($seller['last_message_time']));
                                                            }
                                                        ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="dashboard.php" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-house me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="manage_sellers.php" class="btn btn-outline-success w-100">
                                        <i class="bi bi-people me-2"></i>Manage Sellers
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-info w-100" onclick="exportChatData()">
                                        <i class="bi bi-download me-2"></i>Export Data
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-warning w-100" onclick="showChatSettings()">
                                        <i class="bi bi-gear me-2"></i>Chat Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh page every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Export chat data function
        function exportChatData() {
            alert('Chat data export functionality would be implemented here.');
        }

        // Show chat settings function
        function showChatSettings() {
            alert('Chat settings modal would be implemented here.');
        }

        // Add real-time updates using WebSocket (placeholder)
        // This would connect to a WebSocket server for real-time updates
        function initializeRealTimeUpdates() {
            // WebSocket implementation would go here
            console.log('Real-time updates would be initialized here');
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeRealTimeUpdates();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>