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

    // Fetch announcements targeted to sellers or all users
    $stmt = $pdo->prepare(
        "SELECT title, content, priority, created_at, expiry_date, is_pinned 
         FROM announcements 
         WHERE (target_audience = 'sellers' OR target_audience = 'all') 
           AND is_active = 1 
           AND (expiry_date IS NULL OR expiry_date > NOW()) 
         ORDER BY is_pinned DESC, created_at DESC"
    );
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in dashboard: " . $e->getMessage());
    // Use default values if database error occurs
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
    $recent_activities = [];
    $categories = [];
    $announcements = [];
}
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
    <link rel="stylesheet" href="../seller/assets/css/dashboard_1.css" />
  
</head>

<body data-seller-id="<?php echo htmlspecialchars($seller_id); ?>">
    <?php include 'sidebar.php'; ?>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <h1>Welcome back, <?php echo htmlspecialchars($business_name); ?>! 👋</h1>
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
                            <div class="stat-number"><?php echo $total_products; ?></div>
                            <div class="stat-label">Total Products</div>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right me-2"></i>Manage Products
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon orders">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                            <a href="orders.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right me-2"></i>View Orders
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon revenue">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stat-number">₱<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                            <a href="analytics.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right me-2"></i>View Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRICE CHANGES SECTION WILL BE INSERTED HERE BY JAVASCRIPT -->

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
                                <a href="products.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-box"></i>
                                    </div>
                                    <h5>Manage Products</h5>
                                    <p class="text-muted mb-0">Edit, update, or remove your existing products</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="orders.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-receipt"></i>
                                    </div>
                                    <h5>View Orders</h5>
                                    <p class="text-muted mb-0">Check and manage your customer orders</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="profile.php" class="action-btn">
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

                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox"
                                    style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                                <p class="text-muted mt-2">No recent activity</p>
                                <p class="text-muted small">Start by adding some products!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="activity-icon" style="background: <?php echo $activity['type'] === 'order' ? 'var(--success)' : 'var(--primary)'; ?>;">
                                            <i class="bi bi-<?php echo $activity['type'] === 'order' ? 'cart-check' : 'box-seam'; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo $activity['type'] === 'order' ? 'Order: ' : 'Product: '; ?><?php echo htmlspecialchars($activity['title']); ?>
                                            </h6>
                                            <p class="text-muted mb-0">
                                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Announcements Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="quick-actions">
                        <h4><i class="bi bi-megaphone me-2"></i>Announcements</h4>
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-megaphone"
                                    style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                                <p class="text-muted mt-2">No announcements at the moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-card mb-3 p-3 border rounded" style="background: <?php echo $announcement['is_pinned'] ? '#fffbe6' : '#ffffff'; ?>;">
                                    <h5 class="mb-1">
                                        <?php if ($announcement['is_pinned']): ?>
                                            <i class="bi bi-pin-fill text-warning me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </h5>
                                    <p class="text-muted mb-2 small">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?>
                                        <?php if ($announcement['expiry_date']): ?>
                                            | Expires: <?php echo date('M j, Y', strtotime($announcement['expiry_date'])); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-0">
                                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- PRICE CHANGES DASHBOARD INTEGRATION -->
    <script>
        /**
         * Price Changes Dashboard Integration
         * Integrated directly into the seller dashboard
         */
/**
 * Simplified Price Changes Dashboard Integration
 * Focus: Show simple list of price changes like "Seller 1 changed the price of fish: from 150 to 200"
 */
/**
 * Fixed Price Changes Dashboard Integration
 * This replaces the existing PriceChangesDashboard class in your dashboard.php
 */
class PriceChangesDashboard {
    constructor() {
        // FIX 1: Use correct path for your project structure
        this.apiBaseUrl = '/OroMarket/seller/price-changes.php';

        this.sellerId = this.getCurrentSellerId();
        this.isLoading = false;
        this.refreshInterval = null;
        this.init();
    }

