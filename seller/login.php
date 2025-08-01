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
                    // Login successful - use consistent session variable names
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

        .status-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .status-info h6 {
            color: #0066cc;
            margin-bottom: 10px;
        }

        .status-info ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .status-info li {
            color: #333;
            margin-bottom: 5px;
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
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                        <?php if (strpos($error, 'pending approval') !== false): ?>
                            <hr>
                            <small>
                                <strong>Note:</strong> You will receive an email notification once your application is reviewed.
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Information box for new sellers -->
                <div class="status-info">
                    <h6><i class="bi bi-info-circle"></i> Account Status Information</h6>
                    <ul>
                        <li><strong>Pending:</strong> Your application is under review</li>
                        <li><strong>Approved:</strong> You can log in and start selling</li>
                        <li><strong>Rejected:</strong> Contact support for assistance</li>
                    </ul>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Log in</button>
                </form>

                <div class="signup-link">
                    <p class="mb-0">Don't have an account? <a href="signup.php">Register as a Seller</a></p>
                    <p class="mb-0 mt-2"><small><a href="#" class="text-muted">Forgot Password?</a></small></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>