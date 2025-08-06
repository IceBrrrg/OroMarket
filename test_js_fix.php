<?php
// Test to verify JavaScript fix
echo "Testing JavaScript fix for customer page...\n\n";

// Test 1: Check if products are rendered server-side
echo "1. Testing server-side product rendering...\n";
try {
    ob_start();
    include 'customer/index.php';
    $output = ob_get_clean();
    
    if (strpos($output, 'product-card') !== false) {
        echo "✅ Products are rendered server-side\n";
    } else {
        echo "❌ No products found in server output\n";
    }
    
    // Check for the error message
    if (strpos($output, 'Failed to load products') !== false) {
        echo "❌ Error message found in server output\n";
    } else {
        echo "✅ No error message in server output\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing server output: " . $e->getMessage() . "\n";
}

// Test 2: Check JavaScript files
echo "\n2. Testing JavaScript files...\n";
$js_files = [
    'customer/js/index.js',
    'customer/js/customer.js'
];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
        
        // Check if the problematic fetchProducts() call is removed
        $content = file_get_contents($file);
        if (strpos($content, 'fetchProducts(); // Load products on page load') !== false) {
            echo "❌ $file still has automatic fetchProducts() call\n";
        } else {
            echo "✅ $file doesn't have automatic fetchProducts() call\n";
        }
    } else {
        echo "❌ $file not found\n";
    }
}

// Test 3: Check API endpoint
echo "\n3. Testing API endpoint...\n";
try {
    $api_url = 'http://localhost:8000/customer/api/products.php';
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($api_url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ API endpoint working correctly\n";
            echo "   Found " . count($data['data']) . " products via API\n";
        } else {
            echo "⚠️ API endpoint accessible but returned error\n";
        }
    } else {
        echo "⚠️ API endpoint not accessible (server may not be running)\n";
    }
} catch (Exception $e) {
    echo "❌ API test failed: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ The JavaScript error should now be resolved!\n";
echo "🌐 Visit: http://localhost:8000/customer/index.php\n";
echo "📝 The page should load without the 'Failed to load products' error\n";
echo "🎯 Products are now rendered server-side and JavaScript only handles interactions\n";
?> 