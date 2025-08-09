<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Get dashboard statistics
try {
    // Total sellers
    $query = "SELECT COUNT(*) as total FROM sellers";
    $stmt = $pdo->query($query);
    $total_sellers = $stmt->fetchColumn();

    // Active sellers
    $query = "SELECT COUNT(*) as total FROM sellers WHERE status = 'approved' AND is_active = 1";
    $stmt = $pdo->query($query);
    $active_sellers = $stmt->fetchColumn();

    // Pending applications
    $query = "SELECT COUNT(*) as total FROM sellers WHERE status = 'pending'";
    $stmt = $pdo->query($query);
    $pending_applications = $stmt->fetchColumn();

    // Total products
    $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $stmt = $pdo->query($query);
    $total_products = $stmt->fetchColumn();

    // Total stalls
    $query = "SELECT COUNT(*) as total FROM stalls";
    $stmt = $pdo->query($query);
    $total_stalls = $stmt->fetchColumn();

    // Occupied stalls
    $query = "SELECT COUNT(*) as total FROM stalls WHERE status = 'occupied'";
    $stmt = $pdo->query($query);
    $occupied_stalls = $stmt->fetchColumn();

    // Total conversations
    $query = "SELECT COUNT(*) as total FROM conversations WHERE status = 'active'";
    $stmt = $pdo->query($query);
    $total_conversations = $stmt->fetchColumn();

    // Unread messages
    $query = "SELECT COUNT(*) as total FROM messages WHERE sender_type = 'guest' AND is_read = 0";
    $stmt = $pdo->query($query);
    $unread_messages = $stmt->fetchColumn();

    // Recent seller registrations (last 7 days)
    $query = "SELECT COUNT(*) as total FROM sellers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $pdo->query($query);
    $recent_registrations = $stmt->fetchColumn();

    // Top viewed products
    $query = "SELECT p.name, p.id, pv.view_count, s.username as seller_name 
              FROM products p 
              LEFT JOIN product_views pv ON p.id = pv.product_id 
              LEFT JOIN sellers s ON p.seller_id = s.id 
              WHERE p.is_active = 1 
              ORDER BY pv.view_count DESC 
              LIMIT 5";
    $stmt = $pdo->query($query);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Category distribution
    $query = "SELECT c.name, COUNT(p.id) as product_count 
              FROM categories c 
              LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
              GROUP BY c.id, c.name 
              ORDER BY product_count DESC";
    $stmt = $pdo->query($query);
    $category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activities (sellers and applications)
    $query = "SELECT 
                s.username, 
                s.first_name, 
                s.last_name, 
                s.status, 
                s.created_at,
                'seller_registration' as activity_type
              FROM sellers s 
              WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              ORDER BY s.created_at DESC 
              LIMIT 10";
    $stmt = $pdo->query($query);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stall occupancy by section
    $query = "SELECT 
                section,
                COUNT(*) as total_stalls,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_stalls,
                ROUND((SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as occupancy_rate
              FROM stalls 
              GROUP BY section 
              ORDER BY occupancy_rate DESC";
    $stmt = $pdo->query($query);
    $stall_occupancy = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    // Set default values if queries fail
    $total_sellers = $active_sellers = $pending_applications = $total_products = 0;
    $total_stalls = $occupied_stalls = $total_conversations = $unread_messages = 0;
    $recent_registrations = 0;
    $top_products = $category_stats = $recent_activities = $stall_occupancy = [];
}

// Calculate occupancy percentage
$occupancy_percentage = $total_stalls > 0 ? round(($occupied_stalls / $total_stalls) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
            cursor: pointer;
            position: relative;
            text-decoration: none;
            color: inherit;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: inherit;
        }

        .dashboard-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .dashboard-card:hover::after {
            opacity: 1;
        }

        .card-clickable {
            display: block;
            text-decoration: none;
            color: inherit;
            height: 100%;
        }

        .card-clickable:hover {
            text-decoration: none;
            color: inherit;
        }

        .card-icon {
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

        .card-icon.sellers { background: linear-gradient(45deg, #3498db, #2980b9); }
        .card-icon.products { background: linear-gradient(45deg, #27ae60, #229954); }
        .card-icon.stalls { background: linear-gradient(45deg, #f39c12, #e67e22); }
        .card-icon.messages { background: linear-gradient(45deg, #9b59b6, #8e44ad); }
        .card-icon.applications { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        .card-icon.occupancy { background: linear-gradient(45deg, #1abc9c, #16a085); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .stat-trend {
            font-size: 0.9rem;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .trend-up { background-color: #d4edda; color: #155724; }
        .trend-down { background-color: #f8d7da; color: #721c24; }
        .trend-neutral { background-color: #e2e3e5; color: #6c757d; }

        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            min-height: 400px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 300px;
        }

        .activity-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 10px;
            background: white;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .activity-time {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }

        .occupancy-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }

        .occupancy-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
        }

        .action-btn {
            display: block;
            padding: 15px 20px;
            border-radius: 12px;
            text-align: center;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-decoration: none;
        }

        .action-icon {
            font-size: 20px;
            margin-right: 10px;
        }

        .announcement-btn {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: var(--primary-color) !important;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .announcement-btn:hover {
            background: white !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        .announcement-btn:active {
            transform: translateY(0);
        }

        .announcement-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .announcement-btn:hover::before {
            left: 100%;
        }

        .announcement-modal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .announcement-modal .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }

        .announcement-modal .form-control,
        .announcement-modal .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .announcement-modal .form-control:focus,
        .announcement-modal .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }

        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .priority-low { background-color: #d1ecf1; color: #0c5460; }
        .priority-medium { background-color: #fff3cd; color: #856404; }
        .priority-high { background-color: #f8d7da; color: #721c24; }
        .priority-urgent { background-color: #d4edda; color: #155724; }

        .click-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            opacity: 0.7;
            font-size: 12px;
            color: #6c757d;
        }

        .dashboard-card:hover .click-indicator {
            color: var(--primary-color);
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
            <!-- Welcome Header -->
            <div class="welcome-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                        <p class="mb-0">Here's an overview of your marketplace today - <?php echo date('F j, Y'); ?></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="mb-2">
                            <button class="btn btn-light btn-lg announcement-btn" onclick="showAnnouncementModal()" title="Create New Announcement">
                                <i class="bi bi-megaphone-fill me-2"></i>
                                <span class="d-none d-md-inline">New Announcement</span>
                                <span class="d-md-none">Announce</span>
                            </button>
                        </div>
                        <div class="text-white-50">
                            <i class="bi bi-clock"></i> Last updated: <?php echo date('g:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <a href="manage_sellers.php" class="dashboard-card card-clickable">
                        <div class="card-body text-center">
                            <div class="click-indicator">
                                <i class="bi bi-cursor-pointer"></i>
                            </div>
                            <div class="card-icon sellers mx-auto">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($total_sellers); ?></div>
                            <div class="stat-label">Total Sellers</div>
                            <div class="stat-trend trend-up">
                                <i class="bi bi-arrow-up"></i> <?php echo $recent_registrations; ?> this week
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6">
                    <a href="manage_products.php" class="dashboard-card card-clickable">
                        <div class="card-body text-center">
                            <div class="click-indicator">
                                <i class="bi bi-cursor-pointer"></i>
                            </div>
                            <div class="card-icon products mx-auto">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($total_products); ?></div>
                            <div class="stat-label">Active Products</div>
                            <div class="stat-trend trend-up">
                                <i class="bi bi-eye"></i> View Details
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6">
                    <a href="manage_stalls.php" class="dashboard-card card-clickable">
                        <div class="card-body text-center">
                            <div class="click-indicator">
                                <i class="bi bi-cursor-pointer"></i>
                            </div>
                            <div class="card-icon stalls mx-auto">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div class="stat-number"><?php echo $occupied_stalls; ?>/<?php echo $total_stalls; ?></div>
                            <div class="stat-label">Stall Occupancy</div>
                            <div class="stat-trend <?php echo $occupancy_percentage >= 70 ? 'trend-up' : 'trend-neutral'; ?>">
                                <?php echo $occupancy_percentage; ?>% occupied
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6">
                    <a href="chat_overview.php" class="dashboard-card card-clickable">
                        <div class="card-body text-center">
                            <div class="click-indicator">
                                <i class="bi bi-cursor-pointer"></i>
                            </div>
                            <div class="card-icon messages mx-auto">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($total_conversations); ?></div>
                            <div class="stat-label">Active Chats</div>
                            <?php if ($unread_messages > 0): ?>
                                <div class="stat-trend trend-up">
                                    <i class="bi bi-exclamation-circle"></i> <?php echo $unread_messages; ?> unread
                                </div>
                            <?php else: ?>
                                <div class="stat-trend trend-neutral">All caught up!</div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="dashboard-card" onclick="handleApplicationsClick(<?php echo $pending_applications; ?>)">
                        <div class="card-body text-center">
                            <div class="click-indicator">
                                <i class="bi bi-cursor-pointer"></i>
                            </div>
                            <div class="card-icon applications mx-auto">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($pending_applications); ?></div>
                            <div class="stat-label">Pending Applications</div>
                            <?php if ($pending_applications > 0): ?>
                                <div class="stat-trend trend-up">
                                    <i class="bi bi-exclamation-triangle"></i> Needs Review
                                </div>
                            <?php else: ?>
                                <div class="stat-trend trend-neutral">All reviewed</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="dashboard-card" onclick="showActiveSellerDetails()">
                        <div class="card-body text-center">
                            <div class="click-indicator">
                                <i class="bi bi-cursor-pointer"></i>
                            </div>
                            <div class="card-icon occupancy mx-auto">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($active_sellers); ?></div>
                            <div class="stat-label">Active Sellers</div>
                            <div class="stat-trend trend-up">
                                <?php echo round(($active_sellers / max($total_sellers, 1)) * 100, 1); ?>% of total
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Analytics Row -->
            <div class="row g-4 mb-4">
                <!-- Category Distribution Chart -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="bi bi-pie-chart"></i> Product Categories</h5>
                        <div class="chart-wrapper">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Stall Occupancy by Section -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="bi bi-bar-chart"></i> Stall Occupancy by Section</h5>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($stall_occupancy as $section): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-medium"><?php echo htmlspecialchars($section['section']); ?></span>
                                        <span class="text-muted small">
                                            <?php echo $section['occupied_stalls']; ?>/<?php echo $section['total_stalls']; ?> 
                                            (<?php echo $section['occupancy_rate']; ?>%)
                                        </span>
                                    </div>
                                    <div class="occupancy-bar">
                                        <div class="occupancy-fill" style="width: <?php echo $section['occupancy_rate']; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity and Top Products Row -->
            <div class="row g-4 mb-4">
                <!-- Recent Activities -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="bi bi-clock-history"></i> Recent Activities</h5>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-muted text-center py-3">No recent activities</p>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                                <span class="text-muted">registered as seller</span>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="status-badge status-<?php echo $activity['status']; ?>">
                                                    <?php echo ucfirst($activity['status']); ?>
                                                </div>
                                                <div class="activity-time">
                                                    <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Viewed Products -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3"><i class="bi bi-eye"></i> Most Viewed Products</h5>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($top_products)): ?>
                                <p class="text-muted text-center py-3">No product views yet</p>
                            <?php else: ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                    <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                        <div class="me-3">
                                            <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-medium"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <small class="text-muted">by <?php echo htmlspecialchars($product['seller_name']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-primary"><?php echo number_format($product['view_count'] ?? 0); ?></div>
                                            <small class="text-muted">views</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4">
                <div class="col-md-12">
                    <div class="quick-actions">
                        <h5 class="mb-4"><i class="bi bi-lightning"></i> Quick Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="manage_sellers.php" class="action-btn">
                                    <i class="action-icon bi bi-people"></i>
                                    Manage Sellers
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_products.php" class="action-btn">
                                    <i class="action-icon bi bi-box-seam"></i>
                                    Manage Products
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_stalls.php" class="action-btn">
                                    <i class="action-icon bi bi-shop"></i>
                                    Manage Stalls
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="chat_overview.php" class="action-btn" onclick="checkChatFile(event)">
                                    <i class="action-icon bi bi-chat-dots"></i>
                                    View Messages
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Active Sellers Detail -->
    <div class="modal fade" id="activeSellerModal" tabindex="-1" aria-labelledby="activeSellerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activeSellerModalLabel">
                        <i class="bi bi-graph-up text-success"></i> Active Sellers Overview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="fs-3 text-success fw-bold"><?php echo $active_sellers; ?></div>
                                    <div class="text-muted">Active Sellers</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <div class="fs-3 text-primary fw-bold"><?php echo round(($active_sellers / max($total_sellers, 1)) * 100, 1); ?>%</div>
                                    <div class="text-muted">Activity Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Total Sellers:</span>
                        <strong><?php echo $total_sellers; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Inactive Sellers:</span>
                        <strong><?php echo $total_sellers - $active_sellers; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Recent Registrations (7 days):</span>
                        <strong><?php echo $recent_registrations; ?></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage_sellers.php" class="btn btn-primary">Manage Sellers</a>
                </div>
            </div>
        </div>
    </div>

    <!-- New Announcement Modal -->
    <div class="modal fade announcement-modal" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="announcementModalLabel">
                        <i class="bi bi-megaphone-fill me-2"></i>
                        Create New Announcement
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="announcementForm" action="create_announcement.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="announcementTitle" class="form-label fw-bold">
                                        <i class="bi bi-type text-primary me-1"></i>Announcement Title
                                    </label>
                                    <input type="text" class="form-control" id="announcementTitle" name="title" 
                                           placeholder="Enter announcement title..." required maxlength="200">
                                    <div class="form-text">Maximum 200 characters</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="announcementPriority" class="form-label fw-bold">
                                        <i class="bi bi-flag text-warning me-1"></i>Priority Level
                                    </label>
                                    <select class="form-select" id="announcementPriority" name="priority" required>
                                        <option value="low">Low Priority</option>
                                        <option value="medium" selected>Medium Priority</option>
                                        <option value="high">High Priority</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="announcementContent" class="form-label fw-bold">
                                        <i class="bi bi-chat-text text-info me-1"></i>Announcement Content
                                    </label>
                                    <textarea class="form-control" id="announcementContent" name="content" rows="6" 
                                              placeholder="Write your announcement content here..." required maxlength="2000"></textarea>
                                    <div class="form-text">Maximum 2000 characters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="announcementTarget" class="form-label fw-bold">
                                        <i class="bi bi-people text-success me-1"></i>Target Audience
                                    </label>
                                    <select class="form-select" id="announcementTarget" name="target_audience" required>
                                        <option value="all">All Users</option>
                                        <option value="sellers">Sellers Only</option>
                                        <option value="customers">Customers Only</option>
                                        <option value="admins">Admins Only</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="announcementExpiry" class="form-label fw-bold">
                                        <i class="bi bi-calendar-event text-danger me-1"></i>Expiry Date (Optional)
                                    </label>
                                    <input type="datetime-local" class="form-control" id="announcementExpiry" name="expiry_date">
                                    <div class="form-text">Leave empty for no expiration</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sendEmail" name="send_email" value="1">
                                    <label class="form-check-label fw-bold" for="sendEmail">
                                        <i class="bi bi-envelope text-primary me-1"></i>
                                        Send email notification to target audience
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="pinAnnouncement" name="pin_announcement" value="1">
                                    <label class="form-check-label fw-bold" for="pinAnnouncement">
                                        <i class="bi bi-pin-angle text-warning me-1"></i>
                                        Pin this announcement (appears at top)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview Section -->
                        <hr>
                        <div class="mb-3">
                            <h6 class="fw-bold text-muted mb-3">
                                <i class="bi bi-eye me-1"></i>Preview
                            </h6>
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-1" id="previewTitle">Announcement Title</h6>
                                        <span class="priority-badge priority-medium" id="previewPriority">Medium Priority</span>
                                    </div>
                                    <p class="card-text text-muted small mb-2" id="previewContent">Announcement content will appear here...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-person-circle me-1"></i>
                                            Target: <span id="previewTarget">All Users</span>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?php echo date('M j, Y g:i A'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-megaphone me-1"></i>Publish Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Chart.js Configuration -->
    <script>
        // Handle applications card click
        function handleApplicationsClick(pendingCount) {
            if (pendingCount > 0) {
                if (confirm(`There are ${pendingCount} pending seller applications. Would you like to review them now?`)) {
                    window.location.href = 'manage_sellers.php?filter=pending';
                }
            } else {
                alert('No pending applications at the moment. All applications have been reviewed!');
            }
        }

        // Show active seller details modal
        function showActiveSellerDetails() {
            const modal = new bootstrap.Modal(document.getElementById('activeSellerModal'));
            modal.show();
        }

        // Show announcement modal
        function showAnnouncementModal() {
            const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
            modal.show();
        }

        // Real-time preview for announcement modal
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('announcementTitle');
            const contentInput = document.getElementById('announcementContent');
            const prioritySelect = document.getElementById('announcementPriority');
            const targetSelect = document.getElementById('announcementTarget');
            
            const previewTitle = document.getElementById('previewTitle');
            const previewContent = document.getElementById('previewContent');
            const previewPriority = document.getElementById('previewPriority');
            const previewTarget = document.getElementById('previewTarget');

            function updatePreview() {
                // Update title
                previewTitle.textContent = titleInput.value || 'Announcement Title';
                
                // Update content
                previewContent.textContent = contentInput.value || 'Announcement content will appear here...';
                
                // Update priority
                const priority = prioritySelect.value;
                previewPriority.textContent = priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority';
                previewPriority.className = `priority-badge priority-${priority}`;
                
                // Update target
                const targetText = targetSelect.options[targetSelect.selectedIndex].text;
                previewTarget.textContent = targetText;
            }

            // Add event listeners for real-time preview
            if (titleInput) titleInput.addEventListener('input', updatePreview);
            if (contentInput) contentInput.addEventListener('input', updatePreview);
            if (prioritySelect) prioritySelect.addEventListener('change', updatePreview);
            if (targetSelect) targetSelect.addEventListener('change', updatePreview);

            // Form validation and submission
            const form = document.getElementById('announcementForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i>Publishing...';
                    submitBtn.disabled = true;
                    
                    // Simulate API call (replace with actual form submission)
                    setTimeout(() => {
                        // Show success message
                        alert('Announcement published successfully!');
                        
                        // Close modal and reset form
                        const modal = bootstrap.Modal.getInstance(document.getElementById('announcementModal'));
                        modal.hide();
                        form.reset();
                        updatePreview(); // Reset preview
                        
                        // Reset button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        
                        // You can add actual form submission here
                        // form.submit();
                    }, 2000);
                });
            }

            // Character counter for inputs
            function addCharacterCounter(input, maxLength) {
                const counter = document.createElement('div');
                counter.className = 'form-text text-end';
                counter.style.marginTop = '5px';
                input.parentNode.appendChild(counter);
                
                function updateCounter() {
                    const remaining = maxLength - input.value.length;
                    counter.textContent = `${input.value.length}/${maxLength} characters`;
                    counter.className = remaining < 50 ? 'form-text text-end text-danger' : 'form-text text-end text-muted';
                }
                
                input.addEventListener('input', updateCounter);
                updateCounter();
            }

            // Add character counters
            if (titleInput) addCharacterCounter(titleInput, 200);
            if (contentInput) addCharacterCounter(contentInput, 2000);
        });

        // Check if chat file exists function
        function checkChatFile(event) {
            // You can uncomment this to debug if the file exists
            // console.log('Attempting to navigate to chat_overview.php');
        }

        // Add click handlers for chart containers (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Make chart containers clickable
            const categoryChart = document.querySelector('#categoryChart').closest('.chart-container');
            if (categoryChart) {
                categoryChart.style.cursor = 'pointer';
                categoryChart.addEventListener('click', function() {
                    window.location.href = 'manage_products.php';
                });
            }

            // Add tooltips to cards
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.setAttribute('title', 'Click to view details');
            });
        });

        // Fixed Category Distribution Chart
        document.addEventListener('DOMContentLoaded', function() {
            const categoryData = <?php echo json_encode(array_column($category_stats, 'product_count')); ?>;
            const categoryLabels = <?php echo json_encode(array_column($category_stats, 'name')); ?>;

            // Only create chart if we have data
            if (categoryData.length > 0 && categoryLabels.length > 0) {
                const ctx = document.getElementById('categoryChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categoryData,
                            backgroundColor: [
                                '#3498db', '#27ae60', '#f39c12', '#e74c3c', 
                                '#9b59b6', '#1abc9c', '#95a5a6', '#34495e'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 1.2,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value} products (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10
                            }
                        },
                        onClick: (event, elements) => {
                            if (elements.length > 0) {
                                const elementIndex = elements[0].index;
                                const categoryName = categoryLabels[elementIndex];
                                window.location.href = `manage_products.php?category=${encodeURIComponent(categoryName)}`;
                            }
                        }
                    }
                });
            } else {
                // Show message if no data
                document.getElementById('categoryChart').style.display = 'none';
                const chartContainer = document.querySelector('.chart-wrapper');
                chartContainer.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-pie-chart fs-1"></i><br><p class="mt-2">No category data available</p></div>';
            }
        });

        // Add visual feedback for card interactions
        document.addEventListener('DOMContentLoaded', function() {
            const clickableCards = document.querySelectorAll('.dashboard-card');
            
            clickableCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
                
                card.addEventListener('mousedown', function() {
                    this.style.transform = 'translateY(-4px) scale(0.98)';
                });
                
                card.addEventListener('mouseup', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
            });
        });

        // Add loading states for navigation
        function showLoadingState(element) {
            const originalContent = element.innerHTML;
            element.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Loading...';
            element.style.pointerEvents = 'none';
            
            setTimeout(() => {
                element.innerHTML = originalContent;
                element.style.pointerEvents = 'auto';
            }, 1000);
        }

        // Add CSS for spinning animation
        const style = document.createElement('style');
        style.textContent = `
            .spin {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>