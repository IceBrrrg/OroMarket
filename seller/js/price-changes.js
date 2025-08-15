/**
 * Price Changes Dashboard Integration
 * File: assets/js/price-changes-integration.js
 * 
 * This file integrates the price changes API endpoint into the seller dashboard
 * to show competitive pricing information from other sellers.
 */

class PriceChangesDashboard {
    constructor() {
        this.apiBaseUrl = 'price-changes.php';
        this.sellerId = this.getCurrentSellerId();
        this.isLoading = false;
        this.refreshInterval = null;
        this.init();
    }

    /**
     * Initialize the price changes dashboard
     */
    init() {
        this.createPriceChangesSection();
        this.loadPriceChanges();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    /**
     * Get current seller ID from session or DOM
     */
    getCurrentSellerId() {
        // Try to get from global variable or data attribute
        return window.sellerId || document.body.dataset.sellerId || null;
    }

    /**
     * Create the price changes section in the dashboard
     */
    createPriceChangesSection() {
        const dashboardContainer = document.querySelector('.container-fluid');
        if (!dashboardContainer) return;

        const priceChangesSection = document.createElement('div');
        priceChangesSection.className = 'row g-4 mb-4';
        priceChangesSection.id = 'price-changes-section';
        
        priceChangesSection.innerHTML = `
            <div class="col-12">
                <div class="quick-actions">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>
                            <i class="bi bi-graph-up-arrow me-2"></i>
                            Market Price Changes
                            <span class="badge bg-info ms-2" id="price-changes-count">0</span>
                        </h4>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="category-filter" style="width: auto;">
                                <option value="">All Categories</option>
                            </select>
                            <select class="form-select form-select-sm" id="days-filter" style="width: auto;">
                                <option value="1">Today</option>
                                <option value="3">3 Days</option>
                                <option value="7" selected>7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30">30 Days</option>
                            </select>
                            <button class="btn btn-outline-primary btn-sm" id="refresh-btn">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row g-3 mb-4" id="price-stats-cards">
                        <div class="col-md-3">
                            <div class="card border-0 bg-gradient-primary text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-arrow-up-circle fs-2 mb-2"></i>
                                    <h5 class="mb-1" id="price-increases">0</h5>
                                    <small>Price Increases</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-gradient-danger text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-arrow-down-circle fs-2 mb-2"></i>
                                    <h5 class="mb-1" id="price-decreases">0</h5>
                                    <small>Price Decreases</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-gradient-info text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-percent fs-2 mb-2"></i>
                                    <h5 class="mb-1" id="avg-change">0%</h5>
                                    <small>Avg Change</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-gradient-success text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-people fs-2 mb-2"></i>
                                    <h5 class="mb-1" id="active-sellers">0</h5>
                                    <small>Active Sellers</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Price Changes List -->
                    <div id="price-changes-list">
                        <div class="text-center py-4" id="loading-state">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading price changes...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert before the announcements section
        const announcementsSection = dashboardContainer.querySelector('.row:has(h4:contains("Announcements"))') || 
                                    dashboardContainer.querySelector('.row').nextElementSibling;
        
        if (announcementsSection) {
            dashboardContainer.insertBefore(priceChangesSection, announcementsSection);
        } else {
            dashboardContainer.appendChild(priceChangesSection);
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Refresh button
        document.getElementById('refresh-btn')?.addEventListener('click', () => {
            this.loadPriceChanges();
        });

        // Category filter
        document.getElementById('category-filter')?.addEventListener('change', (e) => {
            this.loadPriceChanges();
        });

        // Days filter
        document.getElementById('days-filter')?.addEventListener('change', (e) => {
            this.loadPriceChanges();
        });
    }

    /**
     * Load price changes from API
     */
    async loadPriceChanges() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();

        try {
            const params = this.getFilterParams();
            const [changesResponse, statsResponse] = await Promise.all([
                this.fetchPriceChanges(params),
                this.fetchPriceStats(params)
            ]);

            if (changesResponse.success && statsResponse.success) {
                this.renderPriceChanges(changesResponse.data);
                this.renderStats(statsResponse.data);
                this.updateCount(changesResponse.count);
            } else {
                this.showErrorState('Failed to load price changes');
            }
        } catch (error) {
            console.error('Error loading price changes:', error);
            this.showErrorState('Network error occurred');
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    /**
     * Get current filter parameters
     */
    getFilterParams() {
        const categoryFilter = document.getElementById('category-filter');
        const daysFilter = document.getElementById('days-filter');
        
        return {
            seller_id: this.sellerId,
            category_id: categoryFilter?.value || '',
            days: daysFilter?.value || '7',
            limit: 20
        };
    }

    /**
     * Fetch price changes from API
     */
    async fetchPriceChanges(params) {
        const url = new URL(this.apiBaseUrl, window.location.origin);
        url.searchParams.append('endpoint', 'changes');
        Object.keys(params).forEach(key => {
            if (params[key]) url.searchParams.append(key, params[key]);
        });

        const response = await fetch(url);
        return await response.json();
    }

    /**
     * Fetch price statistics from API
     */
    async fetchPriceStats(params) {
        const url = new URL(this.apiBaseUrl, window.location.origin);
        url.searchParams.append('endpoint', 'stats');
        Object.keys(params).forEach(key => {
            if (params[key] && key !== 'limit') {
                url.searchParams.append(key, params[key]);
            }
        });

        const response = await fetch(url);
        return await response.json();
    }

    /**
     * Render price changes list
     */
    renderPriceChanges(priceChanges) {
        const listContainer = document.getElementById('price-changes-list');
        if (!listContainer) return;

        if (!priceChanges || priceChanges.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-graph-up" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                    <p class="text-muted mt-2">No price changes found</p>
                    <p class="text-muted small">Try adjusting your filters or check back later</p>
                </div>
            `;
            return;
        }

        const changesHtml = priceChanges.map(change => this.createPriceChangeCard(change)).join('');
        listContainer.innerHTML = `
            <div class="row g-3">
                ${changesHtml}
            </div>
        `;
    }

