<?php
// ajax_sort_products.php
require_once 'fetch_products.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['sort_by'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$sort_by = $input['sort_by'];
$search = isset($input['search']) ? trim($input['search']) : null;
$category_id = isset($input['category_id']) ? (int)$input['category_id'] : null;
$limit = isset($input['limit']) ? (int)$input['limit'] : 12;

// Map frontend sort values to backend sort values
$sort_mapping = [
    'relevance' => 'created_at',
    'price-low' => 'price',
    'price-high' => 'price',
    'rating' => 'rating',
    'newest' => 'created_at',
    'most_viewed' => 'most_viewed'
];

$sort_order = 'DESC';
if ($sort_by === 'price-low') {
    $sort_order = 'ASC';
}

$mapped_sort = isset($sort_mapping[$sort_by]) ? $sort_mapping[$sort_by] : 'created_at';

try {
    // Fetch products based on sorting criteria
    if (!empty($search)) {
        $products = searchProducts($search, $category_id, null, null, $sort_by, $limit);
    } else {
        $products = fetchProducts($limit, $category_id, null, null, null, null, null, $mapped_sort, $sort_order);
    }
    
    // Process products for JSON output
    foreach ($products as &$product) {
        // Ensure all necessary fields are present
        $product['short_description'] = truncateDescription($product['description'], 100);
        $product['view_count'] = (int)($product['view_count'] ?? 0);
        $product['is_featured'] = (bool)($product['is_featured'] ?? false);
        
        // Remove any sensitive data
        unset($product['seller_id']);
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products),
        'sort_by' => $sort_by
    ]);
    
} catch (Exception $e) {
    error_log("Error in ajax_sort_products.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching products',
        'error' => $e->getMessage()
    ]);
}
?>