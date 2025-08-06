<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "=== TESTING IMAGE URL PROCESSING ===\n\n";

// Test the getProductImageUrl function directly
echo "1. Testing getProductImageUrl function:\n";
$test_paths = [
    'uploads/products/product_5_1754310786_0.jpeg',
    'uploads/products/product_6_1754465348_0.jpg',
    'uploads/products/product_7_1754482042_0.jpg'
];

foreach ($test_paths as $path) {
    $result = getProductImageUrl($path);
    echo "   Input: $path\n";
    echo "   Output: $result\n";
    echo "   File exists (output): " . (file_exists($result) ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

// Test with fetchProducts
echo "\n2. Testing fetchProducts image URLs:\n";
$products = fetchProducts(3);
foreach ($products as $product) {
    echo "   Product: {$product['name']}\n";
    echo "   Primary image: " . ($product['primary_image'] ?? 'NULL') . "\n";
    echo "   Image URL: " . ($product['image_url'] ?? 'NULL') . "\n";
    echo "   File exists: " . (file_exists($product['image_url'] ?? '') ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

// Test file existence with different paths
echo "\n3. Testing file existence with different paths:\n";
$test_file = 'product_5_1754310786_0.jpeg';
$paths_to_test = [
    "uploads/products/$test_file",
    "../uploads/products/$test_file",
    "customer/../uploads/products/$test_file"
];

foreach ($paths_to_test as $path) {
    echo "   Path: $path\n";
    echo "   Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

echo "\n=== END TEST ===\n";
?> 