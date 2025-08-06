<?php
require_once 'header.php';
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
                        <img src="../assets/img/avatar.jpg" alt="Store Logo" class="rounded-circle">
                    </div>
                    <div class="store-info">
                        <h3 class="store-name">Store Name</h3>
                        <p class="store-description">Store description and additional details go here</p>
                        <div class="store-rating mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span>(4.5)</span>
                        </div>
                        <a href="#" class="btn btn-outline-success view-stall-btn">View Stall</a>
                    </div>
                </div>
            </div>

            <!-- Product Section -->
            <div class="product-section">
                <h2>Product</h2>
                <div class="product-details-card">
                    <div class="product-image">
                        <img src="../assets/img/fruite-item-1.jpg" alt="Product Image">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">Product Name</h3>
                        <div class="product-price mb-3">₱999.00</div>
                        <div class="product-description mb-3">
                            <p>Product description and details go here. This can include information about the product's
                                features, specifications, and other relevant details.</p>
                        </div>
                        <div class="product-meta mb-4">
                            <div class="meta-item">
                                <span class="label">Category:</span>
                                <span class="value">Fruits</span>
                            </div>
                            <div class="meta-item">
                                <span class="label">Stock:</span>
                                <span class="value">50 items</span>
                            </div>
                            <div class="meta-item">
                                <span class="label">Condition:</span>
                                <span class="value">New</span>
                            </div>
                        </div>
                        <button class="btn btn-success message-vendor-btn">
                            <i class="fas fa-envelope me-2"></i>Message Vendor
                        </button>
                    </div>
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