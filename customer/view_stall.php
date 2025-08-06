<?php
require_once 'header.php';
?>

<div class="main-content">
    <div class="stall-container">
        <!-- Seller Profile Banner -->
        <div class="seller-profile-banner">
            <div class="profile-info">
                <div class="profile-image">
                    <img src="../assets/img/avatar.jpg" alt="Seller Profile" class="rounded-circle">
                </div>
                <div class="profile-details">
                    <h1 class="seller-name">Seller Name</h1>
                    <div class="seller-meta">
                        <div class="location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Stall Location</span>
                        </div>
                        <button class="btn btn-light message-seller-btn">
                            <i class="fas fa-envelope me-2"></i>Message Seller
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="products-section container py-5">
            <div class="section-header">
                <h2>Products</h2>
                <div class="filters">
                    <select class="form-select">
                        <option value="all">All Categories</option>
                        <option value="fruits">Fruits</option>
                        <option value="vegetables">Vegetables</option>
                        <option value="meat">Meat</option>
                        <option value="fish">Fish</option>
                    </select>
                    <select class="form-select">
                        <option value="newest">Newest</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="popular">Most Popular</option>
                    </select>
                </div>
            </div>

            <div class="products-grid">
                <!-- Product Card -->
                <div class="product-card">
                    <div class="product-image">
                        <img src="../assets/img/fruite-item-1.jpg" alt="Product">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">Product Name</h3>
                        <p class="product-price">₱99.00</p>
                        <p class="product-description">Brief description of the product goes here...</p>
                        <button class="btn btn-primary view-product-btn">View Product</button>
                    </div>
                </div>

                <!-- Repeat Product Cards -->
                <div class="product-card">
                    <div class="product-image">
                        <img src="../assets/img/fruite-item-2.jpg" alt="Product">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">Product Name</h3>
                        <p class="product-price">₱149.00</p>
                        <p class="product-description">Brief description of the product goes here...</p>
                        <button class="btn btn-primary view-product-btn">View Product</button>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-image">
                        <img src="../assets/img/fruite-item-3.jpg" alt="Product">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">Product Name</h3>
                        <p class="product-price">₱199.00</p>
                        <p class="product-description">Brief description of the product goes here...</p>
                        <button class="btn btn-primary view-product-btn">View Product</button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <nav aria-label="Product navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
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