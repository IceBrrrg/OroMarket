<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/OroMarket/';

    $stmt = $pdo->prepare(
        "SELECT p.name, p.price, IFNULL(p.price_change, 'no_change') AS price_change, 
                CONCAT(:base_url, pi.image_path) AS image_url 
         FROM products p
         LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
         WHERE p.is_active = 1"
    );
    $stmt->bindParam(':base_url', $base_url, PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = array_map(function ($product) {
        return [
            'name' => $product['name'],
            'price' => number_format($product['price'], 2),
            'change' => $product['price_change'], // 'up', 'down', or 'no_change'
            'image_url' => $product['image_url']
        ];
    }, $products);

    echo json_encode(['success' => true, 'data' => $response]);
} catch (PDOException $e) {
    error_log("Error fetching product prices: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to fetch product prices.']);
}
