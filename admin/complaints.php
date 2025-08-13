<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $complaint_id = (int)$_POST['complaint_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $complaint_id]);
        $success_message = "Complaint status updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating complaint status: " . $e->getMessage();
    }
}

// Create complaints table if it doesn't exist
try {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `complaints` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `complainant_name` varchar(100) NOT NULL,
        `complainant_email` varchar(100) NOT NULL,
        `seller_id` int(11) NOT NULL,
        `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_seller_id` (`seller_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`),
        CONSTRAINT `fk_complaints_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($create_table_sql);
} catch (PDOException $e) {
    // Table creation failed, but continue anyway
    error_log("Failed to create complaints table: " . $e->getMessage());
}

// Get complaints with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filter by status if specified
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if ($status_filter) {
        $where_conditions[] = "c.status = ?";
        $params[] = $status_filter;
    }
    
    if ($search_query) {
        $where_conditions[] = "(c.title LIKE ? OR c.complainant_name LIKE ? OR c.complainant_email LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM complaints c 
                    LEFT JOIN sellers s ON c.seller_id = s.id 
                    $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_complaints = $count_stmt->fetchColumn();
    $total_pages = ceil($total_complaints / $per_page);
    
    // Get complaints with seller info and business name from applications
    $query = "SELECT c.*, s.first_name, s.last_name, sa.business_name
              FROM complaints c 
              LEFT JOIN sellers s ON c.seller_id = s.id 
              LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
              $where_clause
              ORDER BY c.created_at DESC 
              LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get complaint statistics
    $stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                    FROM complaints";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching complaints: " . $e->getMessage();
    $complaints = [];
    $stats = ['total' => 0, 'pending' => 0, 'resolved' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Complaints - OroMarket Admin</title>
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
            --info-color: #17a2b8;
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

        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-card .card-body {
            padding: 1.5rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 15px;
        }

        .stat-icon.total { background: linear-gradient(45deg, #3498db, #2980b9); }
        .stat-icon.pending { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .stat-icon.resolved { background: linear-gradient(45deg, #27ae60, #229954); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 0;
        }

        .filter-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .filter-card .card-header {
            background: linear-gradient(90deg, #f8f9fa, #ffffff);
            border: none;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .complaints-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .complaints-card .card-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1.25rem 1.5rem;
        }

        .complaint-item {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            margin-bottom: 1rem;
            overflow: hidden;
            border-left: 4px solid #dee2e6;
        }

        .complaint-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .complaint-item.priority-high {
            border-left-color: var(--danger-color);
        }

        .complaint-item.priority-medium {
            border-left-color: var(--warning-color);
        }

        .complaint-item.priority-low {
            border-left-color: var(--success-color);
        }

        .complaint-item .card-body {
            padding: 1.5rem;
        }

        .complaint-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(45deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-resolved {
            background: linear-gradient(45deg, #d4edda, #a8e6a3);
            color: #155724;
            border: 1px solid #a8e6a3;
        }

        .complaint-meta {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }

        .complaint-meta strong {
            color: var(--primary-color);
        }

        .complaint-description {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .action-buttons .btn {
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            margin-right: 0.5rem;
        }

        .btn-view {
            background: linear-gradient(45deg, var(--info-color), #138496);
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(45deg, #138496, #117a8b);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
            color: white;
        }

        .btn-resolve {
            background: linear-gradient(45deg, var(--success-color), #1e7e34);
            color: white;
        }

        .btn-resolve:hover {
            background: linear-gradient(45deg, #1e7e34, #155724);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .btn-reopen {
            background: linear-gradient(45deg, var(--warning-color), #d39e00);
            color: white;
        }

        .btn-reopen:hover {
            background: linear-gradient(45deg, #d39e00, #b58900);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
            color: white;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            transform: translateY(-2px);
        }

        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            border-radius: 8px;
            border: none;
            margin: 0 2px;
            padding: 10px 15px;
            color: var(--primary-color);
            font-weight: 500;
            transition: all 0.3s;
        }

        .page-item.active .page-link {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border-color: transparent;
        }

        .page-link:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(45deg, #f8d7da, #f1b0b7);
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        .complaint-timestamp {
            font-size: 0.85rem;
            color: #9ca3af;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .page-header {
                text-align: center;
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .complaint-item .row {
                flex-direction: column;
            }

            .complaint-item .action-buttons {
                margin-top: 1rem;
                text-align: center;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .complaint-item {
            animation: fadeInUp 0.6s ease-out;
        }

        .complaint-item:nth-child(even) {
            animation-delay: 0.1s;
        }

        .complaint-item:nth-child(odd) {
            animation-delay: 0.2s;
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
                        <h1 class="mb-1">
                            <i class="bi bi-exclamation-triangle-fill me-3"></i>
                            Customer Complaints
                        </h1>
                        <p class="mb-0">Manage and resolve customer complaints efficiently</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="text-white-50">
                            <i class="bi bi-calendar3"></i> 
                            <?php echo date('F j, Y'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stats-card card">
                        <div class="card-body text-center">
                            <div class="stat-icon total mx-auto">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <h3 class="stat-number"><?php echo number_format($stats['total']); ?></h3>
                            <p class="stat-label">Total Complaints</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card card">
                        <div class="card-body text-center">
                            <div class="stat-icon pending mx-auto">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <h3 class="stat-number"><?php echo number_format($stats['pending']); ?></h3>
                            <p class="stat-label">Pending Resolution</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card card">
                        <div class="card-body text-center">
                            <div class="stat-icon resolved mx-auto">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h3 class="stat-number"><?php echo number_format($stats['resolved']); ?></h3>
                            <p class="stat-label">Resolved Cases</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel-fill me-2"></i>
                        Filter Complaints
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label for="search" class="form-label fw-semibold">
                                <i class="bi bi-search text-primary me-1"></i>Search
                            </label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Search by title, name, or email...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">
                                <i class="bi bi-flag text-warning me-1"></i>Status
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                    Pending
                                </option>
                                <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>
                                    Resolved
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Apply Filters
                            </button>
                            <a href="complaints.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Complaints List -->
            <div class="complaints-card card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>
                            Complaints Overview
                        </h5>
                        <span class="badge bg-light text-dark px-3 py-2">
                            <?php echo number_format($total_complaints); ?> Total Results
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($complaints)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5 class="mt-3">No Complaints Found</h5>
                            <p>There are no complaints matching your current filters.</p>
                            <?php if ($status_filter || $search_query): ?>
                                <a href="complaints.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="complaints-list">
                            <?php foreach ($complaints as $complaint): ?>
                                <?php 
                                $priority_class = 'priority-low'; // Default
                                $days_old = floor((time() - strtotime($complaint['created_at'])) / (60 * 60 * 24));
                                
                                if ($complaint['status'] === 'pending') {
                                    if ($days_old > 7) $priority_class = 'priority-high';
                                    elseif ($days_old > 3) $priority_class = 'priority-medium';
                                }
                                ?>
                                
                                <div class="complaint-item card <?php echo $priority_class; ?>">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="complaint-title">
                                                    <?php echo htmlspecialchars($complaint['title']); ?>
                                                    <span class="status-badge status-<?php echo $complaint['status']; ?> ms-2">
                                                        <?php echo $complaint['status'] === 'pending' ? 'Pending' : 'Resolved'; ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="complaint-meta">
                                                    <div class="mb-1">
                                                        <i class="bi bi-person-circle me-1"></i>
                                                        <strong>From:</strong> 
                                                        <?php echo htmlspecialchars($complaint['complainant_name']); ?>
                                                        <span class="text-muted">
                                                            (<?php echo htmlspecialchars($complaint['complainant_email']); ?>)
                                                        </span>
                                                    </div>
                                                    <div class="mb-1">
                                                        <i class="bi bi-shop me-1"></i>
                                                        <strong>Against:</strong> 
                                                        <?php echo htmlspecialchars($complaint['business_name'] ?: $complaint['first_name'] . ' ' . $complaint['last_name']); ?>
                                                    </div>
                                                    <div>
                                                        <i class="bi bi-calendar3 me-1"></i>
                                                        <span class="complaint-timestamp">
                                                            <?php echo date('F j, Y \a\t g:i A', strtotime($complaint['created_at'])); ?>
                                                            <?php if ($days_old > 0): ?>
                                                                <span class="text-muted">(<?php echo $days_old; ?> day<?php echo $days_old > 1 ? 's' : ''; ?> ago)</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="complaint-description">
                                                    <?php 
                                                    $description = htmlspecialchars($complaint['description']);
                                                    echo strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description; 
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 text-md-end">
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-view btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewModal<?php echo $complaint['id']; ?>">
                                                        <i class="bi bi-eye-fill"></i> View Details
                                                    </button>
                                                    
                                                    <?php if ($complaint['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-resolve btn-sm" 
                                                                onclick="updateStatus(<?php echo $complaint['id']; ?>, 'resolved')">
                                                            <i class="bi bi-check-lg"></i> Mark Resolved
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-reopen btn-sm" 
                                                                onclick="updateStatus(<?php echo $complaint['id']; ?>, 'pending')">
                                                            <i class="bi bi-arrow-clockwise"></i> Reopen
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Enhanced View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $complaint['id']; ?>" tabindex="-1" 
                                     aria-labelledby="viewModalLabel<?php echo $complaint['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewModalLabel<?php echo $complaint['id']; ?>">
                                                    <i class="bi bi-file-earmark-text-fill me-2"></i>
                                                    Complaint Details
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-4">
                                                    <!-- Main Details -->
                                                    <div class="col-lg-8">
                                                        <div class="complaint-details">
                                                            <div class="row mb-4">
                                                                <div class="col-sm-3">
                                                                    <label class="form-label fw-bold text-primary">
                                                                        <i class="bi bi-type me-1"></i>Title:
                                                                    </label>
                                                                </div>
                                                                <div class="col-sm-9">
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($complaint['title']); ?></h6>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row mb-4">
                                                                <div class="col-sm-3">
                                                                    <label class="form-label fw-bold text-primary">
                                                                        <i class="bi bi-flag me-1"></i>Status:
                                                                    </label>
                                                                </div>
                                                                <div class="col-sm-9">
                                                                    <span class="status-badge status-<?php echo $complaint['status']; ?>">
                                                                        <i class="bi bi-<?php echo $complaint['status'] === 'pending' ? 'clock' : 'check-circle'; ?>-fill me-1"></i>
                                                                        <?php echo $complaint['status'] === 'pending' ? 'Pending Resolution' : 'Resolved'; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row mb-4">
                                                                <div class="col-sm-3">
                                                                    <label class="form-label fw-bold text-primary">
                                                                        <i class="bi bi-calendar-event me-1"></i>Submitted:
                                                                    </label>
                                                                </div>
                                                                <div class="col-sm-9">
                                                                    <div><?php echo date('F j, Y \a\t g:i A', strtotime($complaint['created_at'])); ?></div>
                                                                    <small class="text-muted">
                                                                        (<?php echo $days_old; ?> day<?php echo $days_old > 1 ? 's' : ''; ?> ago)
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row mb-4">
                                                                <div class="col-sm-3">
                                                                    <label class="form-label fw-bold text-primary">
                                                                        <i class="bi bi-chat-text me-1"></i>Description:
                                                                    </label>
                                                                </div>
                                                                <div class="col-sm-9">
                                                                    <div class="complaint-full-description bg-light p-3 rounded">
                                                                        <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Sidebar Info -->
                                                    <div class="col-lg-4">
                                                        <div class="info-sidebar">
                                                            <!-- Complainant Info -->
                                                            <div class="card border-0 bg-light mb-3">
                                                                <div class="card-header bg-primary text-white">
                                                                    <h6 class="mb-0">
                                                                        <i class="bi bi-person-circle me-2"></i>
                                                                        Complainant Information
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="mb-2">
                                                                        <strong>Name:</strong><br>
                                                                        <?php echo htmlspecialchars($complaint['complainant_name']); ?>
                                                                    </div>
                                                                    <div class="mb-2">
                                                                        <strong>Email:</strong><br>
                                                                        <a href="mailto:<?php echo htmlspecialchars($complaint['complainant_email']); ?>" 
                                                                           class="text-primary text-decoration-none">
                                                                            <?php echo htmlspecialchars($complaint['complainant_email']); ?>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Seller Info -->
                                                            <div class="card border-0 bg-light mb-3">
                                                                <div class="card-header bg-secondary text-white">
                                                                    <h6 class="mb-0">
                                                                        <i class="bi bi-shop me-2"></i>
                                                                        Seller Information
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="mb-2">
                                                                        <strong>Business Name:</strong><br>
                                                                        <?php echo htmlspecialchars($complaint['business_name'] ?: 'N/A'); ?>
                                                                    </div>
                                                                    <div class="mb-2">
                                                                        <strong>Seller Name:</strong><br>
                                                                        <?php echo htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']); ?>
                                                                    </div>
                                                                    <div class="mt-2">
                                                                        <a href="manage_sellers.php?search=<?php echo urlencode($complaint['first_name'] . ' ' . $complaint['last_name']); ?>" 
                                                                           class="btn btn-outline-secondary btn-sm">
                                                                            <i class="bi bi-eye me-1"></i>View Seller Details
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Priority Indicator -->
                                                            <div class="card border-0 mb-3" style="border-left: 4px solid <?php 
                                                                echo $priority_class === 'priority-high' ? 'var(--danger-color)' : 
                                                                    ($priority_class === 'priority-medium' ? 'var(--warning-color)' : 'var(--success-color)'); 
                                                            ?> !important;">
                                                                <div class="card-body">
                                                                    <h6 class="card-title">
                                                                        <i class="bi bi-speedometer2 me-2"></i>Priority Level
                                                                    </h6>
                                                                    <span class="badge bg-<?php 
                                                                        echo $priority_class === 'priority-high' ? 'danger' : 
                                                                            ($priority_class === 'priority-medium' ? 'warning' : 'success'); 
                                                                    ?>">
                                                                        <?php 
                                                                        echo $priority_class === 'priority-high' ? 'High Priority' : 
                                                                            ($priority_class === 'priority-medium' ? 'Medium Priority' : 'Low Priority'); 
                                                                        ?>
                                                                    </span>
                                                                    <div class="mt-2">
                                                                        <small class="text-muted">
                                                                            Based on complaint age and status
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            Complaint ID: #<?php echo $complaint['id']; ?>
                                                        </small>
                                                    </div>
                                                    <div>
                                                        <?php if ($complaint['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-resolve" 
                                                                    onclick="updateStatus(<?php echo $complaint['id']; ?>, 'resolved')" 
                                                                    data-bs-dismiss="modal">
                                                                <i class="bi bi-check-lg me-1"></i> Mark as Resolved
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-reopen" 
                                                                    onclick="updateStatus(<?php echo $complaint['id']; ?>, 'pending')" 
                                                                    data-bs-dismiss="modal">
                                                                <i class="bi bi-arrow-clockwise me-1"></i> Reopen Complaint
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-outline-secondary ms-2" data-bs-dismiss="modal">
                                                            <i class="bi bi-x-circle me-1"></i>Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Enhanced Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Complaints pagination" class="mt-4">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item"><span class="page-link">...</span></li>
                                    <?php endif;
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor;
                                    
                                    if ($end_page < $total_pages): ?>
                                        <li class="page-item"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                                                Next <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for status updates -->
    <form id="statusUpdateForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="complaint_id" id="statusComplaintId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Enhanced status update function with better UX
        function updateStatus(complaintId, status) {
            const actionText = status === 'resolved' ? 'mark this complaint as resolved' : 'reopen this complaint';
            const confirmMessage = `Are you sure you want to ${actionText}?`;
            
            if (confirm(confirmMessage)) {
                // Show loading state
                const buttons = document.querySelectorAll(`button[onclick*="${complaintId}"]`);
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-1" role="status"></div>Updating...';
                });
                
                // Update form values
                document.getElementById('statusComplaintId').value = complaintId;
                document.getElementById('statusValue').value = status;
                
                // Submit form
                document.getElementById('statusUpdateForm').submit();
            }
        }

        // Enhanced notification system
        function showNotification(message, type = 'info', duration = 5000) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `custom-notification alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                border: none;
                border-radius: 12px;
                animation: slideInRight 0.5s ease-out;
            `;
            
            const iconMap = {
                success: 'bi-check-circle-fill',
                error: 'bi-exclamation-triangle-fill',
                warning: 'bi-exclamation-circle-fill',
                info: 'bi-info-circle-fill'
            };
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi ${iconMap[type] || iconMap.info} me-2 fs-5"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close btn-sm ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    if (notification && notification.parentNode) {
                        notification.classList.add('fade-out');
                        setTimeout(() => notification.remove(), 300);
                    }
                }, duration);
            }
        }

        // Initialize enhanced features
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to complaint items
            const complaintItems = document.querySelectorAll('.complaint-item');
            complaintItems.forEach((item, index) => {
                // Staggered animation
                item.style.animationDelay = (index * 0.1) + 's';
                
                // Enhanced hover interaction
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.01)';
                    this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Enhanced search functionality with debounce
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    if (query.length > 0) {
                        this.style.borderColor = 'var(--primary-color)';
                        this.style.boxShadow = '0 0 0 0.2rem rgba(44, 62, 80, 0.25)';
                    } else {
                        this.style.borderColor = '#e9ecef';
                        this.style.boxShadow = 'none';
                    }
                    
                    // Auto-search after user stops typing (optional)
                    // searchTimeout = setTimeout(() => {
                    //     if (query.length >= 3 || query.length === 0) {
                    //         this.closest('form').submit();
                    //     }
                    // }, 1000);
                });
            }

            // Status filter change handler
            const statusSelect = document.getElementById('status');
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    // Optional: Auto-submit on status change
                    // this.closest('form').submit();
                });
            }

            // Initialize tooltips if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + F = Focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f' && searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
                
                // Escape = Clear search
                if (e.key === 'Escape' && searchInput === document.activeElement) {
                    searchInput.value = '';
                    searchInput.blur();
                }
            });

            // Show welcome message for first-time visitors
            if (!sessionStorage.getItem('complaintsPageVisited')) {
                setTimeout(() => {
                    showNotification('💡 Tip: Use <kbd>Ctrl+F</kbd> to quickly search complaints, or click on complaint cards for detailed view.', 'info', 8000);
                    sessionStorage.setItem('complaintsPageVisited', 'true');
                }, 1500);
            }

            // Auto-refresh notification for pending complaints
            const pendingCount = <?php echo $stats['pending']; ?>;
            if (pendingCount > 0 && !sessionStorage.getItem('pendingNotificationShown')) {
                setTimeout(() => {
                    showNotification(`⚠️ You have ${pendingCount} complaint${pendingCount > 1 ? 's' : ''} pending resolution.`, 'warning', 6000);
                    sessionStorage.setItem('pendingNotificationShown', 'true');
                }, 2000);
            }
        });

        // Custom CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; transform: translateX(100%); }
            }

            .fade-out {
                animation: fadeOut 0.3s ease-out forwards;
            }

            .custom-notification {
                backdrop-filter: blur(10px);
                border-left: 4px solid;
            }

            .custom-notification.alert-success {
                border-left-color: var(--success-color);
                background: rgba(212, 237, 218, 0.95);
            }

            .custom-notification.alert-danger {
                border-left-color: var(--danger-color);
                background: rgba(248, 215, 218, 0.95);
            }

            .custom-notification.alert-warning {
                border-left-color: var(--warning-color);
                background: rgba(255, 243, 205, 0.95);
            }

            .custom-notification.alert-info {
                border-left-color: var(--info-color);
                background: rgba(209, 236, 241, 0.95);
            }

            kbd {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 3px;
                color: #495057;
                font-size: 0.875rem;
                padding: 2px 4px;
            }

            .complaint-full-description {
                max-height: 300px;
                overflow-y: auto;
                line-height: 1.6;
            }

            .spinner-border-sm {
                width: 1rem;
                height: 1rem;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>