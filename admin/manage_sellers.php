<?php
session_start();
require_once '../includes/db_connect.php';

// Check if admin is logged in - FIXED: Use same session variables as sidebar
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../authenticator.php');
    exit();
}

// Handle status updates
if ($_POST && isset($_POST['action'])) {
    $seller_id = (int) $_POST['seller_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE sellers SET status = 'approved' WHERE id = ?");
            $stmt->execute([$seller_id]);

            // Add notification
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (recipient_type, recipient_id, title, message, link) VALUES ('seller', ?, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php')");
            $notification_stmt->execute([$seller_id]);

            $success_message = "Seller approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE sellers SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$seller_id]);

            // Add notification
            $notification_stmt = $pdo->prepare("INSERT INTO notifications (recipient_type, recipient_id, title, message, link) VALUES ('seller', ?, 'Application Rejected', 'Your seller application has been reviewed and rejected. Please contact support for more details.', 'application_status.php')");
            $notification_stmt->execute([$seller_id]);

            $success_message = "Seller rejected successfully!";
        } elseif ($action === 'suspend') {
            $stmt = $pdo->prepare("UPDATE sellers SET status = 'suspended', is_active = 0 WHERE id = ?");
            $stmt->execute([$seller_id]);

            $success_message = "Seller suspended successfully!";
        } elseif ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE sellers SET is_active = 1 WHERE id = ?");
            $stmt->execute([$seller_id]);

            $success_message = "Seller activated successfully!";
        } elseif ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE sellers SET is_active = 0 WHERE id = ?");
            $stmt->execute([$seller_id]);

            $success_message = "Seller deactivated successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error updating seller: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Build the query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "s.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(s.username LIKE ? OR s.email LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR sa.business_name LIKE ?)";
    $search_param = "%{$search_query}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Valid sort columns
$valid_sorts = ['created_at', 'username', 'status', 'first_name', 'last_name'];
$sort_by = in_array($sort_by, $valid_sorts) ? $sort_by : 'created_at';
$sort_order = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'DESC';

$query = "
    SELECT 
        s.*,
        sa.business_name,
        sa.business_phone,
        sa.selected_stall,
        st.stall_number,
        (SELECT COUNT(*) FROM products p WHERE p.seller_id = s.id) as product_count
    FROM sellers s
    LEFT JOIN seller_applications sa ON s.id = sa.seller_id
    LEFT JOIN stalls st ON sa.selected_stall = st.stall_number
    {$where_clause}
    ORDER BY s.{$sort_by} {$sort_order}
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM sellers
";
$stats_stmt = $pdo->query($stats_query);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Oroquieta Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .stats-row {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card.pending {
            border-left-color: var(--warning-color);
        }

        .stat-card.approved {
            border-left-color: var(--success-color);
        }

        .stat-card.rejected {
            border-left-color: var(--danger-color);
        }

        .stat-card.suspended {
            border-left-color: #6c757d;
        }

        .stat-card.active {
            border-left-color: var(--info-color);
        }

        .filters-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .seller-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid #dee2e6;
        }

        .seller-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .seller-card.pending {
            border-left-color: var(--warning-color);
        }

        .seller-card.approved {
            border-left-color: var(--success-color);
        }

        .seller-card.rejected {
            border-left-color: var(--danger-color);
        }

        .seller-card.suspended {
            border-left-color: #6c757d;
        }

        .seller-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .seller-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .seller-info {
            flex: 1;
            margin-left: 1rem;
        }

        .seller-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .seller-email {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .status-approved {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .status-suspended {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .seller-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }

        .btn-approve:hover {
            background-color: #219a52;
            transform: translateY(-1px);
            color: white;
        }

        .btn-reject {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-reject:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        .btn-suspend {
            background-color: #6c757d;
            color: white;
        }

        .btn-suspend:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .btn-view {
            background-color: var(--info-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #138496;
            transform: translateY(-1px);
            color: white;
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: border-color 0.3s ease;
        }

        .search-box .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: var(--card-shadow);
        }

        @media (max-width: 768px) {
            .seller-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .seller-info {
                margin-left: 0;
                margin-top: 1rem;
            }

            .seller-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                justify-content: center;
            }
        }

        /* Ensure sidebar icons are always visible */
        .sidebar .nav-link i,
        .sidebar .dropdown-item i {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            font-family: "bootstrap-icons" !important;
        }

        /* Ensure sidebar has proper z-index */
        .sidebar {
            z-index: 1000 !important;
        }

        /* Prevent any CSS from hiding sidebar icons */
        .sidebar * {
            visibility: visible !important;
        }

        /* Force icon display */
        .bi {
            display: inline-block !important;
            font-family: "bootstrap-icons" !important;
            font-style: normal;
            font-weight: normal !important;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            vertical-align: middle;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
                    <h1 class="mb-2"><i class="fas fa-users me-2"></i>Manage Sellers</h1>
                    <p class="mb-0">Monitor and manage seller accounts and applications</p>
                </div>
                <div class="text-end">
                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                    <small>Total Sellers</small>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row stats-row">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                            <small class="text-muted">Total</small>
                        </div>
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
          
            <div class="col-md-2">
                <div class="stat-card active">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['active']; ?></h3>
                            <small class="text-muted">Active</small>
                        </div>
                        <i class="fas fa-user-check fa-2x" style="color: var(--info-color);"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" placeholder="Search sellers..."
                            value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="sort">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Created
                        </option>
                        <option value="username" <?php echo $sort_by === 'username' ? 'selected' : ''; ?>>Username
                        </option>
                        <option value="first_name" <?php echo $sort_by === 'first_name' ? 'selected' : ''; ?>>First Name
                        </option>
                        <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Status</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="order">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Sellers List -->
        <div class="sellers-container">
            <?php if (empty($sellers)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No sellers found</h3>
                    <p>No sellers match your current filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($sellers as $seller): ?>
                    <div class="seller-card <?php echo $seller['status']; ?>">
                        <div class="seller-header">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="seller-avatar">
                                    <?php
                                    $initials = strtoupper(substr($seller['first_name'] ?: $seller['username'], 0, 1) .
                                        substr($seller['last_name'] ?: '', 0, 1));
                                    echo $initials;
                                    ?>
                                </div>
                                <div class="seller-info">
                                    <h5 class="seller-name">
                                        <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']) ?: htmlspecialchars($seller['username']); ?>
                                    </h5>
                                    <p class="seller-email"><?php echo htmlspecialchars($seller['email']); ?></p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$seller['is_active']): ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $seller['status']; ?>">
                                    <?php echo ucfirst($seller['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="seller-details">
                            <div class="detail-item">
                                <span class="detail-label">Username</span>
                                <span class="detail-value"><?php echo htmlspecialchars($seller['username']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Business Name</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($seller['business_name'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?php echo htmlspecialchars($seller['phone'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Business Phone</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($seller['business_phone'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Stall</span>
                                <span
                                    class="detail-value"><?php echo htmlspecialchars($seller['selected_stall'] ?: 'Not assigned'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Products</span>
                                <span class="detail-value"><?php echo $seller['product_count']; ?> products</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Joined</span>
                                <span
                                    class="detail-value"><?php echo date('M j, Y', strtotime($seller['created_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Facebook</span>
                                <span class="detail-value">
                                    <?php if ($seller['facebook_url']): ?>
                                        <a href="<?php echo htmlspecialchars($seller['facebook_url']); ?>" target="_blank"
                                            class="text-primary">
                                            <i class="fab fa-facebook me-1"></i>View Profile
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <?php if ($seller['status'] === 'pending'): ?>
                                <button type="button" class="btn-action btn-approve"
                                    onclick="showApproveModal(<?php echo $seller['id']; ?>)">
                                    <i class="fas fa-check"></i>Approve
                                </button>
                                <button type="button" class="btn-action btn-reject"
                                    onclick="showRejectModal(<?php echo $seller['id']; ?>)">
                                    <i class="fas fa-times"></i>Reject
                                </button>
                            <?php endif; ?>

                            <?php if ($seller['status'] === 'approved'): ?>
                                <button type="button" class="btn-action btn-suspend"
                                    onclick="showSuspendModal(<?php echo $seller['id']; ?>)">
                                    <i class="fas fa-ban"></i>Suspend
                                </button>
                            <?php endif; ?>

                            <?php if ($seller['is_active']): ?>
                                <button type="button" class="btn-action btn-suspend"
                                    onclick="showDeactivateModal(<?php echo $seller['id']; ?>)">
                                    <i class="fas fa-user-slash"></i>Deactivate
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-action btn-approve"
                                    onclick="showActivateModal(<?php echo $seller['id']; ?>)">
                                    <i class="fas fa-user-check"></i>Activate
                                </button>
                            <?php endif; ?>

                            <!-- FIXED: Changed from seller_details.php to view_seller.php and add proper onclick handler -->
                            <button type="button" class="btn-action btn-view"
                                onclick="viewSellerDetails(<?php echo $seller['id']; ?>)">
                                <i class="fas fa-eye"></i>View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Modals -->
    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Approve Seller
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this seller?</p>
                    <p class="text-muted mb-0">This will allow the seller to start listing products on the marketplace.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="approveForm" style="display: inline;">
                        <input type="hidden" name="seller_id" id="approveSellerId">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Approve Seller
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Reject Seller
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this seller?</p>
                    <p class="text-muted mb-0">This will reject their application and they will be notified.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="rejectForm" style="display: inline;">
                        <input type="hidden" name="seller_id" id="rejectSellerId">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Reject Seller
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    <div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="suspendModalLabel">
                        <i class="fas fa-ban me-2"></i>Suspend Seller
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to suspend this seller?</p>
                    <p class="text-muted mb-0">This will temporarily disable their account and prevent them from listing
                        products.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="suspendForm" style="display: inline;">
                        <input type="hidden" name="seller_id" id="suspendSellerId">
                        <input type="hidden" name="action" value="suspend">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-ban me-1"></i>Suspend Seller
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivate Modal -->
    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="deactivateModalLabel">
                        <i class="fas fa-user-slash me-2"></i>Deactivate Seller
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to deactivate this seller?</p>
                    <p class="text-muted mb-0">This will disable their account and they won't be able to access the
                        marketplace.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deactivateForm" style="display: inline;">
                        <input type="hidden" name="seller_id" id="deactivateSellerId">
                        <input type="hidden" name="action" value="deactivate">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-user-slash me-1"></i>Deactivate Seller
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Activate Modal -->
    <div class="modal fade" id="activateModal" tabindex="-1" aria-labelledby="activateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="activateModalLabel">
                        <i class="fas fa-user-check me-2"></i>Activate Seller
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to activate this seller?</p>
                    <p class="text-muted mb-0">This will re-enable their account and allow them to access the
                        marketplace.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="activateForm" style="display: inline;">
                        <input type="hidden" name="seller_id" id="activateSellerId">
                        <input type="hidden" name="action" value="activate">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-check me-1"></i>Activate Seller
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FIXED: Function to handle view seller details
        function viewSellerDetails(sellerId) {
            // You can either:
            // 1. Redirect to a seller details page
            window.location.href = 'view_seller.php?id=' + sellerId;

            // 2. Or open in a new tab
            // window.open('view_seller.php?id=' + sellerId, '_blank');

            // 3. Or show a modal with seller details (if you prefer)
            // showSellerModal(sellerId);
        }

        // Modal functions for different actions
        function showApproveModal(sellerId) {
            document.getElementById('approveSellerId').value = sellerId;
            const modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        }

        function showRejectModal(sellerId) {
            document.getElementById('rejectSellerId').value = sellerId;
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }

        function showSuspendModal(sellerId) {
            document.getElementById('suspendSellerId').value = sellerId;
            const modal = new bootstrap.Modal(document.getElementById('suspendModal'));
            modal.show();
        }

        function showDeactivateModal(sellerId) {
            document.getElementById('deactivateSellerId').value = sellerId;
            const modal = new bootstrap.Modal(document.getElementById('deactivateModal'));
            modal.show();
        }

        function showActivateModal(sellerId) {
            document.getElementById('activateSellerId').value = sellerId;
            const modal = new bootstrap.Modal(document.getElementById('activateModal'));
            modal.show();
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Add loading state to action buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (e) {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                    // Re-enable after 3 seconds (in case of errors)
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }, 3000);
                }
            });
        });

        // Search functionality with debounce
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }

        // Filter change auto-submit
        document.querySelectorAll('select[name="status"], select[name="sort"], select[name="order"]').forEach(select => {
            select.addEventListener('change', function () {
                this.form.submit();
            });
        });

        // FIXED: Prevent default form submission on enter key in search
        document.querySelector('input[name="search"]').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    </script>
</body>

</html>