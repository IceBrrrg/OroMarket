<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Get current admin data
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        $error_message = "Admin not found.";
    } else {
        $update_fields = [];
        $params = [];

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/admin_profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $update_fields[] = "profile_image = ?";
                    $params[] = 'uploads/admin_profiles/' . $new_filename;

                    // Update session with new profile image
                    $_SESSION['profile_image'] = 'uploads/admin_profiles/' . $new_filename;
                } else {
                    $error_message = "Failed to upload profile image.";
                }
            } else {
                $error_message = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF files only.";
            }
        }

        // Update basic information
        if (!empty($first_name) && $first_name !== $admin['first_name']) {
            $update_fields[] = "first_name = ?";
            $params[] = $first_name;
        }

        if (!empty($last_name) && $last_name !== $admin['last_name']) {
            $update_fields[] = "last_name = ?";
            $params[] = $last_name;
        }

        if (!empty($email) && $email !== $admin['email']) {
            // Check if email is already taken
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error_message = "Email address is already taken.";
            } else {
                $update_fields[] = "email = ?";
                $params[] = $email;
            }
        }

        if (!empty($phone) && $phone !== $admin['phone']) {
            $update_fields[] = "phone = ?";
            $params[] = $phone;
        }

        // Handle password change
        if (!empty($current_password) && !empty($new_password)) {
            if (!password_verify($current_password, $admin['password'])) {
                $error_message = "Current password is incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "New password must be at least 6 characters long.";
            } else {
                $update_fields[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }

        // Update database if there are changes
        if (!empty($update_fields) && empty($error_message)) {
            $params[] = $_SESSION['user_id'];
            $sql = "UPDATE admins SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute($params)) {
                $success_message = "Profile updated successfully!";

                // Refresh admin data
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $admin = $stmt->fetch();
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        } elseif (empty($error_message)) {
            $success_message = "No changes detected.";
        }
    }
}

// Get current admin data for form
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Admin Dashboard</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            overflow: hidden;
        }

        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
            border-color: var(--secondary-color);
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="fas fa-cog me-2"></i>Profile Settings</h1>
                    <p class="mb-0">Manage your account information and preferences</p>
                </div>
                <div class="text-end">
                    <h3 class="mb-0"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                    </h3>
                    <small>Administrator</small>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card settings-card">
                                <div class="card-body p-4">
                                    <form method="POST" enctype="multipart/form-data">
                                        <!-- Profile Image Section -->
                                        <div class="text-center mb-4">
                                            <?php if (!empty($admin['profile_image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($admin['profile_image']); ?>"
                                                    alt="Profile Image" class="profile-image mb-3" id="profile-preview">
                                            <?php else: ?>
                                                <img src="../assets/img/avatar.jpg" alt="Default Profile"
                                                    class="profile-image mb-3" id="profile-preview">
                                            <?php endif; ?>

                                            <div>
                                                <label for="profile_image" class="form-label">Update Profile
                                                    Image</label>
                                                <div class="upload-area"
                                                    onclick="document.getElementById('profile_image').click()">
                                                    <i class="bi bi-cloud-upload fs-1 text-muted mb-2"></i>
                                                    <p class="mb-0">Click to upload</p>
                                                    <small class="text-muted">JPG, PNG, GIF up to 5MB</small>
                                                </div>
                                                <input type="file" class="form-control d-none" id="profile_image"
                                                    name="profile_image" accept="image/*" onchange="previewImage(this)">
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <!-- Basic Information -->
                                        <h5 class="mb-3"><i class="bi bi-person me-2"></i>Basic Information</h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name"
                                                    name="first_name"
                                                    value="<?php echo htmlspecialchars($admin['first_name'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name"
                                                    value="<?php echo htmlspecialchars($admin['last_name'] ?? ''); ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone" name="phone"
                                                    value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <!-- Change Password -->
                                        <h5 class="mb-3"><i class="bi bi-lock me-2"></i>Change Password</h5>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="current_password" class="form-label">Current
                                                    Password</label>
                                                <input type="password" class="form-control" id="current_password"
                                                    name="current_password">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="new_password" class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="new_password"
                                                    name="new_password">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New
                                                    Password</label>
                                                <input type="password" class="form-control" id="confirm_password"
                                                    name="confirm_password">
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profile-preview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>