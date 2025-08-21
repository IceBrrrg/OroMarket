<?php
// Fetch categories for the filter
try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $stmt->execute();
    $filter_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for filter: " . $e->getMessage());
    $filter_categories = [];
}

// Fetch sellers for the filter
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.first_name, u.last_name, 
               CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM users u 
        INNER JOIN products p ON u.id = p.seller_id 
        WHERE u.user_type = 'seller' AND u.is_active = 1 
        ORDER BY u.first_name ASC
    ");
    $stmt->execute();
    $filter_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching sellers for filter: " . $e->getMessage());
    $filter_sellers = [];
}
?>

<!-- Left Sidebar -->
<div class="sidebar">
    <h4 class="sidebar-title">Filter Options</h4>

    <!-- Categories Filter -->
    <div class="filter-section">
        <h5>Categories</h5>
        <div class="filter-options">
            <div class="filter-option">
                <input type="checkbox" id="category_all" name="category" value="" checked>
                <label for="category_all">All Categories</label>
            </div>
            <?php foreach ($filter_categories as $category): ?>
            <div class="filter-option">
                <input type="checkbox" id="category_<?php echo $category['id']; ?>" 
                       name="category" value="<?php echo $category['id']; ?>">
                <label for="category_<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Price Range Filter -->
    <div class="filter-section">
        <h5>Price Range</h5>
        <div class="price-range">
            <div class="range-group">
                <input type="number" class="form-control form-control-sm" 
                       placeholder="₱ Min" id="minPrice" min="0" step="0.01">
            </div>
            <span>-</span>
            <div class="range-group">
                <input type="number" class="form-control form-control-sm" 
                       placeholder="₱ Max" id="maxPrice" min="0" step="0.01">
            </div>
        </div>
    </div>

    <!-- Seller Filter -->
    <div class="filter-section">
        <h5>View</h5>
        <select id="sellerFilter" class="filter-select" onchange="handleViewChange(this.value)">
            <option value="">All Products</option>
            <option value="scroll-to-sellers">All Sellers</option>
            <option value="">All Products & Sellers</option>
            <?php foreach ($filter_sellers as $seller): ?>
            <option value="<?php echo $seller['id']; ?>">
                <?php echo htmlspecialchars($seller['full_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Featured Filter -->
    <div class="filter-section">
        <h5>Special Items</h5>
        <div class="filter-options">
            <div class="filter-option">
                <input type="checkbox" id="featured_only" name="featured" value="1">
                <label for="featured_only">Featured Products Only</label>
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

    <!-- Active Filters Display -->
    <div class="active-filters" id="activeFilters" style="display: none;">
        <h6>Active Filters:</h6>
        <div class="active-filter-tags" id="activeFilterTags"></div>
    </div>
</div>

<style>
.sidebar {
    background: #fff;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    position: sticky;
    top: 20px;
}

.sidebar-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #2c3e50;
    border-bottom: 2px solid #e8f4f8;
    padding-bottom: 0.5rem;
}

.filter-section {
    margin-bottom: 2rem;
}

.filter-section h5 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #34495e;
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-option input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #52c41a;
    cursor: pointer;
}

.filter-option label {
    font-size: 0.9rem;
    color: #5a6c7d;
    cursor: pointer;
    flex: 1;
    margin: 0;
}

.filter-option:hover label {
    color: #2c3e50;
}

.price-range {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.range-group {
    flex: 1;
}

.form-control-sm {
    padding: 0.5rem;
    border: 2px solid #e8f4f8;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.form-control-sm:focus {
    outline: none;
    border-color: #52c41a;
    box-shadow: 0 0 0 3px rgba(82, 196, 26, 0.1);
}

.filter-select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e8f4f8;
    border-radius: 8px;
    font-size: 0.9rem;
    background-color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #52c41a;
    box-shadow: 0 0 0 3px rgba(82, 196, 26, 0.1);
}

.apply-filters-btn, .clear-filters-btn {
    width: 100%;
    padding: 0.875rem 1rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.apply-filters-btn {
    background: linear-gradient(135deg, #52c41a, #45a820);
    color: white;
}

.apply-filters-btn:hover {
    background: linear-gradient(135deg, #45a820, #3d9419);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(82, 196, 26, 0.3);
}

.clear-filters-btn {
    background: #f8f9fa;
    color: #6c757d;
    border: 2px solid #e9ecef;
}

.clear-filters-btn:hover {
    background: #e9ecef;
    color: #495057;
    transform: translateY(-1px);
}

.active-filters {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid #e8f4f8;
}

.active-filters h6 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #34495e;
    margin-bottom: 0.75rem;
}

.active-filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-tag {
    background: #e8f4f8;
    color: #2c3e50;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.filter-tag .remove-filter {
    cursor: pointer;
    color: #95a5a6;
    font-weight: bold;
}

.filter-tag .remove-filter:hover {
    color: #e74c3c;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        position: static;
        max-height: none;
        margin-bottom: 1rem;
    }
    
    .price-range {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .price-range span {
        display: none;
    }
}</style>