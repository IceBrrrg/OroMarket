<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "=== DEBUGGING getProductImageUrl FUNCTION ===\n\n";

// Test 1: Check what the function returns for actual product images
echo "1. Testing with actual product images:\n";
$test_images = [
    'uploads/products/product_5_1754310786_0.jpeg',
    'uploads/products/product_6_1754465348_0.jpg',
    'uploads/products/product_7_1754482042_0.jpg',
    'uploads/products/product_8_1754483443_0.jpeg'
];

foreach ($test_images as $image_path) {
    echo "   Input: $image_path\n";
    echo "   File exists: " . (file_exists($image_path) ? 'YES' : 'NO') . "\n";
    $result = getProductImageUrl($image_path);
    echo "   Function output: $result\n";
    echo "   ---\n";
}

// Test 2: Check what fetchProducts returns
echo "\n2. Testing fetchProducts image URLs:\n";
$products = fetchProducts(4);
foreach ($products as $product) {
    echo "   Product: {$product['name']}\n";
    echo "   Primary image: " . ($product['primary_image'] ?? 'NULL') . "\n";
    echo "   Image URL: " . ($product['image_url'] ?? 'NULL') . "\n";
    echo "   ---\n";
}

// Test 3: Check if the function is being called correctly
echo "\n3. Testing function logic:\n";
$test_path = 'uploads/products/product_5_1754310786_0.jpeg';
echo "   Test path: $test_path\n";
echo "   Starts with uploads/: " . (strpos($test_path, 'uploads/') === 0 ? 'YES' : 'NO') . "\n";
echo "   File exists: " . (file_exists($test_path) ? 'YES' : 'NO') . "\n";

if (strpos($test_path, 'uploads/') === 0) {
    $web_path = '/' . $test_path;
    echo "   Web path would be: $web_path\n";
    if (file_exists($test_path)) {
        echo "   Should return: $web_path\n";
    } else {
        echo "   Should return: fallback image\n";
    }
}

echo "\n=== END DEBUG ===\n";
?> 