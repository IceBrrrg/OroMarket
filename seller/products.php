<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$seller_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get seller information
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

// Get seller application info for business name
$stmt = $pdo->prepare("SELECT business_name FROM seller_applications WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$seller_id]);
$application = $stmt->fetch();
$business_name = $application ? $application['business_name'] : ($seller['first_name'] . ' ' . $seller['last_name']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Product
    if (isset($_POST['add_product'])) {
        $product_name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $sku = trim($_POST['sku']);
        $weight = floatval($_POST['weight']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Validate inputs
        if (empty($product_name) || $price <= 0 || $stock_quantity < 0) {
            $message = "Please fill in all required fields correctly.";
            $message_type = "danger";
        } else {
            // Insert product into database
            $query = "INSERT INTO products (seller_id, name, description, price, stock_quantity, sku, weight, is_featured, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([$seller_id, $product_name, $description, $price, $stock_quantity, $sku, $weight, $is_featured]);

            if ($result) {
                $product_id = $pdo->lastInsertId();
                
                // Handle multiple image uploads
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                        if ($_FILES['images']['error'][$i] == 0) {
                            $file_type = $_FILES['images']['type'][$i];
                            $file_size = $_FILES['images']['size'][$i];
                            
                            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                                $file_ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                                $filename = 'product_' . $product_id . '_' . ($i + 1) . '_' . time() . '.' . $file_ext;
                                $file_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $file_path)) {
                                    // Insert image record
                                    $img_query = "INSERT INTO product_images (product_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)";
                                    $img_stmt = $pdo->prepare($img_query);
                                    $is_primary = ($i == 0) ? 1 : 0; // First image is primary
                                    $img_stmt->execute([$product_id, 'uploads/products/' . $filename, $is_primary, $i + 1]);
                                }
                            }
                        }
                    }
                }

                $message = "Product added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding product. Please try again.";
                $message_type = "danger";
            }
        }
    }

    // Update Product
    elseif (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id']);
        $product_name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $sku = trim($_POST['sku']);
        $weight = floatval($_POST['weight']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Validate inputs
        if (empty($product_name) || $price <= 0 || $stock_quantity < 0) {
            $message = "Please fill in all required fields correctly.";
            $message_type = "danger";
        } else {
            // Verify product belongs to this seller
            $check_query = "SELECT id FROM products WHERE id = ? AND seller_id = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$product_id, $seller_id]);

            if ($check_stmt->fetch()) {
                // Update product in database
                $query = "UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, sku = ?, weight = ?, is_featured = ?, updated_at = NOW() WHERE id = ? AND seller_id = ?";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([$product_name, $description, $price, $stock_quantity, $sku, $weight, $is_featured, $product_id, $seller_id]);

                if ($result) {
                    $message = "Product updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating product. Please try again.";
                    $message_type = "danger";
                }
            } else {
                $message = "Product not found or access denied.";
                $message_type = "danger";
            }
        }
    }

    // Toggle Product Status
    elseif (isset($_POST['toggle_status'])) {
        $product_id = intval($_POST['product_id']);
        
        // Verify product belongs to this seller and get current status
        $check_query = "SELECT is_active FROM products WHERE id = ? AND seller_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$product_id, $seller_id]);
        $product = $check_stmt->fetch();

        if ($product) {
            $new_status = $product['is_active'] ? 0 : 1;
            $update_query = "UPDATE products SET is_active = ?, updated_at = NOW() WHERE id = ? AND seller_id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $result = $update_stmt->execute([$new_status, $product_id, $seller_id]);

            if ($result) {
                $status_text = $new_status ? 'activated' : 'deactivated';
                $message = "Product {$status_text} successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating product status.";
                $message_type = "danger";
            }
        } else {
            $message = "Product not found or access denied.";
            $message_type = "danger";
        }
    }

    // Delete Product
    elseif (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);

        // Verify product belongs to this seller
        $check_query = "SELECT id FROM products WHERE id = ? AND seller_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$product_id, $seller_id]);

        if ($check_stmt->fetch()) {
            // Delete associated images first
            $img_query = "SELECT image_path FROM product_images WHERE product_id = ?";
            $img_stmt = $pdo->prepare($img_query);
            $img_stmt->execute([$product_id]);
            $images = $img_stmt->fetchAll();

            // Delete image files
            foreach ($images as $image) {
                $file_path = '../' . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Delete image records
            $del_img_query = "DELETE FROM product_images WHERE product_id = ?";
            $del_img_stmt = $pdo->prepare($del_img_query);
            $del_img_stmt->execute([$product_id]);

            // Delete the product
            $delete_query = "DELETE FROM products WHERE id = ? AND seller_id = ?";
            $delete_stmt = $pdo->prepare($delete_query);
            $result = $delete_stmt->execute([$product_id, $seller_id]);

            if ($result) {
                $message = "Product deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting product. Please try again.";
                $message_type = "danger";
            }
        } else {
            $message = "Product not found or access denied.";
            $message_type = "danger";
        }
    }
}

