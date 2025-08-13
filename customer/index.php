<?php
require_once 'header.php';
require_once 'fetch_products.php';

// Fetch products for display
$products = fetchProducts(12);
$categories = fetchCategories();
$featured_products = getFeaturedProducts(6);
$popular_sellers = getSellers(4);
$most_viewed_products = getMostViewedProducts(4); // Get most viewed products

// Fetch announcements targeted to customers or all users
try {
    $stmt = $pdo->prepare(
        "SELECT title, content, priority, created_at, expiry_date, is_pinned 
         FROM announcements 
         WHERE (target_audience = 'customers' OR target_audience = 'all') 
           AND is_active = 1 
           AND (expiry_date IS NULL OR expiry_date > NOW()) 
         ORDER BY is_pinned DESC, created_at DESC"
    );
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching announcements: " . $e->getMessage());
    $announcements = [];
}
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Main Container with Sidebar -->
    <div class="main-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="content-area">
            <!-- Adjusted Price Ticker Bar with PHP Peso Sign -->
            <div class="price-ticker-wrapper">
                <div class="price-ticker">
                    <div class="ticker-content" id="priceTickerContent">
                        <!-- Prices will be dynamically loaded here -->
                    </div>
                </div>
            </div>

            <!-- Results Info and Sort --> 
            <div class="results-info">
                <div class="results-count">
                    <span id="resultsCount">Showing <?php echo count($products); ?> products</span>
                </div>
                <div class="right-controls">
                    <div class="search-box">
                        <input type="text" id="productSearch" placeholder="Search products...">
                        <button type="button" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="sort-options">
                        <label for="sortBy">Sort by:</label>
                        <select id="sortBy" class="sort-select">
                            <option value="relevance">Relevance</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="newest">Newest</option>
                            <option value="most_viewed">Most Viewed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Include existing styles -->
            

        
            <!-- Main Grid -->
            <div class="main-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Popular Products -->
                    <section class="popular-products">
                        <div class="section-header">
                            <h2>All Products</h2>
                            <a href="#" class="view-more">View More</a>
                        </div>

                        <div class="products-grid" id="productsGrid">
                            <?php if (empty($products)): ?>
                                <div class="no-products">
                                    <div class="no-products-content">
                                        <i class="fas fa-shopping-basket"></i>
                                        <h3>No products available</h3>
                                        <p>Check back later for fresh products!</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                                        <div class="product-image">
                                            <img src="<?php echo $product['image_url']; ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop'">
                                            <?php if ($product['is_featured']): ?>
                                                <div class="featured-badge">Featured</div>
                                            <?php endif; ?>
                                            <?php if ($product['view_count'] > 0): ?>
                                                <div class="view-count-badge"><?php echo $product['view_count']; ?> views</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info">
                                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                            <div class="stall-name">
                                                <?php echo htmlspecialchars($product['seller_full_name']); ?>
                                            </div>
                                            <div class="product-description">
                                                <?php
                                                $description = isset($product['description']) ? $product['description'] : '';
                                                $short_desc = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                                                echo htmlspecialchars($short_desc);
                                                ?>
                                            </div>
                                            <div class="product-footer">
                                                <div class="price-section">
                                                    <span class="price"><?php echo $product['formatted_price']; ?></span>
                                                </div>
                                                <!-- REMOVED CHAT BUTTON - Only View Details button remains -->
                                                <div class="product-actions">
                                                    <button class="view-product-btn"
                                                        onclick="viewProduct(<?php echo $product['id']; ?>)"
                                                        title="View product">
                                                        <i class="fas fa-solid fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- All Sellers -->
                    <section class="all-sellers">
                        <div class="section-header">
                            <h2>Our Sellers</h2>
                            <a href="#" class="view-more">View All Sellers</a>
                        </div>

                        <div class="sellers-grid">
                            <?php if (empty($popular_sellers)): ?>
                                <div class="no-sellers">
                                    <p>No sellers available at the moment.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($popular_sellers as $seller): ?>
                                    <div class="seller-card">
                                        <div class="seller-banner"></div>
                                        <div class="seller-info">
                                            <div class="seller-logo">
                                                <img src="<?php echo $seller['profile_image_url']; ?>" alt="Seller Logo">
                                            </div>
                                            <h3><?php echo htmlspecialchars($seller['full_name']); ?></h3>
                                            <p><?php echo $seller['product_count']; ?> products</p>
                                            <div class="seller-actions">
                                                <button class="btn visit-store-btn"
                                                    onclick="viewSellerProducts(<?php echo $seller['id']; ?>)">Visit Store</button>
                                                <!-- REMOVED CHAT BUTTON FROM SELLER CARDS TOO -->
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>


                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <section class="last-order">
                        <h2>Most Viewed</h2>

                        <div class="order-items">
                            <?php if (empty($most_viewed_products)): ?>
                                <div class="no-products-message">
                                    <i class="fas fa-eye"></i>
                                    <p>No viewed products yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($most_viewed_products as $index => $product): ?>
                                    <div class="order-item" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                        <div class="item-image">
                                            <img src="<?php echo $product['image_url']; ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=60&h=60&fit=crop'">
                                        </div>
                                        <div class="item-info">
                                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <p><?php echo htmlspecialchars($product['seller_full_name']); ?></p>
                                            <div class="view-count-badge">
                                                <?php echo $product['view_count']; ?> views
                                            </div>
                                        </div>
                                        <div class="item-price"><?php echo $product['formatted_price']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($most_viewed_products)): ?>
                        <div class="order-navigation">
                            <?php for ($i = 0; $i < min(3, count($most_viewed_products)); $i++): ?>
                                <button class="nav-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></button>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </section>

                    <!-- Announcements Section -->
                    <section class="announcements-section">
                        <h2>Announcements</h2>
                        
                        <div class="announcements-container">
                            <?php if (empty($announcements)): ?>
                                <div class="no-announcements-message">
                                    <i class="fas fa-megaphone"></i>
                                    <p>No announcements at the moment</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement-item <?php echo $announcement['is_pinned'] ? 'pinned' : ''; ?>">
                                        <div class="announcement-header">
                                            <h4 class="announcement-title">
                                                <?php if ($announcement['is_pinned']): ?>
                                                    <i class="fas fa-thumbtack pinned-icon"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($announcement['title']); ?>
                                            </h4>
                                            <span class="announcement-priority priority-<?php echo $announcement['priority']; ?>">
                                                <?php echo ucfirst($announcement['priority']); ?>
                                            </span>
                                        </div>
                                        <div class="announcement-content">
                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                        </div>
                                        <div class="announcement-footer">
                                            <small class="announcement-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                                <?php if ($announcement['expiry_date']): ?>
                                                    | Expires: <?php echo date('M j, Y', strtotime($announcement['expiry_date'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fetch and update price ticker with product images
function fetchPriceTicker() {
    fetch('api/price_ticker.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tickerContent = document.getElementById('priceTickerContent');
                tickerContent.innerHTML = '';

                data.data.forEach(item => {
                    const tickerItem = document.createElement('div');
                    tickerItem.className = 'ticker-item';

                    const productImage = document.createElement('img');
                    productImage.src = item.image_url;
                    productImage.alt = item.name;
                    productImage.className = 'ticker-product-image';

                    const icon = document.createElement('img');
                    if (item.change === 'up') {
                        icon.src = '../assets/img/up-arrow.png'; // Local green arrow
                    } else if (item.change === 'down') {
                        icon.src = '../assets/img/down-arrow.png'; // Local red arrow
                    } else {
                        icon.src = '../assets/img/no-change.png'; // Local gray dash
                    }

                    const text = document.createTextNode(`${item.name}: ₱${item.price}`);

                    tickerItem.appendChild(productImage);
                    tickerItem.appendChild(icon);
                    tickerItem.appendChild(text);
                    tickerContent.appendChild(tickerItem);
                });
            }
        })
        .catch(error => console.error('Error fetching price ticker:', error));
}

// Refresh ticker every 30 seconds
setInterval(fetchPriceTicker, 30000);
fetchPriceTicker();

// Update the ticker animation for a smooth upward transition
function updateTickerAnimation() {
    const tickerContent = document.getElementById('priceTickerContent');
    const firstChild = tickerContent.firstElementChild;
    const tickerHeight = firstChild.offsetHeight;

    tickerContent.style.transition = 'transform 1s cubic-bezier(0.25, 0.1, 0.25, 1)'; // Smooth easing
    tickerContent.style.transform = `translateY(-${tickerHeight}px)`;

    // Reset the position after the animation completes
    setTimeout(() => {
        tickerContent.style.transition = 'none';
        tickerContent.style.transform = 'translateY(0)';
        tickerContent.appendChild(firstChild);
    }, 1000); // Match the transition duration
}

// Ensure the animation runs every 3 seconds
setInterval(updateTickerAnimation, 3000);
</script>

<script>
// Enhanced JavaScript for view tracking and most viewed functionality

// Track product view when clicking on a product
function viewProduct(productId) {
    // Track the view via AJAX
    fetch('track_view.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            action: 'track_view'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('View tracked successfully');
            // Optionally update view count in UI
            updateViewCount(productId, data.new_view_count);
        }
    })
    .catch(error => {
        console.error('Error tracking view:', error);
    });
    
    // Redirect to product page
    window.location.href = `view_product.php?id=${productId}`;
}

