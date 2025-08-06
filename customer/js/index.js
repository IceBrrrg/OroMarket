document.addEventListener('DOMContentLoaded', function() {
    
    let allProducts = []; // Store all products for filtering
    let originalProducts = []; // Store original products from PHP
    
    // Get initial products data from PHP (already rendered on page)
    function initializeProductsFromDOM() {
        const productCards = document.querySelectorAll('.product-card');
        allProducts = [];
        originalProducts = [];
        
        productCards.forEach(card => {
            // Get the actual image source from the rendered img element
            const imgElement = card.querySelector('.product-image img');
            const currentImageSrc = imgElement ? imgElement.src : '';
            
            // Store both the current working image src AND the original data
            const productData = {
                id: card.dataset.productId,
                name: card.querySelector('h3').textContent.trim(),
                description: card.querySelector('.product-desc').textContent.trim(),
                price: card.querySelector('.price').textContent.replace('₱', '').trim(),
                category_name: card.querySelector('.product-category').textContent.trim(),
                seller_full_name: card.querySelector('.seller-name').textContent.replace('by ', '').trim(),
                stock_quantity: parseInt(card.querySelector('.stock-count').textContent.split(' ')[0]) || 0,
                is_featured: card.querySelector('.featured-badge') ? 1 : 0,
                working_image_url: currentImageSrc, // Store the WORKING image URL
                primary_image: currentImageSrc,
                weight: card.querySelector('.product-weight') ? 
                    parseFloat(card.querySelector('.product-weight').textContent.replace('kg', '')) : null
            };
            allProducts.push(productData);
            originalProducts.push(productData);
        });
        
        console.log('Initialized products from DOM:', allProducts.length);
    }
    
    // Get default fallback image
    function getDefaultImage() {
        return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop';
    }
    
    // Function to render products with improved image handling
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
            // Use the working image URL that was captured from the original render
            const imageUrl = product.working_image_url || product.primary_image || getDefaultImage();
            
            // Format price
            const price = typeof product.price === 'string' ? 
                parseFloat(product.price.replace('₱', '').replace(',', '')) || 0 : 
                parseFloat(product.price) || 0;
            
            return `
                <div class="product-card" data-product-id="${product.id}">
                    <div class="product-image">
                        <img src="${imageUrl}" 
                             alt="${product.name}" 
                             onerror="this.src='${getDefaultImage()}'"
                             loading="lazy">
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
                            <span class="seller-name">by ${product.seller_full_name || 'Unknown Seller'}</span>
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
    
    // Category filtering (client-side) - FIXED VERSION
    function filterProductsByCategory(categoryName) {
        console.log('Filtering by category:', categoryName);
        
        if (!categoryName || categoryName === 'all') {
            // Show all products and update the current filtered set
            allProducts = [...originalProducts];
            renderProducts(originalProducts);
            updateResultsCount(originalProducts.length);
            return;
        }
        
        // Filter products by category name (case insensitive)
        const filteredProducts = originalProducts.filter(product => {
            const productCategory = (product.category_name || '').toLowerCase();
            const searchCategory = categoryName.toLowerCase();
            
            // Check for exact match or partial match
            return productCategory.includes(searchCategory) || searchCategory.includes(productCategory);
        });
        
        // Update the current filtered set
        allProducts = [...filteredProducts];
        
        console.log('Filtered products:', filteredProducts.length);
        renderProducts(filteredProducts);
        updateResultsCount(filteredProducts.length);
    }
    
    // Search functionality (client-side)
    function searchProducts(query) {
        console.log('Searching for:', query);
        
        if (!query.trim()) {
            allProducts = [...originalProducts];
            renderProducts(originalProducts);
            updateResultsCount(originalProducts.length);
            return;
        }
        
        const searchTerm = query.toLowerCase();
        const filteredProducts = originalProducts.filter(product => {
            return (
                (product.name || '').toLowerCase().includes(searchTerm) ||
                (product.description || '').toLowerCase().includes(searchTerm) ||
                (product.category_name || '').toLowerCase().includes(searchTerm) ||
                (product.seller_full_name || '').toLowerCase().includes(searchTerm)
            );
        });
        
        allProducts = [...filteredProducts];
        renderProducts(filteredProducts);
        updateResultsCount(filteredProducts.length);
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
                // window.location.href = `view_product.php?id=${productId}`;
            });
        });
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search-container input, #searchInput');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                searchProducts(searchTerm);
            }, 300);
        });
    }
    
    // Sort functionality - IMPROVED VERSION
    const sortBy = document.getElementById('sortBy');
    if (sortBy) {
        sortBy.addEventListener('change', function() {
            const sortValue = this.value;
            
            // Create a copy of current filtered products to sort
            let sortedProducts = [...allProducts];
            
            switch(sortValue) {
                case 'price-low':
                    sortedProducts.sort((a, b) => {
                        const priceA = typeof a.price === 'string' ? parseFloat(a.price.replace('₱', '')) : parseFloat(a.price);
                        const priceB = typeof b.price === 'string' ? parseFloat(b.price.replace('₱', '')) : parseFloat(b.price);
                        return priceA - priceB;
                    });
                    break;
                case 'price-high':
                    sortedProducts.sort((a, b) => {
                        const priceA = typeof a.price === 'string' ? parseFloat(a.price.replace('₱', '')) : parseFloat(a.price);
                        const priceB = typeof b.price === 'string' ? parseFloat(b.price.replace('₱', '')) : parseFloat(b.price);
                        return priceB - priceA;
                    });
                    break;
                case 'newest':
                    sortedProducts.sort((a, b) => parseInt(b.id) - parseInt(a.id));
                    break;
                case 'name':
                    sortedProducts.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'featured':
                    sortedProducts.sort((a, b) => b.is_featured - a.is_featured);
                    break;
                default: // relevance
                    sortedProducts.sort((a, b) => {
                        if (a.is_featured && !b.is_featured) return -1;
                        if (!a.is_featured && b.is_featured) return 1;
                        return parseInt(b.id) - parseInt(a.id);
                    });
            }
            
            // Update allProducts to maintain the sorted order
            allProducts = sortedProducts;
            renderProducts(sortedProducts);
        });
    }
    
    // Category selection - IMPROVED VERSION
    const categoryItems = document.querySelectorAll('.category-item');
    categoryItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all categories
            categoryItems.forEach(cat => cat.classList.remove('active'));
            // Add active class to selected category
            this.classList.add('active');
            
            const categoryName = this.querySelector('span').textContent.toLowerCase();
            console.log('Selected category:', categoryName);
            
            filterProductsByCategory(categoryName);
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
            console.log('Showing order page:', index + 1);
        });
    });
    
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
    
    // Initialize products from existing DOM
    initializeProductsFromDOM();
    
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
    } else {
        btn.classList.add('active');
        icon.classList.remove('far');
        icon.classList.add('fas');
        
        showNotificationMessage('Added to favorites!', 'success');
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
    
    // Simulate adding to cart
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#28a745';
        
        showNotificationMessage('Product added to cart!', 'success');
        
        // Revert button after 2 seconds
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#52c41a';
            btn.disabled = false;
        }, 2000);
    }, 1000);
};

window.viewSellerProducts = function(sellerId) {
    console.log('Viewing seller products:', sellerId);
    // window.location.href = `view_stall.php?seller_id=${sellerId}`;
};

// Helper function for showing notifications
function showNotificationMessage(message, type = 'info') {
    const notification = document.createElement('div');
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
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}