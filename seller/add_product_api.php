<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $seller_id = $_SESSION['user_id'];
    
    // Get form data (removed category_id and dimensions)
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $weight = floatval($_POST['weight'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validate required fields
    if (empty($name) || $price <= 0 || $stock_quantity < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields with valid values']);
        exit();
    }
    
    // Generate SKU if not provided
    if (empty($sku)) {
        $sku = 'PROD-' . strtoupper(substr(md5($name . time()), 0, 8));
    }
    
    // Check if SKU already exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND seller_id != ?");
    $stmt->execute([$sku, $seller_id]);
    if ($stmt->fetch()) {
        // Generate a new unique SKU
        $sku = 'PROD-' . strtoupper(substr(md5($name . time() . rand()), 0, 8));
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert product (removed category_id and dimensions)
    $stmt = $pdo->prepare("
        INSERT INTO products (
            seller_id, name, description, price, stock_quantity, 
            sku, weight, is_featured, is_active, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    
    $stmt->execute([
        $seller_id, $name, $description, $price, $stock_quantity,
        $sku, $weight, $is_featured
    ]);
    
    $product_id = $pdo->lastInsertId();
    
    // Handle image uploads
    $uploaded_images = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = '../uploads/products/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_name = $_FILES['images']['name'][$i];
                $file_size = $_FILES['images']['size'][$i];
                $file_type = $_FILES['images']['type'][$i];
                
                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    continue;
                }
                
                // Validate file size
                if ($file_size > $max_size) {
                    continue;
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = 'product_' . $product_id . '_' . time() . '_' . $i . '.' . $file_extension;
                $file_path = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Insert into product_images table
                    $stmt = $pdo->prepare("
                        INSERT INTO product_images (product_id, image_path, is_primary, display_order, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    
                    $is_primary = ($i === 0) ? 1 : 0; // First image is primary
                    $relative_path = 'uploads/products/' . $new_filename;
                    
                    $stmt->execute([$product_id, $relative_path, $is_primary, $i]);
                    $uploaded_images[] = $relative_path;
                }
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Get the complete product data to return
    $stmt = $pdo->prepare("
        SELECT p.*, GROUP_CONCAT(pi.image_path) as images 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id 
        WHERE p.id = ? 
        GROUP BY p.id
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'message' => 'Product added successfully!',
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'stock_quantity' => $product['stock_quantity'],
            'sku' => $product['sku'],
            'images' => $product['images'] ? explode(',', $product['images']) : [],
            'created_at' => $product['created_at']
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Database error in add_product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Error in add_product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding the product: ' . $e->getMessage()]);
}
?>