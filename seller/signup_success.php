<?php
session_start();

// Check if user came from successful registration
if (!isset($_SESSION['registration_success']) || !$_SESSION['registration_success']) {
    header("Location: signup.php?step=1");
    exit();
}

// Get success data
$username = $_SESSION['registered_username'] ?? 'New Seller';
$stall_number = $_SESSION['selected_stall_number'] ?? 'N/A';

// Clear success flags (but keep them for the page display)
$show_success = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .success-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 90%;
            margin: 2rem;
        }
        
        .success-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            animation: bounce 1s ease-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        .success-title {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .success-subtitle {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin: 2rem 0;
            border-left: 5px solid #28a745;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            font-weight: bold;
            color: #28a745;
        }
        
        .next-steps {
            background: #e3f2fd;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            text-align: left;
        }
        
        .next-steps h5 {
            color: #1976d2;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .next-steps ul {
            color: #424242;
            line-height: 1.8;
        }
        
        .action-buttons {
            margin-top: 2rem;
        }
        
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-left: 1rem;
            transition: transform 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            color: rgba(255,255,255,0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <i class="fas fa-store floating-element" style="top: 10%; left: 10%; font-size: 2rem; animation-delay: 0s;"></i>
        <i class="fas fa-check-circle floating-element" style="top: 20%; right: 15%; font-size: 1.5rem; animation-delay: 1s;"></i>
        <i class="fas fa-handshake floating-element" style="bottom: 30%; left: 15%; font-size: 2.5rem; animation-delay: 2s;"></i>
        <i class="fas fa-trophy floating-element" style="bottom: 10%; right: 10%; font-size: 1.8rem; animation-delay: 3s;"></i>
    </div>

    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 class="success-title">Registration Successful!</h1>
        <p class="success-subtitle">
            Welcome to the Oroquieta Marketplace family! Your seller application has been submitted successfully.
        </p>
        
        <div class="info-card">
            <div class="info-item">
                <span class="info-label"><i class="fas fa-user me-2"></i>Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="fas fa-map-marker-alt me-2"></i>Selected Stall:</span>
                <span class="info-value"><?php echo htmlspecialchars($stall_number); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="fas fa-clock me-2"></i>Application Status:</span>
                <span class="info-value">Pending Review</span>
            </div>
        </div>
        
        <div class="next-steps">
            <h5><i class="fas fa-list-check me-2"></i>What happens next?</h5>
            <ul>
                <li><strong>Review Process:</strong> Our team will review your application and documents within 2-3 business days.</li>
                <li><strong>Email Notification:</strong> You'll receive an email notification about your application status.</li>
                <li><strong>Stall Assignment:</strong> Once approved, your selected stall will be officially assigned to you.</li>
                <li><strong>Account Activation:</strong> Your seller account will be activated and you can start listing products.</li>
            </ul>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Important:</strong> Please keep your registration details safe. You'll need them to access your seller account once approved.
        </div>
        
        <div class="action-buttons">
            <a href="login.php" class="btn-success-custom">
                <i class="fas fa-sign-in-alt me-2"></i>Go to Login
            </a>
            <a href="../index.php" class="btn-secondary-custom">
                <i class="fas fa-home me-2"></i>Back to Homepage
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-question-circle me-1"></i>
                Need help? Contact us at <strong>support@oroquietamarketplace.com</strong>
            </small>
        </div>
    </div>

    <script>
        // Clear the registration success flags after page loads
        <?php if ($show_success): ?>
        setTimeout(function() {
            // Make an AJAX call to clear the session flags
            fetch('clear_success_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });
        }, 1000);
        <?php endif; ?>
        
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effect to buttons
            const buttons = document.querySelectorAll('.btn-success-custom, .btn-secondary-custom');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-2px)';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>