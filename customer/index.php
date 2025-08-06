<?php
require_once 'header.php';
require_once 'fetch_products.php';

// Fetch products for display
$products = fetchProducts(12);
$categories = fetchCategories();
$featured_products = getFeaturedProducts(6);
$popular_sellers = getSellers(4);
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Main Container with Sidebar -->
    <div class="main-container">


        <!-- Main Content Area -->
        <div class="content-area">
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
                            <option value="rating">Rating</option>
                            <option value="newest">Newest</option>
                        </select>
                    </div>
                </div>
            </div>

            <style>
                .results-info {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                }

                .right-controls {
                    display: flex;
                    align-items: center;
                    gap: 20px;
                }

                .search-box {
                    position: relative;
                    width: 300px;
                }

                .search-box input {
                    width: 100%;
                    padding: 8px 35px 8px 15px;
                    border: 2px solid #eee;
                    border-radius: 6px;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                }

                .search-box input:focus {
                    border-color: #81c408;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(129, 196, 8, 0.1);
                }

                .search-button {
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    color: #81c408;
                    cursor: pointer;
                }

                .search-button:hover {
                    color: #6da607;
                }

                @media (max-width: 768px) {
                    .results-info {
                        flex-direction: column;
                        gap: 15px;
                    }

                    .right-controls {
                        flex-direction: column;
                        width: 100%;
                    }

                    .search-box {
                        width: 100%;
                    }

                    .sort-options {
                        width: 100%;
                    }

                    .sort-select {
                        width: 100%;
                    }
                }

                /* Product Description Styles */
                .product-description {
                    color: #666;
                    font-size: 0.85rem;
                    line-height: 1.4;
                    margin: 8px 0;
                    min-height: 40px;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }

                /* Product Card Button Styles */
                .view-product-btn {
                    background-color: #81c408;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-size: 0.9rem;
                    width: 100%;
                }

                .view-product-btn:hover {
                    background-color: #72ac07;
                    transform: translateY(-2px);
                }
            </style>

            <!-- Categories Section -->
            <section class="categories-section">
                <div class="section-header">
                    <h2>Categories</h2>
                    <div class="controls">
                        <div class="nav-arrows">
                            <button class="arrow-btn prev"><i class="fas fa-chevron-left"></i></button>
                            <button class="arrow-btn next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>

                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item" data-category-id="<?php echo $category['id']; ?>">
                            <div class="category-icon">
                                <?php
                                // Map category names to emojis
                                $category_emojis = [
                                    'fruits' => '🍎',
                                    'vegetables' => '🥬',
                                    'meat' => '🥩',
                                    'fish' => '🐟',
                                    'bread' => '🍞',
                                    'drinks' => '🥤',
                                    'seafood' => '🦐',
                                    'dairy' => '🥛',
                                    'grains' => '🌾',
                                    'herbs' => '🌿'
                                ];
                                $category_name_lower = strtolower($category['name']);
                                echo isset($category_emojis[$category_name_lower]) ? $category_emojis[$category_name_lower] : '🛒';
                                ?>
                            </div>
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                            <small>(<?php echo $category['product_count']; ?>)</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

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
                                                <div class="product-actions">
                                                    <button class="view-product-btn"
                                                        onclick="viewProduct(<?php echo $product['id']; ?>)"
                                                        title="View product">
                                                        View product
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
                                            <div class="seller-rating">
                                                <?php
                                                $rating = $seller['rating'];
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                                <span>(<?php echo $rating; ?>)</span>
                                            </div>
                                            <button class="btn visit-store-btn"
                                                onclick="viewSellerProducts(<?php echo $seller['id']; ?>)">Visit Store</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Top Items -->
                    <section class="top-items">
                        <div class="section-header">
                            <h2>Top Items</h2>
                            <div class="nav-arrows">
                                <button class="arrow-btn prev"><i class="fas fa-chevron-left"></i></button>
                                <button class="arrow-btn next"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>

                        <div class="top-items-grid">
                            <div class="top-item green">
                                <div class="item-content">
                                    <h3>Fresh Fruits</h3>
                                </div>
                            </div>
                            <div class="top-item red">
                                <div class="item-content">
                                    <h3>Vegetables</h3>
                                </div>
                            </div>
                            <div class="top-item orange">
                                <div class="item-content">
                                    <h3>Bakery</h3>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <section class="last-order">
                        <h2>Most Viewed</h2>

                        <div class="order-items">
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=50&h=50&fit=crop"
                                        alt="Red Saffron">
                                </div>
                                <div class="item-info">
                                    <h4>Red Saffron</h4>
                                    <p>Weight 500 gm</p>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                </div>
                                <div class="item-price">$150</div>
                            </div>

                            <div class="order-item">
                                <div class="item-image">
                                    <img src="https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=50&h=50&fit=crop"
                                        alt="Friesh Apple">
                                </div>
                                <div class="item-info">
                                    <h4>Friesh Apple</h4>
                                    <p>Weight 2 kg</p>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <div class="item-price">$120</div>
                            </div>

                            <div class="order-item">
                                <div class="item-image">
                                    <img src="https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=50&h=50&fit=crop"
                                        alt="Big Fish">
                                </div>
                                <div class="item-info">
                                    <h4>Big Fish</h4>
                                    <p>Weight 6 kg</p>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                </div>
                                <div class="item-price">$300</div>
                            </div>

                            <div class="order-item">
                                <div class="item-image">
                                    <img src="https://images.unsplash.com/photo-1551024506-0bccd828d307?w=50&h=50&fit=crop"
                                        alt="Sweets">
                                </div>
                                <div class="item-info">
                                    <h4>Sweets</h4>
                                    <p>Weight 2 kg</p>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <div class="item-price">$150</div>
                            </div>
                        </div>

                        <div class="order-navigation">
                            <button class="nav-dot active"></button>
                            <button class="nav-dot"></button>
                            <button class="nav-dot"></button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

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