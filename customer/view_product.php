<?php
require_once 'header.php';
require_once '../includes/db_connect.php'; // Adjust path as needed

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    // Fetch product details with seller and category information
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            s.id as seller_id,
            s.first_name,
            s.last_name,
            s.username as seller_username,
            s.profile_image,
            s.facebook_url,
            c.name as category_name,
            c.icon as category_icon,
            sa.business_name,
            sa.business_phone,
            st.stall_number,
            st.section as stall_section,
            pi.image_path as primary_image
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
        LEFT JOIN stalls st ON st.current_seller_id = s.id AND st.status = 'occupied'
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.id = ? AND p.is_active = 1 AND s.status = 'approved'
    ");
    
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: index.php');
        exit();
    }
    
    // Fetch all product images
    $stmt = $pdo->prepare("
        SELECT image_path, is_primary, display_order 
        FROM product_images 
        WHERE product_id = ? 
        ORDER BY is_primary DESC, display_order ASC
    ");
    $stmt->execute([$product_id]);
    $product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch similar products from the same category (excluding current product)
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            s.first_name,
            s.last_name,
            sa.business_name,
            pi.image_path,
            c.name as category_name
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? 
        AND p.id != ? 
        AND p.is_active = 1 
        AND s.status = 'approved'
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $similar_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: index.php');
    exit();
}

// Helper function to format price
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

// Helper function to get seller display name
function getSellerDisplayName($product) {
    if (!empty($product['business_name'])) {
        return $product['business_name'];
    } elseif (!empty($product['first_name']) && !empty($product['last_name'])) {
        return $product['first_name'] . ' ' . $product['last_name'];
    } else {
        return $product['seller_username'];
    }
}

// Helper function to get product image
function getProductImage($image_path, $default = '../assets/img/fruite-item-1.jpg') {
    if (!empty($image_path) && file_exists('../' . $image_path)) {
        return '../' . $image_path;
    }
    return $default;
}
?>

<div class="main-content">
    <div class="container py-5">
        <h1 class="mb-4">Product and Store Details</h1>

        <div class="product-store-container">
            <!-- Store Section -->
            <div class="store-section">
                <h2>Store</h2>
                <div class="store-card">
                    <div class="store-logo">
                        <?php 
                        $profile_image = !empty($product['profile_image']) ? '../' . $product['profile_image'] : '../assets/img/avatar.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Store Logo" class="rounded-circle">
                    </div>
                    <div class="store-info">
                        <h3 class="store-name"><?php echo htmlspecialchars(getSellerDisplayName($product)); ?></h3>
                        <p class="store-description">
                            <?php if (!empty($product['stall_number'])): ?>
                                Located at Stall <?php echo htmlspecialchars($product['stall_number']); ?> 
                                (<?php echo htmlspecialchars($product['stall_section']); ?>)
                            <?php else: ?>
                                Marketplace vendor offering quality products
                            <?php endif; ?>
                        </p>
                        <div class="store-rating mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span>(4.5)</span>
                        </div>
                        <?php if (!empty($product['stall_number'])): ?>
                            <a href="stall_view.php?stall=<?php echo urlencode($product['stall_number']); ?>" class="btn btn-outline-success view-stall-btn">View Stall</a>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($product['facebook_url']); ?>" target="_blank" class="btn btn-outline-primary ms-2">
                                <i class="fab fa-facebook me-1"></i>Facebook
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Section -->
            <div class="product-section">
                <h2>Product</h2>
                <div class="product-details-card">
                    <div class="product-image">
                        <?php 
                        $main_image = !empty($product['primary_image']) ? '../' . $product['primary_image'] : '../assets/img/fruite-item-1.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <?php if (count($product_images) > 1): ?>
                        <div class="product-thumbnails mt-3">
                            <?php foreach ($product_images as $img): ?>
                                <img src="<?php echo htmlspecialchars(getProductImage($img['image_path'])); ?>" 
                                     alt="Product Image" 
                                     class="thumbnail-img <?php echo $img['is_primary'] ? 'active' : ''; ?>"
                                     onclick="changeMainImage(this.src)">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price mb-3"><?php echo formatPrice($product['price']); ?></div>
                        
                        <?php if (!empty($product['description'])): ?>
                        <div class="product-description mb-3">
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-meta mb-4">
                            <?php if (!empty($product['category_name'])): ?>
                            <div class="meta-item">
                                <span class="label">Category:</span>
                                <span class="value">
                                    <?php if (!empty($product['category_icon'])): ?>
                                        <?php echo $product['category_icon']; ?>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <span class="label">Stock:</span>
                                <span class="value <?php echo $product['stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <?php echo $product['stock_quantity']; ?> items available
                                    <?php else: ?>
                                        Out of stock
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($product['weight']) && $product['weight'] > 0): ?>
                            <div class="meta-item">
                                <span class="label">Weight:</span>
                                <span class="value"><?php echo $product['weight']; ?> grams</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <span class="label">Condition:</span>
                                <span class="value">Fresh</span>
                            </div>
                            
                            <div class="meta-item">
                                <span class="label">Contact:</span>
                                <span class="value">
                                    <?php if (!empty($product['business_phone'])): ?>
                                        <i class="fas fa-phone text-success me-1"></i>
                                        <?php echo htmlspecialchars($product['business_phone']); ?>
                                    <?php else: ?>
                                        Available via message
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button class="btn btn-success message-vendor-btn me-2" onclick="messageVendor(<?php echo $product['seller_id']; ?>)">
                                <i class="fas fa-envelope me-2"></i>Message Vendor
                            </button>
                            <button class="btn btn-primary order-btn" onclick="orderProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-shopping-cart me-2"></i>Order Now
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-times me-2"></i>Out of Stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Products Section -->
        <?php if (!empty($similar_products)): ?>
        <div class="related-products mt-5">
            <h2 class="mb-4">Similar Products from Other Sellers</h2>
            <div class="related-products-grid">
                <?php foreach ($similar_products as $similar): ?>
                <div class="related-product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars(getProductImage($similar['image_path'])); ?>" alt="<?php echo htmlspecialchars($similar['name']); ?>">
                    </div>
                    <div class="product-details">
                        <h3 class="product-name"><?php echo htmlspecialchars($similar['name']); ?></h3>
                        <div class="product-price"><?php echo formatPrice($similar['price']); ?></div>
                        <div class="seller-info">
                            <img src="../assets/img/avatar.jpg" alt="Seller" class="seller-avatar">
                            <span class="seller-name">
                                <?php echo htmlspecialchars(!empty($similar['business_name']) ? $similar['business_name'] : $similar['first_name'] . ' ' . $similar['last_name']); ?>
                            </span>
                        </div>
                        <a href="product_details.php?id=<?php echo $similar['id']; ?>" class="btn custom-btn w-100">View Product</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to change main product image
