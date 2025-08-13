<?php
session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Validate required fields
if (empty($_POST['complainant_name']) || empty($_POST['complainant_email']) || 
    empty($_POST['seller_id']) || empty($_POST['title']) || empty($_POST['description'])) {
    echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
    exit();
}

// Sanitize input data
$complainant_name = trim($_POST['complainant_name']);
$complainant_email = trim($_POST['complainant_email']);
$seller_id = (int)$_POST['seller_id'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);

// Validate email format
if (!filter_var($complainant_email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Please enter a valid email address.'); window.history.back();</script>";
    exit();
}

// Try to save to database
try {
    require_once '../includes/db_connect.php';
    
    // Check if complaints table exists, if not create it
    $stmt = $pdo->query("SHOW TABLES LIKE 'complaints'");
    if ($stmt->rowCount() == 0) {
        // Create simple complaints table
        $pdo->exec("
            CREATE TABLE `complaints` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `complainant_name` varchar(100) NOT NULL,
                `complainant_email` varchar(100) NOT NULL,
                `seller_id` int(11) NOT NULL,
                `status` enum('pending','resolved') NOT NULL DEFAULT 'pending',
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_complaints_seller` (`seller_id`),
                KEY `idx_complaints_status` (`status`),
                CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    
    // Insert complaint
    $stmt = $pdo->prepare("
        INSERT INTO complaints (title, description, complainant_name, complainant_email, seller_id, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$title, $description, $complainant_name, $complainant_email, $seller_id]);
    
    // Success message
    echo "
    <script>
        alert('Your complaint has been submitted successfully! We will review it and get back to you via email.');
        window.location.href = 'index.php';
    </script>";
    
} catch (Exception $e) {
    // If database fails, save to file as backup
    $complaint_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'complainant_name' => $complainant_name,
        'complainant_email' => $complainant_email,
        'seller_id' => $seller_id,
        'title' => $title,
        'description' => $description
    ];
    
    // Create complaints directory if it doesn't exist
    $complaints_dir = '../data/complaints/';
    if (!file_exists($complaints_dir)) {
        mkdir($complaints_dir, 0755, true);
    }
    
    // Save to file
    $filename = $complaints_dir . 'complaint_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
    file_put_contents($filename, json_encode($complaint_data, JSON_PRETTY_PRINT));
    
    echo "
    <script>
        alert('Your complaint has been submitted successfully! We will review it and get back to you via email.');
        window.location.href = 'index.php';
    </script>";
}
?>