// Function to update view count in UI
function updateViewCount(productId, newCount) {
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (productCard) {
        const viewBadge = productCard.querySelector('.view-count-badge');
        if (viewBadge) {
            viewBadge.textContent = `${newCount} views`;
        } else if (newCount > 0) {
            // Create view count badge if it doesn't exist
            const productImage = productCard.querySelector('.product-image');
            const badge = document.createElement('div');
            badge.className = 'view-count-badge';
            badge.textContent = `${newCount} views`;
            productImage.appendChild(badge);
        }
    }
}

// View seller products function
function viewSellerProducts(sellerId) {
    window.location.href = `view_stall.php?seller_id=${sellerId}`;
}

// Enhanced sort functionality to include most viewed
document.getElementById('sortBy').addEventListener('change', function() {
    const sortValue = this.value;
    
    // Show loading state
    const productsGrid = document.getElementById('productsGrid');
    productsGrid.innerHTML = '<div class="loading">Loading products...</div>';
    
    // Fetch sorted products
    fetch('ajax_sort_products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            sort_by: sortValue,
            search: document.getElementById('productSearch').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProductsGrid(data.products);
            document.getElementById('resultsCount').textContent = `Showing ${data.products.length} products`;
        }
    })
    .catch(error => {
        console.error('Error sorting products:', error);
        productsGrid.innerHTML = '<div class="error">Error loading products</div>';
    });
});

