<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "Testing image function...\n";

$test_path = 'uploads/products/product_5_1754310786_0.jpeg';
echo "Test path: $test_path\n";
echo "File exists: " . (file_exists($test_path) ? 'YES' : 'NO') . "\n";

$result = getProductImageUrl($test_path);
echo "Function result: $result\n";

echo "Done.\n";
?> 