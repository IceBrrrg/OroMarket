// Customer JavaScript for product interactions
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize category filtering
    initializeCategoryFiltering();
    
    // Initialize product interactions
    initializeProductInteractions();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize sort functionality
    initializeSorting();
});

// Category filtering
function initializeCategoryFiltering() {
    const categoryItems = document.querySelectorAll('.category-item');
    
    categoryItems.forEach(item => {
        item.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            
            // Remove active class from all categories
            categoryItems.forEach(cat => cat.classList.remove('active'));
            
            // Add active class to clicked category
            this.classList.add('active');
            
            // Filter products by category
            filterProductsByCategory(categoryId);
        });
    });
}

// Filter products by category
async function filterProductsByCategory(categoryId) {
    try {
        showLoading();
        
        const response = await fetch(`api/products.php?action=category&category_id=${categoryId}`);
        const result = await response.json();
        
        if (result.success) {
            renderProducts(result.data);
            updateResultsCount(result.data.length);
        } else {
            showError('Failed to load category products');
        }
        
    } catch (error) {
        console.error('Error filtering products:', error);
        showError('Failed to filter products');
    } finally {
        hideLoading();
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('#searchInput');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                if (query.length >= 2 || query.length === 0) {
                    searchProducts(query);
                }
            }, 500);
        });
    }
}

// Search products
async function searchProducts(query) {
    try {
        showLoading();
        
        const response = await fetch(`api/products.php?action=search&q=${encodeURIComponent(query)}`);
        const result = await response.json();
        
        if (result.success) {
            renderProducts(result.data);
            updateResultsCount(result.data.length);
        } else {
            showError('Failed to search products');
        }
        
    } catch (error) {
        console.error('Error searching products:', error);
        showError('Failed to search products');
    } finally {
        hideLoading();
    }
}

// Sort functionality
function initializeSorting() {
    const sortSelect = document.querySelector('#sortBy');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortBy = this.value;
            sortProducts(sortBy);
        });
    }
}

// Sort products
async function sortProducts(sortBy) {
    try {
        showLoading();
        
        const response = await fetch(`api/products.php?sort_by=${sortBy}`);
        const result = await response.json();
        
        if (result.success) {
            renderProducts(result.data);
        } else {
            showError('Failed to sort products');
        }
        
    } catch (error) {
        console.error('Error sorting products:', error);
        showError('Failed to sort products');
    } finally {
        hideLoading();
    }
}

// Render products
function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    
    if (!grid) return;
    
    if (!products || products.length === 0) {
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
    
    grid.innerHTML = products.map(product => {
        const imageUrl = product.primary_image ? 
            `../uploads/products/${product.primary_image}` : 
            'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop';
        
        const sellerName = product.seller_first_name && product.seller_last_name ? 
            `${product.seller_first_name} ${product.seller_last_name}` : 
            'Unknown Seller';
        
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

// Product interactions
function initializeProductInteractions() {
    // Add click handlers for product cards
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons
            if (e.target.closest('button')) return;
            
            const productId = this.dataset.productId;
            viewProduct(productId);
        });
    });
}

// View product details
function viewProduct(productId) {
    window.location.href = `view_product.php?id=${productId}`;
}

// View seller products
function viewSellerProducts(sellerId) {
    window.location.href = `view_stall.php?seller_id=${sellerId}`;
}

// Add to cart
async function addToCart(productId) {
    try {
        const response = await fetch('api/products.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_to_cart',
                product_id: productId,
                quantity: 1
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Product added to cart!', 'success');
            // Update cart count if you have a cart counter
            updateCartCount();
        } else {
            showNotification(result.message || 'Failed to add to cart', 'error');
        }
        
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('Failed to add to cart', 'error');
    }
}

// Toggle favorite
async function toggleFavorite(productId) {
    try {
        const response = await fetch('api/products.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_to_favorites',
                product_id: productId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Added to favorites!', 'success');
            // Update heart icon
            const heartIcon = event.target.closest('.favorite-btn').querySelector('i');
            heartIcon.classList.remove('far');
            heartIcon.classList.add('fas');
        } else {
            showNotification(result.message || 'Failed to add to favorites', 'error');
        }
        
    } catch (error) {
        console.error('Error toggling favorite:', error);
        showNotification('Failed to add to favorites', 'error');
    }
}

// Utility functions
function showLoading() {
    const grid = document.getElementById('productsGrid');
    if (grid) {
        grid.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <p>Loading products...</p>
            </div>
        `;
    }
}

function hideLoading() {
    // Loading will be hidden when products are rendered
}

function showError(message) {
    const grid = document.getElementById('productsGrid');
    if (grid) {
        grid.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>${message}</p>
                <button onclick="location.reload()">Try Again</button>
            </div>
        `;
    }
}

function updateResultsCount(count) {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        resultsCount.textContent = `Showing ${count} products`;
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

function updateCartCount() {
    // Update cart count in header if it exists
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const currentCount = parseInt(cartCount.textContent) || 0;
        cartCount.textContent = currentCount + 1;
    }
} 