function changeMainImage(src) {
    document.querySelector('.product-image img:first-child').src = src;
    
    // Update thumbnail active state
    document.querySelectorAll('.thumbnail-img').forEach(img => {
        img.classList.remove('active');
    });
    event.target.classList.add('active');
}

// Function to message vendor
function messageVendor(sellerId) {
    // Implement messaging functionality
    alert('Messaging feature will redirect to contact form for seller ID: ' + sellerId);
    // You can redirect to a contact form or open a modal
    // window.location.href = 'contact_seller.php?seller_id=' + sellerId;
}

// Function to order product
function orderProduct(productId) {
    // Implement order functionality
    alert('Order functionality for product ID: ' + productId);
    // You can redirect to order form
    // window.location.href = 'order.php?product_id=' + productId;
}
</script>

<style>
.product-store-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.store-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.store-logo img {
    width: 80px;
    height: 80px;
    object-fit: cover;
}

.store-info {
    margin-left: 1rem;
    flex: 1;
}

.store-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.store-rating {
    color: #ffa500;
}

.product-details-card {
    display: flex;
    gap: 2rem;
    padding: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.product-image {
    flex: 1;
}

.product-image img:first-child {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.product-thumbnails {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.thumbnail-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
}

.thumbnail-img.active,
.thumbnail-img:hover {
    border-color: #28a745;
}

.product-info {
    flex: 1.5;
}

.product-name {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.product-price {
    font-size: 1.8rem;
    color: #28a745;
    font-weight: 700;
}

.product-meta {
    display: grid;
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.meta-item .label {
    font-weight: 600;
    color: #666;
}

.meta-item .value {
    color: #333;
}

.related-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.related-product-card {
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.related-product-card:hover {
    transform: translateY(-5px);
}

.related-product-card .product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.related-product-card .product-details {
    padding: 1rem;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1rem 0;
}

.seller-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

.custom-btn {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.custom-btn:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

@media (max-width: 768px) {
    .product-store-container {
        grid-template-columns: 1fr;
    }
    
    .product-details-card {
        flex-direction: column;
    }
    
    .store-card {
        flex-direction: column;
        text-align: center;
    }
    
    .store-info {
        margin-left: 0;
        margin-top: 1rem;
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