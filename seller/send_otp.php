<?php
session_start();
header('Content-Type: application/json');

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

// Email configuration
$to = $email;
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

// Email headers
$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: Oroquieta Marketplace <noreply@oroquietamarketplace.com>',
    'Reply-To: support@oroquietamarketplace.com',
    'X-Mailer: PHP/' . phpversion()
];

// Send email
try {
    $mail_sent = mail($to, $subject, $message, implode("\r\n", $headers));
    
    if ($mail_sent) {
        // Log successful OTP sending (optional)
        error_log("OTP sent successfully to: " . $email . " | OTP: " . $otp . " | Expires: " . date('Y-m-d H:i:s', $otp_expiry));
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Verification code sent to your email address.',
            'email' => $email,
            'expires_in' => 600 // 10 minutes in seconds
        ]);
    } else {
        // Log email sending failure
        error_log("Failed to send OTP email to: " . $email);
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to send verification email. Please try again or contact support.'
        ]);
    }
    
} catch (Exception $e) {
    // Log exception
    error_log("Exception while sending OTP email: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while sending the verification email. Please try again.'
    ]);
}
?>