<?php
// price_history_api.php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

function getPriceHistory($product_id, $days = 30) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                old_price,
                new_price,
                changed_at,
                ((new_price - old_price) / old_price * 100) as percentage_change
            FROM product_price_history 
            WHERE product_id = ? 
            AND changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY changed_at DESC
        ");
        
        $stmt->execute([$product_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Price history error: " . $e->getMessage());
        return false;
    }
}

function getPriceStatistics($product_id, $period = '30') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                MIN(new_price) as lowest_price,
                MAX(new_price) as highest_price,
                AVG(new_price) as average_price,
                COUNT(*) as total_changes,
                MIN(changed_at) as first_change,
                MAX(changed_at) as last_change
            FROM product_price_history 
            WHERE product_id = ? 
            AND changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        $stmt->execute([$product_id, $period]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Price statistics error: " . $e->getMessage());
        return false;
    }
}

function getPriceTrends($days = 7) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name,
                p.price as current_price,
                p.previous_price,
                p.price_change_percentage,
                p.price_trend,
                p.last_price_update,
                pi.image_path,
                s.first_name,
                s.last_name,
                sa.business_name
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
            WHERE p.is_active = 1 
            AND p.last_price_update >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND p.price_trend != 'stable'
            ORDER BY ABS(p.price_change_percentage) DESC
            LIMIT 20
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Price trends error: " . $e->getMessage());
        return false;
    }
}

function createPriceAlert($product_id, $email, $name, $alert_type, $target_price = null, $threshold = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO price_alerts (product_id, customer_email, customer_name, alert_type, target_price, threshold_percentage)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            target_price = VALUES(target_price),
            threshold_percentage = VALUES(threshold_percentage),
            is_active = 1,
            updated_at = NOW()
        ");
        
        return $stmt->execute([$product_id, $email, $name, $alert_type, $target_price, $threshold]);
    } catch (PDOException $e) {
        error_log("Price alert error: " . $e->getMessage());
        return false;
    }
}

function getPriceChartData($product_id, $days = 30) {
    global $pdo;
    
    try {
        // Get price history with dates
        $stmt = $pdo->prepare("
            SELECT 
                new_price as price,
                DATE_FORMAT(changed_at, '%Y-%m-%d') as date,
                changed_at as datetime
            FROM product_price_history 
            WHERE product_id = ? 
            AND changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY changed_at ASC
        ");
        
        $stmt->execute([$product_id, $days]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add current price as the latest point
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current) {
            $history[] = [
                'price' => $current['price'],
                'date' => date('Y-m-d'),
                'datetime' => date('Y-m-d H:i:s')
            ];
        }
        
        return $history;
    } catch (PDOException $e) {
        error_log("Price chart error: " . $e->getMessage());
        return false;
    }
}

// Handle API requests
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'history':
        $product_id = $_GET['product_id'] ?? 0;
        $days = $_GET['days'] ?? 30;
        $data = getPriceHistory($product_id, $days);
        break;
        
    case 'statistics':
        $product_id = $_GET['product_id'] ?? 0;
        $period = $_GET['period'] ?? 30;
        $data = getPriceStatistics($product_id, $period);
        break;
        
    case 'trends':
        $days = $_GET['days'] ?? 7;
        $data = getPriceTrends($days);
        break;
        
    case 'chart_data':
        $product_id = $_GET['product_id'] ?? 0;
        $days = $_GET['days'] ?? 30;
        $data = getPriceChartData($product_id, $days);
        break;
        
    case 'create_alert':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $data = createPriceAlert(
                $input['product_id'], 
                $input['email'], 
                $input['name'], 
                $input['alert_type'], 
                $input['target_price'] ?? null,
                $input['threshold'] ?? null
            );
        } else {
            $data = ['error' => 'POST method required'];
        }
        break;
        
    default:
        $data = ['error' => 'Invalid action'];
}

echo json_encode([
    'success' => $data !== false,
    'data' => $data,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>