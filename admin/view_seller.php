<?php
session_start();
require_once '../includes/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../authenticator.php');
    exit();
}

// Get seller ID from URL
$seller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$seller_id) {
    header('Location: manage_sellers.php');
    exit();
}

// Get seller details - FIXED QUERY
$query = "
    SELECT 
        s.*,
        sa.business_name,
        sa.business_phone,
        sa.tax_id,
        sa.selected_stall,
        sa.status as application_status,
        sa.admin_notes,
        sa.created_at as application_date,
        st.stall_number,
        (SELECT COUNT(*) FROM products p WHERE p.seller_id = s.id) as product_count
    FROM sellers s
    LEFT JOIN seller_applications sa ON s.id = sa.seller_id
    LEFT JOIN stalls st ON sa.selected_stall = st.stall_number
    WHERE s.id = ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    header('Location: manage_sellers.php?error=seller_not_found');
    exit();
}

// Get seller's products
$products_query = "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 10";
$products_stmt = $pdo->prepare($products_query);
$products_stmt->execute([$seller_id]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Details - <?php echo htmlspecialchars($seller['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
        }

        .seller-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .seller-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .info-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background-color: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .status-approved { background-color: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .status-rejected { background-color: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        .status-suspended { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }

        .product-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="manage_sellers.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Sellers
            </a>
        </div>

        <!-- Seller Header -->
        <div class="seller-header">
            <div class="d-flex align-items-center">
                <div class="seller-avatar">
                    <?php 
                    $initials = strtoupper(substr($seller['first_name'] ?: $seller['username'], 0, 1) . 
                               substr($seller['last_name'] ?: '', 0, 1));
                    echo $initials;
                    ?>
                </div>
                <div class="ms-3">
                    <h1 class="mb-2">
                        <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']) ?: htmlspecialchars($seller['username']); ?>
                    </h1>
                    <p class="mb-2"><?php echo htmlspecialchars($seller['email']); ?></p>
                    <span class="status-badge status-<?php echo $seller['status']; ?>">
                        <?php echo ucfirst($seller['status']); ?>
                    </span>
                    <?php if (!$seller['is_active']): ?>
                        <span class="badge bg-secondary ms-2">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="info-card">
            <h4 class="mb-3"><i class="fas fa-user me-2"></i>Personal Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['username']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['phone'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['address'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Facebook</div>
                    <div class="info-value">
                        <?php if ($seller['facebook_url']): ?>
                            <a href="<?php echo htmlspecialchars($seller['facebook_url']); ?>" target="_blank" class="text-primary">
                                <i class="fab fa-facebook me-1"></i>View Profile
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Joined Date</div>
                    <div class="info-value"><?php echo date('M j, Y', strtotime($seller['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Business Information -->
        <?php if ($seller['business_name']): ?>
        <div class="info-card">
            <h4 class="mb-3"><i class="fas fa-building me-2"></i>Business Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Business Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['business_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Business Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['business_phone'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tax ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['tax_id'] ?: 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Assigned Stall</div>
                    <div class="info-value"><?php echo htmlspecialchars($seller['selected_stall'] ?: 'Not assigned'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Application Date</div>
                    <div class="info-value"><?php echo $seller['application_date'] ? date('M j, Y', strtotime($seller['application_date'])) : 'N/A'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Application Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo $seller['application_status']; ?>">
                            <?php echo ucfirst($seller['application_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php if ($seller['admin_notes']): ?>
            <div class="mt-3">
                <div class="info-label">Admin Notes</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($seller['admin_notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="info-card">
            <h4 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Statistics</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-primary text-white rounded">
                        <h3><?php echo $seller['product_count']; ?></h3>
                        <small>Total Products</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-info text-white rounded">
                        <h3><?php echo $seller['is_active'] ? 'Active' : 'Inactive'; ?></h3>
                        <small>Account Status</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-warning text-white rounded">
                        <h3><?php echo ucfirst($seller['status']); ?></h3>
                        <small>Seller Status</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-success text-white rounded">
                        <h3><?php echo $seller['application_status'] ? ucfirst($seller['application_status']) : 'N/A'; ?></h3>
                        <small>Application Status</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Products -->
        <?php if (!empty($products)): ?>
        <div class="info-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-box me-2"></i>Recent Products</h4>
                <a href="manage_products.php?seller_id=<?php echo $seller['id']; ?>" class="btn btn-outline-primary btn-sm">
                    View All Products
                </a>
            </div>
            <div class="row">
                <?php foreach (array_slice($products, 0, 6) as $product): ?>
                <div class="col-md-4 mb-3">
                    <div class="product-card">
                        <h6 class="mb-2"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-muted mb-2">₱<?php echo number_format($product['price'], 2); ?></p>
                        <small class="text-muted">Added: <?php echo date('M j, Y', strtotime($product['created_at'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="info-card">
            <h4 class="mb-3"><i class="fas fa-cogs me-2"></i>Actions</h4>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($seller['status'] === 'pending'): ?>
                    <form method="POST" action="manage_sellers.php" style="display: inline;">
                        <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Approve this seller?')">
                            <i class="fas fa-check me-1"></i>Approve Seller
                        </button>
                    </form>
                    <form method="POST" action="manage_sellers.php" style="display: inline;">
                        <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this seller?')">
                            <i class="fas fa-times me-1"></i>Reject Seller
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($seller['status'] === 'approved'): ?>
                    <form method="POST" action="manage_sellers.php" style="display: inline;">
                        <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                        <input type="hidden" name="action" value="suspend">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Suspend this seller?')">
                            <i class="fas fa-ban me-1"></i>Suspend Seller
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($seller['is_active']): ?>
                    <form method="POST" action="manage_sellers.php" style="display: inline;">
                        <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                        <input type="hidden" name="action" value="deactivate">
                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Deactivate this seller?')">
                            <i class="fas fa-user-slash me-1"></i>Deactivate
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="manage_sellers.php" style="display: inline;">
                        <input type="hidden" name="seller_id" value="<?php echo $seller['id']; ?>">
                        <input type="hidden" name="action" value="activate">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Activate this seller?')">
                            <i class="fas fa-user-check me-1"></i>Activate
                        </button>
                    </form>
                <?php endif; ?>

                <a href="manage_products.php?seller_id=<?php echo $seller['id']; ?>" class="btn btn-info">
                    <i class="fas fa-box me-1"></i>View Products
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>