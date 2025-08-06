<?php
require_once 'includes/db_connect.php';

echo "=== CHECKING ACTUAL PRODUCTS IN DATABASE ===\n\n";

// Check what products exist in the database
$sql = "SELECT 
            p.id,
            p.name,
            p.description,
            p.price,
            pi.image_path as primary_image
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.is_active = 1
        ORDER BY p.id";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Products in database:\n";
foreach ($products as $product) {
    echo "   ID: {$product['id']}\n";
    echo "   Name: {$product['name']}\n";
    echo "   Price: ₱{$product['price']}\n";
    echo "   Image: " . ($product['primary_image'] ?? 'NULL') . "\n";
    if ($product['primary_image']) {
        echo "   File exists: " . (file_exists($product['primary_image']) ? 'YES' : 'NO') . "\n";
    }
    echo "   ---\n";
}

echo "\nFiles in uploads/products directory:\n";
$files = scandir('uploads/products');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "   - $file\n";
    }
}

echo "\n=== END CHECK ===\n";
?> 