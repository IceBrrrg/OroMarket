<?php
// Updated PHP Email Test Script with automatic PHPMailer download
// Save this as test_email.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Email Configuration
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587; // Changed to 587 for TLS (more reliable than 465)
$smtp_username = 'oroquietamarketplace@gmail.com';
$smtp_password = 'hlmn ezjh arti mkwa';
$from_email = 'oroquietamarketplace@gmail.com';
$from_name = 'Oroquieta Marketplace';

// Test recipient (CHANGE THIS TO YOUR EMAIL)
$test_recipient = 'sskill652@gmail.com'; // ✅ Already set to your email
$test_name = 'Test User';

// Function to download and setup PHPMailer if not available
function setupPHPMailer() {
    $phpmailer_dir = __DIR__ . '/phpmailer';
    
    if (!is_dir($phpmailer_dir)) {
        echo "PHPMailer not found. Attempting to download...\n";
        
        // Create directory
        mkdir($phpmailer_dir, 0755, true);
        
        // Download PHPMailer files
        $files_to_download = [
            'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
            'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
            'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'
        ];
        
        foreach ($files_to_download as $filename => $url) {
            $content = file_get_contents($url);
            if ($content !== false) {
                file_put_contents($phpmailer_dir . '/' . $filename, $content);
                echo "Downloaded: $filename\n";
            } else {
                echo "Failed to download: $filename\n";
                return false;
            }
        }
        echo "PHPMailer downloaded successfully!\n";
    }
    
    // Include PHPMailer files
    require_once $phpmailer_dir . '/Exception.php';
    require_once $phpmailer_dir . '/PHPMailer.php';
    require_once $phpmailer_dir . '/SMTP.php';
    
    return true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Oroquieta Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .log-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .test-card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card test-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-envelope-check"></i> Email Configuration Test</h4>
                        <small>Oroquieta Marketplace Email System (Auto-Setup Version)</small>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!isset($_POST['send_test'])): ?>
                        <!-- Test Form -->
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            <strong>Auto-Setup Enabled:</strong> 
                            This script will automatically download PHPMailer if needed!
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Test Recipient Email</label>
                                <input type="email" class="form-control" value="<?php echo $test_recipient; ?>" readonly>
                                <div class="form-text">Currently set to your email address</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email Type to Test</label>
                                <select name="email_type" class="form-select" required>
                                    <option value="simple">Simple Test Email</option>
                                    <option value="approval">Seller Approval Email</option>
                                    <option value="rejection">Seller Rejection Email</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="debug_mode" id="debug_mode" checked>
                                    <label class="form-check-label" for="debug_mode">
                                        Enable Debug Mode (shows detailed logs)
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" name="send_test" class="btn btn-primary btn-lg">
                                <i class="bi bi-download"></i> Setup PHPMailer & Send Test Email
                            </button>
                        </form>
                        
                        <?php else: ?>
                        <!-- Test Results -->
                        <div class="mb-4">
                            <h5>Test Results</h5>
                            <?php
                            // Start output buffering to capture debug info
                            ob_start();
                            
                            $email_type = $_POST['email_type'];
                            $debug_mode = isset($_POST['debug_mode']);
                            
                            echo "=== EMAIL TEST STARTED ===\n";
                            echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
                            echo "Recipient: " . $test_recipient . "\n";
                            echo "Email Type: " . $email_type . "\n";
                            echo "Debug Mode: " . ($debug_mode ? 'ON' : 'OFF') . "\n";
                            echo "SMTP Settings: {$smtp_host}:{$smtp_port}\n\n";
                            
                            // Setup PHPMailer
                            echo "=== SETTING UP PHPMAILER ===\n";
                            $phpmailer_setup = setupPHPMailer();
                            echo "PHPMailer Setup: " . ($phpmailer_setup ? 'SUCCESS' : 'FAILED') . "\n";
                            echo "PHPMailer Available: " . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? 'YES' : 'NO') . "\n\n";
                            
                            // Test email sending function
                            function testSendEmail($to_email, $to_name, $subject, $body, $debug = false) {
                                global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name;
                                
                                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                                    echo "Using PHPMailer SMTP...\n";
                                    
                                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                                    try {
                                        if ($debug) {
                                            $mail->SMTPDebug = 2;
                                            $mail->Debugoutput = function($str, $level) {
                                                echo "PHPMailer Debug [$level]: $str\n";
                                            };
                                        }

                                        // Server settings
                                        $mail->isSMTP();
                                        $mail->Host = $smtp_host;
                                        $mail->SMTPAuth = true;
                                        $mail->Username = $smtp_username;
                                        $mail->Password = $smtp_password;
                                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Changed to STARTTLS
                                        $mail->Port = $smtp_port;
                                        
                                        // Additional settings for Gmail
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
                                        $mail->isHTML(true);
                                        $mail->Subject = $subject;
                                        $mail->Body = $body;
                                        $mail->AltBody = strip_tags($body);

                                        // Additional headers
                                        $mail->addCustomHeader('List-Unsubscribe', '<mailto:' . $from_email . '>');
                                        $mail->addCustomHeader('X-Mailer', 'Oroquieta Marketplace System');

                                        // Send
                                        $result = $mail->send();
                                        echo "\nPHPMailer Send Result: " . ($result ? "SUCCESS ✅" : "FAILED ❌") . "\n";
                                        
                                        if ($result) {
                                            echo "Email queued for delivery to: $to_email\n";
                                        }
                                        
                                        return $result;
                                        
                                    } catch (\PHPMailer\PHPMailer\Exception $e) {
                                        echo "\n❌ PHPMailer Exception: " . $e->getMessage() . "\n";
                                        echo "Error Info: " . $mail->ErrorInfo . "\n";
                                        return false;
                                    }
                                } else {
                                    echo "❌ PHPMailer not available, cannot send email via SMTP\n";
                                    echo "Please install PHPMailer or configure your server's mail() function\n";
                                    return false;
                                }
                            }
                            
                            // Generate email content based on type
                            switch ($email_type) {
                                case 'approval':
                                    $subject = "🎉 Your Seller Application Has Been Approved - Oroquieta Marketplace [TEST]";
                                    $body = '
                                    <!DOCTYPE html>
                                    <html>
                                    <head>
                                        <style>
                                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                            .header { background: linear-gradient(135deg, #2c3e50, #3498db); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                                            .success-badge { background: #27ae60; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 20px 0; }
                                        </style>
                                    </head>
                                    <body>
                                        <div class="container">
                                            <div class="header">
                                                <h1>🎉 Application Approved! [TEST]</h1>
                                                <p>Oroquieta Marketplace</p>
                                            </div>
                                            <div class="content">
                                                <h2>Congratulations, Test User!</h2>
                                                <div class="success-badge">✅ APPROVED</div>
                                                <p><strong>This is a test email.</strong> Your seller application has been approved!</p>
                                                <p>You can now start listing your products and managing your marketplace presence.</p>
                                                <hr>
                                                <p><small>Test sent at: ' . date('Y-m-d H:i:s') . '</small></p>
                                            </div>
                                        </div>
                                    </body>
                                    </html>';
                                    break;
                                    
                                case 'rejection':
                                    $subject = "Application Status Update - Oroquieta Marketplace [TEST]";
                                    $body = '
                                    <!DOCTYPE html>
                                    <html>
                                    <head>
                                        <style>
                                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                            .header { background: linear-gradient(135deg, #2c3e50, #e74c3c); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                                            .rejection-badge { background: #e74c3c; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 20px 0; }
                                        </style>
                                    </head>
                                    <body>
                                        <div class="container">
                                            <div class="header">
                                                <h1>Application Update [TEST]</h1>
                                                <p>Oroquieta Marketplace</p>
                                            </div>
                                            <div class="content">
                                                <h2>Dear Test User,</h2>
                                                <div class="rejection-badge">❌ NOT APPROVED</div>
                                                <p><strong>This is a test email.</strong> We regret to inform you that your application has not been approved at this time.</p>
                                                <p>Please contact our support team for more information.</p>
                                                <hr>
                                                <p><small>Test sent at: ' . date('Y-m-d H:i:s') . '</small></p>
                                            </div>
                                        </div>
                                    </body>
                                    </html>';
                                    break;
                                    
                                default: // simple
                                    $subject = "Simple Test Email - Oroquieta Marketplace [TEST]";
                                    $body = '
                                    <html>
                                    <body style="font-family: Arial, sans-serif; padding: 20px;">
                                        <h2>🧪 Email Test Successful!</h2>
                                        <p>This is a simple test email from the Oroquieta Marketplace system.</p>
                                        <p><strong>If you receive this email, your configuration is working correctly!</strong></p>
                                        <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                                            <h4>📧 Email Settings Used:</h4>
                                            <ul>
                                                <li><strong>SMTP Server:</strong> ' . $smtp_host . '</li>
                                                <li><strong>Port:</strong> ' . $smtp_port . '</li>
                                                <li><strong>Security:</strong> STARTTLS</li>
                                                <li><strong>From:</strong> ' . $from_email . '</li>
                                            </ul>
                                        </div>
                                        <hr>
                                        <p><small>Test sent at: ' . date('Y-m-d H:i:s') . '</small></p>
                                        <p><small>From: ' . $from_email . '</small></p>
                                    </body>
                                    </html>';
                            }
                            
                            // Send the test email
                            echo "\n=== SENDING EMAIL ===\n";
                            echo "Subject: " . $subject . "\n";
                            echo "To: " . $test_recipient . "\n\n";
                            
                            $send_result = testSendEmail($test_recipient, $test_name, $subject, $body, $debug_mode);
                            
                            echo "\n=== FINAL RESULT ===\n";
                            echo "Email Send Status: " . ($send_result ? "SUCCESS ✅" : "FAILED ❌") . "\n";
                            if ($send_result) {
                                echo "✅ Check your inbox and spam folder at: $test_recipient\n";
                                echo "✅ If successful, you can now use the same configuration in your main app\n";
                            }
                            echo "=== TEST COMPLETED ===\n";
                            
                            // Get the captured output
                            $debug_output = ob_get_clean();
                            
                            // Show result status
                            if ($send_result) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="bi bi-check-circle"></i> <strong>Email sent successfully!</strong><br>';
                                echo 'Check your inbox (and spam folder) at: <strong>' . $test_recipient . '</strong><br>';
                                echo '<small class="text-muted">✅ Your email configuration is now working correctly!</small>';
                                echo '</div>';
                                
                                echo '<div class="alert alert-info">';
                                echo '<i class="bi bi-lightbulb"></i> <strong>Next Steps:</strong><br>';
                                echo '1. Copy the <code>phpmailer</code> folder to your main project directory<br>';
                                echo '2. Update your main application files to include PHPMailer<br>';
                                echo '3. Change SMTP port from 465 to 587 in your main code<br>';
                                echo '4. Use STARTTLS instead of SMTPS encryption';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="bi bi-x-circle"></i> <strong>Email failed to send!</strong><br>';
                                echo 'Check the debug information below for details.';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        
                        <!-- Debug Log -->
                        <div class="mb-3">
                            <h6>Debug Log:</h6>
                            <div class="log-output"><?php echo htmlspecialchars($debug_output); ?></div>
                        </div>
                        
                        <a href="?" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Run Another Test
                        </a>
                        
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer text-muted">
                        <small>
                            <i class="bi bi-gear"></i> 
                            <strong>Changes Made:</strong>
                            <ul class="mb-0 mt-2" style="font-size: 12px;">
                                <li>✅ Auto-downloads PHPMailer if not available</li>
                                <li>✅ Changed SMTP port from 465 to 587 (more reliable)</li>
                                <li>✅ Using STARTTLS encryption instead of SMTPS</li>
                                <li>✅ Added better error handling and debug info</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>