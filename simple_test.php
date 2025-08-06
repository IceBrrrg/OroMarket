<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "Testing fetchProducts function...\n";

try {
    $products = fetchProducts(5);
    echo "Found " . count($products) . " products\n";
    
    foreach ($products as $product) {
        echo "- {$product['name']}: {$product['formatted_price']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 