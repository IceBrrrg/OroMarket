<?php
// fetch_products.php
require_once 'includes/db_connect.php';

/**
 * Fetch products from database with optional filters
 */
function fetchProducts($limit = 10, $category_id = null, $is_featured = null, $seller_id = null) {
    global $pdo;
    
    try {
        // Base query
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.price,
                    p.stock_quantity,
                    p.is_featured,
                    p.is_active,
                    p.created_at,
                    s.username as seller_name,
                    s.first_name as seller_first_name,
                    s.last_name as seller_last_name,
                    c.name as category_name,
                    pi.image_path as primary_image
                FROM products p
                LEFT JOIN sellers s ON p.seller_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_active = 1 AND s.status = 'approved'";
        
        $params = [];
        
        // Add filters
        if ($category_id !== null) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $category_id;
        }
        
        if ($is_featured !== null) {
            $sql .= " AND p.is_featured = :is_featured";
            $params['is_featured'] = $is_featured;
        }
        
        if ($seller_id !== null) {
            $sql .= " AND p.seller_id = :seller_id";
            $params['seller_id'] = $seller_id;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
        $params['limit'] = $limit;
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                $stmt->bindValue(':' . $key, (int)$value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get popular products (you can modify this logic based on your criteria)
 */
function getPopularProducts($limit = 5) {
    return fetchProducts($limit, null, 1); // Featured products as popular
}

/**
 * Get products by category
 */
function getProductsByCategory($category_id, $limit = 10) {
    return fetchProducts($limit, $category_id);
}

/**
 * Get discount products (products with specific criteria)
 */
function getDiscountProducts($limit = 4) {
    // You can modify this to fetch products with actual discounts
    return fetchProducts($limit);
}

/**
 * Format price for display
 */
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Get product image URL
 */
function getProductImageUrl($image_path) {
    if ($image_path && file_exists($image_path)) {
        return $image_path;
    }
    // Return default image if no image found
    return 'assets/img/fruite-item-1.jpg'; // Default placeholder image
}

/**
 * Truncate description
 */
function truncateDescription($description, $length = 50) {
    if (strlen($description) <= $length) {
        return $description;
    }
    return substr($description, 0, $length) . '...';
}
?>