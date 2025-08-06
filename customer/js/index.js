
document.addEventListener('DOMContentLoaded', function() {
    
    // Sample product data
    const products = [
        {
            id: 1,
            name: "Fresh Strawberries",
            description: "Sweet and juicy organic strawberries",
            price: 20.10,
            image: "https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=150&h=100&fit=crop",
            category: "fruits",
            rating: 4.5,
            seller: "Fresh Market Store",
            inStock: true,
            onSale: false,
            organic: true
        },
        {
            id: 2,
            name: "Fresh Cabbage",
            description: "Crisp and fresh green cabbage",
            price: 15.10,
            image: "https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=150&h=100&fit=crop",
            category: "vegetables",
            rating: 4.2,
            seller: "Organic Farm",
            inStock: true,
            onSale: true,
            organic: true
        },
        {
            id: 3,
            name: "Broccoli",
            description: "Fresh green broccoli heads",
            price: 25.10,
            image: "https://images.unsplash.com/photo-1459411621453-7b03977f4bfc?w=150&h=100&fit=crop",
            category: "vegetables",
            rating: 4.8,
            seller: "Organic Farm",
            inStock: true,
            onSale: false,
            organic: true
        },
        {
            id: 4,
            name: "Fresh Oranges",
            description: "Sweet and tangy oranges",
            price: 12.10,
            image: "https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=150&h=100&fit=crop",
            category: "fruits",
            rating: 4.3,
            seller: "Fresh Market Store",
            inStock: true,
            onSale: true,
            organic: false
        },
        {
            id: 5,
            name: "Fresh Apples",
            description: "Crisp red apples",
            price: 18.10,
            image: "https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=150&h=100&fit=crop",
            category: "fruits",
            rating: 4.6,
            seller: "Fresh Market Store",
            inStock: true,
            onSale: false,
            organic: true
        },
        {
            id: 6,
            name: "Fresh Fish",
            description: "Fresh caught local fish",
            price: 45.00,
            image: "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=150&h=100&fit=crop",
            category: "fish",
            rating: 4.7,
            seller: "Local Fishery",
            inStock: true,
            onSale: false,
            organic: false
        },
        {
            id: 7,
            name: "Fresh Milk",
            description: "Organic whole milk",
            price: 8.50,
            image: "https://images.unsplash.com/photo-1550583724-b2692b85b150?w=150&h=100&fit=crop",
            category: "dairy",
            rating: 4.4,
            seller: "Organic Farm",
            inStock: true,
            onSale: false,
            organic: true
        },
        {
            id: 8,
            name: "Whole Grain Bread",
            description: "Freshly baked whole grain bread",
            price: 6.80,
            image: "https://images.unsplash.com/photo-1509440159596-0249088772ff?w=150&h=100&fit=crop",
            category: "bakery",
            rating: 4.9,
            seller: "Bakery Corner",
            inStock: true,
            onSale: true,
            organic: false
        }
    ];

    // Function to render products
    function renderProducts(productsToRender) {
        const grid = document.getElementById('productsGrid');
        const resultsCount = document.getElementById('resultsCount');
        
        if (!grid) return;
        
        resultsCount.textContent = `Showing ${productsToRender.length} products`;
        
        grid.innerHTML = productsToRender.map(product => `
            <div class="product-card" data-category="${product.category}" data-seller="${product.seller}" data-price="${product.price}" data-rating="${product.rating}">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}">
                    <button class="favorite-btn" onclick="toggleFavorite(${product.id})">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <div class="product-info">
                    <h3>${product.name}</h3>
                    <p class="product-desc">${product.description}</p>
                    <div class="product-footer">
                        <span class="price">₱${product.price.toFixed(2)} <small>per kg</small></span>
                        <button class="add-btn" onclick="addToCart(${product.id})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Reinitialize product interactions
        initializeProductInteractions();
    }

    // Function to apply filters
    function applyFilters() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const minPrice = parseFloat(document.getElementById('minPrice')?.value) || 0;
        const maxPrice = parseFloat(document.getElementById('maxPrice')?.value) || Infinity;
        const selectedSeller = document.getElementById('sellerFilter')?.value || '';
        
        // Get selected categories
        const selectedCategories = [];
        document.querySelectorAll('.filter-option input[type="checkbox"]:checked').forEach(checkbox => {
            if (['fruits', 'vegetables', 'meat', 'fish', 'dairy', 'grains', 'beverages', 'bakery'].includes(checkbox.value)) {
                selectedCategories.push(checkbox.value);
            }
        });
        
        // Filter products
        let filteredProducts = products.filter(product => {
            const matchesSearch = product.name.toLowerCase().includes(searchTerm) || 
                               product.description.toLowerCase().includes(searchTerm);
            const matchesPrice = product.price >= minPrice && product.price <= maxPrice;
            const matchesCategory = selectedCategories.length === 0 || selectedCategories.includes(product.category);
            const matchesSeller = !selectedSeller || product.seller === selectedSeller;
            
            return matchesSearch && matchesPrice && matchesCategory && matchesSeller;
        });
        
        // Sort products
        const sortBy = document.getElementById('sortBy')?.value || 'relevance';
        switch(sortBy) {
            case 'price-low':
                filteredProducts.sort((a, b) => a.price - b.price);
                break;
            case 'price-high':
                filteredProducts.sort((a, b) => b.price - a.price);
                break;
            case 'rating':
                filteredProducts.sort((a, b) => b.rating - a.rating);
                break;
            case 'newest':
                filteredProducts.sort((a, b) => b.id - a.id);
                break;
        }
        
        renderProducts(filteredProducts);
    }

    // Function to clear filters
    function clearFilters() {
        // Clear search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        
        // Clear price range
        const minPrice = document.getElementById('minPrice');
        const maxPrice = document.getElementById('maxPrice');
        if (minPrice) minPrice.value = '';
        if (maxPrice) maxPrice.value = '';
        
        // Clear seller filter
        const sellerFilter = document.getElementById('sellerFilter');
        if (sellerFilter) sellerFilter.value = '';
        
        // Clear checkboxes
        document.querySelectorAll('.filter-option input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset sort
        const sortBy = document.getElementById('sortBy');
        if (sortBy) sortBy.value = 'relevance';
        
        // Reapply filters to show all products
        applyFilters();
        
        showNotification('All filters cleared!', 'success');
    }

    // Function to toggle favorite
    function toggleFavorite(productId) {
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
        }
    }

    // Function to add to cart
    function addToCart(productId) {
        const btn = event.target.closest('.add-btn');
        const originalHTML = btn.innerHTML;
        
        // Add loading state
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.style.background = '#45a820';
        
        // Simulate API call
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = '#28a745';
            
            showNotification('Product added to cart!', 'success');
            
            // Revert button after 2 seconds
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.background = '#52c41a';
            }, 2000);
        }, 800);
    }

    // Initialize product interactions
    function initializeProductInteractions() {
        // Favorite buttons
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                this.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (this.classList.contains('active')) {
                    icon.className = 'fas fa-heart';
                    this.style.color = '#ff4d4f';
                } else {
                    icon.className = 'far fa-heart';
                    this.style.color = '';
                }
            });
        });

        // Add to cart buttons
        const addButtons = document.querySelectorAll('.add-btn');
        addButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                addToCart();
            });
        });
    }

    // Add event listeners for filtering
    function initializeFiltering() {
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 300);
            });
        }

        // Price range inputs
        const minPrice = document.getElementById('minPrice');
        const maxPrice = document.getElementById('maxPrice');
        if (minPrice) minPrice.addEventListener('input', applyFilters);
        if (maxPrice) maxPrice.addEventListener('input', applyFilters);

        // Seller filter
        const sellerFilter = document.getElementById('sellerFilter');
        if (sellerFilter) sellerFilter.addEventListener('change', applyFilters);

        // Category checkboxes
        document.querySelectorAll('.filter-option input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', applyFilters);
        });

        // Sort select
        const sortBy = document.getElementById('sortBy');
        if (sortBy) sortBy.addEventListener('change', applyFilters);

        // Apply filters button
        const applyFiltersBtn = document.querySelector('.apply-filters-btn');
        if (applyFiltersBtn) applyFiltersBtn.addEventListener('click', applyFilters);

        // Clear filters button
        const clearFiltersBtn = document.querySelector('.clear-filters-btn');
        if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearFilters);
    }

    // Search functionality
    const searchInput = document.querySelector('.search-container input');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.toLowerCase();
                console.log('Searching for:', searchTerm);
                // Here you would typically make an API call to search products
                // For demo purposes, we'll just log the search term
            }, 300);
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
            
            const categoryName = this.querySelector('span').textContent;
            console.log('Selected category:', categoryName);
            // Here you would filter products by category
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

    // Shop now buttons in discount cards
    const shopNowButtons = document.querySelectorAll('.shop-now-btn');
    shopNowButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const discountCard = this.closest('.discount-card');
            const discount = discountCard.querySelector('h3').textContent;
            console.log('Shopping with discount:', discount);
            showNotification(`Shopping with ${discount}!`, 'info');
        });
    });

    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
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
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Loading states for dynamic content
    function addLoadingState(element) {
        element.classList.add('loading');
        setTimeout(() => {
            element.classList.remove('loading');
        }, 1000);
    }

    // Filter functionality
    const filterBtn = document.querySelector('.filter-btn');
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            console.log('Opening filters...');
            showNotification('Filters feature coming soon!', 'info');
        });
    }

    // Profile dropdown (placeholder)
    const profile = document.querySelector('.profile');
    if (profile) {
        profile.addEventListener('click', function() {
            console.log('Profile clicked');
            showNotification('Profile menu coming soon!', 'info');
        });
    }

    // Header icon interactions
    const headerIcons = document.querySelectorAll('.icon-item');
    headerIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const iconType = this.querySelector('i').className;
            if (iconType.includes('calendar')) {
                showNotification('Calendar feature coming soon!', 'info');
            } else if (iconType.includes('bell')) {
                showNotification('You have 2 new notifications!', 'info');
            } else if (iconType.includes('heart')) {
                showNotification('You have 3 items in wishlist!', 'info');
            }
        });
    });

    // Initialize tooltips and other interactive elements
    function initializeInteractivity() {
        // Add hover effects to cards
        const cards = document.querySelectorAll('.product-card, .discount-card, .category-item');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    // Initialize everything
    initializeFiltering();
    initializeInteractivity();
    renderProducts(products); // Initial render

    // Handle window resize for responsive behavior
    window.addEventListener('resize', function() {
        // Adjust grid layouts if needed
        const grids = document.querySelectorAll('.categories-grid, .products-grid');
        grids.forEach(grid => {
            // Force reflow for responsive grids
            grid.style.display = 'none';
            grid.offsetHeight; // Trigger reflow
            grid.style.display = 'grid';
        });
    });

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
    }
};

window.addToCart = function(productId) {
    const btn = event.target.closest('.add-btn');
    const originalHTML = btn.innerHTML;
    
    // Add loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.style.background = '#45a820';
    
    // Simulate API call
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#28a745';
        
        // Show success message
        // showNotification('Product added to cart!', 'success');
        
        // Revert button after 2 seconds
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '#52c41a';
        }, 2000);
    }, 800);
};