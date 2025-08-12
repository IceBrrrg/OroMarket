<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Get seller information
$seller_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

// Get seller application info for business name
$stmt = $pdo->prepare("SELECT business_name FROM seller_applications WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$seller_id]);
$application = $stmt->fetch();
$business_name = $application ? $application['business_name'] : ($seller['first_name'] . ' ' . $seller['last_name']);

// Fetch announcements for sellers (all users and sellers specifically)
try {
    $stmt = $pdo->prepare("
        SELECT a.*, ad.username as created_by_name 
        FROM announcements a 
        LEFT JOIN admins ad ON a.created_by = ad.id 
        WHERE a.is_active = 1 
        AND (a.target_audience = 'all' OR a.target_audience = 'sellers')
        AND (a.expiry_date IS NULL OR a.expiry_date > NOW())
        ORDER BY a.is_pinned DESC, a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $announcements = [];
}

// Get statistics
$total_announcements = count($announcements);
$urgent_announcements = count(array_filter($announcements, function($a) { return $a['priority'] === 'urgent'; }));
$high_priority = count(array_filter($announcements, function($a) { return $a['priority'] === 'high'; }));
$pinned_announcements = count(array_filter($announcements, function($a) { return $a['is_pinned']; }));
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

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

        /* Welcome Header */
        .welcome-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }


        .welcome-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .welcome-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .stats-cards {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.total { border-left-color: var(--primary); }
        .stat-card.urgent { border-left-color: var(--danger); }
        .stat-card.high { border-left-color: var(--warning); }
        .stat-card.pinned { border-left-color: var(--info); }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .announcements-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .table-header th {
            border: none;
            font-weight: 600;
            padding: 0.875rem 0.75rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody tr.pinned-row {
            background: linear-gradient(90deg, #fff8e1 0%, #ffffff 100%);
            border-left: 4px solid var(--warning);
        }

        .table tbody td {
            padding: 0.875rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border);
        }

        .announcement-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .priority-badge {
            padding: 0.25rem 0.65rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-low { 
            background-color: #e8f5e8; 
            color: #2e7d32; 
        }
        .priority-medium { 
            background-color: #fff3e0; 
            color: #f57c00; 
        }
        .priority-high { 
            background-color: #ffebee; 
            color: #d32f2f; 
        }
        .priority-urgent { 
            background-color: var(--danger); 
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        .new-badge {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 0.5rem;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { box-shadow: 0 0 5px rgba(255, 107, 53, 0.5); }
            to { box-shadow: 0 0 15px rgba(255, 107, 53, 0.8); }
        }

        .announcement-details {
            background-color: #f8f9fa;
            padding: 1.25rem;
            border-top: 1px solid var(--border);
        }

        .announcement-content {
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 0.9rem;
            max-height: 150px;
            overflow-y: auto;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .empty-state h5 {
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .notification-dot {
            width: 6px;
            height: 6px;
            background: var(--danger);
            border-radius: 50%;
            display: inline-block;
            margin-left: 0.5rem;
            animation: pulse 2s infinite;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .welcome-header {
                padding: 2rem 1.5rem;
            }

            .welcome-header h1 {
                font-size: 2rem;
            }

            .table-responsive {
                font-size: 0.85rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }

        /* Responsive table adjustments */
        @media (max-width: 992px) {
            .table th:nth-child(4),
            .table td:nth-child(4),
            .table th:nth-child(6),
            .table td:nth-child(6) {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .table th:nth-child(3),
            .table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <h1><i class="bi bi-megaphone me-3"></i>Marketplace Announcements</h1>
                <p>Stay updated with important marketplace news and updates from the administration.</p>
            </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card total">
                        <div class="stat-number" style="color: var(--primary);"><?php echo $total_announcements; ?></div>
                        <div class="stat-label">Total Announcements</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card urgent">
                        <div class="stat-number text-danger">
                            <?php echo $urgent_announcements; ?>
                            <?php if ($urgent_announcements > 0): ?>
                                <span class="notification-dot"></span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-label">Urgent Announcements</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card high">
                        <div class="stat-number text-warning"><?php echo $high_priority; ?></div>
                        <div class="stat-label">High Priority</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card pinned">
                        <div class="stat-number text-info"><?php echo $pinned_announcements; ?></div>
                        <div class="stat-label">Pinned Announcements</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements Table -->
        <div class="announcements-container">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="bi bi-megaphone"></i>
                    <h5>No Announcements</h5>
                    <p>There are currently no announcements for sellers. Check back later for important updates from the marketplace administration.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-header">
                            <tr>
                                <th width="5%"><i class="bi bi-pin"></i></th>
                                <th width="40%">Title</th>
                                <th width="15%">Priority</th>
                                <th width="15%">Created By</th>
                                <th width="15%">Date</th>
                                <th width="10%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): ?>
                                <?php
                                $priorityClass = 'priority-' . $announcement['priority'];
                                $isNew = strtotime($announcement['created_at']) > strtotime('-3 days');
                                $isExpired = $announcement['expiry_date'] && strtotime($announcement['expiry_date']) < time();
                                ?>
                                <tr class="<?php echo $announcement['is_pinned'] ? 'pinned-row' : ''; ?>" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#announcement-<?php echo $announcement['id']; ?>" 
                                    aria-expanded="false">
                                    <td class="text-center">
                                        <?php if ($announcement['is_pinned']): ?>
                                            <i class="bi bi-pin-fill text-warning"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="announcement-title">
                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                            <?php if ($isNew): ?>
                                                <span class="new-badge">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="priority-badge <?php echo $priorityClass; ?>">
                                            <?php echo ucfirst($announcement['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>
                                            <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Admin'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($announcement['expiry_date']): ?>
                                            <?php if ($isExpired): ?>
                                                <small class="text-danger">Expired</small>
                                            <?php else: ?>
                                                <small class="text-success">Active</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-success">Active</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <div class="collapse" id="announcement-<?php echo $announcement['id']; ?>">
                                            <div class="announcement-details">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <h6 class="mb-2">
                                                            <i class="bi bi-text-paragraph me-1"></i>Content
                                                        </h6>
                                                        <div class="announcement-content">
                                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <h6 class="mb-2">
                                                            <i class="bi bi-info-circle me-1"></i>Details
                                                        </h6>
                                                        <div class="d-flex flex-column gap-2">
                                                            <div>
                                                                <strong>Target:</strong> 
                                                                <span class="badge bg-primary">
                                                                    <?php echo $announcement['target_audience'] === 'all' ? 'All Users' : ucfirst($announcement['target_audience']); ?>
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <strong>Posted:</strong> 
                                                                <small><?php echo date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])); ?></small>
                                                            </div>
                                                            <?php if ($announcement['expiry_date']): ?>
                                                                <div>
                                                                    <strong>Expires:</strong> 
                                                                    <small><?php echo date('F j, Y \a\t g:i A', strtotime($announcement['expiry_date'])); ?></small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation for table rows
            const tableRows = document.querySelectorAll('tbody tr[data-bs-toggle="collapse"]');
            tableRows.forEach(row => {
                row.addEventListener('click', function() {
                    // Add visual feedback
                    this.style.backgroundColor = '#e3f2fd';
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 200);
                });
            });

            // Auto-refresh page every 5 minutes to check for new announcements
            setTimeout(function() {
                location.reload();
            }, 300000); // 5 minutes

            // Mark urgent announcements as viewed
            const urgentRows = document.querySelectorAll('.priority-urgent');
            urgentRows.forEach(badge => {
                const row = badge.closest('tr');
                if (row) {
                    row.addEventListener('click', function() {
                        const notificationDot = document.querySelector('.notification-dot');
                        if (notificationDot) {
                            notificationDot.style.opacity = '0.5';
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>