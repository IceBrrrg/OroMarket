<?php
/**
 * API Endpoint: Get Price Changes from Other Sellers
 * File: api/price-changes.php
 * 
 * This endpoint allows sellers to view price changes made by other sellers
 * to help them stay informed about market pricing trends.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
require_once __DIR__ . '/../includes/db_connect.php';


class PriceChangesAPI {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Get price changes from other sellers
     */
    public function getPriceChanges($seller_id = null, $limit = 50, $category_id = null, $days = 7) {
        try {
            $sql = "SELECT 
                        pph.id,
                        pph.product_id,
                        pph.old_price,
                        pph.new_price,
                        pph.changed_at,
                        p.name as product_name,
                        p.seller_id,
                        CONCAT(s.first_name, ' ', s.last_name) as seller_name,
                        s.username as seller_username,
                        c.name as category_name,
                        c.id as category_id,
                        pi.image_path as product_image,
                        CASE 
                            WHEN pph.new_price > pph.old_price THEN 'increase'
                            WHEN pph.new_price < pph.old_price THEN 'decrease'
                            ELSE 'no_change'
                        END as price_direction,
                        ROUND(((pph.new_price - pph.old_price) / pph.old_price) * 100, 2) as price_change_percentage,
                        ABS(pph.new_price - pph.old_price) as price_change_amount
                    FROM product_price_history pph
                    INNER JOIN products p ON pph.product_id = p.id
                    INNER JOIN sellers s ON p.seller_id = s.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE s.status = 'approved' 
                    AND p.is_active = 1";
            
            $params = [];
            
            // Exclude current seller's price changes if seller_id provided
            if ($seller_id) {
                $sql .= " AND p.seller_id != ?";
                $params[] = $seller_id;
            }
            
            // Filter by category if specified
            if ($category_id) {
                $sql .= " AND p.category_id = ?";
                $params[] = $category_id;
            }
            
            // Filter by date range
            if ($days > 0) {
                $sql .= " AND pph.changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $params[] = $days;
            }
            
            $sql .= " ORDER BY pph.changed_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $this->formatPriceChanges($results),
                'count' => count($results),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch price changes: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get price change statistics
     */
    public function getPriceChangeStats($seller_id = null, $days = 7) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_changes,
                        SUM(CASE WHEN pph.new_price > pph.old_price THEN 1 ELSE 0 END) as price_increases,
                        SUM(CASE WHEN pph.new_price < pph.old_price THEN 1 ELSE 0 END) as price_decreases,
                        AVG(ABS(pph.new_price - pph.old_price)) as avg_change_amount,
                        AVG(ABS(((pph.new_price - pph.old_price) / pph.old_price) * 100)) as avg_change_percentage,
                        c.name as category_name,
                        c.id as category_id,
                        COUNT(DISTINCT p.seller_id) as active_sellers
                    FROM product_price_history pph
                    INNER JOIN products p ON pph.product_id = p.id
                    INNER JOIN sellers s ON p.seller_id = s.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE s.status = 'approved' 
                    AND p.is_active = 1
                    AND pph.changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $params = [$days];
            
            if ($seller_id) {
                $sql .= " AND p.seller_id != ?";
                $params[] = $seller_id;
            }
            
            $sql .= " GROUP BY c.id, c.name ORDER BY total_changes DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get overall stats
            $overallSql = "SELECT 
                                COUNT(*) as total_changes,
                                SUM(CASE WHEN pph.new_price > pph.old_price THEN 1 ELSE 0 END) as price_increases,
                                SUM(CASE WHEN pph.new_price < pph.old_price THEN 1 ELSE 0 END) as price_decreases,
                                AVG(ABS(pph.new_price - pph.old_price)) as avg_change_amount,
                                AVG(ABS(((pph.new_price - pph.old_price) / pph.old_price) * 100)) as avg_change_percentage,
                                COUNT(DISTINCT p.seller_id) as active_sellers
                            FROM product_price_history pph
                            INNER JOIN products p ON pph.product_id = p.id
                            INNER JOIN sellers s ON p.seller_id = s.id
                            WHERE s.status = 'approved' 
                            AND p.is_active = 1
                            AND pph.changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $overallParams = [$days];
            
            if ($seller_id) {
                $overallSql .= " AND p.seller_id != ?";
                $overallParams[] = $seller_id;
            }
            
            $overallStmt = $this->conn->prepare($overallSql);
            $overallStmt->execute($overallParams);
            $overallStats = $overallStmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'overall' => $overallStats,
                    'by_category' => $categoryStats
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch price change statistics: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Format price changes data for better readability
     */
    private function formatPriceChanges($results) {
        $formatted = [];
        
        foreach ($results as $row) {
            $formatted[] = [
                'id' => $row['id'],
                'product' => [
                    'id' => $row['product_id'],
                    'name' => $row['product_name'],
                    'image' => $row['product_image'],
                    'category' => [
                        'id' => $row['category_id'],
                        'name' => $row['category_name']
                    ]
                ],
                'seller' => [
                    'id' => $row['seller_id'],
                    'name' => $row['seller_name'],
                    'username' => $row['seller_username']
                ],
                'price_change' => [
                    'old_price' => (float)$row['old_price'],
                    'new_price' => (float)$row['new_price'],
                    'direction' => $row['price_direction'],
                    'amount_change' => (float)$row['price_change_amount'],
                    'percentage_change' => (float)$row['price_change_percentage'],
                    'formatted_message' => $this->formatPriceChangeMessage($row)
                ],
                'changed_at' => $row['changed_at'],
                'time_ago' => $this->timeAgo($row['changed_at'])
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Format price change message
     */
    private function formatPriceChangeMessage($row) {
        $direction = $row['price_direction'] === 'increase' ? 'increased' : 'decreased';
        $arrow = $row['price_direction'] === 'increase' ? '↗' : '↘';
        
        return sprintf(
            "%s %s the price of %s: from ₱%.2f to ₱%.2f %s",
            $row['seller_name'],
            $direction,
            $row['product_name'],
            $row['old_price'],
            $row['new_price'],
            $arrow
        );
    }
    
    /**
     * Calculate time ago
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31104000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31104000) . ' years ago';
    }
}

// Initialize API
try {
 $api = new PriceChangesAPI($pdo);

    
    // Get request parameters
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_GET['endpoint'] ?? 'changes';
    $seller_id = $_GET['seller_id'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 records
    $category_id = $_GET['category_id'] ?? null;
    $days = min((int)($_GET['days'] ?? 7), 30); // Max 30 days
    
    if ($method === 'GET') {
        switch ($endpoint) {
            case 'changes':
                $response = $api->getPriceChanges($seller_id, $limit, $category_id, $days);
                break;
                
            case 'stats':
                $response = $api->getPriceChangeStats($seller_id, $days);
                break;
                
            default:
                $response = [
                    'success' => false,
                    'error' => 'Invalid endpoint. Available endpoints: changes, stats'
                ];
                break;
        }
    } else {
        $response = [
            'success' => false,
            'error' => 'Only GET method is allowed'
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'API Error: ' . $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);

/**
 * USAGE EXAMPLES:
 * 
 * 1. Get price changes (excluding current seller):
 * GET /api/price-changes.php?endpoint=changes&seller_id=20&limit=20&days=7
 * 
 * 2. Get price changes by category:
 * GET /api/price-changes.php?endpoint=changes&category_id=4&limit=30
 * 
 * 3. Get price change statistics:
 * GET /api/price-changes.php?endpoint=stats&seller_id=20&days=7
 * 
 * 4. Get all recent price changes:
 * GET /api/price-changes.php?endpoint=changes&limit=50&days=3
 */
?>