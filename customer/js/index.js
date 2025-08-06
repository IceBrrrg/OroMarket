document.addEventListener('DOMContentLoaded', function() {
    
    let allProducts = []; // Store all products for filtering
    let currentPage = 1;
    let totalPages = 1;
    const productsPerPage = 12;
    
    // API Base URL - Adjust this to match your project structure
    const API_BASE_URL = '../api/products.php';
    
    // Fetch products from API
    async function fetchProducts(params = {}) {
        try {
            showLoading();
            
            // Build query parameters
            const queryParams = new URLSearchParams({
                page: params.page || currentPage,
                limit: params.limit || productsPerPage,
                ...params
            });
            
            const response = await fetch(`${API_BASE_URL}?${queryParams}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                allProducts = result.data;
                if (result.pagination) {
                    currentPage = result.pagination.page;
                    totalPages = result.pagination.pages;
                    updatePagination(result.pagination);
                }
                renderProducts(allProducts);
                updateResultsCount(result.pagination?.total || allProducts.length);
            } else {
                throw new Error(result.message || 'Failed to fetch products');
            }
            
        } catch (error) {
            console.error('Error fetching products:', error);
            showError('Failed to load products. Please try again later.');
        } finally {
            hideLoading();
        }
    }
    
    // Fetch products by category
    async function fetchProductsByCategory(categoryId) {
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}?action=category&category_id=${categoryId}&limit=${productsPerPage}`);
            const result = await response.json();
            
            if (result.success) {
                allProducts = result.data;
                renderProducts(allProducts);
                updateResultsCount(result.pagination?.total || allProducts.length);
            }
        } catch (error) {
            console.error('Error fetching category products:', error);
            showError('Failed to load category products.');
        } finally {
            hideLoading();
        }
    }
    
    // Search products
    async function searchProducts(query) {
        if (!query.trim()) {
            fetchProducts(); // Load all products if search is empty
            return;
        }
        
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}?action=search&q=${encodeURIComponent(query)}&limit=${productsPerPage}`);
            const result = await response.json();
            
            if (result.success) {
                allProducts = result.data;
                renderProducts(allProducts);
                updateResultsCount(result.pagination?.total || allProducts.length);
            }
        } catch (error) {
            console.error('Error searching products:', error);
            showError('Failed to search products.');
        } finally {
            hideLoading();
        }
    }
    
    // Function to render products
    function renderProducts(productsToRender) {
        const grid = document.getElementById('productsGrid');
        
        if (!grid) {
            console.error('Products grid not found');
            return;
        }
        
        if (!productsToRender || productsToRender.length === 0) {
            grid.innerHTML = `
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
        
        grid.innerHTML = productsToRender.map(product => {
            // Handle image - use primary_image from API or fallback
            const imageUrl = product.primary_image ? 
                `../uploads/products/${product.primary_image}` : 
                'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop';
            
            // Format seller name
            const sellerName = product.seller_first_name && product.seller_last_name ? 
                `${product.seller_first_name} ${product.seller_last_name}` : 
                'Unknown Seller';
            
            // Format price
            const price = parseFloat(product.price) || 0;
            
            return `
                <div class="product-card" data-product-id="${product.id}">
                    <div class="product-image">
                        <img src="${imageUrl}" alt="${product.name}" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop'">
                        <button class="favorite-btn" onclick="toggleFavorite(${product.id})" title="Add to favorites">
                            <i class="far fa-heart"></i>
                        </button>
                        ${product.is_featured == 1 ? '<div class="featured-badge">Featured</div>' : ''}
                        ${product.stock_quantity <= 5 ? '<div class="low-stock-badge">Low Stock</div>' : ''}
                    </div>
                    <div class="product-info">
                        <div class="product-category">${product.category_name || 'Uncategorized'}</div>
                        <h3>${product.name}</h3>
                        <p class="product-desc">${product.description || 'No description available'}</p>
                        <div class="product-meta">
                            <span class="seller-name">by ${sellerName}</span>
                            ${product.weight ? `<span class="product-weight">${product.weight}kg</span>` : ''}
                        </div>
                        <div class="product-footer">
                            <div class="price-section">
                                <span class="price">₱${price.toFixed(2)}</span>
                                <small>per ${product.weight ? 'kg' : 'unit'}</small>
                            </div>
                            <div class="product-actions">
                                <button class="add-btn ${product.stock_quantity <= 0 ? 'disabled' : ''}" 
                                        onclick="addToCart(${product.id})" 
                                        ${product.stock_quantity <= 0 ? 'disabled' : ''}
                                        title="${product.stock_quantity <= 0 ? 'Out of stock' : 'Add to cart'}">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="stock-info">
                            <span class="stock-count">${product.stock_quantity} in stock</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        // Reinitialize product interactions
        initializeProductInteractions();
    }
    
    // Update results count
    function updateResultsCount(total) {
        const resultsCount = document.getElementById('resultsCount');
        if (resultsCount) {
            resultsCount.textContent = `Showing ${total || 0} products`;
        }
    }
    
    // Update pagination
    function updatePagination(pagination) {
        // You can implement pagination UI here if needed
        console.log('Pagination info:', pagination);
    }
    
    // Show loading state
    function showLoading() {
        const grid = document.getElementById('productsGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="loading-state">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading products...</p>
                    </div>
                </div>
            `;
        }
    }
    
    // Hide loading state
    function hideLoading() {
        // Loading will be hidden when products are rendered
    }
    
    // Show error message
    function showError(message) {
        const grid = document.getElementById('productsGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="error-state">
                    <div class="error-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Oops! Something went wrong</h3>
                        <p>${message}</p>
                        <button class="retry-btn" onclick="location.reload()">Try Again</button>
                    </div>
                </div>
            `;
        }
    }
    
    // Function to toggle favorite (API call)
    async function toggleFavoriteAPI(productId) {
        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_to_favorites',
                    product_id: productId,
                    customer_id: 1 // You'll need to get this from session/auth
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Added to favorites!', 'success');
            } else {
                showNotification('Failed to add to favorites', 'error');
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            showNotification('Failed to add to favorites', 'error');
        }
    }
    
    // Function to add to cart (API call)
    async function addToCartAPI(productId, quantity = 1) {
        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: quantity,
                    customer_id: 1 // You'll need to get this from session/auth
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Product added to cart!', 'success');
                return true;
            } else {
                showNotification(result.message || 'Failed to add to cart', 'error');
                return false;
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            showNotification('Failed to add to cart', 'error');
            return false;
        }
    }
    
    // Initialize product interactions
    function initializeProductInteractions() {
        // Product card clicks (for viewing details)
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on buttons
                if (e.target.closest('button')) return;
                
                const productId = this.dataset.productId;
                console.log('Product clicked:', productId);
                // You can implement product detail view here
                // window.location.href = `product-details.php?id=${productId}`;
            });
        });
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search-container input');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    searchProducts(searchTerm);
                } else {
                    fetchProducts(); // Reset to all products
                }
            }, 500); // Increased delay to reduce API calls
        });
    }
    
    // Sort functionality
    const sortBy = document.getElementById('sortBy');
    if (sortBy) {
        sortBy.addEventListener('change', function() {
            const sortValue = this.value;
            
            // Create a copy of products to sort
            let sortedProducts = [...allProducts];
            
            switch(sortValue) {
                case 'price-low':
                    sortedProducts.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                    break;
                case 'price-high':
                    sortedProducts.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                    break;
                case 'newest':
                    sortedProducts.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    break;
                case 'name-a-z':
                    sortedProducts.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'name-z-a':
                    sortedProducts.sort((a, b) => b.name.localeCompare(a.name));
                    break;
                case 'featured':
                    sortedProducts.sort((a, b) => b.is_featured - a.is_featured);
                    break;
                default: // relevance
                    // Keep original order or sort by featured + newest
                    sortedProducts.sort((a, b) => {
                        if (a.is_featured && !b.is_featured) return -1;
                        if (!a.is_featured && b.is_featured) return 1;
                        return new Date(b.created_at) - new Date(a.created_at);
                    });
            }
            
            renderProducts(sortedProducts);
        });
    }
    
    // Category selection
    const categoryItems = document.querySelectorAll('.category-item');
    categoryItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all categories
            categoryItems.forEach(cat => cat.classList.remove('active'));
            // Add active class to selected category
            this.classList.add('active');
            
            const categoryName = this.querySelector('span').textContent.toLowerCase();
            console.log('Selected category:', categoryName);
            
            // Map emoji categories to actual category names or IDs
            // You might want to add data-category-id attributes to your category items
            // For now, we'll use the text content
            // fetchProductsByCategory(categoryId);
            
            // Or filter by category name if you prefer client-side filtering
            if (categoryName === 'all' || categoryName === '') {
                fetchProducts();
            } else {
                const filteredProducts = allProducts.filter(product => 
                    product.category_name && 
                    product.category_name.toLowerCase().includes(categoryName)
                );
                renderProducts(filteredProducts);
                updateResultsCount(filteredProducts.length);
            }
        });
    });
    
    // Arrow navigation for categories and top items
    const prevButtons = document.querySelectorAll('.arrow-btn.prev');
    const nextButtons = document.querySelectorAll('.arrow-btn.next');
    
    prevButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const container = this.closest('section').querySelector('.categories-grid, .top-items-grid');
            if (container) {
                container.scrollBy({
                    left: -200,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    nextButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const container = this.closest('section').querySelector('.categories-grid, .top-items-grid');
            if (container) {
                container.scrollBy({
                    left: 200,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Order navigation dots
    const navDots = document.querySelectorAll('.nav-dot');
    navDots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            navDots.forEach(d => d.classList.remove('active'));
            this.classList.add('active');
            // Here you would show different order pages
            console.log('Showing order page:', index + 1);
        });
    });
    
    // Filter functionality
    const filterBtn = document.querySelector('.filter-btn');
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            console.log('Opening filters...');
            showNotification('Advanced filters coming soon!', 'info');
        });
    }
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#52c41a' : type === 'error' ? '#ff4d4f' : '#1890ff'};
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            font-weight: 500;
            max-width: 300px;
            font-size: 14px;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    // Initialize everything
    console.log('Initializing Oroquieta Marketplace...');
    
    // Products are already loaded via PHP, no need to fetch via API
    // fetchProducts(); // Removed - products are rendered server-side
    
    console.log('Oroquieta Marketplace initialized successfully!');
});

// Global functions for onclick handlers
window.toggleFavorite = function(productId) {
    const btn = event.target.closest('.favorite-btn');
    const icon = btn.querySelector('i');
    
    if (btn.classList.contains('active')) {
        btn.classList.remove('active');
        icon.classList.remove('fas');
        icon.classList.add('far');
        // You could call API to remove from favorites here
    } else {
        btn.classList.add('active');
        icon.classList.remove('far');
        icon.classList.add('fas');
        // Call API to add to favorites
        // toggleFavoriteAPI(productId);
    }
};

window.addToCart = function(productId) {
    const btn = event.target.closest('.add-btn');
    if (btn.disabled) return;
    
    const originalHTML = btn.innerHTML;
    
    // Add loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    btn.style.background = '#45a820';
    
    // Call API to add to cart
    addToCartAPI(productId).then(success => {
        if (success) {
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = '#28a745';
        } else {
            btn.innerHTML = '<i class="fas fa-times"></i>';
            btn.style.background = '#ff4d4f';
        }
        
        // Revert button after 2 seconds
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#52c41a';
            btn.disabled = false;
        }, 2000);
    });
    
    // Define addToCartAPI function
    async function addToCartAPI(productId) {
        try {
            const API_BASE_URL = '../api/products.php';
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_to_cart',
                    product_id: productId,
                    quantity: 1,
                    customer_id: 1 // You'll need to get this from session/auth
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Create notification
                const notification = document.createElement('div');
                notification.textContent = 'Product added to cart!';
                notification.style.cssText = `
                    position: fixed;
                    top: 2rem;
                    right: 2rem;
                    padding: 1rem 1.5rem;
                    background: #52c41a;
                    color: white;
                    border-radius: 10px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    z-index: 1000;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                    font-weight: 500;
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.style.opacity = '1';
                    notification.style.transform = 'translateX(0)';
                }, 100);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            document.body.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
                
                return true;
            } else {
                return false;
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            return false;
        }
    }
};