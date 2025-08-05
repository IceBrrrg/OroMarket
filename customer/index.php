<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaniShop - Grocery Marketplace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../customer/css/index.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h2>Sani<span class="shop">Shop</span></h2>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-item active">
                <i class="fas fa-th-large"></i>
                Dashboard
            </div>
            <div class="nav-item">
                <i class="fas fa-th"></i>
                Categories
            </div>
            <div class="nav-item">
                <i class="far fa-heart"></i>
                Favourite
            </div>
            <div class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                Orders
            </div>
            <div class="nav-item">
                <i class="far fa-comment"></i>
                Messages
            </div>
            <div class="nav-item">
                <i class="fas fa-tags"></i>
                Top Deals
            </div>
            <div class="nav-item">
                <i class="fas fa-cog"></i>
                Settings
            </div>
        </nav>
        
        <div class="logout">
            <div class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                Log Out
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search your grocery products etc....">
            </div>
            
            <div class="header-icons">
                <div class="icon-item">
                    <i class="far fa-calendar"></i>
                </div>
                <div class="icon-item notification">
                    <i class="far fa-bell"></i>
                    <span class="badge">2</span>
                </div>
                <div class="icon-item notification">
                    <i class="far fa-heart"></i>
                    <span class="badge">3</span>
                </div>
                <div class="profile">
                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=40&h=40&fit=crop&crop=face" alt="Profile">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <!-- Categories Section -->
        <section class="categories-section">
            <div class="section-header">
                <h2>Categories</h2>
                <div class="controls">
                    <button class="filter-btn">
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>
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
                <div class="category-item">
                    <div class="category-icon">🍦</div>
                    <span>Ice cream</span>
                </div>
                <div class="category-item">
                    <div class="category-icon">🥤</div>
                    <span>Juice</span>
                </div>
                <div class="category-item">
                    <div class="category-icon">🍯</div>
                    <span>Jam</span>
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
                        <h2>Popular Products</h2>
                        <a href="#" class="view-more">View More</a>
                    </div>
                    
                    <div class="products-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=150&h=100&fit=crop" alt="Strawberry">
                                <button class="favorite-btn active">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3>Strawberry</h3>
                                <p class="product-desc">Lorem ipsum dolor sit amet.</p>
                                <div class="product-footer">
                                    <span class="price">$20.10 <small>per kg</small></span>
                                    <button class="add-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <div class="product-image">
                                <img src="https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=150&h=100&fit=crop" alt="Cabbage">
                                <button class="favorite-btn">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3>Cabbage</h3>
                                <p class="product-desc">Lorem ipsum dolor sit amet.</p>
                                <div class="product-footer">
                                    <span class="price">$15.10 <small>per kg</small></span>
                                    <button class="add-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <div class="product-image">
                                <img src="https://images.unsplash.com/photo-1459411621453-7b03977f4bfc?w=150&h=100&fit=crop" alt="Brocoly">
                                <button class="favorite-btn">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3>Brocoly</h3>
                                <p class="product-desc">Lorem ipsum dolor sit amet.</p>
                                <div class="product-footer">
                                    <span class="price">$25.10 <small>per kg</small></span>
                                    <button class="add-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <div class="product-image">
                                <img src="https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=150&h=100&fit=crop" alt="Orange">
                                <button class="favorite-btn">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3>Orange</h3>
                                <p class="product-desc">Lorem ipsum dolor sit amet.</p>
                                <div class="product-footer">
                                    <span class="price">$12.10 <small>per kg</small></span>
                                    <button class="add-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card">
                            <div class="product-image">
                                <img src="https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=150&h=100&fit=crop" alt="Fresh Apple">
                                <button class="favorite-btn">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3>Fresh Apple</h3>
                                <p class="product-desc">Lorem ipsum dolor sit amet.</p>
                                <div class="product-footer">
                                    <span class="price">$18.10 <small>per kg</small></span>
                                    <button class="add-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Discount Shop -->
                <section class="discount-shop">
                    <div class="section-header">
                        <h2>Discount Shop</h2>
                        <a href="#" class="view-more">View More</a>
                    </div>
                    
                    <div class="discount-grid">
                        <div class="discount-card green">
                            <div class="discount-content">
                                <h3>35% Discount</h3>
                                <p>Order any food from app and get the discount.</p>
                                <button class="shop-now-btn">Shop Now</button>
                            </div>
                        </div>
                        
                        <div class="discount-card blue">
                            <div class="discount-content">
                                <h3>20% Discount</h3>
                                <p>Order any food from app and get the discount.</p>
                                <button class="shop-now-btn">Shop Now</button>
                            </div>
                        </div>
                        
                        <div class="discount-card teal">
                            <div class="discount-content">
                                <h3>20% Discount</h3>
                                <p>Order any food from app and get the discount.</p>
                                <button class="shop-now-btn">Shop Now</button>
                            </div>
                        </div>
                        
                        <div class="discount-card lime">
                            <div class="discount-content">
                                <h3>10% Discount</h3>
                                <p>Order any food from app and get the discount.</p>
                                <button class="shop-now-btn">Shop Now</button>
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
                    <h2>Last Order</h2>
                    
                    <div class="order-items">
                        <div class="order-item">
                            <div class="item-image">
                                <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=50&h=50&fit=crop" alt="Red Saffron">
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
                                <img src="https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=50&h=50&fit=crop" alt="Friesh Apple">
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
                                <img src="https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=50&h=50&fit=crop" alt="Big Fish">
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
                                <img src="https://images.unsplash.com/photo-1551024506-0bccd828d307?w=50&h=50&fit=crop" alt="Sweets">
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

   <script src="../customer/js/index.js"></script>
</body>
</html>