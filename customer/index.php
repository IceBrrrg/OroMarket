<?php
require_once 'header.php';
require_once 'fetch_products.php';

// Fetch products for display
$products = fetchProducts(12);
$categories = fetchCategories();
$featured_products = getFeaturedProducts(6);
$all_sellers = getSellers(1000); // Fetch enough sellers for pagination

// Pagination logic for sellers
$sellers_per_page = 6;
$seller_page = isset($_GET['seller_page']) ? max(1, intval($_GET['seller_page'])) : 1;
$total_sellers = count($all_sellers);
$total_seller_pages = ceil($total_sellers / $sellers_per_page);
$start_index = ($seller_page - 1) * $sellers_per_page;
$popular_sellers = array_slice($all_sellers, $start_index, $sellers_per_page);
$most_viewed_products = getMostViewedProducts(4); // Get most viewed products

// Fetch announcements targeted to customers or all users
try {
    $stmt = $pdo->prepare(
        "SELECT title, content, target_audience, created_at, expiry_date, is_pinned 
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

            <!-- Results Info  -->
            <div class="results-info">
                <div class="results-count">
                    <span id="resultsCount">Showing <?php echo count($products); ?> products</span>
                </div>
                <div class="right-controls">
                    <div class="search-box">
                        <input type="text" id="productSearch" placeholder="Search the market...">
                        <button type="button" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
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
                                                onerror="this.src='https://estore.midas.com.my/image/cache/no_image_uploaded-253x190.png'">
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

                    <!-- All Sellers Section - Updated HTML Structure -->
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
                                        <!-- Green banner at top -->
                                        <div class="seller-banner"></div>

                                        <!-- Profile image circle overlapping the banner -->
                                        <div class="seller-logo">
                                            <img src="<?php
                                            if (!empty($seller['profile_image'])) {
                                                echo '../uploads/profile_images/' . htmlspecialchars($seller['profile_image']);
                                            } else {
                                                echo '../assets/img/avatar.jpg';
                                            }
                                            ?>" alt="<?php echo htmlspecialchars($seller['full_name']); ?>"
                                                onerror="this.src='../assets/img/avatar.jpg';">
                                        </div>

                                        <!-- White content box -->
                                        <div class="seller-info">
                                            <div class="seller-details">
                                                <h3><?php echo htmlspecialchars($seller['full_name']); ?></h3>
                                                <p><?php echo $seller['product_count']; ?>
                                                    product<?php echo $seller['product_count'] != 1 ? 's' : ''; ?></p>

                                            </div>

                                            <!-- Visit Stall button at bottom -->
                                            <div class="seller-actions">
                                                <button class="visit-store-btn"
                                                    onclick="viewSellerProducts(<?php echo $seller['id']; ?>)">
                                                    <i class="fas fa-store"></i>
                                                    Visit Stall
                                                </button>
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
                                    <button class="nav-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                                        data-slide="<?php echo $i; ?>"></button>
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

                                        </div>
                                        <div class="announcement-content">
                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                        </div>
                                        <div class="announcement-footer">
                                            <small class="announcement-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                                                <?php if ($announcement['expiry_date']): ?>
                                                    | Expires:
                                                    <?php echo date('M j, Y', strtotime($announcement['expiry_date'])); ?>
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
    document.getElementById('sortBy').addEventListener('change', function () {
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
        dot.addEventListener('click', function () {
            const slideIndex = this.getAttribute('data-slide');

            // Remove active class from all dots
            document.querySelectorAll('.nav-dot').forEach(d => d.classList.remove('active'));

            // Add active class to clicked dot
            this.classList.add('active');

            console.log(`Navigating to slide ${slideIndex}`);
        });
    });

    // Auto-refresh most viewed section periodically
    setInterval(function () {
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

<script>
    // Enhanced JavaScript for sidebar filtering functionality

// Global variables to track current filters
let currentFilters = {
    categories: [],
    minPrice: null,
    maxPrice: null,
    seller: null,
    featured: false,
    search: ''
};

// Initialize filters when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    loadProducts(); // Load initial products
});

// Initialize filter event listeners
function initializeFilters() {
    // Category filters
    const categoryCheckboxes = document.querySelectorAll('input[name="category"]');
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', handleCategoryFilter);
    });

    // Price range filters
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    
    if (minPriceInput) {
        minPriceInput.addEventListener('input', debounce(handlePriceFilter, 500));
    }
    if (maxPriceInput) {
        maxPriceInput.addEventListener('input', debounce(handlePriceFilter, 500));
    }

    // Seller filter
    const sellerFilter = document.getElementById('sellerFilter');
    if (sellerFilter) {
        sellerFilter.addEventListener('change', handleSellerFilter);
    }

    // Featured filter
    const featuredCheckbox = document.getElementById('featured_only');
    if (featuredCheckbox) {
        featuredCheckbox.addEventListener('change', handleFeaturedFilter);
    }

    // Search filter
    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.addEventListener('input', debounce(handleSearchFilter, 300));
        
        // Handle search button click
        const searchButton = document.querySelector('.search-button');
        if (searchButton) {
            searchButton.addEventListener('click', handleSearchFilter);
        }
    }
}

