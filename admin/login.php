<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
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
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = TRUE");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['is_admin'] = true;
            $_SESSION['profile_image'] = $admin['profile_image'];

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
    <title>Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }

        .login-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .login-header img {
            max-width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }

        .login-body {
            padding: 2.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: var(--card-shadow);
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #e9ecef;
            border-right: none;
            color: #6c757d;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus+.input-group-text {
            border-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 10px;
            }

            .login-body {
                padding: 2rem;
            }
        }
    </style>
</head>

<body>
    <a href="../authenticator.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Role Selection
    </a>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/img/logo-removebg.png" alt="Oroquieta Marketplace Logo">
                <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Login</h4>
                <p class="mb-0 mt-2 opacity-75">Access the marketplace administration panel</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username"
                                placeholder="Enter your username" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter your password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Log In
                    </button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure admin access only
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function (e) {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging In...';

            // Re-enable after 3 seconds (in case of errors)
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            }, 3000);
        });
    </script>
</body>

</html>