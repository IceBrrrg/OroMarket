<?php
session_start();

// Include database connection
include '../includes/db_connect.php';

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

// Log OTP to a file for testing (replace this with email sending in production)
$logFile = '../uploads/otp_log.txt';
file_put_contents($logFile, "Email: $email, OTP: $otp\n", FILE_APPEND);

// Respond with success
echo json_encode(["status" => "success", "message" => "OTP generated and logged successfully."]);
?>
