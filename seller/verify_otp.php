<?php
session_start();

// Include database connection
include '../includes/db_connect.php';

// Get OTP from POST request
$user_otp = $_POST['otp'];

// Get the OTP stored in session
$session_otp = $_SESSION['otp'];

if ($user_otp == $session_otp) {
    // OTP is correct
    unset($_SESSION['otp']); // Clear OTP from session
    echo json_encode(["status" => "success", "message" => "OTP verified successfully."]);
} else {
    // OTP is incorrect
    echo json_encode(["status" => "error", "message" => "Invalid OTP. Please try again."]);
}
?>
