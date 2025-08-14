// price-monitoring.js - Client-side price monitoring functionality

class PriceMonitor {
    constructor() {
        this.apiBase = 'price_history_api.php';
        this.chartInstances = new Map();
        this.init();
    }

    init() {
        this.addPriceAlertButton();
        this.addPriceHistorySection();
        this.loadPriceData();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Price alert form submission
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('price-alert-form')) {
                e.preventDefault();
                this.handlePriceAlert(e.target);
            }
        });

        // Price history tab switching
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('price-period-btn')) {
                this.switchPricePeriod(e.target);
            }
        });
    }

    addPriceAlertButton() {
        const productActions = document.querySelector('.product-actions');
        if (!productActions) return;

        const alertButton = `
            <button class="btn btn-outline-warning price-alert-btn" onclick="priceMonitor.showPriceAlertModal()">
                <i class="fas fa-bell"></i>
                <span>Set Price Alert</span>
            </button>
        `;
        
        productActions.insertAdjacentHTML('beforeend', alertButton);
    }

    addPriceHistorySection() {
        const productDetails = document.querySelector('.product-details-container');
        if (!productDetails) return;

        const priceHistorySection = `
            <div class="price-history-section">
                <h3><i class="fas fa-chart-line"></i> Price History & Analytics</h3>
                
                <div class="price-stats-grid">
                    <div class="price-stat-card">
                        <div class="stat-icon"><i class="fas fa-arrow-trend-up text-success"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="highest-price">-</span>
                            <span class="stat-label">Highest Price</span>
                        </div>
                    </div>
                    
                    <div class="price-stat-card">
                        <div class="stat-icon"><i class="fas fa-arrow-trend-down text-danger"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="lowest-price">-</span>
                            <span class="stat-label">Lowest Price</span>
                        </div>
                    </div>
                    
                    <div class="price-stat-card">
                        <div class="stat-icon"><i class="fas fa-calculator text-info"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="average-price">-</span>
                            <span class="stat-label">Average Price</span>
                        </div>
                    </div>
                    
                    <div class="price-stat-card">
                        <div class="stat-icon"><i class="fas fa-exchange-alt text-warning"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="price-changes">-</span>
                            <span class="stat-label">Price Changes</span>
                        </div>
                    </div>
                </div>

                <div class="price-chart-container">
                    <div class="price-period-tabs">
                        <button class="price-period-btn active" data-days="7">7 Days</button>
                        <button class="price-period-btn" data-days="30">30 Days</button>
                        <button class="price-period-btn" data-days="90">90 Days</button>
                    </div>
                    <canvas id="price-history-chart" width="400" height="200"></canvas>
                </div>

                <div class="price-history-table">
                    <h4>Recent Price Changes</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Old Price</th>
                                    <th>New Price</th>
                                    <th>Change</th>
                                </tr>
                            </thead>
                            <tbody id="price-history-tbody">
                                <tr><td colspan="4" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        productDetails.insertAdjacentHTML('beforeend', priceHistorySection);
    }

    async loadPriceData() {
        const productId = this.getProductId();
        if (!productId) return;

        try {
            // Load price statistics
            await this.loadPriceStatistics(productId);
            
            // Load price chart
            await this.loadPriceChart(productId, 30);
            
            // Load price history table
            await this.loadPriceHistory(productId);
            
        } catch (error) {
            console.error('Error loading price data:', error);
        }
    }

    async loadPriceStatistics(productId) {
        try {
            const response = await fetch(`${this.apiBase}?action=statistics&product_id=${productId}&period=30`);
            const result = await response.json();

            if (result.success && result.data) {
                const stats = result.data;
                document.getElementById('highest-price').textContent = `₱${parseFloat(stats.highest_price || 0).toFixed(2)}`;
                document.getElementById('lowest-price').textContent = `₱${parseFloat(stats.lowest_price || 0).toFixed(2)}`;
                document.getElementById('average-price').textContent = `₱${parseFloat(stats.average_price || 0).toFixed(2)}`;
                document.getElementById('price-changes').textContent = stats.total_changes || 0;
            }
        } catch (error) {
            console.error('Error loading price statistics:', error);
        }
    }

    async loadPriceChart(productId, days = 30) {
        try {
            const response = await fetch(`${this.apiBase}?action=chart_data&product_id=${productId}&days=${days}`);
            const result = await response.json();

            if (result.success && result.data) {
                this.renderPriceChart(result.data);
            }
        } catch (error) {
            console.error('Error loading price chart:', error);
        }
    }

    renderPriceChart(data) {
        const ctx = document.getElementById('price-history-chart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.chartInstances.has('priceChart')) {
            this.chartInstances.get('priceChart').destroy();
        }

        const labels = data.map(item => {
            const date = new Date(item.datetime);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const prices = data.map(item => parseFloat(item.price));

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Price (₱)',
                    data: prices,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Price History'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        this.chartInstances.set('priceChart', chart);
    }

    async loadPriceHistory(productId) {
        try {
            const response = await fetch(`${this.apiBase}?action=history&product_id=${productId}&days=30`);
            const result = await response.json();

            if (result.success && result.data) {
                this.renderPriceHistoryTable(result.data);
            }
        } catch (error) {
            console.error('Error loading price history:', error);
        }
    }

    renderPriceHistoryTable(history) {
        const tbody = document.getElementById('price-history-tbody');
        if (!tbody) return;

        if (history.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No price changes recorded</td></tr>';
            return;
        }

        const rows = history.map(item => {
            const changeValue = parseFloat(item.new_price) - parseFloat(item.old_price);
            const changePercent = parseFloat(item.percentage_change);
            const changeClass = changeValue > 0 ? 'text-success' : 'text-danger';
            const changeIcon = changeValue > 0 ? '↑' : '↓';
            
            return `
                <tr>
                    <td>${new Date(item.changed_at).toLocaleDateString()}</td>
                    <td>₱${parseFloat(item.old_price).toFixed(2)}</td>
                    <td>₱${parseFloat(item.new_price).toFixed(2)}</td>
                    <td class="${changeClass}">
                        ${changeIcon} ₱${Math.abs(changeValue).toFixed(2)} 
                        (${Math.abs(changePercent).toFixed(2)}%)
                    </td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = rows;
    }

    switchPricePeriod(button) {
        // Update active button
        document.querySelectorAll('.price-period-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Reload chart with new period
        const days = parseInt(button.dataset.days);
        const productId = this.getProductId();
        if (productId) {
            this.loadPriceChart(productId, days);
        }
    }

    showPriceAlertModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'priceAlertModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Set Price Alert</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form class="price-alert-form">
                            <div class="mb-3">
                                <label for="alertEmail" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="alertEmail" required>
                            </div>
                            <div class="mb-3">
                                <label for="alertName" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="alertName" required>
                            </div>
                            <div class="mb-3">
                                <label for="alertType" class="form-label">Alert Type</label>
                                <select class="form-select" id="alertType" required>
                                    <option value="price_drop">Notify when price drops</option>
                                    <option value="price_increase">Notify when price increases</option>
                                    <option value="target_price">Notify when price reaches target</option>
                                </select>
                            </div>
                            <div class="mb-3" id="targetPriceGroup" style="display: none;">
                                <label for="targetPrice" class="form-label">Target Price (₱)</label>
                                <input type="number" class="form-control" id="targetPrice" step="0.01" min="0">
                            </div>
                            <div class="mb-3" id="thresholdGroup">
                                <label for="threshold" class="form-label">Threshold (%)</label>
                                <input type="number" class="form-control" id="threshold" step="0.1" min="0.1" max="100" value="5">
                                <div class="form-text">Alert when price changes by this percentage</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Alert</button>
                        </form>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Setup alert type change handler
        document.getElementById('alertType').addEventListener('change', (e) => {
            const targetGroup = document.getElementById('targetPriceGroup');
            const thresholdGroup = document.getElementById('thresholdGroup');
            
            if (e.target.value === 'target_price') {
                targetGroup.style.display = 'block';
                thresholdGroup.style.display = 'none';
            } else {
                targetGroup.style.display = 'none';
                thresholdGroup.style.display = 'block';
            }
        });

        // Show modal
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();

        // Clean up modal when hidden
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    async handlePriceAlert(form) {
        const formData = new FormData(form);
        const productId = this.getProductId();
        
        const alertData = {
            product_id: productId,
            email: document.getElementById('alertEmail').value,
            name: document.getElementById('alertName').value,
            alert_type: document.getElementById('alertType').value,
            target_price: document.getElementById('targetPrice').value || null,
            threshold: document.getElementById('threshold').value || null
        };

        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_alert',
                    ...alertData
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Price alert created successfully!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('priceAlertModal')).hide();
            } else {
                this.showAlert('Failed to create price alert', 'error');
            }
        } catch (error) {
            console.error('Error creating price alert:', error);
            this.showAlert('Error creating price alert', 'error');
        }
    }

    getProductId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || null;
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }

    // Method to update price in real-time (if needed)
    async refreshCurrentPrice() {
        const productId = this.getProductId();
        if (!productId) return;

        try {
            const response = await fetch(`get_product.php?id=${productId}`);
            const result = await response.json();
            
            if (result.success) {
                const currentPriceElement = document.querySelector('.price-amount');
                if (currentPriceElement) {
                    currentPriceElement.textContent = `₱${parseFloat(result.data.price).toFixed(2)}`;
                }
                
                // Update price change indicator
                this.updatePriceChangeIndicator(result.data);
            }
        } catch (error) {
            console.error('Error refreshing price:', error);
        }
    }

    updatePriceChangeIndicator(productData) {
        const priceSection = document.querySelector('.product-price-section');
        if (!priceSection || !productData.previous_price) return;

        // Remove existing indicator
        const existingIndicator = priceSection.querySelector('.price-change-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        if (productData.price_change !== 'no_change') {
            const changePercentage = productData.price_change_percentage || 0;
            const isIncrease = productData.price_change === 'up';
            
            const indicator = document.createElement('div');
            indicator.className = `price-change-indicator ${isIncrease ? 'price-up' : 'price-down'}`;
            indicator.innerHTML = `
                <i class="fas fa-arrow-${isIncrease ? 'up' : 'down'}"></i>
                <span>${Math.abs(changePercentage).toFixed(2)}%</span>
                <small>vs last price</small>
            `;
            
            priceSection.appendChild(indicator);
        }
    }
}

