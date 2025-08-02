<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle product status updates
if (isset($_POST['action']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $sql = "UPDATE products SET status = 'approved' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    } elseif ($action == 'reject') {
        $sql = "UPDATE products SET status = 'rejected' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    } elseif ($action == 'delete') {
        // Delete the product (image is stored as BLOB in products table)

        // Then delete the product
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Oroquieta Marketplace</title>
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

        .filters-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .product-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        .product-image-placeholder {
            width: 60px;
            height: 60px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            color: #6c757d;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .status-rejected {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
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
            margin: 0 0.25rem;
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

        .btn-view {
            background-color: var(--info-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #138496;
            transform: translateY(-1px);
            color: white;
        }

        .btn-delete {
            background-color: #6c757d;
            color: white;
        }

        .btn-delete:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            background: white;
            color: var(--primary-color);
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: var(--card-shadow);
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

        @media (max-width: 768px) {
            .filter-btn {
                margin: 0.25rem;
                font-size: 0.8rem;
                padding: 0.4rem 1rem;
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
                    <h1 class="mb-2"><i class="fas fa-box me-2"></i>Manage Products</h1>
                    <p class="mb-0">Monitor and manage all marketplace products</p>
                </div>
                <div class="text-end">
                    <h3 class="mb-0"><?php echo $total_products ?? 0; ?></h3>
                    <small>Total Products</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="d-flex justify-content-center flex-wrap">
                <button class="filter-btn active" onclick="filterProducts('all')">
                    <i class="fas fa-list me-1"></i>All Products
                </button>
                <button class="filter-btn" onclick="filterProducts('approved')">
                    <i class="fas fa-check-circle me-1"></i>Approved
                </button>
                <button class="filter-btn" onclick="filterProducts('pending')">
                    <i class="fas fa-clock me-1"></i>Pending
                </button>
                <button class="filter-btn" onclick="filterProducts('rejected')">
                    <i class="fas fa-times-circle me-1"></i>Rejected
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card product-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Seller</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT p.*, c.name as category_name, s.shop_name 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id
                                   LEFT JOIN sellers s ON p.seller_id = s.id 
                                   ORDER BY p.id DESC";
                            $stmt = $pdo->query($sql);
                            $products = $stmt->fetchAll();

                            if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-box-open"></i>
                                            <h4>No Products Found</h4>
                                            <p>There are no products to display at the moment.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($products as $row):
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'approved':
                                            $status_class = 'status-approved';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'rejected':
                                            $status_class = 'status-rejected';
                                            break;
                                    }
                                    ?>
                                    <tr class='product-row' data-status='<?php echo $row['status']; ?>'>
                                        <td>
                                            <?php if (!empty($row['image'])): ?>
                                                <img src='data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>'
                                                    class='product-image' alt='Product'>
                                            <?php else: ?>
                                                <div class='product-image-placeholder'>
                                                    <i class='fas fa-image'></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                            <br><small class="text-muted">ID: <?php echo $row['id']; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['category_name'] ?: 'Uncategorized'); ?></td>
                                        <td><strong>₱<?php echo number_format($row['price'], 2); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['shop_name'] ?: 'Unknown Seller'); ?></td>
                                        <td>
                                            <span class='status-badge <?php echo $status_class; ?>'>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] != 'approved'): ?>
                                                <button type="button" class="btn-action btn-approve"
                                                    onclick="approveProduct(<?php echo $row['id']; ?>)">
                                                    <i class="fas fa-check"></i>Approve
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($row['status'] != 'rejected'): ?>
                                                <button type="button" class="btn-action btn-reject"
                                                    onclick="rejectProduct(<?php echo $row['id']; ?>)">
                                                    <i class="fas fa-times"></i>Reject
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="btn-action btn-view"
                                                onclick="viewProductDetails(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i>View
                                            </button>

                                            <button type="button" class="btn-action btn-delete"
                                                onclick="deleteProduct(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-trash"></i>Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modals -->
    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Approve Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this product?</p>
                    <p class="text-muted mb-0">This will make the product visible to customers on the marketplace.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="approveForm" style="display: inline;">
                        <input type="hidden" name="product_id" id="approveProductId">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Approve Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Reject Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this product?</p>
                    <p class="text-muted mb-0">This will hide the product from customers and notify the seller.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="rejectForm" style="display: inline;">
                        <input type="hidden" name="product_id" id="rejectProductId">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Reject Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-trash me-2"></i>Delete Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product?</p>
                    <p class="text-muted mb-0">This action cannot be undone and will permanently remove the product from
                        the system.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="product_id" id="deleteProductId">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-trash me-1"></i>Delete Product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-box me-2"></i>Product Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterProducts(status) {
            const rows = document.querySelectorAll('.product-row');
            const buttons = document.querySelectorAll('.filter-btn');

            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function approveProduct(productId) {
            document.getElementById('approveProductId').value = productId;
            const modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        }

        function rejectProduct(productId) {
            document.getElementById('rejectProductId').value = productId;
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }

        function deleteProduct(productId) {
            document.getElementById('deleteProductId').value = productId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function viewProductDetails(productId) {
            // Implement AJAX call to fetch product details
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
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
    </script>
</body>

</html>