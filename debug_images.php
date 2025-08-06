<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "=== DEBUGGING PRODUCT IMAGES ===\n\n";

// Test 1: Check raw database data
echo "1. Raw database image data:\n";
try {
    $stmt = $pdo->query("SELECT id, name, primary_image FROM (
        SELECT p.id, p.name, pi.image_path as primary_image
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_active = 1
        LIMIT 5
    ) as subquery");
    
    $raw_data = $stmt->fetchAll();
    foreach ($raw_data as $row) {
        echo "   Product: {$row['name']}\n";
        echo "   Raw image path: " . ($row['primary_image'] ?? 'NULL') . "\n";
        echo "   File exists: " . (file_exists($row['primary_image'] ?? '') ? 'YES' : 'NO') . "\n";
        echo "   ---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 2: Check processed image URLs
echo "\n2. Processed image URLs from fetchProducts:\n";
try {
    $products = fetchProducts(5);
    foreach ($products as $product) {
        echo "   Product: {$product['name']}\n";
        echo "   Primary image: " . ($product['primary_image'] ?? 'NULL') . "\n";
        echo "   Image URL: " . ($product['image_url'] ?? 'NULL') . "\n";
        echo "   File exists: " . (file_exists($product['image_url'] ?? '') ? 'YES' : 'NO') . "\n";
        echo "   ---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 3: Check uploads directory
echo "\n3. Uploads directory check:\n";
$uploads_dir = 'uploads/products/';
if (is_dir($uploads_dir)) {
    echo "   Uploads directory exists: YES\n";
    $files = scandir($uploads_dir);
    echo "   Files in uploads: " . count($files) . "\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "     - $file\n";
        }
    }
} else {
    echo "   Uploads directory exists: NO\n";
}

// Test 4: Test getProductImageUrl function
echo "\n4. Testing getProductImageUrl function:\n";
$test_paths = [
    null,
    '',
    'uploads/products/test.jpg',
    'test.jpg',
    '../uploads/products/test.jpg'
];

foreach ($test_paths as $path) {
    $result = getProductImageUrl($path);
    echo "   Input: " . ($path ?? 'NULL') . "\n";
    echo "   Output: $result\n";
    echo "   ---\n";
}

echo "\n=== END DEBUG ===\n";
?> 