// Price Trends Widget for homepage/marketplace
class PriceTrendsWidget {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.apiBase = 'price_history_api.php';
        this.init();
    }

    async init() {
        if (!this.container) return;
        
        await this.loadTrendingProducts();
        this.setupAutoRefresh();
    }

    async loadTrendingProducts() {
        try {
            const response = await fetch(`${this.apiBase}?action=trends&days=7`);
            const result = await response.json();

            if (result.success && result.data) {
                this.renderTrendsWidget(result.data);
            }
        } catch (error) {
            console.error('Error loading trending products:', error);
        }
    }

    renderTrendsWidget(trends) {
        if (!trends || trends.length === 0) {
            this.container.innerHTML = '<p class="text-muted">No recent price changes</p>';
            return;
        }

        const trendsHtml = `
            <div class="price-trends-widget">
                <h4><i class="fas fa-trending-up"></i> Price Trends</h4>
                <div class="trends-list">
                    ${trends.map(product => this.renderTrendItem(product)).join('')}
                </div>
                <div class="trends-footer">
                    <small class="text-muted">Updated ${new Date().toLocaleTimeString()}</small>
                </div>
            </div>
        `;

        this.container.innerHTML = trendsHtml;
    }

    renderTrendItem(product) {
        const sellerName = product.business_name || `${product.first_name} ${product.last_name}`;
        const changeClass = product.price_trend === 'up' ? 'text-success' : 'text-danger';
        const changeIcon = product.price_trend === 'up' ? 'fa-arrow-up' : 'fa-arrow-down';
        const changePercentage = Math.abs(product.price_change_percentage || 0);

        return `
            <div class="trend-item">
                <div class="trend-product">
                    <img src="${product.image_path ? '../' + product.image_path : '../assets/img/fruite-item-1.jpg'}" 
                         alt="${product.name}" class="trend-image">
                    <div class="trend-info">
                        <h6><a href="view_product.php?id=${product.id}">${product.name}</a></h6>
                        <small class="text-muted">by ${sellerName}</small>
                    </div>
                </div>
                <div class="trend-price">
                    <span class="current-price">₱${parseFloat(product.current_price).toFixed(2)}</span>
                    <div class="price-change ${changeClass}">
                        <i class="fas ${changeIcon}"></i>
                        ${changePercentage.toFixed(2)}%
                    </div>
                </div>
            </div>
        `;
    }

    setupAutoRefresh() {
        // Refresh trends every 5 minutes
        setInterval(() => {
            this.loadTrendingProducts();
        }, 5 * 60 * 1000);
    }
}

// Initialize price monitoring when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize price monitor on product pages
    if (document.querySelector('.product-details-container')) {
        window.priceMonitor = new PriceMonitor();
    }

    // Initialize price trends widget if container exists
    if (document.getElementById('price-trends-widget')) {
        window.priceTrendsWidget = new PriceTrendsWidget('price-trends-widget');
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PriceMonitor, PriceTrendsWidget };
}