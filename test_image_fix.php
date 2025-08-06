<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "Testing image fix...\n";

// Test the function directly
$test_path = 'uploads/products/product_5_1754310786_0.jpeg';
$result = getProductImageUrl($test_path);
echo "Input: $test_path\n";
echo "Output: $result\n";

// Test with fetchProducts
$products = fetchProducts(3);
echo "\nProducts with images:\n";
foreach ($products as $product) {
    echo "Product: {$product['name']}\n";
    echo "Image URL: {$product['image_url']}\n";
    echo "---\n";
}

echo "Test completed.\n";
?> 