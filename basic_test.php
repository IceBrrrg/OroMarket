<?php
echo "Starting basic test...\n";

require_once 'includes/db_connect.php';
echo "Database connected.\n";

$sql = "SELECT COUNT(*) as count FROM products WHERE is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetch();

echo "Active products: " . $result['count'] . "\n";

$sql = "SELECT id, name FROM products WHERE is_active = 1 LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll();

echo "Product list:\n";
foreach ($products as $product) {
    echo "  ID: {$product['id']}, Name: {$product['name']}\n";
}

echo "Test completed.\n";
?> 