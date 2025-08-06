<?php
// fetch_products.php

// Determine the correct path to db_connect.php
$db_connect_path = file_exists('includes/db_connect.php') ? 'includes/db_connect.php' : '../includes/db_connect.php';
require_once $db_connect_path;

/**
 * Fetch products from database with optional filters
 */
function fetchProducts($limit = 10, $category_id = null, $is_featured = null, $seller_id = null, $search = null, $min_price = null, $max_price = null, $sort_by = 'created_at', $sort_order = 'DESC') {
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
                    s.id as seller_id,
                    s.username as seller_name,
                    CONCAT(s.first_name, ' ', s.last_name) as seller_full_name,
                    c.name as category_name,
                    c.id as category_id,
                    pi.image_path as primary_image,
                    NULL as avg_rating,
                    0 as review_count
                FROM products p
                LEFT JOIN sellers s ON p.seller_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.is_active = 1 AND s.status = 'approved' AND p.stock_quantity > 0";
        
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
        
        if ($search !== null && !empty(trim($search))) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }
        
        if ($min_price !== null) {
            $sql .= " AND p.price >= :min_price";
            $params['min_price'] = $min_price;
        }
        
        if ($max_price !== null) {
            $sql .= " AND p.price <= :max_price";
            $params['max_price'] = $max_price;
        }
        
        // Add sorting
        switch ($sort_by) {
            case 'price':
                $sql .= " ORDER BY p.price " . $sort_order;
                break;
            case 'name':
                $sql .= " ORDER BY p.name " . $sort_order;
                break;
            case 'rating':
                $sql .= " ORDER BY avg_rating " . $sort_order . ", p.created_at DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
                break;
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }
        
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
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each product
        foreach ($products as &$product) {
            $product['formatted_price'] = formatPrice($product['price']);
            $product['image_url'] = getProductImageUrl($product['primary_image']);
            $product['short_description'] = truncateDescription($product['description'], 60);
            $product['rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
            $product['in_stock'] = $product['stock_quantity'] > 0;
            $product['is_organic'] = checkIfOrganic($product['name'], $product['description']);
            $product['on_sale'] = checkIfOnSale($product['id']); // You can implement discount logic here
        }
        
        return $products;
        
    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch all categories
 */
function fetchCategories() {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.description,
                    c.image,
                    COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id, c.name, c.description, c.image
                ORDER BY c.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get category by ID
 */
function getCategoryById($category_id) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM categories WHERE id = :id AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching category: " . $e->getMessage());
        return null;
    }
}

/**
 * Get popular products (featured or best-selling)
 */
function getPopularProducts($limit = 8) {
    return fetchProducts($limit, null, 1);
}

/**
 * Get products by category
 */
function getProductsByCategory($category_id, $limit = 12) {
    return fetchProducts($limit, $category_id);
}

/**
 * Get featured products for homepage
 */
function getFeaturedProducts($limit = 6) {
    return fetchProducts($limit, null, 1);
}

/**
 * Get recent products
 */
function getRecentProducts($limit = 8) {
    return fetchProducts($limit, null, null, null, null, null, null, 'created_at', 'DESC');
}

/**
 * Search products with filters
 */
function searchProducts($search_term, $category_id = null, $min_price = null, $max_price = null, $sort_by = 'relevance', $limit = 20) {
    $sort_order = 'DESC';
    if ($sort_by === 'price-low') {
        $sort_by = 'price';
        $sort_order = 'ASC';
    } elseif ($sort_by === 'price-high') {
        $sort_by = 'price';
        $sort_order = 'DESC';
    }
    
    return fetchProducts($limit, $category_id, null, null, $search_term, $min_price, $max_price, $sort_by, $sort_order);
}

/**
 * Get sellers with their product count
 */
function getSellers($limit = 10) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    s.id,
                    s.username,
                    CONCAT(s.first_name, ' ', s.last_name) as full_name,
                    s.profile_image,
                    COUNT(p.id) as product_count,
                    4.0 as avg_rating
                FROM sellers s
                LEFT JOIN products p ON s.id = p.seller_id AND p.is_active = 1
                WHERE s.status = 'approved' AND s.is_active = 1
                GROUP BY s.id
                HAVING product_count > 0
                ORDER BY product_count DESC
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sellers as &$seller) {
            $seller['rating'] = 4.0; // Default rating since reviews table doesn't exist
            $seller['profile_image_url'] = $seller['profile_image'] ? $seller['profile_image'] : '../assets/img/avatar.jpg';
        }
        
        return $sellers;
        
    } catch (PDOException $e) {
        error_log("Error fetching sellers: " . $e->getMessage());
        return [];
    }
}

/**
 * Helper functions
 */

function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

function getProductImageUrl($image_path) {
    if ($image_path && file_exists($image_path)) {
        return $image_path;
    }
    // Return default images based on common product types
    $default_images = [
        'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=150&h=100&fit=crop', // Apple
        'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=150&h=100&fit=crop', // Strawberry
        'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=150&h=100&fit=crop', // Orange
        'https://images.unsplash.com/photo-1459411621453-7b03977f4bfc?w=150&h=100&fit=crop', // Broccoli
        'https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=150&h=100&fit=crop'  // Cabbage
    ];
    return $default_images[array_rand($default_images)];
}

function truncateDescription($description, $length = 50) {
    if (!$description) return "Fresh and quality product";
    if (strlen($description) <= $length) {
        return $description;
    }
    return substr($description, 0, $length) . '...';
}

function checkIfOrganic($name, $description) {
    $organic_keywords = ['organic', 'natural', 'eco', 'bio'];
    $text = strtolower($name . ' ' . $description);
    foreach ($organic_keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

function checkIfOnSale($product_id) {
    // You can implement discount/sale logic here
    // For now, randomly set some products as on sale
    return rand(0, 10) > 7; // 30% chance of being on sale
}

/**
 * Get product statistics
 */
function getProductStats() {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_products,
                    COUNT(DISTINCT seller_id) as total_sellers,
                    COUNT(DISTINCT category_id) as total_categories
                FROM products 
                WHERE is_active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching product stats: " . $e->getMessage());
        return ['total_products' => 0, 'featured_products' => 0, 'total_sellers' => 0, 'total_categories' => 0];
    }
}
?>