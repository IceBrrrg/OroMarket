<?php
// customer/api/products.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/db_connect.php';

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'search':
            searchProducts($pdo);
            break;
        case 'category':
            getProductsByCategory($pdo);
            break;
        case 'featured':
            getFeaturedProducts($pdo);
            break;
        case 'details':
            getProductDetails($pdo);
            break;
        default:
            getAllProducts($pdo);
    }
}

function handlePost($pdo, $action) {
    switch ($action) {
        case 'add_to_cart':
            addToCart($pdo);
            break;
        case 'add_to_favorites':
            addToFavorites($pdo);
            break;
        case 'remove_from_favorites':
            removeFromFavorites($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getAllProducts($pdo) {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 12);
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT 
                p.id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.is_featured,
                p.weight,
                p.created_at,
                s.first_name as seller_first_name,
                s.last_name as seller_last_name,
                s.id as seller_id,
                c.name as category_name,
                pi.image_path as primary_image
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1 AND s.status = 'approved'
            ORDER BY p.is_featured DESC, p.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM products p
                 LEFT JOIN sellers s ON p.seller_id = s.id
                 WHERE p.is_active = 1 AND s.status = 'approved'";
    $totalCount = $pdo->query($countSql)->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function searchProducts($pdo) {
    $query = $_GET['q'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 12);
    $offset = ($page - 1) * $limit;
    
    if (empty($query)) {
        getAllProducts($pdo);
        return;
    }
    
    $sql = "SELECT 
                p.id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.is_featured,
                p.weight,
                p.created_at,
                s.first_name as seller_first_name,
                s.last_name as seller_last_name,
                s.id as seller_id,
                c.name as category_name,
                pi.image_path as primary_image
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1 AND s.status = 'approved'
            AND (p.name LIKE :query OR p.description LIKE :query)
            ORDER BY 
                CASE 
                    WHEN p.name LIKE :exact_query THEN 1
                    WHEN p.name LIKE :start_query THEN 2
                    ELSE 3
                END,
                p.is_featured DESC, 
                p.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->bindValue(':exact_query', $query);
    $stmt->bindValue(':start_query', $query . '%');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for search
    $countSql = "SELECT COUNT(*) FROM products p
                 LEFT JOIN sellers s ON p.seller_id = s.id
                 WHERE p.is_active = 1 AND s.status = 'approved'
                 AND (p.name LIKE :query OR p.description LIKE :query)";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindValue(':query', '%' . $query . '%');
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'query' => $query,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function getProductsByCategory($pdo) {
    $categoryId = (int)($_GET['category_id'] ?? 0);
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 12);
    $offset = ($page - 1) * $limit;
    
    if (!$categoryId) {
        getAllProducts($pdo);
        return;
    }
    
    $sql = "SELECT 
                p.id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.is_featured,
                p.weight,
                p.created_at,
                s.first_name as seller_first_name,
                s.last_name as seller_last_name,
                s.id as seller_id,
                c.name as category_name,
                pi.image_path as primary_image
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1 AND s.status = 'approved' AND p.category_id = :category_id
            ORDER BY p.is_featured DESC, p.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for category
    $countSql = "SELECT COUNT(*) FROM products p
                 LEFT JOIN sellers s ON p.seller_id = s.id
                 WHERE p.is_active = 1 AND s.status = 'approved' AND p.category_id = :category_id";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $countStmt->execute();
    $totalCount = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'category_id' => $categoryId,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalCount,
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
}

function getFeaturedProducts($pdo) {
    $limit = (int)($_GET['limit'] ?? 8);
    
    $sql = "SELECT 
                p.id,
                p.name,
                p.description,
                p.price,
                p.stock_quantity,
                p.weight,
                s.first_name as seller_first_name,
                s.last_name as seller_last_name,
                pi.image_path as primary_image
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.is_active = 1 AND p.is_featured = 1 AND s.status = 'approved'
            ORDER BY p.created_at DESC
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
}

function getProductDetails($pdo) {
    $productId = (int)($_GET['id'] ?? 0);
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    $sql = "SELECT 
                p.*,
                s.first_name as seller_first_name,
                s.last_name as seller_last_name,
                s.phone as seller_phone,
                s.email as seller_email,
                c.name as category_name
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = :product_id AND p.is_active = 1 AND s.status = 'approved'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        return;
    }
    
    // Get product images
    $imagesSql = "SELECT image_path, is_primary FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, display_order";
    $imagesStmt = $pdo->prepare($imagesSql);
    $imagesStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $imagesStmt->execute();
    $product['images'] = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product attributes
    $attrSql = "SELECT attribute_name, attribute_value FROM product_attributes WHERE product_id = :product_id";
    $attrStmt = $pdo->prepare($attrSql);
    $attrStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $attrStmt->execute();
    $product['attributes'] = $attrStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $product
    ]);
}

function addToCart($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);
    $customerId = (int)($input['customer_id'] ?? 0); // In a real app, get from session
    
    if (!$productId || !$quantity) {
        throw new Exception('Product ID and quantity are required');
    }
    
    // Check if product exists and has stock
    $sql = "SELECT stock_quantity FROM products WHERE id = :product_id AND is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        return;
    }
    
    if ($product['stock_quantity'] < $quantity) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient stock available'
        ]);
        return;
    }
    
    // In a real application, you would save to a cart table or session
    // For now, we'll just return success
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully',
        'data' => [
            'product_id' => $productId,
            'quantity' => $quantity
        ]
    ]);
}

function addToFavorites($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($input['product_id'] ?? 0);
    $customerId = (int)($input['customer_id'] ?? 0); // In a real app, get from session
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    // In a real application, you would save to a favorites table
    echo json_encode([
        'success' => true,
        'message' => 'Product added to favorites successfully'
    ]);
}

function removeFromFavorites($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($input['product_id'] ?? 0);
    $customerId = (int)($input['customer_id'] ?? 0); // In a real app, get from session
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    // In a real application, you would remove from favorites table
    echo json_encode([
        'success' => true,
        'message' => 'Product removed from favorites successfully'
    ]);
}
?>