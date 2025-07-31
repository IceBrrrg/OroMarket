<?php
session_start();
require_once 'includes/db_connect.php';

// Check if user is already logged in
// if (isset($_SESSION['user_id'])) {
//     // Redirect based on role
//     if ($_SESSION['role'] === 'admin') {
//         header("Location: admin/dashboard.php");
//     } else {
//         header("Location: seller/dashboard.php");
//     }
//     exit();
// }

// Handle role selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];
    if ($role === 'admin') {
        header("Location: admin/login.php");
    } else {
        header("Location: seller/login.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ORO Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 900px;
            width: 100%;
            margin: 20px;
        }

        .role-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .logo-section {
            background-color: #28a745;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-section img {
            max-width: 200px;
            height: auto;
        }

        .role-section {
            padding: 40px;
        }

        .role-btn {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .role-btn:hover {
            transform: translateY(-2px);
        }

        .role-btn i {
            margin-right: 10px;
        }

        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #6c757d;
            text-decoration: none;
        }

        .back-link:hover {
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="login-container">
        <div class="role-card">
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="logo-section">
                        <img src="assets/img/logo.png" alt="ORO Market Logo">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="role-section">
                        <h2 class="text-center mb-4">Welcome to ORO Market</h2>
                        <p class="text-center text-muted mb-4">Please select your role to continue</p>
                        <form method="POST">
                            <button type="submit" name="role" value="admin" class="btn btn-primary role-btn">
                                <i class="bi bi-shield-lock"></i> Login as Admin
                            </button>
                            <button type="submit" name="role" value="seller" class="btn btn-success role-btn">
                                <i class="bi bi-shop"></i> Login as Seller
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>