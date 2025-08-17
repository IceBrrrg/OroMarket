<?php
header('Content-Type: application/json');

// Try different paths for db connection
$db_paths = [
    'includes/db_connect.php',
    '../includes/db_connect.php',
    'db.php',
    '../db.php',
    'config/db.php',
    '../config/db.php'
];

$db_connected = false;
$used_path = '';

foreach ($db_paths as $path) {
    if (file_exists($path)) {
        try {
            require_once $path;
            $used_path = $path;
            $db_connected = true;
            break;
        } catch (Exception $e) {
            error_log("Failed to connect with path $path: " . $e->getMessage());
        }
    }
}

if (!$db_connected || !isset($pdo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => 'Could not establish database connection'
    ]);
    exit;
}

try {
    // Test database connection
    $test_query = $pdo->query("SELECT 1");
    if (!$test_query) {
        throw new Exception("Database connection test failed");
    }

    // Query to get all active products
    $query = "
        SELECT 
            p.id,
            p.name,
            p.description,
            p.price,
            p.previous_price,
            p.price_change,
            p.price_change_percentage,
            p.price_trend,
            p.stock_quantity,
            p.weight,
            p.is_featured,
            p.category_id,
            p.created_at,
            c.name as category_name,
            CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as seller_full_name,
            s.username as seller_username,
            s.profile_image as seller_profile_image,
            CONCAT('₱', FORMAT(p.price, 2)) as formatted_price,
            CASE 
                WHEN LENGTH(p.description) > 100 
                THEN CONCAT(SUBSTRING(p.description, 1, 100), '...')
                ELSE p.description
            END as short_description
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE p.is_active = 1 
        AND s.is_active = 1
        AND s.status = 'approved'
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process products to ensure proper data types and handle missing images
    foreach ($products as &$product) {
        $product['id'] = (int)$product['id'];
        $product['price'] = (float)$product['price'];
        $product['previous_price'] = $product['previous_price'] ? (float)$product['previous_price'] : null;
        $product['price_change_percentage'] = (float)$product['price_change_percentage'];
        $product['stock_quantity'] = (int)$product['stock_quantity'];
        $product['weight'] = $product['weight'] ? (float)$product['weight'] : null;
        $product['is_featured'] = (bool)$product['is_featured'];
        $product['category_id'] = (int)$product['category_id'];
        
        // Set default image
        $product['image_url'] = 'https://estore.midas.com.my/image/cache/no_image_uploaded-253x190.png';
        
        // Check if product has images in product_images table
        try {
            $img_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
            $img_stmt->execute([$product['id']]);
            $image = $img_stmt->fetch(PDO::FETCH_ASSOC);
            if ($image && !empty($image['image_path'])) {
                // Check if path already starts with uploads/ or ../uploads/
                $image_path = $image['image_path'];
                if (!str_starts_with($image_path, 'http') && !str_starts_with($image_path, '/')) {
                    // Add proper path prefix
                    if (!str_starts_with($image_path, 'uploads/')) {
                        $image_path = 'uploads/' . ltrim($image_path, '/');
                    }
                    // Add base path if needed
                    $product['image_url'] = '../' . $image_path;
                } else {
                    $product['image_url'] = $image_path;
                }
            }
        } catch (Exception $e) {
            // Keep default image if query fails
        }
        
        // Ensure description is not null
        if ($product['description'] === null) {
            $product['description'] = '';
            $product['short_description'] = '';
        }
        
        // Set seller display name
        $seller_name = trim($product['seller_full_name']);
        $product['seller_display_name'] = !empty($seller_name) ? $seller_name : $product['seller_username'];
        
        // Add view count
        $product['view_count'] = 0;
        
        // Get actual view count from product_view_logs table
        try {
            $view_stmt = $pdo->prepare("SELECT COUNT(*) as view_count FROM product_view_logs WHERE product_id = ?");
            $view_stmt->execute([$product['id']]);
            $view_result = $view_stmt->fetch(PDO::FETCH_ASSOC);
            if ($view_result) {
                $product['view_count'] = (int)$view_result['view_count'];
            }
        } catch (Exception $e) {
            // Keep default 0 if query fails
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Error in ajax_get_products.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database query failed: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>