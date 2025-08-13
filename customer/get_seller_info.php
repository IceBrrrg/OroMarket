<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data from request body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id']) || !is_numeric($input['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$product_id = (int)$input['product_id'];

try {
    // Fetch seller information based on product ID
    $stmt = $pdo->prepare("
        SELECT 
            s.id as seller_id,
            s.first_name,
            s.last_name,
            s.username,
            sa.business_name,
            sa.business_phone,
            st.stall_number,
            st.section as stall_section
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN seller_applications sa ON s.id = sa.seller_id AND sa.status = 'approved'
        LEFT JOIN stalls st ON st.current_seller_id = s.id AND st.status = 'occupied'
        WHERE p.id = ? AND p.is_active = 1 AND s.status = 'approved'
    ");

    $stmt->execute([$product_id]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($seller) {
        // Format seller name
        $seller_name = !empty($seller['business_name']) 
            ? $seller['business_name'] 
            : $seller['first_name'] . ' ' . $seller['last_name'];

        // Format seller details
        $details = [];
        if (!empty($seller['business_phone'])) {
            $details[] = 'Phone: ' . $seller['business_phone'];
        }
        if (!empty($seller['stall_number'])) {
            $details[] = 'Stall: ' . $seller['stall_number'] . ' (' . $seller['stall_section'] . ')';
        } else {
            $details[] = 'Marketplace Vendor';
        }

        $seller_details = implode(' | ', $details);

        echo json_encode([
            'success' => true,
            'seller' => [
                'seller_id' => $seller['seller_id'],
                'seller_name' => $seller_name,
                'details' => $seller_details
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Seller not found']);
    }

} catch (PDOException $e) {
    error_log("Database error in get_seller_info.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get_seller_info.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>