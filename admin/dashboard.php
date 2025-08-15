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
            left: 0;
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
                    <a href="floorplan.php" class="dashboard-card card-clickable">
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
                                <a href="floorplan.php" class="action-btn">
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
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="announcementTitle" class="form-label fw-bold">
                                        <i class="bi bi-type text-primary me-1"></i>Announcement Title
                                    </label>
                                    <input type="text" class="form-control" id="announcementTitle" name="title" 
                                           placeholder="Enter announcement title..." required maxlength="200">
                                    <div class="form-text">Maximum 200 characters</div>
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
    <!-- Hidden data for JavaScript -->
    <script type="application/json" id="category-data"><?php echo json_encode(array_column($category_stats, 'product_count')); ?></script>
    <script type="application/json" id="category-labels"><?php echo json_encode(array_column($category_stats, 'name')); ?></script>

    <!-- Enhanced Dashboard JavaScript -->
    <script>
    // Handle applications card click
    function handleApplicationsClick(pendingCount) {
        if (pendingCount > 0) {
            if (confirm(`There are ${pendingCount} pending seller applications. Would you like to review them now?`)) {
                window.location.href = 'manage_sellers.php?filter=pending';
            }
        } else {
            showNotification('No pending applications at the moment. All applications have been reviewed!', 'info');
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

    // Real-time preview and form handling
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('announcementTitle');
        const contentInput = document.getElementById('announcementContent');
        const targetSelect = document.getElementById('announcementTarget');
        
        const previewTitle = document.getElementById('previewTitle');
        const previewContent = document.getElementById('previewContent');
        const previewTarget = document.getElementById('previewTarget');

        function updatePreview() {
            if (!previewTitle || !previewContent || !previewTarget) return;
            
            // Update title
            previewTitle.textContent = titleInput.value || 'Announcement Title';
            
            // Update content
            const contentText = contentInput.value || 'Announcement content will appear here...';
            previewContent.textContent = contentText.length > 150 ? contentText.substring(0, 150) + '...' : contentText;
            
            // Update target
            const targetText = targetSelect.options[targetSelect.selectedIndex].text;
            previewTarget.textContent = targetText;
        }

        // Add event listeners for real-time preview
        if (titleInput) titleInput.addEventListener('input', updatePreview);
        if (contentInput) contentInput.addEventListener('input', updatePreview);
        if (targetSelect) targetSelect.addEventListener('change', updatePreview);

        // Enhanced form validation and submission
        const form = document.getElementById('announcementForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!validateAnnouncementForm()) {
                    return;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Publishing...';
                submitBtn.disabled = true;
                
                // Prepare form data
                const formData = new FormData(form);
                
                // Submit form via AJAX
                fetch('create_announcement.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        let successMessage = 'Announcement published successfully! 🎉';
                        if (data.emails_queued > 0) {
                            successMessage += ` ${data.emails_queued} email notifications have been queued.`;
                        }
                        showNotification(successMessage, 'success', 6000);
                        
                        // Close modal and reset form
                        const modal = bootstrap.Modal.getInstance(document.getElementById('announcementModal'));
                        modal.hide();
                        form.reset();
                        updatePreview(); // Reset preview
                        
                    } else {
                        throw new Error(data.message || 'Failed to create announcement');
                    }
                })
                .catch(error => {
                    console.error('Announcement creation error:', error);
                    let errorMessage = 'Failed to create announcement. Please try again.';
                    if (error.message.includes('403')) {
                        errorMessage = 'You do not have permission to create announcements.';
                    } else if (error.message.includes('400')) {
                        errorMessage = 'Please check your input and try again.';
                    }
                    showNotification(errorMessage, 'error', 8000);
                })
                .finally(() => {
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        }

        // Form validation function
        function validateAnnouncementForm() {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            const expiryDate = document.getElementById('announcementExpiry').value;
            
            if (!title) {
                showNotification('Please enter an announcement title.', 'warning');
                titleInput.focus();
                return false;
            }
            
            if (title.length > 200) {
                showNotification('Title must not exceed 200 characters.', 'warning');
                titleInput.focus();
                return false;
            }
            
            if (!content) {
                showNotification('Please enter announcement content.', 'warning');
                contentInput.focus();
                return false;
            }
            
            if (content.length > 2000) {
                showNotification('Content must not exceed 2000 characters.', 'warning');
                contentInput.focus();
                return false;
            }
            
            // Validate expiry date if provided
            if (expiryDate) {
                const expiryTimestamp = new Date(expiryDate).getTime();
                const now = new Date().getTime();
                
                if (expiryTimestamp <= now) {
                    showNotification('Expiry date must be in the future.', 'warning');
                    document.getElementById('announcementExpiry').focus();
                    return false;
                }
            }
            
            return true;
        }

        // Character counter function
        function addCharacterCounter(input, maxLength) {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.style.marginTop = '5px';
            input.parentNode.appendChild(counter);
            
            function updateCounter() {
                const remaining = maxLength - input.value.length;
                const current = input.value.length;
                const percentage = (current / maxLength) * 100;
                
                let color = 'text-muted';
                let icon = '';
                
                if (remaining < 0) {
                    color = 'text-danger';
                    icon = ' <i class="bi bi-exclamation-circle"></i>';
                    input.classList.add('is-invalid');
                } else if (remaining < 50) {
                    color = 'text-warning';
                    icon = ' <i class="bi bi-exclamation-triangle"></i>';
                    input.classList.remove('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
                
                counter.innerHTML = `<span class="${color}">${current}/${maxLength}${icon}</span>`;
                
                // Progress bar
                if (current > 0) {
                    counter.innerHTML += `<div class="progress mt-1" style="height: 3px;">
                        <div class="progress-bar bg-${percentage > 100 ? 'danger' : percentage > 80 ? 'warning' : 'primary'}" 
                             style="width: ${Math.min(percentage, 100)}%"></div>
                    </div>`;
                }
            }
            
            input.addEventListener('input', updateCounter);
            updateCounter();
        }

        // Add character counters
        if (titleInput) addCharacterCounter(titleInput, 200);
        if (contentInput) addCharacterCounter(contentInput, 2000);

        // Initialize preview
        updatePreview();
        
        // Auto-focus title when modal opens
        const announcementModal = document.getElementById('announcementModal');
        if (announcementModal) {
            announcementModal.addEventListener('shown.bs.modal', function() {
                titleInput.focus();
            });
            
            // Reset form when modal closes
            announcementModal.addEventListener('hidden.bs.modal', function() {
                form.reset();
                updatePreview();
            });
        }
    });

    // Check if chat file exists function
    function checkChatFile(event) {
        console.log('Navigating to chat overview...');
    }

    // Fixed Category Distribution Chart
    document.addEventListener('DOMContentLoaded', function() {
        const categoryDataElement = document.getElementById('category-data');
        const categoryLabelsElement = document.getElementById('category-labels');
        
        if (!categoryDataElement || !categoryLabelsElement) {
            console.warn('Category data not found');
            return;
        }
        
        const categoryData = JSON.parse(categoryDataElement.textContent || '[]');
        const categoryLabels = JSON.parse(categoryLabelsElement.textContent || '[]');

        // Only create chart if we have data
        if (categoryData.length > 0 && categoryLabels.length > 0) {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;
            
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: [
                            '#3498db', '#27ae60', '#f39c12', '#e74c3c', 
                            '#9b59b6', '#1abc9c', '#95a5a6', '#34495e',
                            '#16a085', '#2980b9', '#8e44ad', '#d35400'
                        ],
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 4,
                        hoverOffset: 10
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
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#fff',
                            borderWidth: 1,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `📦 ${label}: ${value} products (${percentage}%)`;
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
                            showNotification(`Loading ${categoryName} products...`, 'info', 2000);
                            setTimeout(() => {
                                window.location.href = `manage_products.php?category=${encodeURIComponent(categoryName)}`;
                            }, 500);
                        }
                    },
                    onHover: (event, elements) => {
                        event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });
        } else {
            // Show message if no data
            const chartCanvas = document.getElementById('categoryChart');
            if (chartCanvas) {
                chartCanvas.style.display = 'none';
                const chartContainer = chartCanvas.closest('.chart-wrapper');
                if (chartContainer) {
                    chartContainer.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-pie-chart fs-1 mb-3" style="opacity: 0.5;"></i>
                            <h6 class="mt-2">No category data available</h6>
                            <small>Products will appear here once categories are created</small>
                        </div>
                    `;
                }
            }
        }
    });

    // Enhanced card interactions
    document.addEventListener('DOMContentLoaded', function() {
        const clickableCards = document.querySelectorAll('.dashboard-card');
        
        clickableCards.forEach(card => {
            // Add data attributes for stats tracking
            const statNumber = card.querySelector('.stat-number');
            if (statNumber) {
                const cardText = card.querySelector('.stat-label')?.textContent.toLowerCase();
                if (cardText) {
                    if (cardText.includes('seller')) card.setAttribute('data-stat', 'sellers');
                    else if (cardText.includes('product')) card.setAttribute('data-stat', 'products');
                    else if (cardText.includes('stall')) card.setAttribute('data-stat', 'stalls');
                    else if (cardText.includes('chat')) card.setAttribute('data-stat', 'chats');
                    else if (cardText.includes('application')) card.setAttribute('data-stat', 'applications');
                }
            }
            
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    this.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.15)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                }
            });
            
            card.addEventListener('mousedown', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'translateY(-4px) scale(0.98)';
                }
            });
            
            card.addEventListener('mouseup', function() {
                if (!this.classList.contains('loading')) {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                }
            });
            
            // Add click handler for navigation cards
            if (card.href || card.onclick) {
                card.addEventListener('click', function(e) {
                    if (!this.classList.contains('loading')) {
                        showLoadingState(this);
                    }
                });
            }
        });

        // Make chart containers clickable
        const categoryChart = document.querySelector('#categoryChart');
        if (categoryChart) {
            const chartContainer = categoryChart.closest('.chart-container');
            if (chartContainer) {
                chartContainer.style.cursor = 'pointer';
                chartContainer.style.transition = 'all 0.3s ease';
                
                chartContainer.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                });
                
                chartContainer.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                });
                
                chartContainer.addEventListener('click', function() {
                    showNotification('Redirecting to product management...', 'info', 2000);
                    setTimeout(() => {
                        window.location.href = 'manage_products.php';
                    }, 500);
                });
            }
        }
    });

    // Enhanced loading state function
    function showLoadingState(element) {
        if (element.classList.contains('loading')) return;
        
        element.classList.add('loading');
        const isCard = element.classList.contains('dashboard-card');
        
        if (isCard) {
            // For dashboard cards, show loading overlay
            const cardBody = element.querySelector('.card-body');
            if (cardBody) {
                cardBody.style.opacity = '0.3';
                const loader = document.createElement('div');
                loader.className = 'loading-overlay';
                loader.innerHTML = `
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                loader.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255,255,255,0.9);
                    z-index: 10;
                    border-radius: 15px;
                    animation: fadeIn 0.3s ease-in-out;
                `;
                element.style.position = 'relative';
                element.appendChild(loader);
            }
        }
        
        // Reset after delay
        setTimeout(() => {
            element.classList.remove('loading');
            const cardBody = element.querySelector('.card-body');
            const loader = element.querySelector('.loading-overlay');
            if (cardBody) cardBody.style.opacity = '1';
            if (loader) loader.remove();
            element.style.transform = 'translateY(0) scale(1)';
            element.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
        }, 3000);
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N = New Announcement
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            showAnnouncementModal();
        }
        
        // Escape = Close modals
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
        });
    }
    
    // Ctrl/Cmd + R = Refresh dashboard (prevent default browser refresh)
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }
});

// Manual refresh function
function refreshDashboard() {
    showNotification('Refreshing dashboard data...', 'info', 2000);
    
    // Add subtle loading animation to stats
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        stat.style.opacity = '0.5';
        stat.style.transform = 'scale(0.95)';
    });
    
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Initialize dashboard features
document.addEventListener('DOMContentLoaded', function() {
    // Show welcome message for new sessions
    if (sessionStorage.getItem('dashboardWelcomeShown') !== 'true') {
        setTimeout(() => {
            showNotification('Welcome to the admin dashboard! 👋<br><small>Use <kbd>Ctrl+N</kbd> to create announcements quickly.</small>', 'info', 10000);
            sessionStorage.setItem('dashboardWelcomeShown', 'true');
        }, 1500);
    }
    
    // Add smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Initialize tooltips if Bootstrap tooltips are available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Check for urgent notifications on load
    checkUrgentNotifications();
    
    // Add ripple effect to action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            createRippleEffect(e, this);
        });
    });
});

// Check for urgent notifications
function checkUrgentNotifications() {
    const pendingApps = <?php echo $pending_applications; ?>;
    const unreadMsgs = <?php echo $unread_messages; ?>;
    
    if (pendingApps > 0) {
        setTimeout(() => {
            showNotification(`⚠️ You have ${pendingApps} pending seller application${pendingApps > 1 ? 's' : ''} awaiting review.`, 'warning', 8000);
        }, 3000);
    }
    
    if (unreadMsgs > 0) {
        setTimeout(() => {
            showNotification(`💬 You have ${unreadMsgs} unread message${unreadMsgs > 1 ? 's' : ''} from customers.`, 'info', 8000);
        }, 5000);
    }
}

// Ripple effect function
function createRippleEffect(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
        z-index: 1000;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Utility function for formatting numbers
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
}

// Add custom CSS for animations and enhanced styling
const customStyles = document.createElement('style');
customStyles.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
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
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; transform: translateX(100%); }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    .dashboard-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform, box-shadow;
    }
    
    .custom-notification {
        backdrop-filter: blur(10px);
        border-left: 4px solid;
    }
    
    .custom-notification.alert-success {
        border-left-color: #27ae60;
        background: rgba(212, 237, 218, 0.95);
    }
    
    .custom-notification.alert-danger {
        border-left-color: #e74c3c;
        background: rgba(248, 215, 218, 0.95);
    }
    
    .custom-notification.alert-warning {
        border-left-color: #f39c12;
        background: rgba(255, 243, 205, 0.95);
    }
    
    .custom-notification.alert-info {
        border-left-color: #3498db;
        background: rgba(209, 236, 241, 0.95);
    }
    
    .progress {
        background-color: rgba(0,0,0,0.1);
    }
    
    .chart-container:hover {
        cursor: pointer;
    }
    
    kbd {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        color: #495057;
        font-size: 0.875rem;
        padding: 2px 4px;
    }
    
    .action-btn:active {
        transform: translateY(1px);
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }
`;
document.head.appendChild(customStyles);

// Performance monitoring (optional)
if (typeof performance !== 'undefined') {
    window.addEventListener('load', function() {
        setTimeout(() => {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            if (loadTime > 3000) { // If page takes more than 3 seconds
                console.log('Dashboard loaded in', loadTime + 'ms');
            }
        }, 100);
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>