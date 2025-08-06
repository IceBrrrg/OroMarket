# Customer Frontend - Product Display Implementation

## Overview
This document describes the implementation of product fetching and display functionality in the customer folder of the OroMarket project.

## Features Implemented

### 1. Product Fetching (`customer/fetch_products.php`)
- **Main Function**: `fetchProducts($limit, $category_id, $is_featured, $seller_id, $search, $min_price, $max_price, $sort_by, $sort_order)`
- **Features**:
  - Fetch products with various filters (category, price range, search, etc.)
  - Support for sorting by price, name, rating, and date
  - Pagination support
  - Product image handling with fallback images
  - Price formatting
  - Stock quantity checking
  - Featured product identification

### 2. Category Management
- **Function**: `fetchCategories()`
- **Features**:
  - Fetch all active categories
  - Display product count per category
  - Category filtering functionality

### 3. Seller Information
- **Function**: `getSellers($limit)`
- **Features**:
  - Fetch approved sellers with product counts
  - Seller ratings (placeholder for future implementation)
  - Seller profile images

### 4. API Endpoint (`customer/api/products.php`)
- **RESTful API** for product operations
- **Endpoints**:
  - `GET /api/products.php` - Get all products
  - `GET /api/products.php?action=search&q=query` - Search products
  - `GET /api/products.php?action=category&category_id=id` - Get products by category
  - `GET /api/products.php?action=featured` - Get featured products
  - `POST /api/products.php` - Add to cart/favorites

### 5. Frontend Display (`customer/index.php`)
- **Dynamic Product Grid**: Displays products fetched from database
- **Category Filtering**: Click categories to filter products
- **Search Functionality**: Real-time search with debouncing
- **Sorting Options**: Sort by relevance, price, rating, newest
- **Responsive Design**: Works on mobile and desktop
- **Product Cards**: Display product images, prices, stock, seller info

### 6. Interactive Features (`customer/js/customer.js`)
- **Category Filtering**: Click to filter products by category
- **Search**: Real-time search with 500ms debounce
- **Sorting**: Dynamic sorting with API calls
- **Add to Cart**: Product cart functionality
- **Favorites**: Add/remove from favorites
- **Notifications**: Success/error notifications
- **Loading States**: Loading spinners and error handling

### 7. Enhanced Styling (`customer/css/index.css`)
- **Product Badges**: Featured and low stock indicators
- **Category Active States**: Visual feedback for selected categories
- **Loading States**: Spinners and error messages
- **Notifications**: Toast-style notifications
- **Responsive Grid**: Adaptive product grid layout

## Database Requirements

### Required Tables
1. **products** - Main product information
2. **categories** - Product categories
3. **sellers** - Seller information
4. **product_images** - Product images (optional)

### Optional Tables (for future enhancement)
1. **product_reviews** - Product reviews and ratings
2. **favorites** - User favorites
3. **cart** - Shopping cart

## Usage

### 1. Basic Product Display
```php
require_once 'customer/fetch_products.php';

// Fetch products
$products = fetchProducts(12); // Get 12 products

// Display products
foreach ($products as $product) {
    echo "<div class='product-card'>";
    echo "<h3>{$product['name']}</h3>";
    echo "<p>Price: {$product['formatted_price']}</p>";
    echo "<p>Stock: {$product['stock_quantity']}</p>";
    echo "</div>";
}
```

### 2. Category Filtering
```php
// Get all categories
$categories = fetchCategories();

// Get products by category
$category_products = getProductsByCategory($category_id, 12);
```

### 3. Search Products
```php
// Search products
$search_results = searchProducts($search_term, $category_id, $min_price, $max_price);
```

### 4. API Usage
```javascript
// Fetch products via API
fetch('customer/api/products.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderProducts(data.data);
        }
    });

// Search products
fetch('customer/api/products.php?action=search&q=apple')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderProducts(data.data);
        }
    });
```

## File Structure
```
customer/
├── index.php              # Main customer page
├── fetch_products.php     # Product fetching functions
├── api/
│   └── products.php       # REST API endpoints
├── js/
│   ├── index.js          # Original JavaScript
│   └── customer.js       # New customer interactions
└── css/
    └── index.css         # Enhanced styling
```

## Testing

### 1. Database Connection Test
```bash
php test_db_pdo.php
```

### 2. Product Fetching Test
```bash
php simple_test.php
```

### 3. Full Frontend Test
```bash
php test_customer_page.php
```

### 4. Customer Page
Visit: `http://localhost:8000/customer/index.php`

## Current Status

### ✅ Working Features
- Database connection
- Product fetching and display
- Category filtering
- Search functionality
- Sort functionality
- Responsive design
- Product cards with images
- Stock information
- Seller information
- API endpoints

### 🔄 Future Enhancements
- Product reviews and ratings
- Shopping cart functionality
- User authentication
- Wishlist/favorites
- Product comparison
- Advanced filtering
- Product recommendations
- Order management

## Troubleshooting

### Common Issues

1. **No products displayed**
   - Check if products exist in database
   - Verify seller status is 'approved'
   - Check if products are active (is_active = 1)

2. **Database connection errors**
   - Verify database credentials in `includes/db_connect.php`
   - Check if XAMPP MySQL service is running
   - Ensure database `oroquieta_marketplace` exists

3. **Missing images**
   - Check `uploads/products/` directory
   - Verify image paths in database
   - Fallback images are provided automatically

4. **API errors**
   - Check file permissions
   - Verify API endpoint URLs
   - Check browser console for JavaScript errors

## Support
For issues or questions, check the test files in the root directory or review the error logs in your web server configuration. 