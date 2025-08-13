<?php
// price_monitoring_admin.php - Admin endpoint for price monitoring
require_once '../includes/db_connect.php';

// Simple admin check - replace with your actual admin authentication
if (!isset($_SESSION['admin_id'])) {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Admin authentication required']);
        exit;
    }
}

header('Content-Type: application/json');

function getTopPriceChanges($days = 7, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name as product_name,
                p.price as current_price,
                p.previous_price,
                p.price_change_percentage,
                p.price_trend,
                p.last_price_update,
                s.first_name,
                s.last_name,
                sa.business_name,
                COUNT(ph.id) as changes_count
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
            LEFT JOIN product_price_history ph ON p.id = ph.product_id 
                AND ph.changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            WHERE p.is_active = 1
            GROUP BY p.id
            HAVING changes_count > 0
            ORDER BY ABS(p.price_change_percentage) DESC
            LIMIT ?
        ");
        
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Top price changes error: " . $e->getMessage());
        return false;
    }
}

function getPriceAlertsReport() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                alert_type,
                COUNT(*) as total_alerts,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_alerts,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_alerts
            FROM price_alerts
            GROUP BY alert_type
            
            UNION ALL
            
            SELECT 
                'TOTAL' as alert_type,
                COUNT(*) as total_alerts,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_alerts,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_alerts
            FROM price_alerts
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Price alerts report error: " . $e->getMessage());
        return false;
    }
}

function updateDailyStats() {
    global $pdo;
    
    try {
        // Check if stored procedure exists, if not create basic update
        $stmt = $pdo->prepare("
            UPDATE products p 
            SET p.previous_price = p.price,
                p.last_price_update = NOW()
            WHERE p.is_active = 1
        ");
        
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Daily stats update error: " . $e->getMessage());
        return false;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_daily_stats':
            $result = updateDailyStats();
            echo json_encode([
                'success' => $result, 
                'message' => $result ? 'Daily stats updated' : 'Failed to update stats'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// Handle GET requests for reports
$report = $_GET['report'] ?? '';
$data = [];

switch ($report) {
    case 'top_changes':
        $days = intval($_GET['days'] ?? 7);
        $limit = intval($_GET['limit'] ?? 10);
        $data = getTopPriceChanges($days, $limit);
        break;
        
    case 'alerts_summary':
        $data = getPriceAlertsReport();
        break;
        
    default:
        $data = ['error' => 'Invalid report type. Available reports: top_changes, alerts_summary'];
}

echo json_encode([
    'success' => $data !== false,
    'data' => $data,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>