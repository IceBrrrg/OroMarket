<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['is_seller']) && $_SESSION['is_seller'] === true) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Prepare and execute query using PDO
        $stmt = $pdo->prepare("SELECT * FROM sellers WHERE username = ? AND is_active = TRUE");
        $stmt->execute([$username]);
        $seller = $stmt->fetch();

        if ($seller && password_verify($password, $seller['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $seller['id'];
            $_SESSION['username'] = $seller['username'];
            $_SESSION['is_seller'] = true;

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login - ORO Market</title>
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
            max-width: 400px;
            width: 100%;
            margin: 20px;
        }

        .login-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-header {
            background-color: #198754;
            padding: 20px;
            text-align: center;
            color: white;
        }

        .login-header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
        }

        .login-body {
            padding: 30px;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #6c757d;
            text-decoration: none;
        }

        .back-link:hover {
            color: #198754;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <a href="../authenticator.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Role Selection
    </a>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/img/logo.png" alt="ORO Market Logo">
                <h4 class="mb-0">Seller Login</h4>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>

                <div class="signup-link">
                    <p class="mb-0">Don't have an account? <a href="signup.php">Register as a Seller</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>