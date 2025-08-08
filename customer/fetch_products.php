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
                    0 as review_count,
                    COALESCE(pv.view_count, 0) as view_count
                FROM products p
                LEFT JOIN sellers s ON p.seller_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_views pv ON p.id = pv.product_id
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
            case 'most_viewed':
                $sql .= " ORDER BY view_count DESC, p.created_at DESC";
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
            $product['image_url'] = getCorrectImagePath($product['primary_image']);
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
 * Get most viewed products
 */
function getMostViewedProducts($limit = 4) {
    return fetchProducts($limit, null, null, null, null, null, null, 'most_viewed', 'DESC');
}

/**
 * Track product view
 */
function trackProductView($product_id, $user_id = null, $session_id = null) {
    global $pdo;
    
    try {
        error_log("Starting trackProductView for product_id: $product_id");
        
        // If no session_id provided, use PHP session ID
        if (!$session_id) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $session_id = session_id();
        }
        
        // Get client IP address
        $ip_address = 'unknown';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        error_log("Tracking details - User ID: " . ($user_id ?? 'null') . ", Session: $session_id, IP: $ip_address");
        
        // REDUCED COOLDOWN - Check if this session/user has viewed this product in the last 5 minutes (instead of 1 hour)
        $five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        
        $check_sql = "SELECT id FROM product_view_logs 
                      WHERE product_id = :product_id 
                      AND viewed_at > :five_minutes_ago 
                      AND (session_id = :session_id" . 
                      ($user_id ? " OR (user_id IS NOT NULL AND user_id = :user_id)" : "") . ")";
        
        $check_stmt = $pdo->prepare($check_sql);
        $check_params = [
            'product_id' => $product_id,
            'five_minutes_ago' => $five_minutes_ago,
            'session_id' => $session_id
        ];
        
        if ($user_id) {
            $check_params['user_id'] = $user_id;
        }
        
        $check_stmt->execute($check_params);
        $recent_view = $check_stmt->fetch();
        
        error_log("Recent view check result: " . ($recent_view ? 'found recent view (within 5 min)' : 'no recent view'));
        
        // If not viewed in the last 5 minutes, record the view
        if (!$recent_view) {
            error_log("Recording new view");
            
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Insert view log
                $log_sql = "INSERT INTO product_view_logs (product_id, user_id, session_id, ip_address, user_agent, viewed_at) 
                            VALUES (:product_id, :user_id, :session_id, :ip_address, :user_agent, NOW())";
                
                $log_stmt = $pdo->prepare($log_sql);
                $log_result = $log_stmt->execute([
                    'product_id' => $product_id,
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent
                ]);
                
                error_log("View log insert result: " . ($log_result ? 'success' : 'failed'));
                
                if (!$log_result) {
                    throw new Exception("Failed to insert view log");
                }
                
                // Update or create product_views record
                $view_sql = "INSERT INTO product_views (product_id, view_count, last_viewed) 
                             VALUES (:product_id, 1, NOW()) 
                             ON DUPLICATE KEY UPDATE 
                             view_count = view_count + 1, 
                             last_viewed = NOW()";
                
                $view_stmt = $pdo->prepare($view_sql);
                $view_result = $view_stmt->execute(['product_id' => $product_id]);
                
                error_log("View count update result: " . ($view_result ? 'success' : 'failed'));
                
                if (!$view_result) {
                    throw new Exception("Failed to update view count");
                }
                
                // Get the new count to verify
                $verify_sql = "SELECT view_count FROM product_views WHERE product_id = :product_id";
                $verify_stmt = $pdo->prepare($verify_sql);
                $verify_stmt->execute(['product_id' => $product_id]);
                $new_count = $verify_stmt->fetch();
                
                error_log("New view count after increment: " . ($new_count ? $new_count['view_count'] : 'not found'));
                
                // Commit transaction
                $pdo->commit();
                
                error_log("View tracking completed successfully - New count: " . ($new_count ? $new_count['view_count'] : '?'));
                return true;
                
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("Transaction failed: " . $e->getMessage());
                return false;
            }
            
        } else {
            error_log("View already recorded in the last 5 minutes, skipping");
            return true; // Return true even if already viewed (not an error)
        }
        
    } catch (PDOException $e) {
        error_log("Database error in trackProductView: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("General error in trackProductView: " . $e->getMessage());
        return false;
    }
}
function trackProductViewNoRestriction($product_id, $user_id = null, $session_id = null) {
    global $pdo;
    
    try {
        error_log("Starting trackProductViewNoRestriction for product_id: $product_id");
        
        // If no session_id provided, use PHP session ID
        if (!$session_id) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $session_id = session_id();
        }
        
        // Get client IP address
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Always insert a view log (no restrictions)
            $log_sql = "INSERT INTO product_view_logs (product_id, user_id, session_id, ip_address, user_agent, viewed_at) 
                        VALUES (:product_id, :user_id, :session_id, :ip_address, :user_agent, NOW())";
            
            $log_stmt = $pdo->prepare($log_sql);
            $log_result = $log_stmt->execute([
                'product_id' => $product_id,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent
            ]);
            
            error_log("View log insert result: " . ($log_result ? 'success' : 'failed'));
            
            if (!$log_result) {
                throw new Exception("Failed to insert view log");
            }
            
            // Always increment the view count
            $view_sql = "INSERT INTO product_views (product_id, view_count, last_viewed) 
                         VALUES (:product_id, 1, NOW()) 
                         ON DUPLICATE KEY UPDATE 
                         view_count = view_count + 1, 
                         last_viewed = NOW()";
            
            $view_stmt = $pdo->prepare($view_sql);
            $view_result = $view_stmt->execute(['product_id' => $product_id]);
            
            error_log("View count update result: " . ($view_result ? 'success' : 'failed'));
            
            if (!$view_result) {
                throw new Exception("Failed to update view count");
            }
            
            // Get the new count
            $verify_sql = "SELECT view_count FROM product_views WHERE product_id = :product_id";
            $verify_stmt = $pdo->prepare($verify_sql);
            $verify_stmt->execute(['product_id' => $product_id]);
            $new_count = $verify_stmt->fetch();
            
            error_log("New view count: " . ($new_count ? $new_count['view_count'] : 'not found'));
            
            // Commit transaction
            $pdo->commit();
            
            return true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            error_log("Transaction failed: " . $e->getMessage());
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error in trackProductViewNoRestriction: " . $e->getMessage());
        return false;
    }
}
function checkViewTrackingStatus($product_id) {
    global $pdo;
    
    try {
        // Get current view count
        $view_sql = "SELECT * FROM product_views WHERE product_id = :product_id";
        $view_stmt = $pdo->prepare($view_sql);
        $view_stmt->execute(['product_id' => $product_id]);
        $view_data = $view_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get recent logs
        $log_sql = "SELECT * FROM product_view_logs WHERE product_id = :product_id ORDER BY viewed_at DESC LIMIT 5";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute(['product_id' => $product_id]);
        $recent_logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total log count
        $count_sql = "SELECT COUNT(*) as total_logs FROM product_view_logs WHERE product_id = :product_id";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute(['product_id' => $product_id]);
        $log_count = $count_stmt->fetch()['total_logs'];
        
        return [
            'view_data' => $view_data,
            'recent_logs' => $recent_logs,
            'total_logs' => $log_count,
            'current_session' => session_id(),
            'current_time' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        error_log("Error checking view tracking status: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get current view count for a product
 */
function getCurrentViewCount($product_id) {
    global $pdo;
    
    try {
        $sql = "SELECT view_count FROM product_views WHERE product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['view_count'] : 0;
        
    } catch (PDOException $e) {
        error_log("Error getting view count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Debug function to check view tracking tables and data
 */
function debugViewTracking($product_id = null) {
    global $pdo;
    
    try {
        $debug_info = [];
        
        // Check if tables exist
        $tables = ['product_views', 'product_view_logs'];
        foreach ($tables as $table) {
            $sql = "SHOW TABLES LIKE '$table'";
            $stmt = $pdo->query($sql);
            $debug_info["table_$table"] = $stmt->rowCount() > 0;
            
            if ($debug_info["table_$table"]) {
                // Get table structure
                $desc_sql = "DESCRIBE $table";
                $desc_stmt = $pdo->query($desc_sql);
                $debug_info["{$table}_structure"] = $desc_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get record count
                $count_sql = "SELECT COUNT(*) as count FROM $table";
                $count_stmt = $pdo->query($count_sql);
                $debug_info["{$table}_count"] = $count_stmt->fetch()['count'];
            }
        }
        
        // If product_id specified, get specific data
        if ($product_id) {
            // Get view count for this product
            $view_sql = "SELECT * FROM product_views WHERE product_id = :product_id";
            $view_stmt = $pdo->prepare($view_sql);
            $view_stmt->execute(['product_id' => $product_id]);
            $debug_info['product_view_data'] = $view_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get recent logs for this product
            $log_sql = "SELECT * FROM product_view_logs WHERE product_id = :product_id ORDER BY viewed_at DESC LIMIT 5";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute(['product_id' => $product_id]);
            $debug_info['recent_logs'] = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $debug_info;
        
    } catch (PDOException $e) {
        error_log("Error in debug view tracking: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

/**
 * Force create view tracking tables (use this if tables don't exist)
 */
function forceCreateViewTrackingTables() {
    global $pdo;
    
    try {
        error_log("Creating view tracking tables...");
        
        // Drop tables if they exist (for fresh start)
        $pdo->exec("DROP TABLE IF EXISTS product_view_logs");
        $pdo->exec("DROP TABLE IF EXISTS product_views");
        
        // Create product_views table
        $create_views_table = "CREATE TABLE product_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            view_count INT DEFAULT 0,
            last_viewed DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_product (product_id),
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB";
        
        $pdo->exec($create_views_table);
        error_log("Created product_views table");
        
        // Create product_view_logs table
        $create_logs_table = "CREATE TABLE product_view_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_date (product_id, viewed_at),
            INDEX idx_session (session_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB";
        
        $pdo->exec($create_logs_table);
        error_log("Created product_view_logs table");
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error creating view tracking tables: " . $e->getMessage());
        return false;
    }
}

// Add this debug endpoint
if (isset($_GET['debug_view_tracking'])) {
    header('Content-Type: application/json');
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    echo json_encode(debugViewTracking($product_id), JSON_PRETTY_PRINT);
    exit;
}

// Add this to force recreate tables if needed
if (isset($_GET['recreate_tables']) && $_GET['recreate_tables'] === 'confirm') {
    header('Content-Type: application/json');
    $result = forceCreateViewTrackingTables();
    echo json_encode(['success' => $result], JSON_PRETTY_PRINT);
    exit;
}
function debugViewCount($product_id) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    pv.view_count, 
                    pv.last_viewed,
                    COUNT(pvl.id) as log_count
                FROM product_views pv
                LEFT JOIN product_view_logs pvl ON pv.product_id = pvl.product_id
                WHERE pv.product_id = :product_id
                GROUP BY pv.product_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Debug view count for product $product_id: " . json_encode($result));
        return $result;
        
    } catch (Exception $e) {
        error_log("Error in debug view count: " . $e->getMessage());
        return null;
    }
}



/**
 * Get product by ID with view tracking
 */
function getProductById($product_id, $track_view = false, $user_id = null) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    p.*,
                    s.username as seller_name,
                    CONCAT(s.first_name, ' ', s.last_name) as seller_full_name,
                    c.name as category_name,
                    c.id as category_id,
                    pi.image_path as primary_image,
                    COALESCE(pv.view_count, 0) as view_count,
                    pv.last_viewed
                FROM products p
                LEFT JOIN sellers s ON p.seller_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_views pv ON p.id = pv.product_id
                WHERE p.id = :id AND p.is_active = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Track view if requested (but after fetching current data)
            if ($track_view) {
                trackProductView($product_id, $user_id);
                // Fetch updated view count
                $view_sql = "SELECT view_count FROM product_views WHERE product_id = :product_id";
                $view_stmt = $pdo->prepare($view_sql);
                $view_stmt->execute(['product_id' => $product_id]);
                $view_result = $view_stmt->fetch();
                if ($view_result) {
                    $product['view_count'] = $view_result['view_count'];
                }
            }
            
            $product['formatted_price'] = formatPrice($product['price']);
            $product['image_url'] = getCorrectImagePath($product['primary_image']);
        }
        
        return $product;
        
    } catch (PDOException $e) {
        error_log("Error fetching product by ID: " . $e->getMessage());
        return null;
    }
}
function testViewTracking() {
    global $pdo;
    
    try {
        // Check if tables exist
        $tables = ['product_views', 'product_view_logs'];
        $results = [];
        
        foreach ($tables as $table) {
            $sql = "SHOW TABLES LIKE '$table'";
            $stmt = $pdo->query($sql);
            $exists = $stmt->rowCount() > 0;
            $results[$table] = $exists;
            
            if ($exists) {
                // Check table structure
                $desc_sql = "DESCRIBE $table";
                $desc_stmt = $pdo->query($desc_sql);
                $columns = $desc_stmt->fetchAll(PDO::FETCH_COLUMN);
                $results[$table . '_columns'] = $columns;
                
                // Check record count
                $count_sql = "SELECT COUNT(*) as count FROM $table";
                $count_stmt = $pdo->query($count_sql);
                $count = $count_stmt->fetch()['count'];
                $results[$table . '_count'] = $count;
            }
        }
        
        return $results;
        
    } catch (PDOException $e) {
        error_log("Error testing view tracking: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

// Add this debug endpoint at the end of your fetch_products.php
if (isset($_GET['debug_views']) && $_GET['debug_views'] === '1') {
    header('Content-Type: application/json');
    $test_results = testViewTracking();
    echo json_encode($test_results, JSON_PRETTY_PRINT);
    exit;
}



/**
 * Create necessary tables for view tracking
 */
function createViewTrackingTables() {
    global $pdo;
    
    try {
        // Create product_views table
        $create_views_table = "CREATE TABLE IF NOT EXISTS product_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            view_count INT DEFAULT 0,
            last_viewed DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_product (product_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_views_table);
        
        // Create product_view_logs table for detailed tracking
        $create_logs_table = "CREATE TABLE IF NOT EXISTS product_view_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_date (product_id, viewed_at),
            INDEX idx_session (session_id),
            INDEX idx_user (user_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_logs_table);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error creating view tracking tables: " . $e->getMessage());
        return false;
    }
}

/**
 * Get view statistics for admin dashboard
 */
function getViewStatistics($days = 7) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    COALESCE(pv.view_count, 0) as total_views,
                    COUNT(pvl.id) as recent_views
                FROM products p
                LEFT JOIN product_views pv ON p.id = pv.product_id
                LEFT JOIN product_view_logs pvl ON p.id = pvl.product_id 
                    AND pvl.viewed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                WHERE p.is_active = 1
                GROUP BY p.id, p.name, pv.view_count
                ORDER BY total_views DESC
                LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['days' => $days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting view statistics: " . $e->getMessage());
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

/**
 * Fixed image path function - this is the main fix
 */
function getCorrectImagePath($stored_path) {
    // If no stored path, return default image
    if (empty($stored_path)) {
        return '../assets/img/default-product.jpg';
    }
    
    // Clean the stored path
    $clean_path = trim($stored_path);
    
    // If the path already starts with '../' or '/', use it as is
    if (strpos($clean_path, '../') === 0 || strpos($clean_path, '/') === 0) {
        return $clean_path;
    }
    
    // If the path starts with 'uploads/', add '../' prefix
    if (strpos($clean_path, 'uploads/') === 0) {
        return '../' . $clean_path;
    }
    
    // If it's just a filename, assume it's in uploads/products/
    return '../uploads/products/' . $clean_path;
}

/**
 * Alternative function to verify if image file actually exists
 */
function findProductImageOroMarket($product_id, $stored_path = null) {
    // Get the document root or project base directory
    $project_root = $_SERVER['DOCUMENT_ROOT'] . '/OroMarket';
    
    // If project is not in /OroMarket/, adjust this path
    if (!is_dir($project_root)) {
        $project_root = dirname(dirname(__FILE__)); // Goes up from customer/ to project root
    }
    
    // First, try the stored path if provided
    if ($stored_path) {
        $clean_path = ltrim(trim($stored_path), '/');
        
        // Try different path combinations
        $possible_paths = [
            $project_root . '/' . $clean_path,
            $project_root . '/uploads/products/' . basename($clean_path),
            dirname(__FILE__) . '/../' . $clean_path
        ];
        
        foreach ($possible_paths as $full_path) {
            if (file_exists($full_path)) {
                // Convert to web path
                $web_path = str_replace($project_root, '', $full_path);
                return '../' . ltrim($web_path, '/');
            }
        }
    }
    
    // If stored path doesn't work, search for files by product ID
    $upload_dir = $project_root . '/uploads/products/';
    
    if (is_dir($upload_dir)) {
        // Look for files that start with product_{id}_
        $files = glob($upload_dir . "product_{$product_id}_*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        
        if (!empty($files)) {
            $filename = basename($files[0]);
            return '../uploads/products/' . $filename;
        }
    }
    
    // Return default image if nothing found
    return '../assets/img/default-product.jpg';
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

// Initialize view tracking tables if they don't exist
createViewTrackingTables();
?>