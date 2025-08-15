<?php
require_once 'header.php';

// Database connection
require_once '../includes/db_connect.php';

// Get seller ID from URL parameter
$seller_id = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;

if ($seller_id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch seller information with stall details
$seller_query = "
    SELECT s.*, sa.business_name, st.stall_number, st.section 
    FROM sellers s 
    LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
    LEFT JOIN stalls st ON s.id = st.current_seller_id 
    WHERE s.id = ? AND s.status = 'approved' AND s.is_active = 1
";

$stmt = $pdo->prepare($seller_query);
$stmt->execute([$seller_id]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    header('Location: index.php');
    exit();
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$products_per_page = 9;
$offset = ($page - 1) * $products_per_page;

// Build WHERE clause for products
$where_conditions = ["p.seller_id = ?"];
$params = [$seller_id];

if ($category_filter !== 'all') {
    $where_conditions[] = "c.name = ?";
    $params[] = $category_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// Build ORDER BY clause
$order_by = "p.created_at DESC"; // default
switch ($sort_filter) {
    case 'price-low':
        $order_by = "p.price ASC";
        break;
    case 'price-high':
        $order_by = "p.price DESC";
        break;
    case 'popular':
        $order_by = "p.is_featured DESC, p.created_at DESC";
        break;
    case 'newest':
    default:
        $order_by = "p.created_at DESC";
        break;
}

// Fetch products with pagination
$products_query = "
    SELECT p.*, c.name as category_name, pi.image_path
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE $where_clause AND p.is_active = 1
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

$params[] = $products_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($products_query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total products count for pagination
$count_query = "
    SELECT COUNT(*) as total
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_clause AND p.is_active = 1
";

$count_params = array_slice($params, 0, -2); // Remove LIMIT and OFFSET params
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $products_per_page);

// Fetch categories for filter dropdown
$categories_query = "SELECT DISTINCT c.name FROM categories c 
                     INNER JOIN products p ON c.id = p.category_id 
                     WHERE p.seller_id = ? AND p.is_active = 1";
$stmt = $pdo->prepare($categories_query);
$stmt->execute([$seller_id]);
$available_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Helper function to get seller display name
function getSellerDisplayName($seller)
{
    if (!empty($seller['business_name'])) {
        return $seller['business_name'];
    } elseif (!empty($seller['first_name']) && !empty($seller['last_name'])) {
        return $seller['first_name'] . ' ' . $seller['last_name'];
    } else {
        return $seller['username'] ?? 'Unknown Seller';
    }
}
?>

<div class="main-content">
    <div class="container py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Market</a></li>
                <li class="breadcrumb-item"><a href="index.php">Sellers</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars(getSellerDisplayName($seller)); ?>
                </li>
            </ol>
        </nav>

        <div class="stall-container">
            <!-- Seller Profile Banner -->
            <div class="seller-profile-banner">
                <div class="profile-info">
                    <div class="profile-image">
                        <img src="<?php echo !empty($seller['profile_image']) ? '../' . htmlspecialchars($seller['profile_image']) : '../assets/img/avatar.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars(getSellerDisplayName($seller)); ?>" 
                             class="rounded-circle">
                    </div>
                    <div class="profile-details">
                        <h1 class="seller-name">
                            <?php echo htmlspecialchars(getSellerDisplayName($seller)); ?>
                        </h1>
                        <div class="seller-meta">
                            <div class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($seller['stall_number'] ? 'Stall ' . $seller['stall_number'] . ' - ' . $seller['section'] : 'Location TBA'); ?></span>
                            </div>
                            <div class="seller-actions">
                                <button class="btn btn-light message-seller-btn" 
                                        onclick="startChatWithSeller(<?php echo $seller_id; ?>)">
                                    <i class="fas fa-envelope me-2"></i>Message Seller
                                </button>
                                <?php if (!empty($seller['facebook_url'])): ?>
                                <a href="<?php echo htmlspecialchars($seller['facebook_url']); ?>" target="_blank"
                                    class="btn btn-light btn-outline-primary">
                                    <i class="fab fa-facebook"></i>
                                    Facebook
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="products-section">
                <div class="section-header">
                    <h2>Products (<?php echo $total_products; ?>)</h2>
                    <div class="filters">
                        <select class="form-select" onchange="filterProducts(this.value, 'category')">
                            <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($available_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                        <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($category)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" onchange="filterProducts(this.value, 'sort')">
                            <option value="newest" <?php echo $sort_filter === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price-low" <?php echo $sort_filter === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price-high" <?php echo $sort_filter === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="popular" <?php echo $sort_filter === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="no-products text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">No Products Found</h3>
                        <p class="text-muted">This seller hasn't added any products yet or no products match your filters.</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo !empty($product['image_path']) ? '../' . htmlspecialchars($product['image_path']) : '../assets/img/default-product.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php if ($product['is_featured']): ?>
                                        <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <span class="out-of-stock-badge">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                    <?php if (!empty($product['category_name'])): ?>
                                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php endif; ?>
                                    <p class="product-description">
                                        <?php echo htmlspecialchars(substr($product['description'] ?: 'No description available', 0, 100)); ?>
                                        <?php echo strlen($product['description'] ?: '') > 100 ? '...' : ''; ?>
                                    </p>
                                    <div class="product-stock">
                                        <small class="text-muted">
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <?php echo $product['stock_quantity']; ?> in stock
                                            <?php else: ?>
                                                Out of stock
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="product-actions">
                                        <button class="btn btn-primary view-product-btn" 
                                                onclick="viewProduct(<?php echo $product['id']; ?>)"
                                                <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                            <?php echo $product['stock_quantity'] > 0 ? 'View Product' : 'Out of Stock'; ?>
                                        </button>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <button class="btn btn-outline-primary inquire-btn" 
                                                    onclick="startChatWithSeller(<?php echo $seller_id; ?>, <?php echo $product['id']; ?>)">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <nav aria-label="Product navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?seller_id=<?php echo $seller_id; ?>&page=<?php echo $page - 1; ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_filter); ?>" tabindex="-1">Previous</a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?seller_id=<?php echo $seller_id; ?>&page=<?php echo $i; ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_filter); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?seller_id=<?php echo $seller_id; ?>&page=<?php echo $page + 1; ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_filter); ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Chat CSS -->
<link rel="stylesheet" href="css/chat.css">

<script>
function filterProducts(value, type) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set(type, value);
    urlParams.set('page', '1'); // Reset to first page when filtering
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

function viewProduct(productId) {
    window.location.href = 'view_product.php?id=' + productId;
}

function contactSeller(email, phone) {
    let contactOptions = [];
    
    if (email) {
        contactOptions.push(`Email: ${email}`);
    }
    if (phone) {
        contactOptions.push(`Phone: ${phone}`);
    }
    
    if (contactOptions.length > 0) {
        alert('Contact Seller:\n' + contactOptions.join('\n'));
    } else {
        alert('Contact information not available.');
    }
}
</script>

<!-- Include Chat JavaScript -->
<script src="js/chat.js"></script>

<style>
/* Breadcrumb Styles */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
}

.breadcrumb-item a {
    color: #82c408;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #72ac07;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6b7280;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #9ca3af;
}

.seller-profile-banner {
    background: linear-gradient(135deg, #82c408 0%, #72ac07 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 16px;
}

.profile-info {
    display: flex;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.profile-image img {
    width: 140px;
    height: 150px;
    object-fit: cover;
    border: 4px solid white;
}

.profile-details {
    margin-left: 2rem;
    flex: 1;
}

.seller-name {
    margin-bottom: 0.5rem;
    font-size: 2rem;
    color: white;
    font-weight: 700;
}

.seller-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.seller-actions {
    display: flex;
    gap: 1rem;
}

.message-seller-btn, .contact-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 2px solid white;
}

.message-seller-btn {
    background: white;
    color: #82c408;
}

.message-seller-btn:hover {
    background: #f8f9fa;
    color: #72ac07;
    transform: translateY(-2px);
}

.contact-btn {
    background: transparent;
    color: white;
}

.contact-btn:hover {
    background: white;
    color: #82c408;
    transform: translateY(-2px);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
}

.section-header h2 {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.filters {
    display: flex;
    gap: 1rem;
}

.filters select {
    min-width: 180px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    background: white;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.featured-badge, .out-of-stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
    color: white;
}

.featured-badge {
    background: #82c408;
    color: white;
}

.out-of-stock-badge {
    background: #dc3545;
}

.product-info {
    padding: 1.5rem;
}

.product-name {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #1f2937;
    font-weight: 600;
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #059669;
    margin-bottom: 0.5rem;
}

.product-category {
    display: inline-block;
    background: #f3f4f6;
    color: #6b7280;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.product-description {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.product-stock {
    margin-bottom: 1rem;
}

.product-actions {
    display: flex;
    gap: 0.75rem;
}

.view-product-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    background: #82c408;
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.view-product-btn:hover:not(:disabled) {
    background: #72ac07;
    transform: translateY(-1px);
}

.view-product-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.inquire-btn {
    padding: 0.75rem;
    border-radius: 8px;
    border: 2px solid #82c408;
    background: white;
    color: #82c408;
    width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.inquire-btn:hover {
    background: #82c408;
    color: white;
    transform: translateY(-1px);
}

.no-products {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: #f9fafb;
    border-radius: 12px;
    margin: 2rem 0;
}

.pagination-container {
    margin-top: 3rem;
}

/* Theme color updates for pagination */
.page-link {
    background-color: white;
    border-color: #d1d5db;
    color: #6b7280;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin: 0 2px;
    transition: all 0.3s ease;
}

.page-link:hover {
    background-color: #82c408;
    border-color: #82c408;
    color: white;
}

.page-item.active .page-link {
    background-color: #82c408;
    border-color: #82c408;
    color: white;
}

.page-item.disabled .page-link {
    background-color: #f3f4f6;
    border-color: #e5e7eb;
    color: #9ca3af;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .profile-info {
        padding: 0 1rem;
    }
    
    .seller-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .seller-actions {
        align-self: stretch;
    }
}

@media (max-width: 768px) {
    .profile-info {
        flex-direction: column;
        text-align: center;
        padding: 0 1rem;
    }
    
    .profile-details {
        margin-left: 0;
        margin-top: 1rem;
    }
    
    .seller-meta {
        align-items: center;
    }
    
    .seller-actions {
        width: 100%;
        justify-content: center;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .filters {
        width: 100%;
        flex-direction: column;
    }
    
    .filters select {
        min-width: auto;
        width: 100%;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .seller-name {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .seller-profile-banner {
        padding: 1.5rem 0;
        margin-bottom: 1.5rem;
    }
    
    .profile-image img {
        width: 80px;
        height: 80px;
    }
    
    .seller-name {
        font-size: 1.25rem;
    }
    
    .section-header h2 {
        font-size: 1.5rem;
    }
    
    .product-card {
        margin: 0 0.5rem;
    }
    
    .seller-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .message-seller-btn, .contact-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script src="../customer/js/index.js"></script>

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/lib/easing/easing.min.js"></script>
<script src="../assets/lib/waypoints/waypoints.min.js"></script>
<script src="../assets/lib/lightbox/js/lightbox.min.js"></script>
<script src="../assets/lib/owlcarousel/owl.carousel.min.js"></script>

<!-- Template Javascript -->
<script src="../assets/js/main.js"></script>
</body>
</html>