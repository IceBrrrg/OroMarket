<!-- Left Sidebar -->
<div class="sidebar">
    <h4 class="sidebar-title">Filter & Categories</h4>

    <!-- Search Bar -->
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search products..." id="searchInput">
    </div>

    <!-- Categories Filter -->
    <div class="filter-section">
        <h5>Categories</h5>
        <div class="filter-options">
            <div class="filter-option">
                <input type="checkbox" id="fruits" value="fruits">
                <label for="fruits">🍎 Fruits</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="vegetables" value="vegetables">
                <label for="vegetables">🥬 Vegetables</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="meat" value="meat">
                <label for="meat">🥩 Meat</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="fish" value="fish">
                <label for="fish">🐟 Fish</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="dairy" value="dairy">
                <label for="dairy">🥛 Dairy</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="grains" value="grains">
                <label for="grains">🌾 Grains</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="beverages" value="beverages">
                <label for="beverages">🥤 Beverages</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="bakery" value="bakery">
                <label for="bakery">🍞 Bakery</label>
            </div>
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

    <!-- Rating Filter -->
    <div class="filter-section">
        <h5>Rating</h5>
        <div class="filter-options">
            <div class="filter-option">
                <input type="checkbox" id="rating5" value="5">
                <label for="rating5">⭐⭐⭐⭐⭐ 5 Stars</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="rating4" value="4">
                <label for="rating4">⭐⭐⭐⭐ 4+ Stars</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="rating3" value="3">
                <label for="rating3">⭐⭐⭐ 3+ Stars</label>
            </div>
        </div>
    </div>

    <!-- Availability Filter -->
    <div class="filter-section">
        <h5>Availability</h5>
        <div class="filter-options">
            <div class="filter-option">
                <input type="checkbox" id="inStock" value="inStock">
                <label for="inStock">In Stock</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="onSale" value="onSale">
                <label for="onSale">On Sale</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="organic" value="organic">
                <label for="organic">Organic</label>
            </div>
        </div>
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