// Function to update products grid - REMOVED CHAT BUTTON FROM GENERATED HTML
function updateProductsGrid(products) {
    const productsGrid = document.getElementById('productsGrid');
    
    if (products.length === 0) {
        productsGrid.innerHTML = `
            <div class="no-products">
                <div class="no-products-content">
                    <i class="fas fa-shopping-basket"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filters</p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    products.forEach(product => {
        html += `
            <div class="product-card" data-product-id="${product.id}">
                <div class="product-image">
                    <img src="${product.image_url}" 
                         alt="${product.name}"
                         onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop'">
                    ${product.is_featured ? '<div class="featured-badge">Featured</div>' : ''}
                    ${product.view_count > 0 ? `<div class="view-count-badge">${product.view_count} views</div>` : ''}
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.name}</h3>
                    <div class="stall-name">${product.seller_full_name}</div>
                    <div class="product-description">${product.short_description || ''}</div>
                    <div class="product-footer">
                        <div class="price-section">
                            <span class="price">${product.formatted_price}</span>
                        </div>
                        <div class="product-actions">
                            <button class="view-product-btn" onclick="viewProduct(${product.id})" title="View product">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    productsGrid.innerHTML = html;
}

// Navigation dots for most viewed section
document.querySelectorAll('.nav-dot').forEach(dot => {
    dot.addEventListener('click', function() {
        const slideIndex = this.getAttribute('data-slide');
        
        // Remove active class from all dots
        document.querySelectorAll('.nav-dot').forEach(d => d.classList.remove('active'));
        
        // Add active class to clicked dot
        this.classList.add('active');
        
        console.log(`Navigating to slide ${slideIndex}`);
    });
});

// Auto-refresh most viewed section periodically
setInterval(function() {
    fetch('ajax_most_viewed.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.products) {
            updateMostViewedSection(data.products);
        }
    })
    .catch(error => {
        console.error('Error refreshing most viewed:', error);
    });
}, 60000); // Refresh every minute

function updateMostViewedSection(products) {
    const orderItems = document.querySelector('.order-items');
    
    if (products.length === 0) {
        orderItems.innerHTML = `
            <div class="no-products-message">
                <i class="fas fa-eye"></i>
                <p>No viewed products yet</p>
            </div>
        `;
        return;
    }
    
    
}
</script>

<script src="../customer/js/index.js"></script>
<script src="../customer/js/customer.js"></script>

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