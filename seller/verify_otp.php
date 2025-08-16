<?php
session_start();
header('Content-Type: application/json');

// Check if OTP is provided
if (!isset($_POST['otp']) || empty(trim($_POST['otp']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter the OTP code.'
    ]);
    exit;
}

$entered_otp = trim($_POST['otp']);

// Validate OTP format (6 digits)
if (!preg_match('/^\d{6}$/', $entered_otp)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'OTP must be exactly 6 digits.'
    ]);
    exit;
}

// Check if session OTP exists
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'OTP session expired. Please request a new code.'
    ]);
    exit;
}

// Check if OTP has expired (10 minutes expiry)
if (time() > $_SESSION['otp_expiry']) {
    // Clean up expired OTP
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    
    echo json_encode([
        'status' => 'error',
        'message' => 'OTP has expired. Please request a new code.'
    ]);
    exit;
}

// Verify OTP
if ($_SESSION['otp'] === $entered_otp) {
    // OTP is correct - mark email as verified
    $_SESSION['email_verified'] = true;
    $_SESSION['otp_step'] = false;
    
    // Clean up OTP session data
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    
    // Log successful verification (optional)
    error_log("OTP verified successfully for email: " . ($_SESSION['signup_data']['email'] ?? 'unknown'));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Email verified successfully!'
    ]);
} else {
    // Increment failed attempts (optional security measure)
    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }
    $_SESSION['otp_attempts']++;
    
    // Optional: Lock after too many failed attempts
    if ($_SESSION['otp_attempts'] >= 5) {
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['otp_attempts']);
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Too many failed attempts. Please request a new OTP.'
        ]);
        exit;
    }
    
    $remaining_attempts = 5 - $_SESSION['otp_attempts'];
    echo json_encode([
        'status' => 'error',
        'message' => "Incorrect OTP. {$remaining_attempts} attempts remaining."
    ]);
}
?>