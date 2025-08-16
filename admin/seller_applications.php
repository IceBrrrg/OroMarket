<?php
// Turn off error display to prevent HTML output in AJAX responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../includes/db_connect.php';

// Include PHPMailer
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Email sending function using PHPMailer or basic mail()
function sendEmail($to_email, $to_name, $subject, $body, $is_html = true) {
    // Updated Gmail SMTP Configuration (same as working test)
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587; // Changed from 465 to 587
    $smtp_username = 'oroquietamarketplace@gmail.com';
    $smtp_password = 'hlmn ezjh arti mkwa';
    $from_email = 'oroquietamarketplace@gmail.com';
    $from_name = 'Oroquieta Marketplace';

    // Log the email attempt
    error_log("Attempting to send email to: " . $to_email . " - Subject: " . $subject);

    // Use PHPMailer (should be available now)
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Changed from SMTPS to STARTTLS
            $mail->Port = $smtp_port;

            // SSL options for Gmail compatibility
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($from_email, $from_name);

            // Content
            $mail->isHTML($is_html);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body); // Plain text version

            // Additional headers to improve delivery
            $mail->addCustomHeader('List-Unsubscribe', '<mailto:' . $from_email . '>');
            $mail->addCustomHeader('X-Mailer', 'Oroquieta Marketplace System');

            // Send the email
            $result = $mail->send();
            
            if ($result) {
                error_log("✅ Email sent successfully to: " . $to_email);
            } else {
                error_log("❌ Email failed to send to: " . $to_email);
            }
            
            return $result;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            error_log("Error Info: " . $mail->ErrorInfo);
            return false;
        }
    } else {
        // PHPMailer not available - this should not happen now
        error_log("❌ PHPMailer not available! Check if files are properly included.");
        return false;
    }
}

