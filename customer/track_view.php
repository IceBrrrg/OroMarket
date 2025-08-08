<?php
// track_view.php - UPDATED VERSION with better debugging and no cooldown for testing
session_start();
require_once 'fetch_products.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$raw_input = file_get_contents('php://input');
error_log("Raw input received: " . $raw_input);

$input = json_decode($raw_input, true);

if (!$input || !isset($input['product_id']) || !isset($input['action'])) {
    error_log("Invalid input: " . json_encode($input));
    echo json_encode(['success' => false, 'message' => 'Invalid input', 'received' => $input]);
    exit;
}

$product_id = (int)$input['product_id'];
$action = $input['action'];

error_log("Processing view tracking for product ID: $product_id, action: $action");

if ($action === 'track_view') {
    // Get user ID if logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Ensure session is started and get session ID
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $session_id = session_id();
    
    error_log("Tracking view - User ID: " . ($user_id ?? 'null') . ", Session ID: " . $session_id);
    
    // Get status before tracking
    $status_before = checkViewTrackingStatus($product_id);
    $count_before = $status_before['view_data'] ? (int)$status_before['view_data']['view_count'] : 0;
    
    error_log("View count before tracking: " . $count_before);
    
    // Use no-restriction tracking for testing - you can switch back to trackProductView() later
    $success = trackProductViewNoRestriction($product_id, $user_id, $session_id);
    
    error_log("Track view result: " . ($success ? 'success' : 'failed'));
    
    if ($success) {
        // Get updated status
        $status_after = checkViewTrackingStatus($product_id);
        $count_after = $status_after['view_data'] ? (int)$status_after['view_data']['view_count'] : 0;
        
        error_log("View count after tracking: " . $count_after);
        
        echo json_encode([
            'success' => true,
            'message' => 'View tracked successfully',
            'new_view_count' => $count_after,
            'before_count' => $count_before,
            'increment' => $count_after - $count_before,
            'debug' => [
                'product_id' => $product_id,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'tracking_result' => $success,
                'status_before' => $status_before,
                'status_after' => $status_after,
                'time' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to track view',
            'debug' => [
                'product_id' => $product_id,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'status_before' => $status_before ?? null,
                'time' => date('Y-m-d H:i:s')
            ]
        ]);
    }
} else if ($action === 'get_status') {
    // New action to just get current status without tracking
    $status = checkViewTrackingStatus($product_id);
    echo json_encode([
        'success' => true,
        'message' => 'Status retrieved',
        'status' => $status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>