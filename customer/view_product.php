<?php
require_once 'header.php';
require_once '../includes/db_connect.php'; // Adjust path as needed

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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
function formatPrice($price)
{
    return '₱' . number_format($price, 2);
}

// Helper function to get seller display name
function getSellerDisplayName($product)
{
    if (!empty($product['business_name'])) {
        return $product['business_name'];
    } elseif (!empty($product['first_name']) && !empty($product['last_name'])) {
        return $product['first_name'] . ' ' . $product['last_name'];
    } else {
        return $product['seller_username'];
    }
}

// Helper function to get product image
function getProductImage($image_path, $default = '../assets/img/fruite-item-1.jpg')
{
    if (!empty($image_path) && file_exists('../' . $image_path)) {
        return '../' . $image_path;
    }
    return $default;
}
?>

<div class="main-content">
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Market</a></li>
                <li class="breadcrumb-item"><a href="index.php">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?>
                </li>
            </ol>
        </nav>

        <div class="product-details-container">
            <div class="product-images-section">
                <div class="main-image-container">
                    <?php
                    $main_image = !empty($product['primary_image']) ? '../' . $product['primary_image'] : '../assets/img/fruite-item-1.jpg';
                    ?>
                    <img src="<?php echo htmlspecialchars($main_image); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image"
                        class="main-product-image">
                </div>

                <?php if (count($product_images) > 1): ?>
                        <div class="image-thumbnails">
                            <?php foreach ($product_images as $img): ?>
                                    <div class="thumbnail-container <?php echo $img['is_primary'] ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars(getProductImage($img['image_path'])); ?>"
                                            alt="Product Image" class="thumbnail-img"
                                            onclick="changeMainImage(this.src, this.parentElement)">
                                    </div>
                            <?php endforeach; ?>
                        </div>
                <?php endif; ?>
            </div>

            <div class="product-info-section">
                <div class="product-header">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-category">
                        <?php if (!empty($product['category_icon'])): ?>
                                <span class="category-icon"><?php echo $product['category_icon']; ?></span>
                        <?php endif; ?>
                        <span
                            class="category-name"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                    </div>
                </div>

                <div class="product-price-section">
                    <div class="price-display">
                        <span class="price-amount"><?php echo formatPrice($product['price']); ?></span>
                        <?php if (!empty($product['weight']) && $product['weight'] > 0): ?>
                                <span class="price-unit">per <?php echo $product['weight']; ?>g</span>
                        <?php endif; ?>
                    </div>
                    <div
                        class="stock-status <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <i
                            class="fas <?php echo $product['stock_quantity'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <span>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                    <?php echo $product['stock_quantity']; ?> items available
                            <?php else: ?>
                                    Out of stock
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($product['description'])): ?>
                        <div class="product-description">
                            <h3>Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                <?php endif; ?>

                <div class="product-actions">
                       <?php if ($product['stock_quantity'] > 0): ?>
                            <button class="btn btn-outline-primary message-btn"
                                onclick="startChatWithSeller(<?php echo $product['seller_id']; ?>, <?php echo $product['id']; ?>)">
                                <i class="fas fa-envelope"></i>
                                <span>Inquire Vendor</span>
                            </button>
                    <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-times"></i>
                                <span>Out of Stock</span>
                            </button>
                    <?php endif; ?>
                </div>

                <div class="product-details">
                    <h3>Product Details</h3>
                    <div class="details-grid">
                        <?php if (!empty($product['weight']) && $product['weight'] > 0): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Weight:</span>
                                    <span class="detail-value"><?php echo $product['weight']; ?> grams</span>
                                </div>
                        <?php endif; ?>

                        <div class="detail-item">
                            <span class="detail-label">Condition:</span>
                            <span class="detail-value">Fresh</span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value">
                                <?php if (!empty($product['business_phone'])): ?>
                                        <i class="fas fa-phone text-success"></i>
                                        <?php echo htmlspecialchars($product['business_phone']); ?>
                                <?php else: ?>
                                        Available via message
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="store-section">
            <h2>About the Seller</h2>
            <div class="store-card">
                <div class="store-header">
                    <div class="store-avatar">
                        <?php
                        $profile_image = !empty($product['profile_image']) ? '../' . $product['profile_image'] : '../assets/img/avatar.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Store Avatar">
                    </div>
                    <div class="store-info">
                        <h3 class="store-name"><?php echo htmlspecialchars(getSellerDisplayName($product)); ?></h3>
                        <div class="store-location">
                            <?php if (!empty($product['stall_number'])): ?>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Stall <?php echo htmlspecialchars($product['stall_number']); ?>
                                        (<?php echo htmlspecialchars($product['stall_section']); ?>)</span>
                            <?php else: ?>
                                    <i class="fas fa-store"></i>
                                    <span>Marketplace Vendor</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="store-actions">
                    <?php if (!empty($product['stall_number'])): ?>
                            <a href="stall_view.php?stall=<?php echo urlencode($product['stall_number']); ?>"
                                class="btn btn-outline-success">
                                <i class="fas fa-store"></i>
                                View Stall
                            </a>
                    <?php endif; ?>

                    <?php if (!empty($product['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($product['facebook_url']); ?>" target="_blank"
                                class="btn btn-outline-primary">
                                <i class="fab fa-facebook"></i>
                                Facebook
                            </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($similar_products)): ?>
                <div class="similar-products-section">
                    <h2>Similar Products</h2>
                    <div class="similar-products-grid">
                        <?php foreach ($similar_products as $similar): ?>
                                <div class="similar-product-card">
                                    <div class="product-image">
                                        <img src="<?php echo htmlspecialchars(getProductImage($similar['image_path'])); ?>"
                                            alt="<?php echo htmlspecialchars($similar['name']); ?>">
                                    </div>
                                    <div class="product-content">
                                        <h3 class="product-name"><?php echo htmlspecialchars($similar['name']); ?></h3>
                                        <div class="product-price"><?php echo formatPrice($similar['price']); ?></div>
                                        <div class="seller-info">
                                            <img src="../assets/img/avatar.jpg" alt="Seller" class="seller-avatar">
                                            <span class="seller-name">
                                                <?php echo htmlspecialchars(!empty($similar['business_name']) ? $similar['business_name'] : $similar['first_name'] . ' ' . $similar['last_name']); ?>
                                            </span>
                                        </div>
                                        <a href="view_product.php?id=<?php echo $similar['id']; ?>"
                                            class="btn btn-outline-primary w-100">
                                            View Product
                                        </a>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                    </div>
                </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="css/chat.css">

<script>
    // Function to change main product image
    function changeMainImage(src, thumbnailElement) {
        document.getElementById('main-product-image').src = src;

        // Update thumbnail active state
        document.querySelectorAll('.thumbnail-container').forEach(container => {
            container.classList.remove('active');
        });
        thumbnailElement.classList.add('active');
    }

    // Function to order product
    function orderProduct(productId) {
        // Implement order functionality
        alert('Order functionality for product ID: ' + productId);
        // You can redirect to order form
        // window.location.href = 'order.php?product_id=' + productId;
    }
</script>

<script src="js/chat.js"></script>
<link rel="stylesheet" href="css/view_product.css">


<script>
    // Function to change main product image
    function changeMainImage(src, thumbnailElement) {
        document.getElementById('main-product-image').src = src;

        // Update thumbnail active state
        document.querySelectorAll('.thumbnail-container').forEach(container => {
            container.classList.remove('active');
        });
        thumbnailElement.classList.add('active');
    }

    // Function to order product
    function orderProduct(productId) {
        // Implement order functionality
        alert('Order functionality for product ID: ' + productId);
        // You can redirect to order form
        // window.location.href = 'order.php?product_id=' + productId;
    }

    // Track product view
    document.addEventListener('DOMContentLoaded', function() {
        // Get product ID from URL or PHP variable
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id') || <?php echo $product_id; ?>;
        
        if (productId) {
            trackProductView(productId);
        }
    });

    // Function to track product view via AJAX
    function trackProductView(productId) {
        console.log('Tracking view for product ID:', productId);
        
        fetch('track_view.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_id: parseInt(productId),
                action: 'track_view'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('View tracking response:', data);
            
            if (data.success) {
                console.log('View tracked successfully. New count:', data.new_view_count);
                
                // Update view count display if there's an element for it
                const viewCountElement = document.querySelector('.view-count');
                if (viewCountElement && data.new_view_count) {
                    viewCountElement.textContent = data.new_view_count + ' views';
                }
            } else {
                console.error('Failed to track view:', data.message);
                console.error('Debug info:', data.debug);
            }
        })
        .catch(error => {
            console.error('Error tracking view:', error);
        });
    }
</script>

<script src="js/chat.js"></script>
<script src="../customer/js/index.js"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/lib/easing/easing.min.js"></script>
<script src="../assets/lib/waypoints/waypoints.min.js"></script>
<script src="../assets/lib/lightbox/js/lightbox.min.js"></script>
<script src="../assets/lib/owlcarousel/owl.carousel.min.js"></script>

<script src="../assets/js/main.js"></script>
</body>

</html>