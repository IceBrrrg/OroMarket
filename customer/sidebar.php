<!-- Left Sidebar -->
<div class="sidebar">
    <h4 class="sidebar-title">Filter Options</h4>

    <!-- Categories Filter -->
    <div class="filter-section">
        <h5>Categories</h5>
        <div class="filter-options">
            
        </div>
    </div>

    <!-- Price Range Filter -->
    <div class="filter-section">
        <h5>Price Range</h5>
        <div class="price-range">
            <div class="range-group">
                <input type="number" class="form-control form-control-sm" placeholder="₱ Min" id="minPrice" min="0">
            </div>
            <span>-</span>
            <div class="range-group">
                <input type="number" class="form-control form-control-sm" placeholder="₱ Max" id="maxPrice" min="0">
            </div>
        </div>
    </div>

    <!-- Seller Filter -->
    <div class="filter-section">
        <h5>Seller</h5>
        <select id="sellerFilter" class="filter-select">
            <option value="">All Sellers</option>
            <option value="seller1">Fresh Market Store</option>
            <option value="seller2">Organic Farm</option>
            <option value="seller3">Local Fishery</option>
            <option value="seller4">Butcher Shop</option>
            <option value="seller5">Bakery Corner</option>
        </select>
    </div>


    <!-- Apply Filters Button -->
    <button class="apply-filters-btn" onclick="applyFilters()">
        <i class="fas fa-filter"></i>
        Apply Filters
    </button>

    <!-- Clear Filters Button -->
    <button class="clear-filters-btn" onclick="clearFilters()">
        <i class="fas fa-times"></i>
        Clear All
    </button>
</div>