<?php 
session_start();

// Include database connection
include '../includes/db_connect.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // PHPMailer installed via Composer

// Generate a random 6-digit OTP
$otp = rand(100000, 999999);

// Store OTP in session
$_SESSION['otp'] = $otp;

// Get user email from session
$email = $_SESSION['signup_data']['email'] ?? null;

if (!$email) {
    echo json_encode(["status" => "error", "message" => "Email not found in session."]);
    exit();
}

try {
    $mail = new PHPMailer(true);

    // SMTP Configuration
   $mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'oroquietamarketplace@gmail.com';
$mail->Password   = 'hlmn ezjh arti mkwa'; // App password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;


    // Sender & Recipient
    $mail->setFrom('oroquietamarketplace@gmail.com', 'Oroquieta Marketplace');
    $mail->addAddress($email);

    // Email Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body    = "<p>Your One-Time Password (OTP) is: <strong>{$otp}</strong></p>";
    $mail->AltBody = "Your OTP is: {$otp}";

    // Send Email
    $mail->send();

    echo json_encode(["status" => "success", "message" => "OTP sent to your email."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}
?>