// Get seller's products with images
$query = "SELECT p.*, 
                 (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image,
                 (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) as image_count
          FROM products p 
          WHERE p.seller_id = ? 
          ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll();

// Get categories for dropdown
$cat_query = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
$cat_stmt = $pdo->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #0f172a;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, var(--dark) 0%, #1e293b 100%);
            padding: 2rem 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .sidebar-brand h2 {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0 1rem;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .nav-link i {
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .container-fluid {
            max-width: 1400px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .page-header p {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Product Cards */
        .product-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--info) 100%);
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .product-image-placeholder {
            width: 100%;
            height: 200px;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-active {
            background: var(--success);
            color: white;
        }

        .badge-inactive {
            background: var(--danger);
            color: white;
        }

        .badge-featured {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--warning);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .card-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .price-info {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stock-info {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .btn-action {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-edit {
            background: var(--primary);
            color: white;
        }

        .btn-edit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-toggle {
            background: var(--warning);
            color: white;
        }

        .btn-toggle:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        /* Image Preview */
        .image-preview {
            display: none;
            margin-top: 1rem;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-secondary);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Success Message */
        .alert-success {
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            border: none;
            color: white;
            border-radius: var(--border-radius);
        }

        .alert-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            border: none;
            color: white;
            border-radius: var(--border-radius);
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--info) 100%);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.25rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 260px;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Modern Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="bi bi-shop"></i>ORO Market</h2>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link active">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="openAddProductModal()">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Product</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="bi bi-cart-check"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="revenue.php" class="nav-link">
                    <i class="bi bi-graph-up"></i>
                    <span>Revenue</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link">
                    <i class="bi bi-person"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: 2rem;">
                <a href="../logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="bi bi-box-seam me-2"></i>Product Management</h1>
                        <p>Manage your product inventory and listings for <?php echo htmlspecialchars($business_name); ?></p>
                    </div>
                    <button class="btn btn-light btn-lg" onclick="openAddProductModal()">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: var(--primary);">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stats-number"><?php echo count($products); ?></div>
                        <div class="stats-label">Total Products</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: var(--success);">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stats-number"><?php echo count(array_filter($products, function($p) { return $p['is_active']; })); ?></div>
                        <div class="stats-label">Active Products</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: var(--warning);">
                            <i class="bi bi-star"></i>
                        </div>
                        <div class="stats-number"><?php echo count(array_filter($products, function($p) { return $p['is_featured']; })); ?></div>
                        <div class="stats-label">Featured Products</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: var(--info);">
                            <i class="bi bi-images"></i>
                        </div>
                        <div class="stats-number"><?php echo array_sum(array_column($products, 'image_count')); ?></div>
                        <div class="stats-label">Total Images</div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="product-card">
                                <?php if ($product['is_featured']): ?>
                                    <div class="badge-featured">
                                        <i class="bi bi-star-fill me-1"></i>Featured
                                    </div>
                                <?php endif; ?>
                                
                                <div class="status-badge <?php echo $product['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </div>

                                <div class="position-relative">
                                    <?php if (!empty($product['primary_image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($product['primary_image']); ?>"
                                            class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    
                                    <?php if (!empty($product['description'])): ?>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?><?php echo strlen($product['description']) > 100 ? '...' : ''; ?></p>
                                    <?php endif; ?>

                                    <div class="price-info">
                                        <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                                        <div class="stock-info">
                                            <?php if (!empty($product['sku'])): ?>
                                                SKU: <?php echo htmlspecialchars($product['sku']); ?><br>
                                            <?php endif; ?>
                                            Stock: <?php echo $product['stock_quantity']; ?>
                                            <?php if ($product['image_count'] > 0): ?>
                                                <br><i class="bi bi-images me-1"></i><?php echo $product['image_count']; ?> image(s)
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </button>
                                        <button class="btn-action btn-toggle" onclick="toggleProductStatus(<?php echo $product['id']; ?>, <?php echo $product['is_active'] ? 'false' : 'true'; ?>)">
                                            <i class="bi bi-<?php echo $product['is_active'] ? 'eye-slash' : 'eye'; ?> me-1"></i><?php echo $product['is_active'] ? 'Hide' : 'Show'; ?>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-box-seam"></i>
                            <h4>No products yet</h4>
                            <p>Start building your inventory by adding your first product.</p>
                            <button class="btn btn-primary btn-lg" onclick="openAddProductModal()">
                                <i class="bi bi-plus-circle me-2"></i>Add Your First Product
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="addProductForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="productName" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="productName" name="name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="productPrice" class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" id="productPrice" name="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="productStock" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" id="productStock" name="stock_quantity" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="productSKU" class="form-label">SKU (Optional)</label>
                                <input type="text" class="form-control" id="productSKU" name="sku" placeholder="e.g., PROD-001">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="productWeight" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="productWeight" name="weight" step="0.01" min="0">
                            </div>
                            
                            <div class="col-md-12">
                                <label for="productDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="productDescription" name="description" rows="4" placeholder="Describe your product..."></textarea>
                            </div>
                            
                            <div class="col-md-12">
                                <label for="productImages" class="form-label">Product Images</label>
                                <input type="file" class="form-control" id="productImages" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
                                <small class="text-muted">You can select multiple images. First image will be the main product image.</small>
                                <div id="imagePreview" class="image-preview mt-2"></div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="productFeatured" name="is_featured">
                                    <label class="form-check-label" for="productFeatured">
                                        Mark as Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="edit_productName" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="edit_productName" name="name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="edit_productPrice" class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" id="edit_productPrice" name="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="edit_productStock" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" id="edit_productStock" name="stock_quantity" min="0" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="edit_productSKU" class="form-label">SKU (Optional)</label>
                                <input type="text" class="form-control" id="edit_productSKU" name="sku" placeholder="e.g., PROD-001">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="edit_productWeight" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="edit_productWeight" name="weight" step="0.01" min="0">
                            </div>
                            
                            <div class="col-md-12">
                                <label for="edit_productDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_productDescription" name="description" rows="4" placeholder="Describe your product..."></textarea>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Current Images</label>
                                <div id="currentImages" class="mb-2"></div>
                                <label for="edit_productImages" class="form-label">Add New Images</label>
                                <input type="file" class="form-control" id="edit_productImages" name="images[]" multiple accept="image/*">
                                <small class="text-muted">Select new images to add to this product.</small>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_productFeatured" name="is_featured">
                                    <label class="form-check-label" for="edit_productFeatured">
                                        Mark as Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Forms for Actions -->
    <form id="toggleStatusForm" method="POST" style="display: none;">
        <input type="hidden" id="toggle_product_id" name="product_id">
        <input type="hidden" name="toggle_status" value="1">
    </form>

    <form id="deleteProductForm" method="POST" style="display: none;">
        <input type="hidden" id="delete_product_id" name="product_id">
        <input type="hidden" name="delete_product" value="1">
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Modal Functions
        function openAddProductModal() {
            const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
            modal.show();
        }

        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                preview.style.display = 'block';
                
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'd-inline-block me-2 mb-2 position-relative';
                            div.innerHTML = `
                                <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Preview">
                                ${i === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0">Main</span>' : ''}
                            `;
                            preview.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            } else {
                preview.style.display = 'none';
            }
        }

        function editProduct(productId) {
            // Get product data from the page or make AJAX call
            const productCard = document.querySelector(`[onclick*="${productId}"]`).closest('.product-card');
            const productName = productCard.querySelector('.card-title').textContent;
            const productPrice = productCard.querySelector('.price').textContent.replace('₱', '').replace(',', '');
            
            // For now, we'll use a simple approach - in a real app you'd fetch from server
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        
                        // Populate edit form
                        document.getElementById('edit_product_id').value = product.id;
                        document.getElementById('edit_productName').value = product.name;
                        document.getElementById('edit_productPrice').value = product.price;
                        document.getElementById('edit_productStock').value = product.stock_quantity;
                        document.getElementById('edit_productSKU').value = product.sku || '';
                        document.getElementById('edit_productWeight').value = product.weight || '';
                        document.getElementById('edit_productDescription').value = product.description || '';
                        document.getElementById('edit_productFeatured').checked = product.is_featured == 1;
                        
                        // Show current images
                        const currentImagesDiv = document.getElementById('currentImages');
                        if (data.images && data.images.length > 0) {
                            currentImagesDiv.innerHTML = data.images.map((img, index) => `
                                <div class="d-inline-block me-2 mb-2 position-relative">
                                    <img src="../${img.image_path}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;" alt="Product Image">
                                    ${img.is_primary ? '<span class="badge bg-primary position-absolute top-0 start-0">Main</span>' : ''}
                                </div>
                            `).join('');
                        } else {
                            currentImagesDiv.innerHTML = '<p class="text-muted">No images uploaded</p>';
                        }
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
                        modal.show();
                    } else {
                        alert('Error loading product data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading product data');
                });
        }

        function toggleProductStatus(productId, newStatus) {
            const action = newStatus === 'true' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this product?`)) {
                document.getElementById('toggle_product_id').value = productId;
                document.getElementById('toggleStatusForm').submit();
            }
        }

        function deleteProduct(productId, productName) {
            if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                document.getElementById('delete_product_id').value = productId;
                document.getElementById('deleteProductForm').submit();
            }
        }

        // Auto-generate SKU based on product name
        document.getElementById('productName').addEventListener('input', function(e) {
            const skuField = document.getElementById('productSKU');
            if (!skuField.value && e.target.value) {
                const sku = e.target.value
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .substring(0, 6) + '-' + Math.random().toString(36).substr(2, 3).toUpperCase();
                skuField.value = sku;
            }
        });

        // Price formatting
        document.getElementById('productPrice').addEventListener('blur', function(e) {
            if (e.target.value) {
                e.target.value = parseFloat(e.target.value).toFixed(2);
            }
        });

        document.getElementById('edit_productPrice').addEventListener('blur', function(e) {
            if (e.target.value) {
                e.target.value = parseFloat(e.target.value).toFixed(2);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + N to open add product modal
            if (e.altKey && e.key === 'n') {
                e.preventDefault();
                openAddProductModal();
            }
            
            // Escape to close modal
            if (e.key === 'Escape') {
                const modal = document.querySelector('.modal.show');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            }
        });

        // Initialize tooltips and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate product cards on load
            const cards = document.querySelectorAll('.product-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 5000);
            });
        });
    </script>
</body>

</html>