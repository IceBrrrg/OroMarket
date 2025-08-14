<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

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
    if (!isset($_GET['complaint_id']) || empty($_GET['complaint_id'])) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID is required']);
        exit;
    }
    
    $complaint_id = intval($_GET['complaint_id']);
    
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
    if (!isset($_POST['seller_id']) || !isset($_POST['message_text'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields (seller_id, message_text)']);
        exit;
    }
    
    $seller_id = intval($_POST['seller_id']);
    $message_text = trim($_POST['message_text']);
    $admin_name = $_POST['admin_name'] ?? 'Admin';
    
    if ($seller_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid seller ID']);
        exit;
    }
    
    if (empty($message_text)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Seller not found']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE guest_name = ? AND seller_id = ?");
        $stmt->execute([$admin_name, $seller_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            $stmt = $pdo->prepare("INSERT INTO conversations (guest_name, guest_contact, seller_id, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
            $stmt->execute([$admin_name, 'admin@oroquieta-marketplace.com', $seller_id]);
            $conversation_id = $pdo->lastInsertId();
        } else {
            $conversation_id = $conversation['id'];
        }
        
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oroquieta Marketplace - Complaint Manager</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, .1);
            --border-radius: 8px;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .stats-cards {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.total { border-left-color: var(--primary-color); }
        .stat-card.pending { border-left-color: var(--warning-color); }
        .stat-card.resolved { border-left-color: var(--success-color); }
        .stat-card.sellers { border-left-color: var(--info-color); }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .controls-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .complaints-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .complaints-table {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #2f6186;
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-resolved {
            background-color: #d1eddd;
            color: #0f5132;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close:hover {
            color: #ccc;
        }

        #modalBody {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Chat Modal Styles */
        .chat-modal {
            display: none;
            position: fixed;
            z-index: 1055;
            right: 20px;
            bottom: 20px;
            width: 400px;
            height: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.3s ease-out;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .chat-close:hover {
            color: #ccc;
        }

        .chat-messages {
            height: 350px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
        }

        .message-bubble {
            margin: 0.5rem 0;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            max-width: 80%;
            word-wrap: break-word;
        }

        .message-admin {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            margin-left: auto;
            text-align: right;
        }

        .message-seller {
            background: white;
            color: #333;
            border: 1px solid #e9ecef;
            margin-right: auto;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .chat-input {
            padding: 1rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 0.5rem;
        }

        .chat-input input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius);
        }

        .chat-send {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
        }

        .chat-send:hover {
            opacity: 0.9;
        }

        .loading-spinner {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .loading-spinner i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .chat-loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        /* Animations */
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes slideInUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .chat-modal {
                right: 10px;
                bottom: 10px;
                width: calc(100vw - 20px);
                max-width: 350px;
            }

            .table {
                font-size: 0.9rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }

        /* Additional utility styles */
        .complaint-title {
            font-weight: 600;
            color: var(--primary-color);
        }

        .seller-info {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .actions {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .table-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Complaint Manager</h1>
                    <p class="mb-0 opacity-75">Manage and resolve customer complaints efficiently</p>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($message) && isset($message_type)): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card total">
                        <div class="stat-number text-primary"><?php echo $stats['total_complaints']; ?></div>
                        <div class="stat-label">Total Complaints</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card pending">
                        <div class="stat-number text-warning"><?php echo $stats['pending_complaints']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card resolved">
                        <div class="stat-number text-success"><?php echo $stats['resolved_complaints']; ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card sellers">
                        <div class="stat-number text-info"><?php echo $stats['sellers_with_complaints']; ?></div>
                        <div class="stat-label">Sellers with Complaints</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls-container">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="search" class="form-label fw-bold">
                        <i class="bi bi-search text-primary me-1"></i>Search Complaints
                    </label>
                    <input type="text" id="search" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by title, complainant, or seller...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label fw-bold">
                        <i class="bi bi-filter text-primary me-1"></i>Filter by Status
                    </label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        <a href="complaint_manager.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Complaints List -->
        <div class="complaints-container">
            <div class="table-header">
                <h2><i class="bi bi-list-ul me-2"></i>Complaints List</h2>
            </div>
            
            <?php if (empty($complaints)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4>No Complaints Found</h4>
                    <p>No complaints match your current filter criteria.</p>
                </div>
            <?php else: ?>
                <div class="complaints-table">
    <table class="table table-hover">
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
                    <td><span class="badge bg-secondary">#<?php echo $complaint['id']; ?></span></td>
                    <td>
                        <div class="complaint-title" title="<?php echo htmlspecialchars($complaint['title']); ?>">
                            <?php echo htmlspecialchars(strlen($complaint['title']) > 30 ? substr($complaint['title'], 0, 30) . '...' : $complaint['title']); ?>
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
                    <td>
                        <i class="bi bi-calendar me-1"></i>
                        <?php echo date('M d, Y', strtotime($complaint['created_at'])); ?>
                    </td>
                    <td>
                        <div class="actions">
                            <button onclick="viewComplaint(<?php echo $complaint['id']; ?>)" 
                                    class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            
                            <?php if ($complaint['seller_id']): ?>
                                <button onclick="openChat(<?php echo $complaint['seller_id']; ?>, '<?php echo htmlspecialchars(trim($complaint['seller_name']) ?: 'Unknown Seller'); ?>')" 
                                        class="btn btn-sm btn-info" title="Chat with Seller">
                                    <i class="bi bi-chat-dots"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($complaint['status'] === 'pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="btn btn-sm btn-success" 
                                            title="Mark as Resolved"
                                            onclick="return confirm('Mark this complaint as resolved?')">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" class="btn btn-sm btn-warning" 
                                            title="Mark as Pending"
                                            onclick="return confirm('Mark this complaint as pending?')">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        title="Delete Complaint"
                                        onclick="return confirm('Are you sure you want to delete this complaint? This action cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for viewing complaint details -->
    <div id="complaintModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-file-text me-2"></i>Complaint Details</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody">
                <div class="loading-spinner">
                    <i class="bi bi-hourglass-split"></i>
                    <p>Loading complaint details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chatModal" class="chat-modal">
        <div class="chat-header">
            <h3 id="chatSellerName"><i class="bi bi-chat-dots me-2"></i>Chat with Seller</h3>
            <button class="chat-close" onclick="closeChat()">&times;</button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="chat-loading">
                <i class="bi bi-hourglass-split"></i>
                <p>Loading conversation...</p>
            </div>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" class="form-control" placeholder="Type your message..." maxlength="500">
            <button class="chat-send" onclick="sendMessage()">
                <i class="bi bi-send"></i>
            </button>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentSellerId = null;
        let chatUpdateInterval = null;
        let currentComplaintData = null;
        const adminName = 'Admin';

        function viewComplaint(complaintId) {
            if (!complaintId || complaintId <= 0) {
                alert('Invalid complaint ID');
                return;
            }
            
            document.getElementById('complaintModal').style.display = 'block';
            document.getElementById('modalBody').innerHTML = `
                <div class="loading-spinner">
                    <i class="bi bi-hourglass-split"></i>
                    <p>Loading complaint details...</p>
                </div>
            `;
            
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
                            <div class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                                <p>Error loading complaint details: ${escapeHtml(data.message)}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalBody').innerHTML = `
                        <div class="text-center text-danger">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                            <p>Error loading complaint details. Please try again.</p>
                        </div>
                    `;
                });
        }

        function displayComplaintDetails(complaint) {
            const modalBody = document.getElementById('modalBody');
            
            let chatButton = '';
            if (complaint.seller_id) {
                chatButton = `
                    <div class="mt-4">
                        <button onclick="openChatFromModal(${complaint.seller_id}, '${escapeHtml(complaint.seller_name || 'Unknown Seller')}')" 
                                class="btn btn-info">
                            <i class="bi bi-chat-dots me-2"></i>Chat with Seller
                        </button>
                    </div>
                `;
            }
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="text-primary">${escapeHtml(complaint.title)}</h3>
                            <span class="status-badge status-${complaint.status}">${complaint.status.charAt(0).toUpperCase() + complaint.status.slice(1)}</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="bi bi-person me-2"></i>Complainant Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> ${escapeHtml(complaint.complainant_name)}</p>
                                <p><strong>Email:</strong> ${escapeHtml(complaint.complainant_email)}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0"><i class="bi bi-shop me-2"></i>Seller Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> ${escapeHtml(complaint.seller_name || 'Unknown')}</p>
                                <p><strong>Username:</strong> ${escapeHtml(complaint.seller_username || 'N/A')}</p>
                                <p><strong>Email:</strong> ${escapeHtml(complaint.seller_email || 'N/A')}</p>
                                <p><strong>Phone:</strong> ${escapeHtml(complaint.seller_phone || 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0"><i class="bi bi-file-text me-2"></i>Complaint Description</h5>
                            </div>
                            <div class="card-body">
                                <div class="bg-light p-3 rounded" style="white-space: pre-wrap;">
                                    ${escapeHtml(complaint.description)}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Additional Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Date Filed:</strong> ${new Date(complaint.created_at).toLocaleString()}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Complaint ID:</strong> <span class="badge bg-secondary">#${complaint.id}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${chatButton}
            `;
        }
        
        function closeModal() {
            document.getElementById('complaintModal').style.display = 'none';
            currentComplaintData = null;
        }

        function openChatFromModal(sellerId, sellerName) {
            closeModal();
            openChat(sellerId, sellerName);
        }

        function openChat(sellerId, sellerName) {
            if (!sellerId || sellerId <= 0) {
                alert('Invalid seller ID');
                return;
            }
            
            currentSellerId = sellerId;
            document.getElementById('chatSellerName').innerHTML = `<i class="bi bi-chat-dots me-2"></i>Chat with ${sellerName}`;
            document.getElementById('chatModal').style.display = 'block';
            
            loadChatMessages();
            
            if (chatUpdateInterval) {
                clearInterval(chatUpdateInterval);
            }
            chatUpdateInterval = setInterval(loadChatMessages, 3000);
        }

        function closeChat() {
            document.getElementById('chatModal').style.display = 'none';
            currentSellerId = null;
            
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
                            <div class="text-center text-danger p-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <p>Error loading messages: ${escapeHtml(data.message)}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('chatMessages').innerHTML = `
                        <div class="text-center text-danger p-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            <p>Error loading messages. Please try again.</p>
                        </div>
                    `;
                });
        }

        function displayMessages(messages) {
            const chatMessages = document.getElementById('chatMessages');
            
            if (messages.length === 0 || !messages[0].id) {
                chatMessages.innerHTML = `
                    <div class="text-center text-muted p-4">
                        <i class="bi bi-chat-dots" style="font-size: 2em; margin-bottom: 10px; opacity: 0.5;"></i>
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
                    loadChatMessages();
                } else {
                    alert('Error sending message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            })
            .finally(() => {
                input.disabled = false;
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
    </script>
</body>
</html>