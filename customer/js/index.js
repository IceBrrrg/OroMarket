
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'mobile-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    toggleBtn.style.cssText = `
        display: none;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1001;
        background: #52c41a;
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(82, 196, 26, 0.3);
        transition: all 0.3s ease;
    `;

    document.body.appendChild(toggleBtn);

    // Show toggle button on mobile
    function checkMobile() {
        if (window.innerWidth <= 768) {
            toggleBtn.style.display = 'flex';
            toggleBtn.style.alignItems = 'center';
            toggleBtn.style.justifyContent = 'center';
        } else {
            toggleBtn.style.display = 'none';
            sidebar.classList.remove('open');
        }
    }

    checkMobile();
    window.addEventListener('resize', checkMobile);

    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        this.innerHTML = sidebar.classList.contains('open') 
            ? '<i class="fas fa-times"></i>' 
            : '<i class="fas fa-bars"></i>';
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !toggleBtn.contains(e.target) && 
            sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });

    // Navigation functionality
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all items
            navItems.forEach(nav => nav.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');
            
            // Close mobile sidebar after selection
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    });

    // Search functionality
    const searchInput = document.querySelector('.search-container input');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.toLowerCase();
            console.log('Searching for:', searchTerm);
            // Here you would typically make an API call to search products
            // For demo purposes, we'll just log the search term
        }, 300);
    });

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

    // Product interactions
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            
            const icon = this.querySelector('i');
            if (this.classList.contains('active')) {
                icon.className = 'fas fa-heart';
                this.style.color = '#ff4d4f';
                // Add to favorites
                console.log('Added to favorites');
            } else {
                icon.className = 'far fa-heart';
                this.style.color = '';
                // Remove from favorites
                console.log('Removed from favorites');
            }
        });
    });

    // Add to cart functionality
    const addButtons = document.querySelectorAll('.add-btn');
    addButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Add loading state
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.style.background = '#45a820';
            
            // Simulate API call
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.background = '#28a745';
                
                // Show success message
                showNotification('Product added to cart!', 'success');
                
                // Revert button after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.background = '#52c41a';
                }, 2000);
            }, 800);
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
    filterBtn.addEventListener('click', function() {
        console.log('Opening filters...');
        showNotification('Filters feature coming soon!', 'info');
    });

    // Profile dropdown (placeholder)
    const profile = document.querySelector('.profile');
    profile.addEventListener('click', function() {
        console.log('Profile clicked');
        showNotification('Profile menu coming soon!', 'info');
    });

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

    // Call initialization
    initializeInteractivity();

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

    console.log('SaniShop Marketplace initialized successfully!');
});