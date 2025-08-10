<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

require_once '../includes/db_connect.php';

$seller_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            // Update basic profile information
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $facebook_url = trim($_POST['facebook_url']);

            // Validate email uniqueness (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM sellers WHERE email = ? AND id != ?");
            $stmt->execute([$email, $seller_id]);
            if ($stmt->fetch()) {
                throw new Exception("Email address is already in use by another seller.");
            }

            // Update seller information
            $stmt = $pdo->prepare("UPDATE sellers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, facebook_url = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $address, $facebook_url, $seller_id]);

            $success_message = "Profile updated successfully!";
        }

        if (isset($_POST['change_password'])) {
            // Change password
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM sellers WHERE id = ?");
            $stmt->execute([$seller_id]);
            $seller = $stmt->fetch();

            if (!password_verify($current_password, $seller['password'])) {
                throw new Exception("Current password is incorrect.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }

            if (strlen($new_password) < 6) {
                throw new Exception("New password must be at least 6 characters long.");
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE sellers SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $seller_id]);

            $success_message = "Password changed successfully!";
        }

        if (isset($_POST['upload_profile_image'])) {
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/profile_images/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Only JPG, JPEG, PNG, and GIF files are allowed.");
                }

                if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    throw new Exception("File size must be less than 5MB.");
                }

                // Generate unique filename
                $filename = 'profile_' . $seller_id . '_' . uniqid() . '.' . $file_extension;
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                    // Delete old profile image if exists
                    $stmt = $pdo->prepare("SELECT profile_image FROM sellers WHERE id = ?");
                    $stmt->execute([$seller_id]);
                    $old_image = $stmt->fetchColumn();
                    
                    if ($old_image && file_exists('../' . $old_image)) {
                        unlink('../' . $old_image);
                    }

                    // Update database with new image path
                    $db_path = 'uploads/profile_images/' . $filename;
                    $stmt = $pdo->prepare("UPDATE sellers SET profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$db_path, $seller_id]);

                    $success_message = "Profile image updated successfully!";
                } else {
                    throw new Exception("Failed to upload image. Please try again.");
                }
            } else {
                throw new Exception("Please select an image file to upload.");
            }
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current seller information
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

// Get seller application info for business name
$stmt = $pdo->prepare("SELECT business_name FROM seller_applications WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$seller_id]);
$application = $stmt->fetch();
$business_name = $application ? $application['business_name'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ff6b35;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .profile-upload-area {
            border: 2px dashed #ff6b35;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .profile-upload-area:hover {
            background-color: rgba(255, 107, 53, 0.05);
            border-color: #f7931e;
        }
        
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .settings-card .card-header {
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        
        .form-control:focus {
            border-color: #ff6b35;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #f7931e, #ff6b35);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        /* Main Content Layout - matches other seller pages */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        /* Sidebar collapsed state - matches dashboard.php exactly */
        body.sidebar-collapsed .main-content {
            margin-left: 80px;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 80px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar.show + .main-content {
                margin-left: 260px;
            }
        }

        /* Ensure consistent font family throughout - matches dashboard.php */
        body {
            font-family: 'Inter', sans-serif !important;
            background: linear-gradient(135deg, #fff5f2 0%, #ffd4c2 100%);
            color: #2d3436;
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Inter', sans-serif !important;
        }

        .text-primary {
            color: #ff6b35 !important;
        }

        /* Welcome Header - matches dashboard.php styling */
        .welcome-header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .welcome-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .welcome-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            margin-bottom: 0;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'header.php'; ?>
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="welcome-header">
                <h1>
                    <i class="bi bi-person me-2"></i>Profile Settings
                </h1>
                <p>Manage your personal information and account settings</p>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Image Section -->
                <div class="col-lg-4">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-image me-2"></i>Profile Picture
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <?php if ($seller['profile_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($seller['profile_image']); ?>" 
                                         alt="Profile Image" class="profile-image-preview">
                                <?php else: ?>
                                    <div class="profile-image-preview d-flex align-items-center justify-content-center bg-light">
                                        <i class="bi bi-person-circle text-muted" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <div class="profile-upload-area mb-3" onclick="document.getElementById('profile_image').click()">
                                    <i class="bi bi-cloud-upload text-primary" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">Click to upload new image</p>
                                    <small class="text-muted">JPG, PNG, GIF (Max 5MB)</small>
                                </div>
                                <input type="file" id="profile_image" name="profile_image" 
                                       accept="image/*" style="display: none;" onchange="previewImage(this)">
                                <button type="submit" name="upload_profile_image" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>Update Image
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile Information Section -->
                <div class="col-lg-8">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-person-lines-fill me-2"></i>Personal Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($seller['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($seller['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($seller['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($seller['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" 
                                              placeholder="Enter your complete address"><?php echo htmlspecialchars($seller['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="facebook_url" class="form-label">Facebook Profile URL</label>
                                    <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                           value="<?php echo htmlspecialchars($seller['facebook_url'] ?? ''); ?>" 
                                           placeholder="https://www.facebook.com/yourprofile">
                                </div>

                                <?php if ($business_name): ?>
                                <div class="mb-3">
                                    <label class="form-label">Business Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($business_name); ?>" 
                                           readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted">Business name cannot be changed here. Contact admin if needed.</small>
                                </div>
                                <?php endif; ?>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Password Change Section -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-shield-lock me-2"></i>Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password" minlength="6" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" minlength="6" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="bi bi-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.profile-image-preview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace the placeholder div with an img element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Preview';
                        img.className = 'profile-image-preview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
