<?php
session_start();

header('Content-Type: application/json');

// Get OTP from request
$userOtp = $_POST['otp'] ?? '';

if (!$userOtp) {
    echo json_encode(["status" => "error", "message" => "OTP is required."]);
    exit;
}

// Compare with stored OTP
if ($userOtp == $_SESSION['otp']) {
    echo json_encode(["status" => "success", "message" => "OTP verified successfully."]);
    unset($_SESSION['otp']); // Remove OTP after use
} else {
    echo json_encode(["status" => "error", "message" => "Invalid OTP."]);
}
