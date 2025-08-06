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
?>

<div class="main-content">
    <div class="stall-container">
        <!-- Seller Profile Banner -->
        <div class="seller-profile-banner">
            <div class="profile-info">
                <div class="profile-image">
                    <img src="<?php echo !empty($seller['profile_image']) ? '../' . htmlspecialchars($seller['profile_image']) : '../assets/img/avatar.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?>" 
                         class="rounded-circle">
                </div>
                <div class="profile-details">
                    <h1 class="seller-name">
                        <?php echo htmlspecialchars($seller['business_name'] ?: $seller['first_name'] . ' ' . $seller['last_name']); ?>
                    </h1>
                    <div class="seller-meta">
                        <div class="location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($seller['stall_number'] ? 'Stall ' . $seller['stall_number'] . ' - ' . $seller['section'] : 'Location TBA'); ?></span>
                        </div>
                        <button class="btn btn-light message-seller-btn" onclick="contactSeller('<?php echo htmlspecialchars($seller['email']); ?>', '<?php echo htmlspecialchars($seller['phone']); ?>')">
                            <i class="fas fa-envelope me-2"></i>Message Seller
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="products-section container py-5">
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
                                <button class="btn btn-primary view-product-btn" 
                                        onclick="viewProduct(<?php echo $product['id']; ?>)"
                                        <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <?php echo $product['stock_quantity'] > 0 ? 'View Product' : 'Out of Stock'; ?>
                                </button>
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

<script>
function filterProducts(value, type) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set(type, value);
    urlParams.set('page', '1'); // Reset to first page when filtering
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

function viewProduct(productId) {
    window.location.href = 'product-detail.php?id=' + productId;
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

<style>
.seller-profile-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.profile-info {
    display: flex;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.profile-image img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border: 4px solid white;
}

.profile-details {
    margin-left: 2rem;
}

.seller-name {
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.seller-meta {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.filters {
    display: flex;
    gap: 1rem;
}

.filters select {
    min-width: 180px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
    background: #ffc107;
    color: #000;
}

.out-of-stock-badge {
    background: #dc3545;
}

.product-info {
    padding: 1rem;
}

.product-name {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.product-price {
    font-size: 1.2rem;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 0.5rem;
}

.product-category {
    display: inline-block;
    background: #f8f9fa;
    color: #6c757d;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
}

.product-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.product-stock {
    margin-bottom: 1rem;
}

.no-products {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

@media (max-width: 768px) {
    .profile-info {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-details {
        margin-left: 0;
        margin-top: 1rem;
    }
    
    .seller-meta {
        flex-direction: column;
        gap: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .filters {
        width: 100%;
        flex-direction: column;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
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