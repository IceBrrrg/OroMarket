<?php
session_start();
require_once '../includes/db_connect.php';

// Remove the login check since this page should be accessible after registration
// The session is cleared after successful registration, so there's no seller_id to check

// Optional: You could add a flag to ensure they came from the registration process
// For example, you could set a temporary session variable in the signup process
// and check for it here, then unset it
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - ORO Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            color: #198754;
            margin-bottom: 20px;
        }

        .timeline {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .timeline-item {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="mb-4">Application Submitted Successfully!</h2>
            <p class="lead">Thank you for submitting your seller application. Our team will review your application
                within 1-2 business days.</p>

            <div class="timeline">
                <h5 class="mb-3">What happens next?</h5>
                <div class="timeline-item">
                    <strong>Application Review</strong>
                    <p class="mb-0">Our admin team will review your application and verify your business documents.</p>
                </div>
                <div class="timeline-item">
                    <strong>Email Notification</strong>
                    <p class="mb-0">You will receive an email with the decision on your application.</p>
                </div>
                <div class="timeline-item">
                    <strong>Account Activation</strong>
                    <p class="mb-0">If approved, your seller account will be activated and you can start listing
                        products.</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="index.php" class="btn btn-primary me-2">Go to Homepage</a>
                <a href="login.php" class="btn btn-outline-secondary">Go to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>