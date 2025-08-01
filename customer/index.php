<?php
session_start();
require_once '../includes/db_connect.php';

// Get categories for sidebar
$cat_query = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
$cat_stmt = $pdo->query($cat_query);

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 999999;

// Build query for products
$where_conditions = ["p.status = 'approved'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($price_min > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $price_min;
}

if ($price_max < 999999) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $price_max;
}

$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT p.*, c.name as category_name, s.shop_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN sellers s ON p.seller_id = s.id 
          WHERE $where_clause 
          ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Window Shopping - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-bar {
            background: white;
            border-radius: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 25px;
            margin: 20px 0;
        }

        .sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .category-item {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-item:hover {
            background-color: #f8f9fa;
        }

        .category-item.active {
            background-color: var(--primary);
            color: white;
        }

        .filter-section {
            margin-bottom: 25px;
        }

        .filter-section h6 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .price-range {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .btn-apply-filters {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-apply-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .shop-name {
            font-size: 0.875rem;
            color: var(--secondary);
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary);
        }

        .no-products i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-bottom: 20px;
                position: static;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/img/logo-removebg.png" alt="ORO Market" height="40">
            </a>

            <div class="d-flex align-items-center">
                <a href="../index.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="authenticator.php" class="btn btn-outline-light">
                    <i class="fas fa-user"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <div class="container">
        <div class="search-bar">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search products..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <!-- Categories -->
                    <div class="filter-section">
                        <h6><i class="fas fa-tags me-2"></i>Categories</h6>
                        <div class="category-list">
                            <div class="category-item <?php echo $category_filter == 0 ? 'active' : ''; ?>"
                                onclick="window.location.href='?search=<?php echo urlencode($search); ?>&price_min=<?php echo $price_min; ?>&price_max=<?php echo $price_max; ?>'">
                                All Categories
                            </div>
                            <?php while ($category = $cat_stmt->fetch()): ?>
                                <div class="category-item <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>"
                                    onclick="window.location.href='?search=<?php echo urlencode($search); ?>&category=<?php echo $category['id']; ?>&price_min=<?php echo $price_min; ?>&price_max=<?php echo $price_max; ?>'">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Filter Options -->
                    <div class="filter-section">
                        <h6><i class="fas fa-sliders-h me-2"></i>Filter Options</h6>

                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="price_min" placeholder="Min"
                                        value="<?php echo $price_min > 0 ? $price_min : ''; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="price_max" placeholder="Max"
                                        value="<?php echo $price_max < 999999 ? $price_max : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="mb-3">
                            <label class="form-label">Availability</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="available" checked>
                                <label class="form-check-label" for="available">
                                    Available
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-apply-filters w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Product Area -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="product-card">
                                    <div class="position-relative">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>"
                                                class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>

                                        <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </div>

                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="shop-name mb-2">
                                            <i class="fas fa-store me-1"></i>
                                            <?php echo htmlspecialchars($product['shop_name'] ?? 'Unknown Seller'); ?>
                                        </p>
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span
                                                class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                            <small class="text-muted">per
                                                <?php echo htmlspecialchars($product['unit']); ?></small>
                                        </div>

                                        <?php if (!empty($product['description'])): ?>
                                            <p class="card-text small text-muted">
                                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-footer bg-transparent border-0">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary btn-sm"
                                                onclick="viewProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-shopping-cart me-1"></i>Message Seller
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="no-products">
                                <i class="fas fa-search"></i>
                                <h4>No products found</h4>
                                <p>Try adjusting your search criteria or browse all categories.</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Browse All Products
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productModalBody">
                    <!-- Product details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function viewProduct(productId) {
            // TODO: Implement product details modal
            alert('Product details functionality will be implemented for product ID: ' + productId);
        }

        function addToCart(productId) {
            // TODO: Implement add to cart functionality
            alert('Add to cart functionality will be implemented for product ID: ' + productId);
        }

        // Auto-submit form when filters change
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', function () {
                // The onclick already handles navigation
            });
        });

        // Apply filters button
        document.querySelector('.btn-apply-filters').addEventListener('click', function () {
            const form = document.querySelector('form');
            const priceMin = document.querySelector('input[name="price_min"]').value;
            const priceMax = document.querySelector('input[name="price_max"]').value;

            // Add current search and category to form
            const searchInput = document.createElement('input');
            searchInput.type = 'hidden';
            searchInput.name = 'search';
            searchInput.value = '<?php echo htmlspecialchars($search); ?>';
            form.appendChild(searchInput);

            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            categoryInput.name = 'category';
            categoryInput.value = '<?php echo $category_filter; ?>';
            form.appendChild(categoryInput);

            form.submit();
        });
    </script>
</body>

</html>