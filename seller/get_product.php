<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$seller_id = $_SESSION['user_id'];
$product_id = intval($_GET['id']);

try {
    // Get product data with category
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ? AND p.seller_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$product_id, $seller_id]);
    $product = $stmt->fetch();

    if ($product) {
        // Return product data as JSON
        echo json_encode([
            'success' => true,
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'category_id' => $product['category_id'],
                'category_name' => $product['category_name'],
                'status' => $product['status'],
                'unit' => $product['unit'],
                'description' => $product['description'],
                'image' => $product['image']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>