// Function to generate approval email template
function getApprovalEmailTemplate($seller_name, $business_name) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2c3e50, #3498db); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-badge { background: #27ae60; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 20px 0; }
            .btn { background: #3498db; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Application Approved!</h1>
                <p>Oroquieta Marketplace</p>
            </div>
            <div class='content'>
                <h2>Congratulations, " . htmlspecialchars($seller_name) . "!</h2>
                
                <div class='success-badge'>✅ APPROVED</div>
                
                <p>Great news! Your seller application for <strong>" . htmlspecialchars($business_name) . "</strong> has been approved by our admin team.</p>
                
                <h3>What happens next?</h3>
                <ul>
                    <li>✅ Your seller account is now active</li>
                    <li>📦 You can start listing your products</li>
                    <li>🏪 Your requested stall has been assigned to you</li>
                    <li>💼 You can access your seller dashboard</li>
                </ul>
                
                <p>You can now log in to your seller account and start managing your marketplace presence.</p>
                
                <a href='#' class='btn'>Access Your Dashboard</a>
                
                <div class='footer'>
                    <p>Welcome to the Oroquieta Marketplace family!</p>
                    <p><strong>Need help?</strong> Contact our support team at oroquietamarketplace@gmail.com</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

// Function to generate rejection email template
function getRejectionEmailTemplate($seller_name, $business_name, $reason = '') {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2c3e50, #e74c3c); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .rejection-badge { background: #e74c3c; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 20px 0; }
            .reason-box { background: #fff; border-left: 4px solid #e74c3c; padding: 15px; margin: 20px 0; }
            .btn { background: #3498db; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Application Update</h1>
                <p>Oroquieta Marketplace</p>
            </div>
            <div class='content'>
                <h2>Dear " . htmlspecialchars($seller_name) . ",</h2>
                
                <div class='rejection-badge'>❌ NOT APPROVED</div>
                
                <p>Thank you for your interest in becoming a seller at Oroquieta Marketplace. After careful review, we regret to inform you that your application for <strong>" . htmlspecialchars($business_name) . "</strong> has not been approved at this time.</p>
                
                " . ($reason ? "
                <div class='reason-box'>
                    <h4>📋 Reason for Decision:</h4>
                    <p>" . htmlspecialchars($reason) . "</p>
                </div>
                " : "") . "
                
                <h3>What you can do:</h3>
                <ul>
                    <li>📞 Contact our support team for more details</li>
                    <li>📝 Review our seller requirements</li>
                    <li>🔄 Consider reapplying in the future after addressing any issues</li>
                </ul>
                
                <p>We appreciate your interest in our marketplace and encourage you to reach out if you have any questions about this decision.</p>
                
                <div class='footer'>
                    <p><strong>Questions?</strong> Contact our support team at oroquietamarketplace@gmail.com</p>
                    <p>Thank you for your understanding.</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

// Admin approval process functions
function approveSeller($seller_id, $pdo) {
    try {
        $pdo->beginTransaction();

        // Get seller information for email
        $stmt = $pdo->prepare("SELECT s.email, s.first_name, s.last_name, s.username, sa.business_name 
                               FROM sellers s 
                               LEFT JOIN seller_applications sa ON s.id = sa.seller_id 
                               WHERE s.id = ?");
        $stmt->execute([$seller_id]);
        $seller_info = $stmt->fetch();

        if (!$seller_info) {
            throw new Exception("Seller not found");
        }

        // Update seller status to approved
        $stmt = $pdo->prepare("UPDATE sellers SET status = 'approved', is_active = 1 WHERE id = ?");
        $stmt->execute([$seller_id]);

        // Update seller application status
        $stmt = $pdo->prepare("UPDATE seller_applications SET status = 'approved' WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // Handle stall assignment (your existing code)
        $stmt = $pdo->prepare("SELECT stall_id FROM stall_applications WHERE seller_id = ? AND status = 'pending'");
        $stmt->execute([$seller_id]);
        $stall_application = $stmt->fetch();

        if ($stall_application) {
            $stmt = $pdo->prepare("UPDATE stall_applications SET status = 'approved' WHERE seller_id = ?");
            $stmt->execute([$seller_id]);

            $stmt = $pdo->prepare("UPDATE stalls SET status = 'occupied', current_seller_id = ? WHERE id = ?");
            $stmt->execute([$seller_id, $stall_application['stall_id']]);
        }

        // Send notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_type, recipient_id, title, message, link) 
            VALUES ('seller', ?, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php')
        ");
        $stmt->execute([$seller_id]);

        $pdo->commit();

        // Send approval email using the updated function
        if ($seller_info['email']) {
            $seller_name = trim(($seller_info['first_name'] ?? '') . ' ' . ($seller_info['last_name'] ?? '')) ?: $seller_info['username'];
            $business_name = $seller_info['business_name'] ?: 'your business';
            
            $subject = "🎉 Your Seller Application Has Been Approved - Oroquieta Marketplace";
            $email_body = getApprovalEmailTemplate($seller_name, $business_name);
            
            $email_sent = sendEmail($seller_info['email'], $seller_name, $subject, $email_body);
            
            if ($email_sent) {
                error_log("✅ Approval email sent successfully to: " . $seller_info['email']);
            } else {
                error_log("❌ Failed to send approval email to: " . $seller_info['email']);
            }
        }

        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error approving seller: " . $e->getMessage());
        return false;
    }
}

// When admin rejects a seller application:
function rejectSeller($seller_id, $pdo, $reason = '')
{
    try {
        $pdo->beginTransaction();

        // Get seller information for email
        $stmt = $pdo->prepare("SELECT s.email, s.first_name, s.last_name, s.username, sa.business_name 
                               FROM sellers s 
                               LEFT JOIN seller_applications sa ON s.id = sa.seller_id 
                               WHERE s.id = ?");
        $stmt->execute([$seller_id]);
        $seller_info = $stmt->fetch();

        if (!$seller_info) {
            throw new Exception("Seller not found");
        }

        // First, get all stall applications for this seller (not just pending ones)
        $stmt = $pdo->prepare("SELECT stall_id FROM stall_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        $stall_applications = $stmt->fetchAll();

        // Update seller status to rejected
        $stmt = $pdo->prepare("UPDATE sellers SET status = 'rejected', is_active = 0 WHERE id = ?");
        $stmt->execute([$seller_id]);

        // Update seller application status
        $stmt = $pdo->prepare("UPDATE seller_applications SET status = 'rejected', admin_notes = ? WHERE seller_id = ?");
        $stmt->execute([$reason, $seller_id]);

        // Free up ALL stalls associated with this seller
        if ($stall_applications) {
            foreach ($stall_applications as $stall_app) {
                // Update stall application status to rejected
                $stmt = $pdo->prepare("UPDATE stall_applications SET status = 'rejected' WHERE seller_id = ? AND stall_id = ?");
                $stmt->execute([$seller_id, $stall_app['stall_id']]);

                // Free up the stall - make it available and remove seller assignment
                $stmt = $pdo->prepare("UPDATE stalls SET status = 'available', current_seller_id = NULL WHERE id = ?");
                $stmt->execute([$stall_app['stall_id']]);

                // Log the stall update for debugging
                error_log("Freed up stall ID: " . $stall_app['stall_id'] . " for rejected seller ID: " . $seller_id);
            }
        }

        // Send notification to seller
        $message = "Your seller application has been reviewed and rejected. " . ($reason ? "Reason: $reason" : "Please contact support for more details.");
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_type, recipient_id, title, message, link) 
            VALUES ('seller', ?, 'Application Rejected', ?, 'application_status.php')
        ");
        $stmt->execute([$seller_id, $message]);

        $pdo->commit();

        // Send rejection email
        if ($seller_info['email']) {
            $seller_name = trim(($seller_info['first_name'] ?? '') . ' ' . ($seller_info['last_name'] ?? '')) ?: $seller_info['username'];
            $business_name = $seller_info['business_name'] ?: 'your business';
            
            $subject = "Application Status Update - Oroquieta Marketplace";
            $email_body = getRejectionEmailTemplate($seller_name, $business_name, $reason);
            
            $email_sent = sendEmail($seller_info['email'], $seller_name, $subject, $email_body);
            
            if (!$email_sent) {
                error_log("Failed to send rejection email to: " . $seller_info['email']);
            }
        }

        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error rejecting seller: " . $e->getMessage());
        return false;
    }
}

// Alternative: Complete deletion function (if you prefer to delete all data)
function deleteSeller($seller_id, $pdo, $reason = '')
{
    try {
        $pdo->beginTransaction();

        // Get seller information for email before deletion
        $stmt = $pdo->prepare("SELECT s.email, s.first_name, s.last_name, s.username, sa.business_name 
                               FROM sellers s 
                               LEFT JOIN seller_applications sa ON s.id = sa.seller_id 
                               WHERE s.id = ?");
        $stmt->execute([$seller_id]);
        $seller_info = $stmt->fetch();

        // Get all stall applications for this seller first
        $stmt = $pdo->prepare("SELECT stall_id FROM stall_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        $stall_applications = $stmt->fetchAll();

        // Free up all associated stalls
        if ($stall_applications) {
            foreach ($stall_applications as $stall_app) {
                $stmt = $pdo->prepare("UPDATE stalls SET status = 'available', current_seller_id = NULL WHERE id = ?");
                $stmt->execute([$stall_app['stall_id']]);
            }
        }

        // Delete in correct order to avoid foreign key constraints

        // 1. Delete notifications
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE recipient_type = 'seller' AND recipient_id = ?");
        $stmt->execute([$seller_id]);

        // 2. Delete products (if any)
        $stmt = $pdo->prepare("DELETE FROM products WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // 3. Delete stall applications
        $stmt = $pdo->prepare("DELETE FROM stall_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // 4. Delete seller application
        $stmt = $pdo->prepare("DELETE FROM seller_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // 5. Finally delete the seller account
        $stmt = $pdo->prepare("DELETE FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);

        // Optional: Log the deletion for audit purposes
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (admin_id, action_type, details, created_at) 
            VALUES (?, 'seller_deleted', ?, NOW())
        ");
        $admin_id = $_SESSION['user_id'] ?? 0;
        $details = "Deleted rejected seller ID: $seller_id" . ($reason ? " - Reason: $reason" : "");
        $stmt->execute([$admin_id, $details]);

        $pdo->commit();

        // Send rejection email before deletion (if email exists)
        if ($seller_info && $seller_info['email']) {
            $seller_name = trim(($seller_info['first_name'] ?? '') . ' ' . ($seller_info['last_name'] ?? '')) ?: $seller_info['username'];
            $business_name = $seller_info['business_name'] ?: 'your business';
            
            $subject = "Application Status Update - Oroquieta Marketplace";
            $email_body = getRejectionEmailTemplate($seller_name, $business_name, $reason);
            
            $email_sent = sendEmail($seller_info['email'], $seller_name, $subject, $email_body);
            
            if (!$email_sent) {
                error_log("Failed to send rejection email to: " . $seller_info['email']);
            }
        }

        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting seller: " . $e->getMessage());
        return false;
    }
}

// Updated handler for rejection - you can choose which function to use
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'reject') {
    // Prevent any output before JSON
    ob_clean();
    
    try {
        $application_id = (int)$_POST['application_id'];
        $admin_notes = $_POST['admin_notes'] ?? '';

        // Get application details
        $sql = "SELECT seller_id FROM seller_applications WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();

        if ($application) {
            // Choose one of these approaches:

            // Option 1: Just reject but keep data (with proper stall freeing)
            if (rejectSeller($application['seller_id'], $pdo, $admin_notes)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Application rejected and email sent successfully!']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error rejecting application. Please try again.']);
            }

            // Option 2: Complete deletion (uncomment this and comment above if you prefer deletion)
            /*
            if (deleteSeller($application['seller_id'], $pdo, $admin_notes)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Application rejected and email sent successfully!']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error deleting application. Please try again.']);
            }
            */
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Application not found.']);
        }
    } catch (Exception $e) {
        error_log("Error processing rejection: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
    exit();
}

// Debug endpoint to check if script is working
if (isset($_GET['debug'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'working',
        'session_user' => $_SESSION['user_id'] ?? 'not set',
        'is_admin' => $_SESSION['is_admin'] ?? 'not set',
        'post_data' => $_POST,
        'get_data' => $_GET
    ]);
    exit();
}

// Handle application approval/rejection via AJAX
if (isset($_POST['ajax_action']) && isset($_POST['application_id'])) {
    // Start output buffering and clean any previous output
    ob_start();
    ob_clean();
    
    try {
        $application_id = (int)$_POST['application_id'];
        $action = $_POST['ajax_action'];
        $admin_notes = $_POST['admin_notes'] ?? '';

        // Log the action for debugging
        error_log("Processing application action: $action for ID: $application_id");

        if ($action == 'approve') {
            // Get application details
            $sql = "SELECT * FROM seller_applications WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();

            if ($application) {
                error_log("Found application for approval: " . print_r($application, true));
                
                // Use the new approval function
                if (approveSeller($application['seller_id'], $pdo)) {
                    // Update admin notes if provided
                    if ($admin_notes) {
                        $update_sql = "UPDATE seller_applications SET admin_notes = ? WHERE id = ?";
                        $stmt = $pdo->prepare($update_sql);
                        $stmt->execute([$admin_notes, $application_id]);
                    }
                    
                    $response = ['success' => true, 'message' => 'Application approved and email sent successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error approving application. Please try again.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Application not found.'];
            }
        } elseif ($action == 'reject') {
            // Get application details
            $sql = "SELECT seller_id FROM seller_applications WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();

            if ($application) {
                error_log("Found application for rejection: " . print_r($application, true));
                
                // Use the new rejection function
                if (rejectSeller($application['seller_id'], $pdo, $admin_notes)) {
                    $response = ['success' => true, 'message' => 'Application rejected and email sent successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error rejecting application. Please try again.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Application not found.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid action specified.'];
        }
    } catch (Exception $e) {
        error_log("Error processing application action: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $response = ['success' => false, 'message' => 'An error occurred. Please try again.'];
    }

    // Clean any remaining output buffer and send JSON response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get application details for modal
if (isset($_GET['get_application']) && isset($_GET['id'])) {
    // Prevent any output before JSON
    ob_clean();
    
    try {
        $id = (int)$_GET['id'];
        $sql = "SELECT sa.*, s.username, s.email as seller_email, s.phone, s.first_name, s.last_name, s.status as seller_status,
                       st.stall_number, st.section, st.floor_number, st.monthly_rent
                FROM seller_applications sa 
                LEFT JOIN sellers s ON sa.seller_id = s.id 
                LEFT JOIN stall_applications sta ON sa.seller_id = sta.seller_id AND sta.status IN ('pending', 'approved')
                LEFT JOIN stalls st ON sta.stall_id = st.id
                WHERE sa.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($application) {
            // Parse documents if they exist
            if ($application['documents_submitted']) {
                $documents = json_decode($application['documents_submitted'], true);

                if (json_last_error() === JSON_ERROR_NONE && $documents) {
                    $application['documents'] = $documents;
                } else {
                    error_log("JSON decode error: " . json_last_error_msg());
                    $application['documents'] = [];
                }
            } else {
                $application['documents'] = [];
            }

            header('Content-Type: application/json');
            echo json_encode($application);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Application not found']);
        }
    } catch (Exception $e) {
        error_log("Error fetching application: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error occurred']);
    }
    exit();
}

// Count pending applications for notification badge
$pending_count_sql = "SELECT COUNT(*) FROM seller_applications WHERE status = 'pending'";
$pending_count = $pdo->query($pending_count_sql)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/seller_applications.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
        }

        /* Email success indicator */
        .email-indicator {
            font-size: 0.85em;
            margin-top: 5px;
            color: #28a745;
        }

        .email-indicator.error {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h1 class="mb-2">Seller Applications</h1>
                            <p class="mb-0">Review and manage seller applications for marketplace access</p>
                        </div>
                    </div>
                    <?php if ($pending_count > 0): ?>
                        <div class="notification-badge">
                            <span class="badge bg-warning fs-6"><?php echo $pending_count; ?> Pending</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="inbox-container">
                <div class="inbox-header">
                    <div class="inbox-filters">
                        <button class="btn active" onclick="filterApplications('all', this)">
                            <i class="bi bi-inbox"></i> All
                        </button>
                        <button class="btn" onclick="filterApplications('pending', this)">
                            <i class="bi bi-clock"></i> Pending
                            <?php if ($pending_count > 0): ?>
                                <span class="notification-count"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="btn" onclick="filterApplications('approved', this)">
                            <i class="bi bi-check-circle"></i> Approved
                        </button>
                        <button class="btn" onclick="filterApplications('rejected', this)">
                            <i class="bi bi-x-circle"></i> Rejected
                        </button>
                    </div>
                </div>

                <div class="inbox-list" id="inboxList">
                    <?php
                    // Get all applications with seller status
                    $sql = "SELECT sa.*, s.username, s.email as seller_email, s.phone, s.first_name, s.last_name, s.status as seller_status,
                                   st.stall_number, st.section, st.floor_number
                            FROM seller_applications sa 
                            LEFT JOIN sellers s ON sa.seller_id = s.id 
                            LEFT JOIN stall_applications sta ON sa.seller_id = sta.seller_id AND sta.status IN ('pending', 'approved')
                            LEFT JOIN stalls st ON sta.stall_id = st.id
                            ORDER BY sa.created_at DESC";
                    $stmt = $pdo->query($sql);

                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            $is_new = (strtotime($row['created_at']) > strtotime('-24 hours'));
                            $read_status = ($row['status'] == 'pending') ? 'unread' : 'read';

                            $business_name = $row['business_name'] ?? $row['username'] ?? 'N/A';
                            $owner_name = $row['bank_account_name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: $row['username'] ?? 'N/A';
                            $email = $row['business_email'] ?? $row['seller_email'] ?? 'N/A';
                            $phone = $row['business_phone'] ?? $row['phone'] ?? 'N/A';

                            // Create preview text with seller status
                            $preview_parts = [];
                            if ($row['stall_number']) {
                                $preview_parts[] = "Stall: {$row['stall_number']} ({$row['section']})";
                            }
                            $preview_parts[] = "Phone: {$phone}";
                            if ($row['tax_id']) {
                                $preview_parts[] = "Tax ID: {$row['tax_id']}";
                            }
                            if ($row['seller_status']) {
                                $preview_parts[] = "Seller Status: " . ucfirst($row['seller_status']);
                            }
                            $preview_text = implode(' • ', $preview_parts);

                            // Format timestamp
                            $timestamp = date('M d', strtotime($row['created_at']));
                            if (date('Y-m-d') == date('Y-m-d', strtotime($row['created_at']))) {
                                $timestamp = date('g:i A', strtotime($row['created_at']));
                            }

                            echo "<div class='inbox-item {$read_status}' data-status='{$row['status']}' onclick='viewApplication({$row['id']})'>";
                            echo "  <div class='status-dot {$row['status']}'></div>";
                            echo "  <div class='sender-info'>";
                            echo "    <div class='sender-name'>{$owner_name}";
                            if ($is_new && $row['status'] == 'pending') {
                                echo "<span class='new-badge'>NEW</span>";
                            }
                            echo "    </div>";
                            echo "    <div class='sender-email'>{$email}</div>";
                            echo "  </div>";
                            echo "  <div class='subject-preview'>";
                            echo "    <div class='subject'>Seller Application: {$business_name}</div>";
                            echo "    <div class='preview-text'>{$preview_text}</div>";
                            echo "  </div>";
                            echo "  <div class='timestamp'>{$timestamp}</div>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='empty-inbox'>";
                        echo "  <i class='bi bi-inbox'></i>";
                        echo "  <h4>No applications yet</h4>";
                        echo "  <p>Seller applications will appear here when submitted.</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer" id="modalFooter">
                    <!-- Action buttons will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterApplications(status, buttonElement) {
            const items = document.querySelectorAll('.inbox-item');
            items.forEach(item => {
                const itemStatus = item.dataset.status;
                if (status === 'all' || itemStatus === status) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            // Update active button state
            document.querySelectorAll('.inbox-filters .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            buttonElement.classList.add('active');
        }

        function viewApplication(id) {
            const modalBody = document.getElementById('modalBody');
            const modalFooter = document.getElementById('modalFooter');

            // Show loading state
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading application details...</p>
                </div>
            `;
            modalFooter.innerHTML = '';

            const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
            modal.show();

            // Fetch application details
            fetch(`?get_application=1&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Application data received:', data);

                    if (data.error) {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }

                    // Build documents HTML
                    let documentsHtml = '';

                    // Check if documents exist and process them
                    if (data.documents) {
                        let hasDocuments = false;

                        if (Array.isArray(data.documents)) {
                            hasDocuments = data.documents.length > 0;
                        } else if (typeof data.documents === 'object') {
                            hasDocuments = Object.keys(data.documents).length > 0;
                        }

                        if (hasDocuments) {
                            documentsHtml = '<div class="document-preview">';

                            if (Array.isArray(data.documents)) {
                                // Handle array format (old format)
                                data.documents.forEach(doc => {
                                    if (doc && doc.trim() !== '') {
                                        const filename = doc.split('/').pop();
                                        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(filename);

                                        documentsHtml += `
                                            <div class="document-item">
                                                ${isImage ?
                                                `<img src="../${doc}" alt="Document" class="img-thumbnail" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                     <i class="bi bi-file-earmark-text fs-1 text-muted" style="display:none;"></i>` :
                                                `<i class="bi bi-file-earmark-text fs-1 text-muted"></i>`
                                            }
                                                <div class="flex-grow-1">
                                                    <strong>${filename}</strong><br>
                                                    <a href="../${doc}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View Document
                                                    </a>
                                                </div>
                                            </div>
                                        `;
                                    }
                                });
                            } else {
                                // Handle object format (new format)
                                Object.entries(data.documents).forEach(([docType, docPath]) => {
                                    if (docPath && docPath.trim() !== '') {
                                        const filename = docPath.split('/').pop();
                                        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(filename);

                                        // Clean up document type name
                                        let docLabel = docType.replace(/_/g, ' ').replace(/document/g, '').trim();
                                        docLabel = docLabel.toUpperCase();
                                        if (docLabel === '') docLabel = 'Document';

                                        documentsHtml += `
                                            <div class="document-item">
                                                ${isImage ?
                                                `<img src="../${docPath}" alt="Document" class="img-thumbnail" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                     <i class="bi bi-file-earmark-text fs-1 text-muted" style="display:none;"></i>` :
                                                `<i class="bi bi-file-earmark-text fs-1 text-muted"></i>`
                                            }
                                                <div class="flex-grow-1">
                                                    <strong>${docLabel}</strong><br>
                                                    <small class="text-muted">${filename}</small><br>
                                                    <a href="../${docPath}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View Document
                                                    </a>
                                                </div>
                                            </div>
                                        `;
                                    }
                                });
                            }
                            documentsHtml += '</div>';
                        } else {
                            documentsHtml = '<p class="text-muted">No documents submitted</p>';
                        }
                    } else {
                        documentsHtml = '<p class="text-muted">No documents submitted</p>';
                    }

                    // Stall information
                    let stallInfo = '';
                    if (data.stall_number) {
                        stallInfo = `
                            <div class="stall-info">
                                <h6><i class="bi bi-shop"></i> Requested Stall</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Stall Number:</strong> ${data.stall_number}</p>
                                        <p><strong>Section:</strong> ${data.section}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Floor:</strong> ${data.floor_number}</p>
                                        <p><strong>Monthly Rent:</strong> ₱${parseFloat(data.monthly_rent).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // Seller status badge
                    const getStatusBadge = (status) => {
                        const statusClasses = {
                            'pending': 'bg-warning',
                            'approved': 'bg-success',
                            'rejected': 'bg-danger'
                        };
                        return `<span class="badge ${statusClasses[status] || 'bg-secondary'}">${status ? status.toUpperCase() : 'UNKNOWN'}</span>`;
                    };

                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Business Information</h6>
                                <p><strong>Store Name:</strong> ${data.business_name || 'N/A'}</p>
                                <p><strong>Business Email:</strong> ${data.business_email || 'N/A'}</p>
                                <p><strong>Business Phone:</strong> ${data.business_phone || 'N/A'}</p>
                                <p><strong>Tax ID:</strong> ${data.tax_id || 'N/A'}</p>
                                <p><strong>Registration Number:</strong> ${data.business_registration_number || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Seller Information</h6>
                                <p><strong>Username:</strong> ${data.username || 'N/A'}</p>
                                <p><strong>Name:</strong> ${(data.first_name || '') + ' ' + (data.last_name || '') || 'N/A'}</p>
                                <p><strong>Personal Email:</strong> ${data.seller_email || 'N/A'}</p>
                                <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                                <p><strong>Account Status:</strong> ${getStatusBadge(data.seller_status)}</p>
                            </div>
                        </div>
                        
                        ${stallInfo}
                        
                        <div class="mt-3">
                            <h6>Submitted Documents</h6>
                            ${documentsHtml}
                        </div>
                        
                        ${data.admin_notes ? `
                            <div class="mt-3">
                                <h6>Admin Notes</h6>
                                <div class="alert alert-info">${data.admin_notes}</div>
                            </div>
                        ` : ''}
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Applied:</strong> ${new Date(data.created_at).toLocaleString()}<br>
                                <strong>Application Status:</strong> ${getStatusBadge(data.status)}
                            </small>
                        </div>
                    `;

                    // Add action buttons for pending applications
                    if (data.status === 'pending') {
                        modalFooter.innerHTML = `
                            <div class="w-100">
                                <div class="mb-3">
                                    <label for="adminNotes" class="form-label">Admin Notes (Optional)</label>
                                    <textarea class="form-control" id="adminNotes" rows="2" placeholder="Add any notes about this decision..."></textarea>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> These notes will be included in the email notification to the seller.
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-danger" onclick="processApplication(${id}, 'reject')">
                                        <i class="bi bi-x-lg"></i> Reject & Send Email
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="processApplication(${id}, 'approve')">
                                        <i class="bi bi-check-lg"></i> Approve & Send Email
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        modalFooter.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Error loading application details.</div>';
                });
        }

        function processApplication(id, action) {
            const adminNotes = document.getElementById('adminNotes')?.value || '';
            const actionText = action === 'approve' ? 'approve' : 'reject';

            if (!confirm(`Are you sure you want to ${actionText} this application?\n\nAn email notification will be sent to the seller automatically.`)) {
                return;
            }

            // Show processing state
            const actionButtons = document.querySelectorAll('#modalFooter button');
            actionButtons.forEach(btn => {
                if (btn.textContent.includes(actionText === 'approve' ? 'Approve' : 'Reject')) {
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing...`;
                    btn.disabled = true;
                }
            });

            const formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('application_id', id);
            formData.append('admin_notes', adminNotes);

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('applicationModal')).hide();

                        // Show success message with email confirmation
                        showToast(data.message, 'success');

                        // Reload page to reflect changes
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        // Restore button state
                        actionButtons.forEach(btn => {
                            btn.disabled = false;
                            if (btn.textContent.includes('Processing')) {
                                btn.innerHTML = action === 'approve' 
                                    ? '<i class="bi bi-check-lg"></i> Approve & Send Email'
                                    : '<i class="bi bi-x-lg"></i> Reject & Send Email';
                            }
                        });
                        
                        showToast(data.message || 'Error processing application', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Restore button state
                    actionButtons.forEach(btn => {
                        btn.disabled = false;
                        if (btn.textContent.includes('Processing')) {
                            btn.innerHTML = action === 'approve' 
                                ? '<i class="bi bi-check-lg"></i> Approve & Send Email'
                                : '<i class="bi bi-x-lg"></i> Reject & Send Email';
                        }
                    });
                    
                    showToast('Error processing application. Please try again.', 'error');
                });
        }

        function showToast(message, type) {
            // Create toast element
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${type === 'success' ? 'check-circle' : 'x-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            // Add to toast container (create if doesn't exist)
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }

            toastContainer.insertAdjacentHTML('beforeend', toastHtml);

            // Show toast element
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 5000
            });
            toast.show();

            // Remove toast element after it's hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }

        // Add email status indicator
        function showEmailStatus(success, message) {
            const emailIndicator = document.createElement('div');
            emailIndicator.className = `email-indicator ${success ? '' : 'error'}`;
            emailIndicator.innerHTML = `<i class="bi bi-envelope${success ? '-check' : '-x'}"></i> ${message}`;
            
            const modalFooter = document.getElementById('modalFooter');
            modalFooter.appendChild(emailIndicator);
            
            setTimeout(() => {
                emailIndicator.remove();
            }, 5000);
        }
    </script>
</body>

</html>
<?php
if (isset($_GET['verify_phpmailer'])) {
    echo "<div style='padding: 20px; background: #f0f8ff; border: 1px solid #ccc; margin: 20px;'>";
    echo "<h3>PHPMailer Status Check</h3>";
    echo "<p><strong>PHPMailer Available:</strong> " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p><strong>Files Expected:</strong></p>";
    echo "<ul>";
    
    $required_files = [
        'phpmailer/Exception.php',
        'phpmailer/PHPMailer.php', 
        'phpmailer/SMTP.php'
    ];
    
    foreach ($required_files as $file) {
        $exists = file_exists(__DIR__ . '/' . $file);
        echo "<li>$file: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "</li>";
    }
    
    echo "</ul>";
    echo "<p><small>Visit: your-file.php?verify_phpmailer=1</small></p>";
    echo "</div>";
    exit;
}
?>