<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "=== TESTING FILE PATH CONSTRUCTION ===\n\n";

// Test the file path construction
$test_image = 'uploads/products/product_5_1754310786_0.jpeg';

echo "1. Testing file path construction:\n";
echo "   Original path: $test_image\n";
echo "   __DIR__: " . __DIR__ . "\n";
echo "   Constructed path: " . __DIR__ . '/../' . $test_image . "\n";
echo "   File exists (constructed): " . (file_exists(__DIR__ . '/../' . $test_image) ? 'YES' : 'NO') . "\n";
echo "   File exists (relative): " . (file_exists('../' . $test_image) ? 'YES' : 'NO') . "\n";
echo "   File exists (absolute): " . (file_exists($test_image) ? 'YES' : 'NO') . "\n";

echo "\n2. Testing getProductImageUrl function:\n";
$result = getProductImageUrl($test_image);
echo "   Input: $test_image\n";
echo "   Output: $result\n";

echo "\n3. Testing with different path approaches:\n";
$paths_to_test = [
    $test_image,
    '../' . $test_image,
    __DIR__ . '/../' . $test_image,
    realpath(__DIR__ . '/../' . $test_image)
];

foreach ($paths_to_test as $path) {
    echo "   Path: $path\n";
    echo "   Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

echo "\n4. Current working directory:\n";
echo "   getcwd(): " . getcwd() . "\n";
echo "   __DIR__: " . __DIR__ . "\n";

echo "\n=== END TEST ===\n";
?> 