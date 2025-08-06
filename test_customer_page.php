<?php
// Test page to verify customer frontend functionality
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Customer Frontend Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .product-card { border: 1px solid #eee; border-radius: 8px; padding: 15px; }
        .product-image { width: 100%; height: 150px; background: #f0f0f0; border-radius: 4px; margin-bottom: 10px; }
        .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin: 20px 0; }
        .category-item { text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Customer Frontend Test</h1>";

// Test 1: Database Connection
echo "<div class='test-section'>
    <h2>1. Database Connection Test</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Products Fetching
echo "<div class='test-section'>
    <h2>2. Products Fetching Test</h2>";
try {
    $products = fetchProducts(10);
    echo "<p class='success'>✅ Successfully fetched " . count($products) . " products</p>";
    
    if (count($products) > 0) {
        echo "<div class='product-grid'>";
        foreach (array_slice($products, 0, 4) as $product) {
            echo "<div class='product-card'>
                <div class='product-image'></div>
                <h3>{$product['name']}</h3>
                <p><strong>Price:</strong> {$product['formatted_price']}</p>
                <p><strong>Category:</strong> {$product['category_name']}</p>
                <p><strong>Seller:</strong> {$product['seller_full_name']}</p>
                <p><strong>Stock:</strong> {$product['stock_quantity']}</p>
            </div>";
        }
        echo "</div>";
    } else {
        echo "<p class='info'>ℹ️ No products found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Products fetching failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Categories Fetching
echo "<div class='test-section'>
    <h2>3. Categories Fetching Test</h2>";
try {
    $categories = fetchCategories();
    echo "<p class='success'>✅ Successfully fetched " . count($categories) . " categories</p>";
    
    if (count($categories) > 0) {
        echo "<div class='category-grid'>";
        foreach ($categories as $category) {
            echo "<div class='category-item'>
                <div style='font-size: 2rem;'>{$category['name'][0]}</div>
                <strong>{$category['name']}</strong>
                <br><small>({$category['product_count']} products)</small>
            </div>";
        }
        echo "</div>";
    } else {
        echo "<p class='info'>ℹ️ No categories found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Categories fetching failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Sellers Fetching
echo "<div class='test-section'>
    <h2>4. Sellers Fetching Test</h2>";
try {
    $sellers = getSellers(5);
    echo "<p class='success'>✅ Successfully fetched " . count($sellers) . " sellers</p>";
    
    if (count($sellers) > 0) {
        echo "<div class='product-grid'>";
        foreach ($sellers as $seller) {
            echo "<div class='product-card'>
                <h3>{$seller['full_name']}</h3>
                <p><strong>Products:</strong> {$seller['product_count']}</p>
                <p><strong>Rating:</strong> {$seller['rating']}/5</p>
            </div>";
        }
        echo "</div>";
    } else {
        echo "<p class='info'>ℹ️ No sellers found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Sellers fetching failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: API Endpoint Test
echo "<div class='test-section'>
    <h2>5. API Endpoint Test</h2>
    <p>Testing the API endpoint: <a href='customer/api/products.php' target='_blank'>customer/api/products.php</a></p>
    <p>This should return JSON data with products.</p>
</div>";

// Test 6: Customer Page Test
echo "<div class='test-section'>
    <h2>6. Customer Page Test</h2>
    <p>Test the actual customer page: <a href='customer/index.php' target='_blank'>customer/index.php</a></p>
    <p>This should display the full customer interface with products.</p>
</div>";

echo "</body>
</html>";
?> 