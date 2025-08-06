<?php
// Test customer page access
echo "Testing customer page access...\n";

// Test if we can include the customer index file
try {
    // Start output buffering to capture any output
    ob_start();
    
    // Include the customer index file
    include 'customer/index.php';
    
    // Get the output
    $output = ob_get_clean();
    
    // Check if the output contains expected content
    if (strpos($output, 'product-card') !== false) {
        echo "✅ Customer page loads successfully with product cards\n";
    } else {
        echo "⚠️ Customer page loads but may not have products\n";
    }
    
    // Check for any PHP errors
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'Warning') !== false) {
        echo "❌ Customer page has errors\n";
    } else {
        echo "✅ No PHP errors detected\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading customer page: " . $e->getMessage() . "\n";
}

echo "\nTest completed. You can now access the customer page at: http://localhost:8000/customer/index.php\n";
?> 