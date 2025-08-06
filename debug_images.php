<?php
// corrected_debug_images.php - Place this in your OroMarket root directory

require_once 'includes/db_connect.php';

echo "<h2>OroMarket Image Debug Information</h2>";

// Get the correct base path for your OroMarket project
$oro_market_base = __DIR__; // Since this script is in the root directory

echo "<p><strong>OroMarket Base Directory:</strong> $oro_market_base</p>";

try {
    // 1. Check what's in the product_images table
    echo "<h3>1. Product Images Table Data:</h3>";
    $sql = "SELECT pi.product_id, pi.image_path, pi.is_primary, p.name 
            FROM product_images pi 
            LEFT JOIN products p ON pi.product_id = p.id 
            ORDER BY pi.product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $image_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($image_records)) {
        echo "<p style='color: red;'>No records found in product_images table!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Image Path (DB)</th><th>Is Primary</th><th>File Exists?</th><th>Full Server Path</th><th>Web URL</th></tr>";
        
        foreach ($image_records as $record) {
            // Try different path combinations
            $stored_path = ltrim($record['image_path'], '/');
            $full_path_1 = $oro_market_base . '/' . $stored_path;
            $full_path_2 = $oro_market_base . '/uploads/products/' . basename($record['image_path']);
            
            $file_exists_1 = file_exists($full_path_1) ? 'YES' : 'NO';
            $file_exists_2 = file_exists($full_path_2) ? 'YES' : 'NO';
            
            $final_exists = ($file_exists_1 === 'YES') ? 'YES (Path 1)' : (($file_exists_2 === 'YES') ? 'YES (Path 2)' : 'NO');
            $color = (strpos($final_exists, 'YES') !== false) ? 'green' : 'red';
            
            $web_url = '/' . $stored_path;
            
            echo "<tr>";
            echo "<td>{$record['product_id']}</td>";
            echo "<td>{$record['name']}</td>";
            echo "<td>{$record['image_path']}</td>";
            echo "<td>{$record['is_primary']}</td>";
            echo "<td style='color: $color;'>{$final_exists}</td>";
            echo "<td>Path1: {$full_path_1}<br>Path2: {$full_path_2}</td>";
            echo "<td><a href='$web_url' target='_blank'>$web_url</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Check what files actually exist in uploads/products/
    echo "<h3>2. Files in uploads/products/ directory:</h3>";
    $upload_dir = $oro_market_base . '/uploads/products/';
    echo "<p><strong>Checking directory:</strong> $upload_dir</p>";
    
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        $image_files = array_filter($files, function($file) {
            return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
        });
        
        if (empty($image_files)) {
            echo "<p style='color: red;'>No image files found in uploads/products/</p>";
        } else {
            echo "<p style='color: green;'>Found " . count($image_files) . " image files:</p>";
            echo "<ul>";
            foreach ($image_files as $file) {
                $web_path = "/uploads/products/$file";
                $file_size = filesize($upload_dir . $file);
                echo "<li><strong>$file</strong> (Size: " . number_format($file_size) . " bytes) - <a href='$web_path' target='_blank'>View</a></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>Directory uploads/products/ does not exist at: $upload_dir</p>";
        
        // Check if uploads directory exists
        $uploads_dir = $oro_market_base . '/uploads/';
        if (is_dir($uploads_dir)) {
            echo "<p style='color: orange;'>But uploads/ directory exists. Contents:</p>";
            $upload_contents = scandir($uploads_dir);
            echo "<ul>";
            foreach ($upload_contents as $item) {
                if ($item !== '.' && $item !== '..') {
                    $item_path = $uploads_dir . $item;
                    $type = is_dir($item_path) ? 'Directory' : 'File';
                    echo "<li>$item ($type)</li>";
                }
            }
            echo "</ul>";
        }
    }
    
    // 3. Test the current fetchProducts function
    echo "<h3>3. Testing Image URL Generation:</h3>";
    
    // Include your current fetch_products.php
    if (file_exists('customer/fetch_products.php')) {
        include_once 'customer/fetch_products.php';
        $products = fetchProducts(5); // Get first 5 products
        
        if (empty($products)) {
            echo "<p style='color: red;'>No products returned from fetchProducts()</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Product ID</th><th>Name</th><th>Primary Image (DB)</th><th>Generated URL</th><th>Image Test</th></tr>";
            
            foreach ($products as $product) {
                $image_test = "<img src='{$product['image_url']}' width='50' height='50' style='border: 1px solid #ccc;' onerror=\"this.style.border='2px solid red'; this.alt='❌ Failed';\"> <br><small>{$product['image_url']}</small>";
                
                echo "<tr>";
                echo "<td>{$product['id']}</td>";
                echo "<td>{$product['name']}</td>";
                echo "<td>{$product['primary_image']}</td>";
                echo "<td>{$product['image_url']}</td>";
                echo "<td>$image_test</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>customer/fetch_products.php not found!</p>";
    }
    
    // 4. Directory permissions check
    echo "<h3>4. Directory Permissions:</h3>";
    $dirs_to_check = [
        $oro_market_base . '/uploads/',
        $oro_market_base . '/uploads/products/',
        $oro_market_base . '/assets/',
        $oro_market_base . '/assets/img/'
    ];
    
    foreach ($dirs_to_check as $dir) {
        if (is_dir($dir)) {
            $perms = substr(sprintf('%o', fileperms($dir)), -4);
            $readable = is_readable($dir) ? 'YES' : 'NO';
            $writable = is_writable($dir) ? 'YES' : 'NO';
            echo "<p style='color: green;'>✅ $dir - Permissions: $perms, Readable: $readable, Writable: $writable</p>";
        } else {
            echo "<p style='color: red;'>❌ $dir - Directory does not exist</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Quick Fix Instructions:</h3>";
echo "<ol>";
echo "<li>Create a default product image at <code>/assets/img/default-product.jpg</code></li>";
echo "<li>Replace your <code>getProductImageUrl</code> function with the corrected version</li>";
echo "<li>Use the <code>findProductImageOroMarket</code> function for better file detection</li>";
echo "</ol>";
?>