// Handle category filter changes
function handleCategoryFilter(event) {
    const checkbox = event.target;
    const categoryId = checkbox.value;

    if (categoryId === '') {
        // "All Categories" checkbox
        if (checkbox.checked) {
            // Uncheck all other categories
            document.querySelectorAll('input[name="category"]:not([value=""])').forEach(cb => {
                cb.checked = false;
            });
            currentFilters.categories = [];
        }
    } else {
        // Specific category checkbox
        if (checkbox.checked) {
            // Uncheck "All Categories"
            document.getElementById('category_all').checked = false;
            
            // Add to current filters
            if (!currentFilters.categories.includes(categoryId)) {
                currentFilters.categories.push(categoryId);
            }
        } else {
            // Remove from current filters
            currentFilters.categories = currentFilters.categories.filter(id => id !== categoryId);
            
            // If no categories selected, check "All Categories"
            if (currentFilters.categories.length === 0) {
                document.getElementById('category_all').checked = true;
            }
        }
    }

    applyFilters();
}

// Handle price filter changes
function handlePriceFilter() {
    const minPrice = document.getElementById('minPrice').value;
    const maxPrice = document.getElementById('maxPrice').value;

    currentFilters.minPrice = minPrice ? parseFloat(minPrice) : null;
    currentFilters.maxPrice = maxPrice ? parseFloat(maxPrice) : null;

    applyFilters();
}

// Handle seller filter changes
function handleSellerFilter(event) {
    currentFilters.seller = event.target.value || null;
    applyFilters();
}

// Handle featured filter changes
function handleFeaturedFilter(event) {
    currentFilters.featured = event.target.checked;
    applyFilters();
}

// Handle search filter changes
function handleSearchFilter() {
    const searchValue = document.getElementById('productSearch').value.trim();
    currentFilters.search = searchValue;
    applyFilters();
}

// Apply all current filters
function applyFilters() {
    // Show loading state
    showLoadingState();

    // Send AJAX request to filter products
    fetch('ajax_filter_products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(currentFilters)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProductsGrid(data.products);
            updateResultsCount(data.products.length);
            updateActiveFilters();
        } else {
            showErrorState(data.message || 'Error filtering products');
        }
    })
    .catch(error => {
        console.error('Error applying filters:', error);
        showErrorState('Network error occurred');
    });
}

// Clear all filters
function clearFilters() {
    // Reset filter state
    currentFilters = {
        categories: [],
        minPrice: null,
        maxPrice: null,
        seller: null,
        featured: false,
        search: ''
    };

    // Reset UI elements
    document.getElementById('category_all').checked = true;
    document.querySelectorAll('input[name="category"]:not([value=""])').forEach(cb => {
        cb.checked = false;
    });

    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('sellerFilter').value = '';
    document.getElementById('featured_only').checked = false;
    document.getElementById('productSearch').value = '';

    // Hide active filters
    document.getElementById('activeFilters').style.display = 'none';

    // Reload all products
    loadProducts();
}

// Load initial products (without filters)
function loadProducts() {
    showLoadingState();

    fetch('ajax_get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProductsGrid(data.products);
                updateResultsCount(data.products.length);
            } else {
                showErrorState('Error loading products');
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showErrorState('Network error occurred');
        });
}

// Update the products grid with filtered results
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
                         alt="${escapeHtml(product.name)}"
                         onerror="this.src='https://estore.midas.com.my/image/cache/no_image_uploaded-253x190.png'">
                    ${product.is_featured ? '<div class="featured-badge">Featured</div>' : ''}
                    ${product.view_count > 0 ? `<div class="view-count-badge">${product.view_count} views</div>` : ''}
                </div>
                <div class="product-info">
                    <h3 class="product-name">${escapeHtml(product.name)}</h3>
                    <div class="stall-name">${escapeHtml(product.seller_full_name)}</div>
                    <div class="product-description">
                        ${product.short_description ? escapeHtml(product.short_description) : ''}
                    </div>
                    <div class="product-footer">
                        <div class="price-section">
                            <span class="price">${product.formatted_price}</span>
                        </div>
                        <div class="product-actions">
                            <button class="view-product-btn" onclick="viewProduct(${product.id})" title="View product">
                                <i class="fas fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    productsGrid.innerHTML = html;
}

