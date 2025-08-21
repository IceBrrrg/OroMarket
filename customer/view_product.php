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
        LIMIT 5
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
                    $main_image = !empty($product['primary_image']) ? '../' . $product['primary_image'] : 'https://estore.midas.com.my/image/cache/no_image_uploaded-253x190.png';
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

                <div class="price-history-section mt-4">
                    <h3><i class="fas fa-chart-line"></i> Price History & Analytics</h3>

                    <div class="price-stats-grid">
                        <div class="price-stat-card">
                            <div class="stat-icon"><i class="fas fa-arrow-trend-up text-success"></i></div>
                            <div class="stat-info">
                                <span class="stat-value" id="highest-price">-</span>
                                <span class="stat-label">Highest Price</span>
                            </div>
                        </div>

                        <div class="price-stat-card">
                            <div class="stat-icon"><i class="fas fa-arrow-trend-down text-danger"></i></div>
                            <div class="stat-info">
                                <span class="stat-value" id="lowest-price">-</span>
                                <span class="stat-label">Lowest Price</span>
                            </div>
                        </div>

                        <div class="price-stat-card">
                            <div class="stat-icon"><i class="fas fa-calculator text-info"></i></div>
                            <div class="stat-info">
                                <span class="stat-value" id="average-price">-</span>
                                <span class="stat-label">Average Price</span>
                            </div>
                        </div>

                        <div class="price-stat-card">
                            <div class="stat-icon"><i class="fas fa-exchange-alt text-warning"></i></div>
                            <div class="stat-info">
                                <span class="stat-value" id="price-changes">-</span>
                                <span class="stat-label">Price Changes</span>
                            </div>
                        </div>
                    </div>

                    <div class="price-chart-container">
                        <div class="price-period-tabs">
                            <button class="price-period-btn active" data-days="7">7 Days</button>
                            <button class="price-period-btn" data-days="30">30 Days</button>
                            <button class="price-period-btn" data-days="90">90 Days</button>
                        </div>
                        <div style="position: relative; height: 400px; width: 100%;">
                            <canvas id="price-history-chart"></canvas>
                        </div>
                    </div>

                    <div class="price-history-table">
                        <h4>Recent Price Changes</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Old Price</th>
                                        <th>New Price</th>
                                        <th>Change</th>
                                    </tr>
                                </thead>
                                <tbody id="price-history-tbody">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
                            <span>Inquire Seller</span>
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
                                <span class="detail-value"><?php echo $product['weight']; ?> kilograms</span>
                            </div>
                        <?php endif; ?>



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
                    <a href="view_stall.php?seller_id=<?php echo urlencode($product['seller_id']); ?>"
                        class="visit-stall-btn">
                        <i class="fas fa-store"></i>
                        Visit Stall
                    </a>

                    <?php if (!empty($product['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($product['facebook_url']); ?>" target="_blank"
                            class="facebook-btn">
                            <i class="fab fa-facebook"></i>
                            Facebook Page
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($similar_products)): ?>
            <div class="similar-products-section">
                <h2><i class="fas fa-tags"></i> Similar Products</h2>
                <div class="similar-products-grid">
                    <?php foreach ($similar_products as $similar): ?>
                        <a href="view_product.php?id=<?php echo $similar['id']; ?>" class="similar-product-card">
                            <div class="similar-product-image">
                                <img src="<?php echo htmlspecialchars(getProductImage($similar['image_path'])); ?>"
                                    alt="<?php echo htmlspecialchars($similar['name']); ?>">
                            </div>
                            <div class="similar-product-info">
                                <h4><?php echo htmlspecialchars($similar['name']); ?></h4>
                                <div class="similar-product-price"><?php echo formatPrice($similar['price']); ?></div>
                                <p class="similar-product-seller">
                                    <?php echo htmlspecialchars(!empty($similar['business_name']) ? $similar['business_name'] : $similar['first_name'] . ' ' . $similar['last_name']); ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="css/chat.css">
<link rel="stylesheet" href="css/view_product.css">
<link rel="stylesheet" href="css/similar_products.css">
</style>

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

    // Track product view
    document.addEventListener('DOMContentLoaded', function () {
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

<!-- Chart.js for price monitoring charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<!-- Price Monitoring JavaScript -->
<script>
    // price-monitoring.js - Client-side price monitoring functionality

    class PriceMonitor {
        constructor() {
            this.apiBase = 'price_history_api.php';
            this.chartInstances = new Map();
            this.init();
        }

        init() {
            this.addPriceHistorySection();
            this.loadPriceData();
            this.setupEventListeners();
        }

        setupEventListeners() {
            // Price history tab switching
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('price-period-btn')) {
                    this.switchPricePeriod(e.target);
                }
            });
        }

        addPriceHistorySection() {
            // Price history section is already in the HTML, no need to add dynamically
        }

        async loadPriceData() {
            const productId = this.getProductId();
            if (!productId) return;

            try {
                // Load price statistics
                await this.loadPriceStatistics(productId);

                // Load price chart
                await this.loadPriceChart(productId, 30);

                // Load price history table
                await this.loadPriceHistory(productId);

            } catch (error) {
                console.error('Error loading price data:', error);
            }
        }

        async loadPriceStatistics(productId) {
            try {
                const response = await fetch(`${this.apiBase}?action=statistics&product_id=${productId}&period=30`);
                const result = await response.json();

                if (result.success && result.data) {
                    const stats = result.data;
                    document.getElementById('highest-price').textContent = `₱${parseFloat(stats.highest_price || 0).toFixed(2)}`;
                    document.getElementById('lowest-price').textContent = `₱${parseFloat(stats.lowest_price || 0).toFixed(2)}`;
                    document.getElementById('average-price').textContent = `₱${parseFloat(stats.average_price || 0).toFixed(2)}`;
                    document.getElementById('price-changes').textContent = stats.total_changes || 0;
                }
            } catch (error) {
                console.error('Error loading price statistics:', error);
            }
        }

        async loadPriceChart(productId, days = 30) {
            try {
                const response = await fetch(`${this.apiBase}?action=chart_data&product_id=${productId}&days=${days}`);
                const result = await response.json();

                if (result.success && result.data) {
                    this.renderPriceChart(result.data);
                }
            } catch (error) {
                console.error('Error loading price chart:', error);
            }
        }

        renderPriceChart(data) {
            const ctx = document.getElementById('price-history-chart');
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (this.chartInstances.has('priceChart')) {
                this.chartInstances.get('priceChart').destroy();
            }

            // Sort data by date to ensure proper chronological order
            const sortedData = data.sort((a, b) => new Date(a.datetime) - new Date(b.datetime));

            const labels = sortedData.map(item => {
                const date = new Date(item.datetime);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            const prices = sortedData.map(item => parseFloat(item.price));

            // Calculate min and max for better Y-axis scaling
            const minPrice = Math.min(...prices);
            const maxPrice = Math.max(...prices);
            const padding = (maxPrice - minPrice) * 0.1; // 10% padding

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Price (₱)',
                        data: prices,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.2,
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#007bff',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Price History'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            min: Math.max(0, minPrice - padding),
                            max: maxPrice + padding,
                            ticks: {
                                callback: function (value) {
                                    return '₱' + value.toFixed(2);
                                }
                            }
                        },
                        x: {
                            reverse: false // Ensure chronological order
                        }
                    }
                }
            });

            this.chartInstances.set('priceChart', chart);
        }

        async loadPriceHistory(productId) {
            try {
                const response = await fetch(`${this.apiBase}?action=history&product_id=${productId}&days=30`);
                const result = await response.json();

                if (result.success && result.data) {
                    this.renderPriceHistoryTable(result.data);
                }
            } catch (error) {
                console.error('Error loading price history:', error);
            }
        }

        renderPriceHistoryTable(history) {
            const tbody = document.getElementById('price-history-tbody');
            if (!tbody) return;

            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No price changes recorded</td></tr>';
                return;
            }

            const rows = history.map(item => {
                const changeValue = parseFloat(item.new_price) - parseFloat(item.old_price);
                const changePercent = parseFloat(item.percentage_change);
                const changeClass = changeValue > 0 ? 'text-success' : 'text-danger';
                const changeIcon = changeValue > 0 ? '↑' : '↓';

                return `
                <tr>
                    <td>${new Date(item.changed_at).toLocaleDateString()}</td>
                    <td>₱${parseFloat(item.old_price).toFixed(2)}</td>
                    <td>₱${parseFloat(item.new_price).toFixed(2)}</td>
                    <td class="${changeClass}">
                        ${changeIcon} ₱${Math.abs(changeValue).toFixed(2)} 
                        (${Math.abs(changePercent).toFixed(2)}%)
                    </td>
                </tr>
            `;
            }).join('');

            tbody.innerHTML = rows;
        }

        switchPricePeriod(button) {
            // Update active button
            document.querySelectorAll('.price-period-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Reload chart with new period
            const days = parseInt(button.dataset.days);
            const productId = this.getProductId();
            if (productId) {
                this.loadPriceChart(productId, days);
            }
        }

        getProductId() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('id') || null;
        }

        // Method to update price in real-time (if needed)
        async refreshCurrentPrice() {
            const productId = this.getProductId();
            if (!productId) return;

            try {
                const response = await fetch(`get_product.php?id=${productId}`);
                const result = await response.json();

                if (result.success) {
                    const currentPriceElement = document.querySelector('.price-amount');
                    if (currentPriceElement) {
                        currentPriceElement.textContent = `₱${parseFloat(result.data.price).toFixed(2)}`;
                    }

                    // Update price change indicator
                    this.updatePriceChangeIndicator(result.data);
                }
            } catch (error) {
                console.error('Error refreshing price:', error);
            }
        }

        updatePriceChangeIndicator(productData) {
            const priceSection = document.querySelector('.product-price-section');
            if (!priceSection || !productData.previous_price) return;

            // Remove existing indicator
            const existingIndicator = priceSection.querySelector('.price-change-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            if (productData.price_change !== 'no_change') {
                const changePercentage = productData.price_change_percentage || 0;
                const isIncrease = productData.price_change === 'up';

                const indicator = document.createElement('div');
                indicator.className = `price-change-indicator ${isIncrease ? 'price-up' : 'price-down'}`;
                indicator.innerHTML = `
                <i class="fas fa-arrow-${isIncrease ? 'up' : 'down'}"></i>
                <span>${Math.abs(changePercentage).toFixed(2)}%</span>
                <small>vs last price</small>
            `;

                priceSection.appendChild(indicator);
            }
        }
    }

    // Initialize price monitoring when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize price monitor on product pages
        if (document.querySelector('.product-details-container')) {
            window.priceMonitor = new PriceMonitor();
        }
    });
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