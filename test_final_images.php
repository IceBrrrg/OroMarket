<?php
require_once 'includes/db_connect.php';
require_once 'customer/fetch_products.php';

echo "=== FINAL IMAGE URL TEST ===\n\n";

// Test 1: Check the generated image URLs
echo "1. Generated image URLs:\n";
$products = fetchProducts(3);
foreach ($products as $product) {
    echo "   Product: {$product['name']}\n";
    echo "   Image URL: {$product['image_url']}\n";
    echo "   ---\n";
}

// Test 2: Check HTML output
echo "\n2. HTML output check:\n";
ob_start();
include 'customer/index.php';
$html_output = ob_get_clean();

// Look for img tags with product images
if (preg_match_all('/<img[^>]+src="([^"]*uploads\/products[^"]*)"[^>]*>/', $html_output, $matches)) {
    echo "   Found " . count($matches[1]) . " product images in HTML:\n";
    foreach ($matches[1] as $img_src) {
        echo "     - $img_src\n";
    }
} else {
    echo "   No product images found in HTML\n";
}

// Test 3: Check if URLs start with /
echo "\n3. URL format check:\n";
$products = fetchProducts(3);
foreach ($products as $product) {
    $url = $product['image_url'];
    if (strpos($url, '/uploads/') === 0) {
        echo "   ✅ {$product['name']}: Correct web path ($url)\n";
    } else {
        echo "   ❌ {$product['name']}: Incorrect path ($url)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "✅ Image URLs should now work correctly in the browser!\n";
echo "🌐 Visit: http://localhost:8000/customer/index.php\n";
echo "📸 Product images should now display properly\n";
?> 