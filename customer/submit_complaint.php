<?php
session_start();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Validate required fields
if (
    empty($_POST['complainant_name']) || empty($_POST['complainant_email']) ||
    empty($_POST['seller_id']) || empty($_POST['title']) || empty($_POST['description'])
) {
    include 'error_modal.php';
    showErrorModal('Please fill in all required fields.', true);
    exit();
}

// Sanitize input data
$complainant_name = trim($_POST['complainant_name']);
$complainant_email = trim($_POST['complainant_email']);
$seller_id = (int) $_POST['seller_id'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);

// Validate email format
if (!filter_var($complainant_email, FILTER_VALIDATE_EMAIL)) {
    include 'error_modal.php';
    showErrorModal('Please enter a valid email address.', true);
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

    // Success message with styled modal
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Complaint Submitted</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>
        <style>
            body {
                margin: 0;
                font-family: 'Inter', sans-serif;
                background: rgba(0, 0, 0, 0.5);
            }
            .success-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2.5rem;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 90%;
                width: 400px;
                animation: modalFadeIn 0.5s ease-out forwards;
            }
            @keyframes modalFadeIn {
                from { 
                    opacity: 0; 
                    transform: translate(-50%, -60%);
                }
                to { 
                    opacity: 1; 
                    transform: translate(-50%, -50%);
                }
            }
            .success-icon {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: #82c408;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                animation: iconScale 0.5s ease-out 0.2s forwards;
                transform: scale(0);
            }
            @keyframes iconScale {
                from { transform: scale(0); }
                to { transform: scale(1); }
            }
            .success-icon i {
                color: white;
                font-size: 2.5rem;
            }
            .modal-title {
                color: #2d3436;
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }
            .modal-message {
                color: #636e72;
                font-size: 1rem;
                line-height: 1.5;
                margin-bottom: 1.5rem;
            }
            .modal-button {
                background: #82c408;
                color: white;
                border: none;
                padding: 0.8rem 2rem;
                border-radius: 8px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .modal-button:hover {
                background: #72ac07;
                transform: translateY(-2px);
            }
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(5px);
                animation: overlayFadeIn 0.5s ease-out forwards;
            }
            @keyframes overlayFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
    </head>
    <body>
        <div class='modal-overlay'>
            <div class='success-modal'>
                <div class='success-icon'>
                    <i class='bi bi-check-lg'></i>
                </div>
                <h4 class='modal-title'>Complaint Submitted Successfully!</h4>
                <p class='modal-message'>Thank you for your feedback. We will review it and get back to you via email soon.</p>
                <button class='modal-button' onclick='window.location.href=\"index.php\"'>
                    Back to Home
                </button>
            </div>
        </div>
    </body>
    </html>";

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

    // Show success modal with backup message
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Complaint Submitted</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>
        <style>
            body {
                margin: 0;
                font-family: 'Inter', sans-serif;
                background: rgba(0, 0, 0, 0.5);
            }
            .success-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2.5rem;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 90%;
                width: 400px;
                animation: modalFadeIn 0.5s ease-out forwards;
            }
            @keyframes modalFadeIn {
                from { 
                    opacity: 0; 
                    transform: translate(-50%, -60%);
                }
                to { 
                    opacity: 1; 
                    transform: translate(-50%, -50%);
                }
            }
            .success-icon {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: #ffc107;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                animation: iconScale 0.5s ease-out 0.2s forwards;
                transform: scale(0);
            }
            @keyframes iconScale {
                from { transform: scale(0); }
                to { transform: scale(1); }
            }
            .success-icon i {
                color: white;
                font-size: 2.5rem;
            }
            .modal-title {
                color: #2d3436;
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }
            .modal-message {
                color: #636e72;
                font-size: 1rem;
                line-height: 1.5;
                margin-bottom: 1.5rem;
            }
            .modal-button {
                background: #ffc107;
                color: #000;
                border: none;
                padding: 0.8rem 2rem;
                border-radius: 8px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .modal-button:hover {
                background: #ffb300;
                transform: translateY(-2px);
            }
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(5px);
                animation: overlayFadeIn 0.5s ease-out forwards;
            }
            @keyframes overlayFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        </style>
    </head>
    <body>
        <div class='modal-overlay'>
            <div class='success-modal'>
                <div class='success-icon'>
                    <i class='bi bi-exclamation'></i>
                </div>
                <h4 class='modal-title'>Complaint Submitted</h4>
                <p class='modal-message'>Your complaint has been received. While we experienced a minor technical issue, your feedback has been saved and will be processed.</p>
                <button class='modal-button' onclick='window.location.href=\"index.php\"'>
                    Back to Market
                </button>
            </div>
        </div>
    </body>
    </html>";
}
?>