// Update results count
function updateResultsCount(count) {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        resultsCount.textContent = `Showing ${count} product${count !== 1 ? 's' : ''}`;
    }
}

// Update active filters display
function updateActiveFilters() {
    const activeFiltersDiv = document.getElementById('activeFilters');
    const activeFilterTags = document.getElementById('activeFilterTags');
    
    if (!activeFiltersDiv || !activeFilterTags) return;

    let tags = [];

    // Category filters
    if (currentFilters.categories.length > 0) {
        currentFilters.categories.forEach(categoryId => {
            const checkbox = document.getElementById(`category_${categoryId}`);
            if (checkbox) {
                const label = checkbox.nextElementSibling.textContent;
                tags.push({
                    type: 'category',
                    value: categoryId,
                    label: `Category: ${label}`
                });
            }
        });
    }

    // Price filters
    if (currentFilters.minPrice !== null || currentFilters.maxPrice !== null) {
        let priceLabel = 'Price: ';
        if (currentFilters.minPrice !== null && currentFilters.maxPrice !== null) {
            priceLabel += `₱${currentFilters.minPrice} - ₱${currentFilters.maxPrice}`;
        } else if (currentFilters.minPrice !== null) {
            priceLabel += `₱${currentFilters.minPrice}+`;
        } else {
            priceLabel += `Up to ₱${currentFilters.maxPrice}`;
        }
        tags.push({
            type: 'price',
            value: null,
            label: priceLabel
        });
    }

    // Seller filter
    if (currentFilters.seller) {
        const sellerSelect = document.getElementById('sellerFilter');
        const selectedOption = sellerSelect.querySelector(`option[value="${currentFilters.seller}"]`);
        if (selectedOption) {
            tags.push({
                type: 'seller',
                value: currentFilters.seller,
                label: `Seller: ${selectedOption.textContent}`
            });
        }
    }

    // Featured filter
    if (currentFilters.featured) {
        tags.push({
            type: 'featured',
            value: null,
            label: 'Featured Only'
        });
    }

    // Search filter
    if (currentFilters.search) {
        tags.push({
            type: 'search',
            value: null,
            label: `Search: "${currentFilters.search}"`
        });
    }

    // Display tags
    if (tags.length > 0) {
        activeFilterTags.innerHTML = tags.map(tag => `
            <div class="filter-tag">
                ${escapeHtml(tag.label)}
                <span class="remove-filter" onclick="removeFilter('${tag.type}', ${tag.value ? `'${tag.value}'` : 'null'})">&times;</span>
            </div>
        `).join('');
        activeFiltersDiv.style.display = 'block';
    } else {
        activeFiltersDiv.style.display = 'none';
    }
}

// Remove individual filter
function removeFilter(type, value) {
    switch (type) {
        case 'category':
            const categoryCheckbox = document.getElementById(`category_${value}`);
            if (categoryCheckbox) {
                categoryCheckbox.checked = false;
                currentFilters.categories = currentFilters.categories.filter(id => id !== value);
                if (currentFilters.categories.length === 0) {
                    document.getElementById('category_all').checked = true;
                }
            }
            break;

        case 'price':
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            currentFilters.minPrice = null;
            currentFilters.maxPrice = null;
            break;

        case 'seller':
            document.getElementById('sellerFilter').value = '';
            currentFilters.seller = null;
            break;

        case 'featured':
            document.getElementById('featured_only').checked = false;
            currentFilters.featured = false;
            break;

        case 'search':
            document.getElementById('productSearch').value = '';
            currentFilters.search = '';
            break;
    }

    applyFilters();
}

// Show loading state
function showLoadingState() {
    const productsGrid = document.getElementById('productsGrid');
    if (productsGrid) {
        productsGrid.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading products...</p>
            </div>
        `;
    }
}

// Show error state
function showErrorState(message) {
    const productsGrid = document.getElementById('productsGrid');
    if (productsGrid) {
        productsGrid.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>${escapeHtml(message)}</p>
                <button onclick="loadProducts()" class="retry-btn">Try Again</button>
            </div>
        `;
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Debounce function to limit API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add styles for loading and error states
const additionalStyles = `
<style>
.loading-state, .error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
    grid-column: 1 / -1;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #52c41a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-state i {
    font-size: 3rem;
    color: #e74c3c;
    margin-bottom: 1rem;
}

.retry-btn {
    background: #52c41a;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 1rem;
    font-weight: 600;
}

.retry-btn:hover {
    background: #45a820;
}
</style>
`;

// Inject additional styles
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', additionalStyles);
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