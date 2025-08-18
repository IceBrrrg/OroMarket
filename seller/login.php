<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is already logged in
if (isset($_SESSION['seller_id']) && isset($_SESSION['seller_username'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Modified query to include status check
            $stmt = $pdo->prepare("SELECT id, username, email, password, status FROM sellers WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($seller && password_verify($password, $seller['password'])) {
                // Check if account is approved
                if ($seller['status'] !== 'approved') {
                    $status_messages = [
                        'pending' => 'Your account is pending approval. Please wait for admin review.',
                        'rejected' => 'Your account application has been rejected. Please contact support.',
                        'suspended' => 'Your account has been suspended. Please contact support.'
                    ];
                    $error = $status_messages[$seller['status']] ?? 'Your account is not active.';
                } else {
                    // ✅ Login successful — set all required session variables
                    $_SESSION['user_id'] = $seller['id'];
                    $_SESSION['is_seller'] = true;

                    $_SESSION['seller_id'] = $seller['id'];
                    $_SESSION['seller_username'] = $seller['username'];
                    $_SESSION['seller_email'] = $seller['email'];

                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Login failed. Please try again.";
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
            --primary-color: #ff6b35;
            --secondary-color: #f7931e;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, #fff5f2 0%, #ffd4c2 100%);
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
            background-color: white;
            border-radius: 20px;
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
            box-shadow: 0 0 0 0.2rem rgba(247, 147, 30, 0.25);
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
            color: #6c757d;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .back-link:hover {
            color: var(--primary-color);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
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

        .status-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .status-info h6 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .status-info ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }

        .status-info li {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            color: var(--secondary-color);
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
    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Home
    </a>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/img/logo-removebg.png" alt="ORO Market Logo">
                <h4 class="mb-0"><i class="fas fa-store me-2"></i>Seller Login</h4>
                <p class="mb-0 mt-2 opacity-75">Access your seller dashboard and manage your products</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <?php if (strpos($error, 'pending approval') !== false): ?>
                            <hr>
                            <small>
                                <strong>Note:</strong> You will receive an email notification once your application is reviewed.
                            </small>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Information box for new sellers -->
                <div class="status-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Account Status Information</h6>
                    <ul>
                        <li><strong>Pending:</strong> Your application is under review</li>
                        <li><strong>Approved:</strong> You can log in and start selling</li>
                        <li><strong>Rejected:</strong> Contact support for assistance</li>
                    </ul>
                </div>

                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username"
                                placeholder="Enter your username or email"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
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
                        <i class="fas fa-store me-1"></i>
                        Seller access only
                    </small>
                </div>

                <div class="signup-link">
                    <p class="mb-0">Don't have an account? <a href="signup.php">Register as a Seller</a></p>
                    <p class="mb-0 mt-2"><small><a href="#" class="text-muted">Forgot Password?</a></small></p>
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