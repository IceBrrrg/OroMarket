<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    header("Location: announcements.php?error=" . urlencode('Unauthorized access'));
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header("Location: announcements.php?error=" . urlencode('Method not allowed'));
    exit();
}

// Get and validate input data
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$target_audience = $_POST['target_audience'] ?? 'all';
$expiry_date = $_POST['expiry_date'] ?? null;
$send_email = isset($_POST['send_email']) ? 1 : 0;
$pin_announcement = isset($_POST['pin_announcement']) ? 1 : 0;

// Validation
$errors = [];

if (empty($title)) {
    $errors[] = 'Title is required';
} elseif (strlen($title) > 200) {
    $errors[] = 'Title must not exceed 200 characters';
}

if (empty($content)) {
    $errors[] = 'Content is required';
} elseif (strlen($content) > 2000) {
    $errors[] = 'Content must not exceed 2000 characters';
}

if (!in_array($target_audience, ['all', 'sellers', 'customers', 'admins'])) {
    $errors[] = 'Invalid target audience';
}

if (!empty($expiry_date)) {
    $expiry_timestamp = strtotime($expiry_date);
    if (!$expiry_timestamp || $expiry_timestamp <= time()) {
        $errors[] = 'Expiry date must be in the future';
    }
}

if (!empty($errors)) {
    header("Location: announcements.php?error=" . urlencode(implode('. ', $errors)));
    exit();
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // If this announcement is pinned, unpin all other announcements
    if ($pin_announcement) {
        $stmt = $pdo->prepare("UPDATE announcements SET is_pinned = 0");
        $stmt->execute();
    }

    // Insert the announcement
    $stmt = $pdo->prepare("
        INSERT INTO announcements (
            title, content, target_audience, expiry_date, 
            is_pinned, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $title, $content, $target_audience, 
        $expiry_date, $pin_announcement, $_SESSION['user_id']
    ]);

    $announcement_id = $pdo->lastInsertId();

    // If send_email is enabled, queue email notifications
    if ($send_email) {
        // Get target users based on audience
        $user_query = "";
        $params = [];
        
        switch ($target_audience) {
            case 'sellers':
                $user_query = "SELECT id, email, first_name, last_name FROM sellers WHERE status = 'approved' AND is_active = 1";
                break;
            case 'customers':
                $user_query = "SELECT id, email, first_name, last_name FROM users WHERE is_active = 1";
                break;
            case 'admins':
                $user_query = "SELECT id, email, username as first_name, '' as last_name FROM admin_users WHERE is_active = 1";
                break;
            case 'all':
                // For 'all', we'll need to get from multiple tables
                $user_query = "
                    SELECT id, email, first_name, last_name, 'seller' as user_type FROM sellers WHERE status = 'approved' AND is_active = 1
                    UNION ALL
                    SELECT id, email, first_name, last_name, 'customer' as user_type FROM users WHERE is_active = 1
                    UNION ALL
                    SELECT id, email, username as first_name, '' as last_name, 'admin' as user_type FROM admin_users WHERE is_active = 1
                ";
                break;
        }

        if ($user_query) {
            $stmt = $pdo->prepare($user_query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Insert email notifications into a queue table (if you have one)
            if (!empty($users)) {
                // Check if email_queue table exists, if not, skip email functionality
                try {
                    $check_table = $pdo->query("SHOW TABLES LIKE 'email_queue'");
                    if ($check_table->rowCount() > 0) {
                        $email_stmt = $pdo->prepare("
                            INSERT INTO email_queue (
                                recipient_email, recipient_name, subject, body, 
                                template_type, created_at, status
                            ) VALUES (?, ?, ?, ?, 'announcement', NOW(), 'pending')
                        ");

                        $email_subject = "New Announcement: " . $title;
                        $email_body = generateAnnouncementEmailBody($title, $content);

                        foreach ($users as $user) {
                            $recipient_name = trim($user['first_name'] . ' ' . $user['last_name']);
                            if (empty($recipient_name)) {
                                $recipient_name = $user['email'];
                            }
                            
                            $email_stmt->execute([
                                $user['email'],
                                $recipient_name,
                                $email_subject,
                                $email_body
                            ]);
                        }
                    }
                } catch (Exception $e) {
                    // Email functionality is optional, don't fail the announcement creation
                    error_log("Email queue functionality not available: " . $e->getMessage());
                }
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    // Log the action
    error_log("Admin {$_SESSION['username']} created announcement: {$title}");

    // Redirect with success message
    header("Location: announcements.php?success=" . urlencode('Announcement created successfully!'));
    exit();

} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    error_log("Error creating announcement: " . $e->getMessage());
    
    header("Location: announcements.php?error=" . urlencode('Failed to create announcement. Please try again.'));
    exit();
}

// Helper function to generate email body
function generateAnnouncementEmailBody($title, $content) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2c3e50, #3498db); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 25px; border-radius: 0 0 8px 8px; }
            .footer { text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2 style='margin: 0;'>📢 New Announcement</h2>
                <p style='margin: 5px 0 0 0; opacity: 0.9;'>Oroquieta Marketplace</p>
            </div>
            <div class='content'>
                <h3 style='color: #2c3e50; margin-bottom: 15px;'>{$title}</h3>
                <div style='background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #3498db;'>
                    " . nl2br(htmlspecialchars($content)) . "
                </div>
                <div class='footer'>
                    <p>This announcement was sent from Oroquieta Marketplace Admin Panel</p>
                    <p>Date: " . date('F j, Y g:i A') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>