<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$seller_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$product_id = intval($_GET['id']);

try {
    // Get product data with category information
    $query = "SELECT p.*, c.name as category_name FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ? AND p.seller_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$product_id, $seller_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Get product images
    $img_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC";
    $img_stmt = $pdo->prepare($img_query);
    $img_stmt->execute([$product_id]);
    $images = $img_stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'product' => $product,
        'images' => $images
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_product.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>