<?php
require_once 'header.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Main Container with Sidebar -->
    <div class="main-container">
        <?php require_once 'sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="content-area">
            <!-- Results Info and Sort -->
            <div class="results-info">
                <div class="results-count">
                    <span id="resultsCount">Showing 24 products</span>
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
                    <div class="category-item">
                        <div class="category-icon">🍎</div>
                        <span>Fruits</span>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">🍞</div>
                        <span>Bread</span>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">🥬</div>
                        <span>Vegetable</span>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">🐟</div>
                        <span>Fish</span>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">🥩</div>
                        <span>Meat</span>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">🥤</div>
                        <span>Drinks</span>
                    </div>
                    <div class="category-item">
                        <div class="category-icon">🦐</div>
                        <span>Sea Food</span>
                    </div>
                   
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
                            <!-- Product cards will be dynamically loaded here -->
                        </div>
                    </section>

                    <!-- All Sellers -->
                    <section class="all-sellers">
                        <div class="section-header">
                            <h2>Our Sellers</h2>
                            <a href="#" class="view-more">View All Sellers</a>
                        </div>

                        <div class="sellers-grid">
                            <div class="seller-card">
                                <div class="seller-banner"></div>
                                <div class="seller-info">
                                    <div class="seller-logo">
                                        <img src="../assets/img/avatar.jpg" alt="Seller Logo">
                                    </div>
                                    <h3>Fresh Market Store</h3>
                                    <p>Fresh fruits and vegetables</p>
                                    <div class="seller-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                        <span>(4.5)</span>
                                    </div>
                                    <button class="btn visit-store-btn">Visit Store</button>
                                </div>
                            </div>

                            <div class="seller-card">
                                <div class="seller-banner"></div>
                                <div class="seller-info">
                                    <div class="seller-logo">
                                        <img src="../assets/img/avatar.jpg" alt="Seller Logo">
                                    </div>
                                    <h3>Organic Farm</h3>
                                    <p>100% Organic Products</p>
                                    <div class="seller-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <span>(5.0)</span>
                                    </div>
                                    <button class="btn visit-store-btn">Visit Store</button>
                                </div>
                            </div>

                            <div class="seller-card">
                                <div class="seller-banner"></div>
                                <div class="seller-info">
                                    <div class="seller-logo">
                                        <img src="../assets/img/avatar.jpg" alt="Seller Logo">
                                    </div>
                                    <h3>Local Fishery</h3>
                                    <p>Fresh Seafood Daily</p>
                                    <div class="seller-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <span>(4.0)</span>
                                    </div>
                                    <button class="btn visit-store-btn">Visit Store</button>
                                </div>
                            </div>

                            <div class="seller-card">
                                <div class="seller-banner"></div>
                                <div class="seller-info">
                                    <div class="seller-logo">
                                        <img src="../assets/img/avatar.jpg" alt="Seller Logo">
                                    </div>
                                    <h3>Butcher Shop</h3>
                                    <p>Premium Quality Meats</p>
                                    <div class="seller-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                        <span>(4.7)</span>
                                    </div>
                                    <button class="btn visit-store-btn">Visit Store</button>
                                </div>
                            </div>
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