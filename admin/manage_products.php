<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle product status updates using current schema (is_active)
if (isset($_POST['action']) && isset($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE products SET is_active = 1 WHERE id = ?");
        $stmt->execute([$product_id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->execute([$product_id]);
    } elseif ($action === 'delete') {
        // product_images will cascade-delete via FK
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
    }
}

// Pre-compute total products
try {
    $total_products = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (Exception $e) {
    $total_products = 0;
}

// Handle AJAX request for product details
if (isset($_GET['action']) && $_GET['action'] === 'get_product_details' && isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    
    try {
        // Fetch detailed product information
        $sql = "SELECT p.*, c.name AS category_name,
                       CONCAT(COALESCE(sa.business_name, s.username)) AS seller_name,
                       s.email AS seller_email,
                       s.phone AS seller_phone
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN sellers s ON p.seller_id = s.id
                LEFT JOIN seller_applications sa ON sa.seller_id = s.id AND sa.status = 'approved'
                WHERE p.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            echo json_encode(['error' => 'Product not found']);
            exit;
        }
        
        // Fetch all product images
        $images_sql = "SELECT image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC";
        $images_stmt = $pdo->prepare($images_sql);
        $images_stmt->execute([$product_id]);
        $images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $product['images'] = $images;
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($product);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
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

        .status-active {
            background: #e8f7ef;
            color: #27ae60;
        }

        .status-inactive {
            background: #fdecea;
            color: #e74c3c;
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
            background: #2f6186;
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


        .product-images .carousel-item img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.product-images .carousel-control-prev,
.product-images .carousel-control-next {
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    top: 50%;
    transform: translateY(-50%);
}

.product-images .carousel-control-prev {
    left: 10px;
}

.product-images .carousel-control-next {
    right: 10px;
}

.product-info .row {
    border-bottom: 1px solid #f0f0f0;
    padding: 8px 0;
}

.product-info .row:last-child {
    border-bottom: none;
}

#productDescription {
    min-height: 100px;
    white-space: pre-wrap;
}

.modal-xl {
    max-width: 1200px;
}

@media (max-width: 768px) {
    .modal-xl {
        max-width: 95%;
        margin: 10px auto;
    }
    
    .product-images .carousel-item img {
        height: 250px;
    }
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
                            // Fetch products with primary image path
                            $sql = "SELECT p.*, c.name AS category_name,
                                           CONCAT(COALESCE(sa.business_name, s.username)) AS seller_name,
                                           (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    LEFT JOIN sellers s ON p.seller_id = s.id
                                    LEFT JOIN seller_applications sa ON sa.seller_id = s.id AND sa.status = 'approved'
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
                                    $status_class = $row['is_active'] ? 'status-active' : 'status-inactive';
                                    ?>
                                    <tr class='product-row' data-status='<?php echo $row['status']; ?>'>
                                        <td>
                                            <?php if (!empty($row['primary_image']) && file_exists('../' . $row['primary_image'])): ?>
                                                <img src='<?php echo '../' . htmlspecialchars($row['primary_image']); ?>'
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
                                        <td><?php echo htmlspecialchars($row['seller_name'] ?: 'Unknown Seller'); ?></td>
                                        <td>
                                            <span class='status-badge <?php echo $status_class; ?>'>
                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$row['is_active']): ?>
                                                <button type="button" class="btn-action btn-approve"
                                                    onclick="approveProduct(<?php echo (int) $row['id']; ?>)">
                                                    <i class="fas fa-check"></i>Activate
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($row['is_active']): ?>
                                                <button type="button" class="btn-action btn-reject"
                                                    onclick="rejectProduct(<?php echo (int) $row['id']; ?>)">
                                                    <i class="fas fa-times"></i>Deactivate
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

    <!-- Replace your existing View Details Modal with this enhanced version -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #2f6186">
                <h5 class="modal-title">
                    <i class="fas fa-box me-2"></i>Product Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="productDetailsContent">
                    <!-- Loading state -->
                    <div class="text-center py-4" id="loadingState">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading product details</p>
                        <p style="font-style: italic;">(Refresh the page if it takes too long)</p>
                    </div>
                    
                    <!-- Error state -->
                    <div class="alert alert-danger d-none" id="errorState">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="errorMessage">Failed to load product details.</span>
                    </div>
                    
                    <!-- Product details content -->
                    <div class="d-none" id="productContent">
                        <div class="row">
                            <!-- Product Images -->
                            <div class="col-md-5">
                                <div class="product-images">
                                    <div id="productImageCarousel" class="carousel slide" data-bs-ride="carousel">
                                        <div class="carousel-inner" id="carouselImages">
                                            <!-- Images will be loaded here -->
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#productImageCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon"></span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#productImageCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Information -->
                            <div class="col-md-7">
                                <div class="product-info">
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Product ID:</strong></div>
                                        <div class="col-sm-8" id="productId">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Name:</strong></div>
                                        <div class="col-sm-8" id="productName">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Category:</strong></div>
                                        <div class="col-sm-8" id="productCategory">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Price:</strong></div>
                                        <div class="col-sm-8" id="productPrice">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Stock:</strong></div>
                                        <div class="col-sm-8" id="productStock">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Weight:</strong></div>
                                        <div class="col-sm-8" id="productWeight">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Status:</strong></div>
                                        <div class="col-sm-8" id="productStatus">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Featured:</strong></div>
                                        <div class="col-sm-8" id="productFeatured">-</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Created:</strong></div>
                                        <div class="col-sm-8" id="productCreated">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Description -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6><strong>Description:</strong></h6>
                                <div class="border rounded p-3 bg-light" id="productDescription">
                                    -
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seller Information -->
                        <div class="row">
                            <div class="col-12">
                                <h6><strong>Seller Information:</strong></h6>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Name:</strong>
                                                <span id="sellerName">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Email:</strong>
                                                <span id="sellerEmail">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Phone:</strong>
                                                <span id="sellerPhone">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-primary" id="editProductBtn" style="display: none;">
                    <i class="fas fa-edit me-1"></i>Edit Product
                </button>
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

        // Replace your existing viewProductDetails function with this enhanced version
function viewProductDetails(productId) {
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
    
    // Reset modal state
    document.getElementById('loadingState').classList.remove('d-none');
    document.getElementById('errorState').classList.add('d-none');
    document.getElementById('productContent').classList.add('d-none');
    
    // Fetch product details via AJAX
    fetch(`?action=get_product_details&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Hide loading state
            document.getElementById('loadingState').classList.add('d-none');
            
            // Populate product details
            populateProductDetails(data);
            
            // Show content
            document.getElementById('productContent').classList.remove('d-none');
        })
        .catch(error => {
            console.error('Error fetching product details:', error);
            
            // Hide loading state
            document.getElementById('loadingState').classList.add('d-none');
            
            // Show error state
            document.getElementById('errorMessage').textContent = error.message || 'Failed to load product details.';
            document.getElementById('errorState').classList.remove('d-none');
        });
}

function populateProductDetails(product) {
    // Basic product information
    document.getElementById('productId').textContent = product.id || '-';
    document.getElementById('productName').textContent = product.name || '-';
    document.getElementById('productCategory').textContent = product.category_name || 'Uncategorized';
    document.getElementById('productPrice').textContent = product.price ? `₱${parseFloat(product.price).toLocaleString()}` : '-';
    document.getElementById('productStock').textContent = product.stock_quantity || '0';
    document.getElementById('productWeight').textContent = product.weight ? `${product.weight} kg` : '-';
    document.getElementById('productDescription').textContent = product.description || 'No description available.';
    
    // Status badge
    const statusElement = document.getElementById('productStatus');
    const isActive = parseInt(product.is_active);
    statusElement.innerHTML = `<span class="badge ${isActive ? 'bg-success' : 'bg-danger'}">${isActive ? 'Active' : 'Inactive'}</span>`;
    
    // Featured badge
    const featuredElement = document.getElementById('productFeatured');
    const isFeatured = parseInt(product.is_featured);
    featuredElement.innerHTML = `<span class="badge ${isFeatured ? 'bg-warning' : 'bg-secondary'}">${isFeatured ? 'Yes' : 'No'}</span>`;
    
    // Created date
    const createdDate = product.created_at ? new Date(product.created_at).toLocaleDateString() : '-';
    document.getElementById('productCreated').textContent = createdDate;
    
    // Seller information
    document.getElementById('sellerName').textContent = product.seller_name || 'Unknown';
    document.getElementById('sellerEmail').textContent = product.seller_email || '-';
    document.getElementById('sellerPhone').textContent = product.seller_phone || '-';
    
    // Handle product images
    const carouselImages = document.getElementById('carouselImages');
    carouselImages.innerHTML = '';
    
    if (product.images && product.images.length > 0) {
        product.images.forEach((image, index) => {
            const carouselItem = document.createElement('div');
            carouselItem.className = `carousel-item ${index === 0 ? 'active' : ''}`;
            
            const img = document.createElement('img');
            img.src = `../${image.image_path}`;
            img.className = 'd-block w-100';
            img.alt = 'Product Image';
            img.onerror = function() {
                this.src = 'https://via.placeholder.com/400x300?text=No+Image';
            };
            
            carouselItem.appendChild(img);
            carouselImages.appendChild(carouselItem);
        });
    } else {
        // No images available
        const carouselItem = document.createElement('div');
        carouselItem.className = 'carousel-item active';
        
        const img = document.createElement('img');
        img.src = 'https://via.placeholder.com/400x300?text=No+Image+Available';
        img.className = 'd-block w-100';
        img.alt = 'No Image Available';
        
        carouselItem.appendChild(img);
        carouselImages.appendChild(carouselItem);
    }
    
    // Show/hide carousel controls based on number of images
    const prevControl = document.querySelector('.carousel-control-prev');
    const nextControl = document.querySelector('.carousel-control-next');
    if (product.images && product.images.length > 1) {
        prevControl.style.display = 'flex';
        nextControl.style.display = 'flex';
    } else {
        prevControl.style.display = 'none';
        nextControl.style.display = 'none';
    }
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