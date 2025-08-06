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

// Get total products
$query = "SELECT COUNT(*) as total FROM products WHERE seller_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$row = $stmt->fetch();
$total_products = $row['total'];

// Get total orders
$query = "SELECT COUNT(*) as total FROM orders WHERE seller_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$row = $stmt->fetch();
$total_orders = $row['total'];

// Get total revenue
$query = "SELECT SUM(total_amount) as total FROM orders WHERE seller_id = ? AND payment_status = 'paid'";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$row = $stmt->fetch();
$total_revenue = $row['total'] ? $row['total'] : 0;

// Get recent activities (last 5)
$query = "SELECT 'order' as type, order_number as title, created_at FROM orders WHERE seller_id = ? 
          UNION ALL 
          SELECT 'product' as type, name as title, created_at FROM products WHERE seller_id = ? 
          ORDER BY created_at DESC LIMIT 5";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id, $seller_id]);
$recent_activities = $stmt->fetchAll();
try {
    // Get total products (update the existing query)
    $query = "SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND is_active = 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$seller_id]);
    $row = $stmt->fetch();
    $total_products = $row['total'];

    // Get total orders (keep existing)
    $query = "SELECT COUNT(*) as total FROM orders WHERE seller_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$seller_id]);
    $row = $stmt->fetch();
    $total_orders = $row['total'];

    // Get total revenue (keep existing)
    $query = "SELECT SUM(total_amount) as total FROM orders WHERE seller_id = ? AND payment_status = 'paid'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$seller_id]);
    $row = $stmt->fetch();
    $total_revenue = $row['total'] ? $row['total'] : 0;

    // Get recent activities with products and orders
    $query = "
        SELECT 'order' as type, order_number as title, created_at FROM orders WHERE seller_id = ? 
        UNION ALL 
        SELECT 'product' as type, name as title, created_at FROM products WHERE seller_id = ? AND is_active = 1
        ORDER BY created_at DESC LIMIT 5
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$seller_id, $seller_id]);
    $recent_activities = $stmt->fetchAll();

    // Get categories for the dropdown
    $query = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database error in dashboard: " . $e->getMessage());
    // Use default values if database error occurs
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
    $recent_activities = [];
    $categories = [];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

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

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--info) 100%);
        }

        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: white;
        }

        .card-icon.products {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .card-icon.orders {
            background: linear-gradient(135deg, var(--success) 0%, #219653 100%);
        }

        .card-icon.revenue {
            background: linear-gradient(135deg, var(--warning) 0%, #f7931e 100%);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        /* Quick Actions */
        .quick-actions,
        .recent-activity {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .quick-actions h4,
        .recent-activity h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .action-btn {
            display: block;
            background: white;
            border: 2px solid var(--border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: var(--text-primary);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.2rem;
        }

        .action-btn h5 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Activity Items */
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 0.9rem;
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

        .form-control,
        .form-select {
            border: 2px solid var(--border);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

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

        /* Animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
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

        .dashboard-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .dashboard-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .dashboard-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* Success Message */
        .alert-success {
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            border: none;
            color: white;
            border-radius: var(--border-radius);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 0;
            }

            .welcome-header {
                padding: 2rem 1rem;
            }

            .stat-number {
                font-size: 2rem;
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
                <h1>Welcome back, Sample Business! 👋</h1>
                <p>Here's what's happening with your store today. Keep up the great work!</p>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-number">0</div>
                            <div class="stat-label">Total Products</div>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right me-2"></i>Manage Products
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Quick Actions & Recent Activity -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="quick-actions">
                        <h4><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h4>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <a href="#" class="action-btn" onclick="openAddProductModal()">
                                    <div class="action-icon">
                                        <i class="bi bi-plus-lg"></i>
                                    </div>
                                    <h5>Add New Product</h5>
                                    <p class="text-muted mb-0">Create a new product listing to expand your inventory</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="#" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-box"></i>
                                    </div>
                                    <h5>Manage Products</h5>
                                    <p class="text-muted mb-0">Edit, update, or remove your existing products</p>
                                </a>
                            </div>

                            <div class="col-md-6">
                                <a href="#" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-person-gear"></i>
                                    </div>
                                    <h5>Update Profile</h5>
                                    <p class="text-muted mb-0">Manage your shop information and settings</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="recent-activity">
                        <h4><i class="bi bi-clock-history me-2"></i>Recent Activity</h4>

                        <div class="text-center py-4">
                            <i class="bi bi-inbox"
                                style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                            <p class="text-muted mt-2">No recent activity</p>
                            <p class="text-muted small">Start by adding some products!</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tips -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="quick-actions">
                        <h4><i class="bi bi-lightbulb me-2"></i>Tips to Boost Your Sales</h4>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="action-btn" style="cursor: default; border-color: var(--success);">
                                    <div class="action-icon" style="background: var(--success);">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                    <h5>High-Quality Photos</h5>
                                    <p class="text-muted mb-0">Use clear, well-lit images to showcase your products
                                        effectively</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="action-btn" style="cursor: default; border-color: var(--info);">
                                    <div class="action-icon" style="background: var(--info);">
                                        <i class="bi bi-star"></i>
                                    </div>
                                    <h5>Detailed Descriptions</h5>
                                    <p class="text-muted mb-0">Write comprehensive product descriptions to help
                                        customers decide</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="action-btn" style="cursor: default; border-color: var(--warning);">
                                    <div class="action-icon" style="background: var(--warning);">
                                        <i class="bi bi-lightning"></i>
                                    </div>
                                    <h5>Quick Response</h5>
                                    <p class="text-muted mb-0">Respond to orders and inquiries promptly to build trust
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
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
            <div class="modal-body">
                <form id="addProductForm">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="productName" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="productName" name="name" required>
                        </div>

                        <div class="col-md-4">
                            <label for="productCategory" class="form-label">Category *</label>
                            <select class="form-select" id="productCategory" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <label for="productWeight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="productWeight" name="weight" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" id="productFeatured" name="is_featured">
                                <label class="form-check-label" for="productFeatured">
                                    Mark as Featured Product
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label for="productDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="productDescription" name="description" rows="4" placeholder="Describe your product..."></textarea>
                        </div>

                        <div class="col-md-12">
                            <label for="productImages" class="form-label">Product Images</label>
                            <input type="file" class="form-control" id="productImages" name="images[]" multiple accept="image/*">
                            <small class="text-muted">You can select multiple images. First image will be the main product image.</small>
                            <div id="imagePreview" class="image-preview mt-2"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitProduct()">
                    <span class="loading-spinner"></span>
                    <i class="bi bi-plus-circle me-2"></i>Add Product
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Updated submitProduct function with category validation
function submitProduct() {
    const form = document.getElementById('addProductForm');
    const submitBtn = document.querySelector('.modal-footer .btn-primary');
    const spinner = submitBtn.querySelector('.loading-spinner');
    const btnText = submitBtn.querySelector('i');

    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Additional validation
    const price = parseFloat(document.getElementById('productPrice').value);
    const stock = parseInt(document.getElementById('productStock').value);
    const categoryId = parseInt(document.getElementById('productCategory').value);

    if (price <= 0) {
        document.getElementById('productPrice').classList.add('is-invalid');
        isValid = false;
    }

    if (stock < 0) {
        document.getElementById('productStock').classList.add('is-invalid');
        isValid = false;
    }

    if (categoryId <= 0) {
        document.getElementById('productCategory').classList.add('is-invalid');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Please fill in all required fields with valid values.', 'danger');
        return;
    }

    // Show loading state
    spinner.style.display = 'inline-block';
    btnText.style.display = 'none';
    submitBtn.disabled = true;

    // Prepare form data for submission
    const formData = new FormData(form);

    // Make actual API call to save to database
    fetch('add_product_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Add to local products array
            products.push(data.product);

            // Update dashboard stats
            updateDashboardStats();

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
            modal.hide();
            form.reset();
            document.getElementById('imagePreview').style.display = 'none';

            // Show success message
            showNotification(data.message, 'success');

            // Add to recent activity
            addRecentActivity('product', data.product.name);

            // Optionally refresh the page to show updated data
            setTimeout(() => {
                location.reload();
            }, 2000);

        } else {
            throw new Error(data.message || 'Failed to add product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'danger');
    })
    .finally(() => {
        // Reset loading state
        spinner.style.display = 'none';
        btnText.style.display = 'inline';
        submitBtn.disabled = false;
    });
}

// Add category validation styling
document.getElementById('productCategory').addEventListener('change', function() {
    this.classList.remove('is-invalid');
    if (this.value) {
        this.classList.add('is-valid');
    }
});

// Remove auto-generate SKU function since SKU is removed
// Keep other existing JavaScript functions...
</script>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global variables
        let products = [];
        let totalProducts = 0;
        let totalOrders = 0;
        let totalRevenue = 0;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function () {
            initializeDashboard();
            setupImagePreview();
        });

        function initializeDashboard() {
            // Animate counter numbers
            animateCounters();

            // Add hover effects to dashboard cards
            addCardHoverEffects();

            // Setup notification system
            setupNotifications();
        }

        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.innerText.replace(/[^\d]/g, '')) || 0;
                const increment = target / 100;
                let current = 0;

                if (target > 0) {
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }

                        if (counter.innerText.includes('₱')) {
                            counter.innerText = '₱' + Math.floor(current).toLocaleString() + '.00';
                        } else {
                            counter.innerText = Math.floor(current).toLocaleString();
                        }
                    }, 20);
                }
            });
        }

        function addCardHoverEffects() {
            const dashboardCards = document.querySelectorAll('.dashboard-card');
            dashboardCards.forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        }

        function setupNotifications() {
            // Notification system for success messages
            window.showNotification = function (message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    box-shadow: var(--shadow-lg);
                    border: none;
                    border-radius: var(--border-radius);
                `;
                notification.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }
        }

        // Modal Functions
        function openAddProductModal() {
            const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
            modal.show();
        }

        function setupImagePreview() {
            const imageInput = document.getElementById('productImages');
            const previewContainer = document.getElementById('imagePreview');

            imageInput.addEventListener('change', function (e) {
                previewContainer.innerHTML = '';
                const files = e.target.files;

                if (files.length > 0) {
                    previewContainer.style.display = 'block';

                    Array.from(files).forEach((file, index) => {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                const imageContainer = document.createElement('div');
                                imageContainer.className = 'col-md-3 mb-2';
                                imageContainer.innerHTML = `
                                    <div class="position-relative">
                                        <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 100px; object-fit: cover;" alt="Preview ${index + 1}">
                                        ${index === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1">Main</span>' : ''}
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" onclick="removeImage(${index})">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                `;

                                if (previewContainer.children.length === 0) {
                                    previewContainer.innerHTML = '<div class="row g-2"></div>';
                                }
                                previewContainer.querySelector('.row').appendChild(imageContainer);
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                } else {
                    previewContainer.style.display = 'none';
                }
            });
        }

        function removeImage(index) {
            const imageInput = document.getElementById('productImages');
            const files = Array.from(imageInput.files);

            // Create new FileList without the removed file
            const dt = new DataTransfer();
            files.forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });

            imageInput.files = dt.files;
            imageInput.dispatchEvent(new Event('change'));
        }

        function submitProduct() {
            const form = document.getElementById('addProductForm');
            const submitBtn = document.querySelector('.modal-footer .btn-primary');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const btnText = submitBtn.querySelector('i');

            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Additional validation
            const price = parseFloat(document.getElementById('productPrice').value);
            const stock = parseInt(document.getElementById('productStock').value);

            if (price <= 0) {
                document.getElementById('productPrice').classList.add('is-invalid');
                isValid = false;
            }

            if (stock < 0) {
                document.getElementById('productStock').classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                showNotification('Please fill in all required fields with valid values.', 'danger');
                return;
            }

            // Show loading state
            spinner.style.display = 'inline-block';
            btnText.style.display = 'none';
            submitBtn.disabled = true;

            // Prepare form data for submission
            const formData = new FormData(form);

            // Make actual API call to save to database
            fetch('add_product_api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Add to local products array
                        products.push(data.product);

                        // Update dashboard stats
                        updateDashboardStats();

                        // Close modal and reset form
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                        modal.hide();
                        form.reset();
                        document.getElementById('imagePreview').style.display = 'none';

                        // Show success message
                        showNotification(data.message, 'success');

                        // Add to recent activity
                        addRecentActivity('product', data.product.name);

                        // Optionally refresh the page to show updated data
                        setTimeout(() => {
                            location.reload();
                        }, 2000);

                    } else {
                        throw new Error(data.message || 'Failed to add product');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error: ' + error.message, 'danger');
                })
                .finally(() => {
                    // Reset loading state
                    spinner.style.display = 'none';
                    btnText.style.display = 'inline';
                    submitBtn.disabled = false;
                });
        }

        function updateDashboardStats() {
            totalProducts = products.length;

            // Update product count
            const productCounter = document.querySelector('.dashboard-card .stat-number');
            if (productCounter) {
                animateNumber(productCounter, totalProducts);
            }
        }

        function animateNumber(element, target) {
            const current = parseInt(element.innerText) || 0;
            const increment = (target - current) / 20;
            let step = current;

            const timer = setInterval(() => {
                step += increment;
                if ((increment > 0 && step >= target) || (increment < 0 && step <= target)) {
                    step = target;
                    clearInterval(timer);
                }
                element.innerText = Math.floor(step).toLocaleString();
            }, 50);
        }

        function addRecentActivity(type, title) {
            const activityContainer = document.querySelector('.recent-activity');
            const emptyState = activityContainer.querySelector('.text-center');

            if (emptyState) {
                emptyState.remove();
            }

            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            activityItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="activity-icon" style="background: ${type === 'order' ? 'var(--success)' : 'var(--primary)'};">
                        <i class="bi bi-${type === 'order' ? 'cart-check' : 'box-seam'}"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">
                            ${type === 'order' ? 'New Order: ' : 'Product Added: '}${title}
                        </h6>
                        <p class="text-muted mb-0">
                            ${new Date().toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            })}
                        </p>
                    </div>
                </div>
            `;

            // Insert at the beginning
            const firstActivity = activityContainer.querySelector('.activity-item');
            if (firstActivity) {
                activityContainer.insertBefore(activityItem, firstActivity);
            } else {
                activityContainer.appendChild(activityItem);
            }

            // Keep only the last 5 activities
            const activities = activityContainer.querySelectorAll('.activity-item');
            if (activities.length > 5) {
                activities[activities.length - 1].remove();
            }
        }

        // Form validation styles
        const style = document.createElement('style');
        style.textContent = `
            .form-control.is-invalid, .form-select.is-invalid {
                border-color: var(--danger);
                box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
            }
            
            .form-control.is-invalid:focus, .form-select.is-invalid:focus {
                border-color: var(--danger);
                box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
            }
            
            .was-validated .form-control:valid, .form-control.is-valid {
                border-color: var(--success);
                box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.25);
            }
            
            /* Ripple effect for buttons */
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .alert {
                animation: slideInRight 0.3s ease-out;
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

            /* Modal animations */
            .modal.fade .modal-dialog {
                transition: transform 0.3s ease-out;
                transform: translate(0, -50px);
            }
            
            .modal.show .modal-dialog {
                transform: none;
            }
            
            /* Hover effects for action buttons */
            .action-btn {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .action-btn:hover .action-icon {
                transform: scale(1.1);
                transition: transform 0.3s ease;
            }
            
            /* Loading button state */
            .btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }
            
            /* Custom scrollbar for modal */
            .modal-body::-webkit-scrollbar {
                width: 6px;
            }
            
            .modal-body::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 3px;
            }
            
            .modal-body::-webkit-scrollbar-thumb {
                background: var(--primary);
                border-radius: 3px;
            }
            
            .modal-body::-webkit-scrollbar-thumb:hover {
                background: var(--primary-dark);
            }
        `;
        document.head.appendChild(style);

        // Auto-generate SKU based on product name
        document.getElementById('productName').addEventListener('input', function (e) {
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
        document.getElementById('productPrice').addEventListener('blur', function (e) {
            if (e.target.value) {
                e.target.value = parseFloat(e.target.value).toFixed(2);
            }
        });

        // Add click effects to buttons
        document.querySelectorAll('.btn, .action-btn[href]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.3);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                `;

                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);

                setTimeout(() => {
                    if (ripple.parentNode) {
                        ripple.remove();
                    }
                }, 600);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
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

        // Add tooltip to keyboard shortcut
        document.querySelector('a[onclick="openAddProductModal()"]').title = 'Alt + N';
    </script>
</body>

</html>