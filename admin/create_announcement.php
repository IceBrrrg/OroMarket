<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get and validate input data
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
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

if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
    $errors[] = 'Invalid priority level';
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
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
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
            title, content, priority, target_audience, expiry_date, 
            is_pinned, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $title, $content, $priority, $target_audience, 
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
                // This is a simplified approach - you might want to create a unified users view
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

            // Insert email notifications into a queue table
            if (!empty($users)) {
                $email_stmt = $pdo->prepare("
                    INSERT INTO email_queue (
                        recipient_email, recipient_name, subject, body, 
                        template_type, created_at, status
                    ) VALUES (?, ?, ?, ?, 'announcement', NOW(), 'pending')
                ");

                $email_subject = "New Announcement: " . $title;
                $email_body = $this->generateAnnouncementEmailBody($title, $content, $priority);

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
        }
    }

    // Commit transaction
    $pdo->commit();

    // Log the action
    error_log("Admin {$_SESSION['username']} created announcement: {$title}");

    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Announcement created successfully!',
        'announcement_id' => $announcement_id,
        'emails_queued' => $send_email && !empty($users) ? count($users) : 0
    ]);

} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    error_log("Error creating announcement: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create announcement. Please try again.'
    ]);
}

// Helper function to generate email body
function generateAnnouncementEmailBody($title, $content, $priority) {
    $priority_colors = [
        'low' => '#17a2b8',
        'medium' => '#ffc107', 
        'high' => '#fd7e14',
        'urgent' => '#dc3545'
    ];
    
    $priority_color = $priority_colors[$priority] ?? '#6c757d';
    $priority_text = ucfirst($priority) . ' Priority';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2c3e50, #3498db); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 25px; border-radius: 0 0 8px 8px; }
            .priority-badge { 
                background: {$priority_color}; 
                color: white; 
                padding: 5px 10px; 
                border-radius: 15px; 
                font-size: 12px; 
                font-weight: bold;
                display: inline-block;
                margin-bottom: 15px;
            }
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
                <div class='priority-badge'>{$priority_text}</div>
                <h3 style='color: #2c3e50; margin-bottom: 15px;'>{$title}</h3>
                <div style='background: white; padding: 20px; border-radius: 5px; border-left: 4px solid {$priority_color};'>
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