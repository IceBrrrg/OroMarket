<?php
session_start();
header('Content-Type: application/json');

// Include PHPMailer (installed in parent directory)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Path to vendor from seller directory

// Check if user has signup data in session
if (!isset($_SESSION['signup_data']['email']) || empty($_SESSION['signup_data']['email'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email not found in session. Please start the registration process again.'
    ]);
    exit;
}

$email = $_SESSION['signup_data']['email'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format.'
    ]);
    exit;
}

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Set OTP expiry time (10 minutes from now)
$otp_expiry = time() + (10 * 60);

// Store OTP and expiry in session
$_SESSION['otp'] = $otp;
$_SESSION['otp_expiry'] = $otp_expiry;
$_SESSION['otp_attempts'] = 0; // Reset attempts counter

// Gmail SMTP Configuration - Try different settings
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465; // Try SSL port instead of STARTTLS
$smtp_username = 'oroquietamarketplace@gmail.com'; // Fixed spelling (added 'e')
$smtp_password = 'hlmn ezjh arti mkwa'; // Try with original spaces format
$from_email = 'oroquietamarketplace@gmail.com';
$from_name = 'Oroquieta Marketplace';

// Email content
$subject = "Oroquieta Marketplace - Email Verification Code";

// Create HTML email content
$message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e9ecef;
        }
        .otp-code {
            background: #fff;
            border: 2px dashed #667eea;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border-radius: 10px;
        }
        .otp-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            font-family: monospace;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🏪 Oroquieta Marketplace</h1>
        <p>Email Verification Required</p>
    </div>
    
    <div class='content'>
        <h2>Hello!</h2>
        <p>Thank you for registering as a seller with Oroquieta Marketplace. To complete your registration, please verify your email address using the code below:</p>
        
        <div class='otp-code'>
            <p style='margin: 0; font-size: 14px; color: #666;'>Your verification code is:</p>
            <div class='otp-number'>{$otp}</div>
        </div>
        
        <div class='warning'>
            <strong>Important:</strong>
            <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                <li>This code will expire in <strong>10 minutes</strong></li>
                <li>Do not share this code with anyone</li>
                <li>If you didn't request this, please ignore this email</li>
            </ul>
        </div>
        
        <p>Enter this code in the verification window to continue with your seller registration.</p>
        
        <p>If you have any questions, please contact our support team.</p>
        
        <p>Best regards,<br>
        <strong>Oroquieta Marketplace Team</strong></p>
    </div>
    
    <div class='footer'>
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>© 2024 Oroquieta Marketplace. All rights reserved.</p>
    </div>
</body>
</html>
";

// Send email using PHPMailer
try {
    $mail = new PHPMailer(true);

    // Enable verbose debug output (remove in production)
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        error_log("PHPMailer Debug: $str");
    };

    // Server settings - Try SSL instead of STARTTLS
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Changed to SSL
    $mail->Port       = $smtp_port;
    
    // Additional security settings for Gmail
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($email);
    $mail->addReplyTo('support@oroquietamarketplace.com', 'Support Team');

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;

    // Send the email
    $mail->send();

    // Log successful OTP sending
    error_log("OTP sent successfully to: " . $email . " | OTP: " . $otp . " | Expires: " . date('Y-m-d H:i:s', $otp_expiry));

    echo json_encode([
        'status' => 'success',
        'message' => 'Verification code sent to your email address.',
        'email' => $email,
        'expires_in' => 600 // 10 minutes in seconds
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Failed to send OTP email to: " . $email . " | Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send verification email. Please try again or contact support.',
        'debug' => $mail->ErrorInfo,
        'exception' => $e->getMessage()
    ]);
}
?>