    /**
     * Create individual price change card
     */
    createPriceChangeCard(change) {
        const directionIcon = change.price_change.direction === 'increase' ? 'arrow-up' : 'arrow-down';
        const directionColor = change.price_change.direction === 'increase' ? 'success' : 'danger';
        const changeSign = change.price_change.direction === 'increase' ? '+' : '-';

        return `
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1" title="${change.product.name}">
                                    ${this.truncateText(change.product.name, 40)}
                                </h6>
                                <small class="text-muted">
                                    by ${change.seller.name} • ${change.product.category.name}
                                </small>
                            </div>
                            ${change.product.image ? 
                                `<img src="${change.product.image}" class="rounded ms-2" style="width: 40px; height: 40px; object-fit: cover;" alt="Product">` : 
                                `<div class="bg-light rounded ms-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>`
                            }
                        </div>
                        
                        <div class="price-change-info">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="old-price text-decoration-line-through text-muted small">
                                        ₱${change.price_change.old_price.toFixed(2)}
                                    </div>
                                    <div class="new-price fw-bold">
                                        ₱${change.price_change.new_price.toFixed(2)}
                                    </div>
                                </div>
                                <div class="text-${directionColor} text-end">
                                    <i class="bi bi-${directionIcon}-circle fs-5"></i>
                                    <div class="fw-bold">
                                        ${changeSign}₱${change.price_change.amount_change.toFixed(2)}
                                    </div>
                                    <small>${changeSign}${Math.abs(change.price_change.percentage_change).toFixed(1)}%</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>${change.time_ago}
                            </small>
                            <button class="btn btn-outline-primary btn-sm" onclick="priceChangesDashboard.viewProductDetails('${change.product.id}')">
                                <i class="bi bi-eye me-1"></i>View
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render statistics
     */
    renderStats(stats) {
        const overall = stats.overall;
        
        if (overall) {
            document.getElementById('price-increases').textContent = overall.price_increases || 0;
            document.getElementById('price-decreases').textContent = overall.price_decreases || 0;
            document.getElementById('avg-change').textContent = 
                overall.avg_change_percentage ? `${parseFloat(overall.avg_change_percentage).toFixed(1)}%` : '0%';
            document.getElementById('active-sellers').textContent = overall.active_sellers || 0;
        }
    }

    /**
     * Update price changes count
     */
    updateCount(count) {
        const countBadge = document.getElementById('price-changes-count');
        if (countBadge) {
            countBadge.textContent = count || 0;
        }
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        const loadingState = document.getElementById('loading-state');
        const refreshBtn = document.getElementById('refresh-btn');
        
        if (loadingState) {
            loadingState.style.display = 'block';
        }
        
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm"></i>';
        }
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        const loadingState = document.getElementById('loading-state');
        const refreshBtn = document.getElementById('refresh-btn');
        
        if (loadingState) {
            loadingState.style.display = 'none';
        }
        
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        }
    }

    /**
     * Show error state
     */
    showErrorState(message) {
        const listContainer = document.getElementById('price-changes-list');
        if (!listContainer) return;

        listContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">${message}</p>
                <button class="btn btn-outline-primary btn-sm" onclick="priceChangesDashboard.loadPriceChanges()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Try Again
                </button>
            </div>
        `;
    }

    /**
     * Start auto refresh
     */
    startAutoRefresh() {
        // Refresh every 5 minutes
        this.refreshInterval = setInterval(() => {
            this.loadPriceChanges();
        }, 5 * 60 * 1000);
    }

    /**
     * Stop auto refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * View product details (placeholder for future implementation)
     */
    viewProductDetails(productId) {
        // You can implement this to show product details modal or navigate to product page
        console.log('View product details:', productId);
        
        // Example: Show notification for now
        if (window.showNotification) {
            window.showNotification(`Product details for ID: ${productId}`, 'info');
        }
    }

    /**
     * Truncate text to specified length
     */
    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    /**
     * Load categories for filter
     */
    async loadCategories() {
        try {
            // You might need to adjust this endpoint based on your API structure
            const response = await fetch('../api/categories.php');
            const data = await response.json();
            
            if (data.success) {
                const categorySelect = document.getElementById('category-filter');
                if (categorySelect) {
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    /**
     * Destroy the dashboard instance
     */
    destroy() {
        this.stopAutoRefresh();
        const section = document.getElementById('price-changes-section');
        if (section) {
            section.remove();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the seller dashboard
    if (document.querySelector('.main-content') && 
        (window.location.pathname.includes('seller') || window.location.pathname.includes('dashboard'))) {
        
        // Create global instance
        window.priceChangesDashboard = new PriceChangesDashboard();
        
        // Load categories for filter
        window.priceChangesDashboard.loadCategories();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.priceChangesDashboard) {
        window.priceChangesDashboard.destroy();
    }
});

// Add custom CSS styles
const priceChangesStyles = `
<style>
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark, #0056b3) 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.price-change-info .old-price {
    font-size: 0.8rem;
}

.price-change-info .new-price {
    font-size: 1.1rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#price-changes-section {
    animation: fadeInUp 0.6s ease-out;
}

.card {
    border-radius: 12px !important;
}

.badge {
    font-size: 0.75em;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .col-md-3 {
        margin-bottom: 1rem;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', priceChangesStyles);