    /**
     * Initialize the price changes dashboard
     */
    init() {
        this.createPriceChangesSection();
        this.loadPriceChanges();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    /**
     * Get current seller ID from session or DOM
     */
    getCurrentSellerId() {
        return document.body.dataset.sellerId || <?php echo $seller_id; ?>;
    }

    /**
     * Create simplified price changes section
     */
    createPriceChangesSection() {
        const dashboardContainer = document.querySelector('.container-fluid');
        if (!dashboardContainer) return;

        const priceChangesSection = document.createElement('div');
        priceChangesSection.className = 'row g-4 mb-4';
        priceChangesSection.id = 'price-changes-section';
        
        priceChangesSection.innerHTML = `
            <div class="col-12">
                <div class="quick-actions">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>
                            <i class="bi bi-graph-up-arrow me-2"></i>
                            Price Changes
                        </h4>
                        <button class="btn btn-outline-primary btn-sm" id="refresh-btn">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                    
                    <!-- Price Changes List -->
                    <div id="price-changes-list">
                        <div class="text-center py-4" id="loading-state">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading price changes...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert after the stats cards but before quick actions
        const statsSection = dashboardContainer.querySelector('.row.g-4.mb-4');
        if (statsSection && statsSection.nextElementSibling) {
            dashboardContainer.insertBefore(priceChangesSection, statsSection.nextElementSibling);
        } else {
            dashboardContainer.appendChild(priceChangesSection);
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Refresh button
        document.getElementById('refresh-btn')?.addEventListener('click', () => {
            this.loadPriceChanges();
        });
    }

    /**
     * Load price changes from API
     */
    async loadPriceChanges() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();

        try {
            const params = {
                seller_id: this.sellerId,
                limit: 10,
                days: 7
            };
            
            const response = await this.fetchPriceChanges(params);

            if (response && response.success) {
                this.renderPriceChanges(response.data);
            } else {
                this.showErrorState(response?.message || 'Failed to load price changes');
            }
        } catch (error) {
            console.error('Error loading price changes:', error);
            this.showErrorState('Network error occurred: ' + error.message);
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    /**
     * Fetch price changes from API
     * FIX 2: Improved URL construction and error handling
     */
    async fetchPriceChanges(params) {
        // FIX 3: Use window.location.origin for proper base URL
        const url = new URL(this.apiBaseUrl, window.location.origin);
        url.searchParams.append('endpoint', 'changes');
        
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });

        console.log('Fetching from URL:', url.toString()); // Debug log

        const response = await fetch(url.toString());
        
        // FIX 4: Better error handling
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response received:', text.substring(0, 200));
            throw new Error('Server returned HTML instead of JSON - check if price-changes.php exists');
        }
        
        return await response.json();
    }

    /**
     * Render simple price changes list
     */
    renderPriceChanges(priceChanges) {
        const listContainer = document.getElementById('price-changes-list');
        if (!listContainer) return;

        if (!priceChanges || priceChanges.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-graph-up" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                    <p class="text-muted mt-2">No recent price changes</p>
                    <small class="text-muted">Price changes from the last 7 days will appear here</small>
                </div>
            `;
            return;
        }

        // Create simple list of price changes
        const changesHtml = priceChanges.map(change => {
            const sellerName = change.seller?.name || 'Unknown Seller';
            const productName = change.product?.name || 'Unknown Product';
            const oldPrice = change.price_change?.old_price || '0';
            const newPrice = change.price_change?.new_price || '0';
            const timeAgo = change.time_ago || 'Recently';
            
            const priceDirection = parseFloat(newPrice) > parseFloat(oldPrice) ? 'up' : 'down';
            const priceColor = priceDirection === 'up' ? 'text-success' : 'text-danger';
            const priceIcon = priceDirection === 'up' ? 'bi-arrow-up' : 'bi-arrow-down';
            
            return `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <i class="bi ${priceIcon} ${priceColor} me-2"></i>
                            <div>
                                <strong>${sellerName}</strong> changed the price of <strong>${productName}</strong>
                                <br>
                                <small class="text-muted">
                                    From <span class="text-decoration-line-through">₱${parseFloat(oldPrice).toFixed(2)}</span> 
                                    to <span class="fw-bold ${priceColor}">₱${parseFloat(newPrice).toFixed(2)}</span>
                                </small>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted text-nowrap ms-2">${timeAgo}</small>
                </div>
            `;
        }).join('');

        listContainer.innerHTML = `
            <div class="bg-light rounded p-3">
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Showing recent price changes from the last 7 days
                    </small>
                </div>
                ${changesHtml}
            </div>
        `;
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        const loadingState = document.getElementById('loading-state');
        const refreshBtn = document.getElementById('refresh-btn');
        
        if (loadingState) {
            loadingState.style.display = 'block';
        }
        
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading...';
        }
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        const loadingState = document.getElementById('loading-state');
        const refreshBtn = document.getElementById('refresh-btn');
        
        if (loadingState) {
            loadingState.style.display = 'none';
        }
        
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
        }
    }

    /**
     * Show error state
     */
    showErrorState(message) {
        const listContainer = document.getElementById('price-changes-list');
        if (!listContainer) return;

        listContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">${message}</p>
                <div class="mt-3">
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="priceChangesDashboard.loadPriceChanges()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Try Again
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="console.log('Debug info:', priceChangesDashboard)">
                        <i class="bi bi-bug me-1"></i>Debug Info
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Start auto refresh every 2 minutes
     */
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadPriceChanges();
        }, 2 * 60 * 1000);
    }

    /**
     * Stop auto refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * Destroy the dashboard instance
     */
    destroy() {
        this.stopAutoRefresh();
        const section = document.getElementById('price-changes-section');
        if (section) {
            section.remove();
        }
    }
}
// Initialize Price Changes Dashboard when DOM is ready


// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.priceChangesDashboard) {
        window.priceChangesDashboard.destroy();
    }
});
        // Initialize Price Changes Dashboard when DOM is ready
        let priceChangesDashboard;
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Price Changes Dashboard
            priceChangesDashboard = new PriceChangesDashboard();
            window.priceChangesDashboard = priceChangesDashboard;
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (window.priceChangesDashboard) {
                window.priceChangesDashboard.destroy();
            }
        });
    </script>

    <!-- Price Changes Custom Styles -->
    <style>
        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark, #0056b3) 100%);
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        .price-change-info .old-price {
            font-size: 0.8rem;
        }

        .price-change-info .new-price {
            font-size: 1.1rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #price-changes-section {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .col-md-3 {
                margin-bottom: 1rem;
            }
        }
    </style>

    <!-- Original Dashboard Scripts -->
    <script>
        // Global variables
        let products = [];
        let totalProducts = <?php echo $total_products; ?>;
        let totalOrders = <?php echo $total_orders; ?>;
        let totalRevenue = <?php echo $total_revenue; ?>;

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

        // Add category validation styling
        document.getElementById('productCategory').addEventListener('change', function() {
            this.classList.remove('is-invalid');
            if (this.value) {
                this.classList.add('is-valid');
            }
        });
    </script>

</body>

</html>