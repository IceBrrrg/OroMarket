<?php
session_start();
require_once '../includes/db_connect.php'; // Ensure this uses PDO as discussed!

// Initialize variables
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$error = '';
$success = '';

// Store form data in session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step'])) {
        $current_step = (int) $_POST['step'];

        // Store data in session based on current step
        if ($current_step === 1) {
            // Basic information
            $_SESSION['signup_data']['username'] = $_POST['username'] ?? '';
            $_SESSION['signup_data']['email'] = $_POST['email'] ?? '';
            $_SESSION['signup_data']['password'] = $_POST['password'] ?? '';
            $_SESSION['signup_data']['confirm_password'] = $_POST['confirm_password'] ?? '';
            $_SESSION['signup_data']['first_name'] = $_POST['first_name'] ?? '';
            $_SESSION['signup_data']['last_name'] = $_POST['last_name'] ?? '';
            $_SESSION['signup_data']['phone'] = $_POST['phone'] ?? '';

            // Validate step 1
            if (
                empty($_SESSION['signup_data']['username']) ||
                empty($_SESSION['signup_data']['email']) ||
                empty($_SESSION['signup_data']['password']) ||
                empty($_SESSION['signup_data']['confirm_password'])
            ) {
                $error = "All fields are required.";
            } elseif ($_SESSION['signup_data']['password'] !== $_SESSION['signup_data']['confirm_password']) {
                $error = "Passwords do not match.";
            } elseif (strlen($_SESSION['signup_data']['password']) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                // Check if username or email already exists
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sellers WHERE username = ? OR email = ?");
                    $stmt->execute([$_SESSION['signup_data']['username'], $_SESSION['signup_data']['email']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Username or email already exists.";
                    } else {
                        // Move to next step
                        header("Location: signup.php?step=2");
                        exit();
                    }
                } catch (PDOException $e) {
                    error_log("Database error in step 1: " . $e->getMessage());
                    $error = "Database error occurred. Please try again.";
                }
            }
        } elseif ($current_step === 2) {
            // Business information
            $_SESSION['signup_data']['business_name'] = $_POST['business_name'] ?? '';
            $_SESSION['signup_data']['business_phone'] = $_POST['business_phone'] ?? '';
            $_SESSION['signup_data']['tax_id'] = $_POST['tax_id'] ?? '';
            $_SESSION['signup_data']['facebook_url'] = $_POST['facebook_url'] ?? '';


            // Validate step 2
            if (
                empty($_SESSION['signup_data']['business_name']) ||
                empty($_SESSION['signup_data']['business_phone']) ||
                empty($_SESSION['signup_data']['tax_id']) ||
                empty($_SESSION['signup_data']['facebook_url'])
            ) {
                $error = "All business fields are required.";
            } else {
                // Move to next step
                header("Location: signup.php?step=3");
                exit();
            }
        } elseif ($current_step === 3) {

            // --- Handle individual document uploads ---
            $upload_dir = '../uploads/seller_documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Define required documents and their session keys
            $required_documents = [
                'dti_document' => 'DTI Certificate',
                'business_permit_document' => 'Business Permit',
                'barangay_clearance_document' => 'Barangay Clearance',
                'bir_tin_document' => 'BIR (TIN)',
                'sanitary_permit_document' => 'Sanitary Permit'
            ];

            $uploaded_document_paths = [];
            $all_documents_uploaded = true; // Flag to check if all required docs are present

            foreach ($required_documents as $input_name => $label) {
                if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES[$input_name]['name'];
                    $file_tmp = $_FILES[$input_name]['tmp_name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    // Allowed extensions for documents (you might want to restrict this further)
                    $allowed_doc_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                    if (!in_array($file_ext, $allowed_doc_extensions)) {
                        $error = "Invalid file type for $label. Only JPG, JPEG, PNG, PDF are allowed.";
                        $all_documents_uploaded = false;
                        break; // Stop processing other files if one is invalid
                    }

                    $new_file_name = uniqid($input_name . '_') . '.' . $file_ext; // Unique name with doc type prefix
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp, $destination)) {
                        $uploaded_document_paths[$input_name] = 'uploads/seller_documents/' . $new_file_name;
                    } else {
                        $error = "Error uploading $label. Please try again.";
                        $all_documents_uploaded = false;
                        break;
                    }
                } else {
                    // This document was not uploaded or had an error
                    $error = "$label is required.";
                    $all_documents_uploaded = false;
                    break;
                }
            }

            $_SESSION['signup_data']['uploaded_documents'] = $uploaded_document_paths;

            // Validate step 3 and check if all documents were uploaded
            if (!$all_documents_uploaded) {
                // Error already set by the loop for specific document missing/invalid
            } else {
                // Move to step 4 (stall selection)
                header("Location: signup.php?step=4");
                exit();
            }
        } elseif ($current_step === 4) {
            // Stall selection
            if (isset($_POST['stall']) && !empty($_POST['stall'])) {
                $selected_stall = $_POST['stall'];

                // Validate that the stall exists and is available
                try {
                    $stmt = $pdo->prepare("SELECT id, status FROM stalls WHERE stall_number = ?");
                    $stmt->execute([$selected_stall]);
                    $stall = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$stall) {
                        $error = "Selected stall does not exist. Please choose another stall.";
                    } elseif ($stall['status'] !== 'available') {
                        $error = "Selected stall is not available. Please choose another stall.";
                    } else {
                        $_SESSION['signup_data']['selected_stall'] = $selected_stall;
                        $_SESSION['signup_data']['stall_id'] = $stall['id'];

                        // Create seller account and application
                        try {
                            $pdo->beginTransaction();

                            // Insert into sellers table

                            // In your signup.php file, modify the seller creation part (step 4):

                            // Around line 178, modify the seller insertion:
                            $stmt = $pdo->prepare("
    INSERT INTO sellers (username, email, password, first_name, last_name, phone, facebook_url, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
");

                            $hashed_password = password_hash($_SESSION['signup_data']['password'], PASSWORD_DEFAULT);

                            $stmt->execute([
                                $_SESSION['signup_data']['username'],
                                $_SESSION['signup_data']['email'],
                                $hashed_password,
                                $_SESSION['signup_data']['first_name'],
                                $_SESSION['signup_data']['last_name'],
                                $_SESSION['signup_data']['phone'],
                                $_SESSION['signup_data']['facebook_url']
                            ]);


                            $seller_id = $pdo->lastInsertId();

                            // Insert into seller_applications table (now including stall selection)
                            $stmt = $pdo->prepare("
                                INSERT INTO seller_applications (
                                    seller_id, business_name, business_phone,
                                     tax_id,  
                                    documents_submitted, selected_stall
                                )
                                VALUES (?, ?, ?, ?, ?, ?)");

                            // Encode all uploaded document paths into a single JSON string
                            $documents_json = !empty($_SESSION['signup_data']['uploaded_documents']) ? json_encode($_SESSION['signup_data']['uploaded_documents']) : null;

                            $stmt->execute([
                                $seller_id,
                                $_SESSION['signup_data']['business_name'],
                                $_SESSION['signup_data']['business_phone'],
                                $_SESSION['signup_data']['tax_id'],
                                $documents_json,
                                $_SESSION['signup_data']['selected_stall']
                            ]);

                            // Create stall application
                            $stmt = $pdo->prepare("
                                INSERT INTO stall_applications (stall_id, seller_id, status)
                                VALUES (?, ?, 'pending')
                            ");
                            $stmt->execute([$_SESSION['signup_data']['stall_id'], $seller_id]);

                            // Optionally reserve the stall
                            $stmt = $pdo->prepare("UPDATE stalls SET status = 'reserved' WHERE id = ?");
                            $stmt->execute([$_SESSION['signup_data']['stall_id']]);

                            $pdo->commit();

                            // Clear session data
                            unset($_SESSION['signup_data']);

                            // Redirect to success page
                            header("Location: signup_success.php");
                            exit();

                        } catch (Exception $e) {
                            $pdo->rollBack();
                            error_log("Database error in step 4: " . $e->getMessage());
                            $error = "An error occurred during application submission. Please try again.";
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Database error validating stall: " . $e->getMessage());
                    $error = "Error validating stall selection. Please try again.";
                }
            } else {
                $error = "Please select a stall to continue.";
            }
        }
    }

    // Handle direct stall selection on step 4 (from the floorplan buttons)
    if (isset($_POST['stall']) && $step === 4 && !isset($_POST['step'])) {
        $_SESSION['signup_data']['selected_stall'] = $_POST['stall'];
    }
}

// Get stored data from session
$data = $_SESSION['signup_data'] ?? [];
$uploaded_document_paths = $data['uploaded_documents'] ?? []; // For displaying previews
$selected_stall = $data['selected_stall'] ?? '';

// For step 4, get list of available stalls
$available_stalls = [];
if ($step === 4) {
    try {
        $stmt = $pdo->prepare("SELECT stall_number, section, monthly_rent FROM stalls WHERE status = 'available' ORDER BY stall_number");
        $stmt->execute();
        $available_stalls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching available stalls: " . $e->getMessage());
        $error = "Error loading available stalls. Please refresh and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Signup - ORO Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if ($step === 4): ?>
        <link rel="stylesheet" href="../assets/css/floorplan.css">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/signup.css">
</head>

<body>
    <div class="main-container">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-store me-3"></i>Join Our Community</h2>
                <p>Become a seller at Oroquieta Marketplace and connect with local customers</p>

                <div class="step-indicator">
                    <div class="step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                        <i class="fas fa-user"></i> Basic Info
                    </div>
                    <div class="step <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                        <i class="fas fa-store"></i> Stall Info
                    </div>
                    <div class="step <?php echo $step === 3 ? 'active' : ($step > 3 ? 'completed' : ''); ?>">
                        <i class="fas fa-file-alt"></i> Documents
                    </div>
                    <div class="step <?php echo $step === 4 ? 'active' : ''; ?>">
                        <i class="fas fa-map-marked-alt"></i> Choose Stall
                    </div>
                </div>
            </div>

            <div class="form-content">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="step" value="<?php echo $step; ?>">

                    <?php if ($step === 1): ?>
                        <div class="form-section">
                            <h4><i class="fas fa-user-circle"></i>Personal Information</h4>
                            <p class="text-muted mb-4">Let's start with your basic information to create your account.</p>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?php echo htmlspecialchars($data['username'] ?? ''); ?>"
                                        placeholder="Choose a unique username" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>"
                                        placeholder="your.email@example.com" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Minimum 6 characters" required>
                                    <small class="text-muted">Password must be at least 6 characters long</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" placeholder="Re-enter your password" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="<?php echo htmlspecialchars($data['first_name'] ?? ''); ?>"
                                        placeholder="Your first name">
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="<?php echo htmlspecialchars($data['last_name'] ?? ''); ?>"
                                        placeholder="Your last name">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>"
                                    placeholder="+63 912 345 6789">
                                <small class="text-muted">We'll use this to contact you about your application</small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="login.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>Continue to Business Information
                                </button>
                            </div>
                        </div>

                    <?php elseif ($step === 2): ?>
                        <h4 class="mb-3">Stall Information</h4>
                        <div class="mb-3">
                            <label for="business_name" class="form-label">Store Name *</label>
                            <input type="text" class="form-control" id="business_name" name="business_name"
                                value="<?php echo htmlspecialchars($data['business_name'] ?? ''); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="business_phone" class="form-label">Business Phone Numberr *</label>
                                <input type="text" class="form-control" id="business_phone" name="business_phone"
                                    value="<?php echo htmlspecialchars($data['business_phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="mb-3">
                                <label for="facebook_url" class="form-label">Facebook Profile URL</label>
                                <input type="url" class="form-control" id="facebook_url" name="facebook_url"
                                    value="<?php echo htmlspecialchars($data['facebook_url'] ?? ''); ?>"
                                    placeholder="https://facebook.com/yourprofile">
                                <small class="text-muted">This will be used for customers to contact you directly.</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tax_id" class="form-label">Tax ID *</label>
                                <input type="text" class="form-control" id="tax_id" name="tax_id"
                                    value="<?php echo htmlspecialchars($data['tax_id'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="signup.php?step=1" class="btn btn-secondary">Previous</a>
                            <button type="submit" class="btn btn-primary">Next</button>
                        </div>

                    <?php elseif ($step === 3): ?>
                        <div class="form-section">
                            <h4><i class="fas fa-file-upload"></i>Required Business Documents</h4>
                            <p class="text-muted mb-4">Please upload all required business documents. Click on uploaded
                                images to view them in full size.</p>

                            <!-- DTI Certificate Card -->
                            <div class="document-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5><i class="fas fa-certificate text-primary"></i> DTI Certificate</h5>
                                    <span
                                        class="document-status <?php echo isset($uploaded_document_paths['dti_document']) ? 'status-uploaded' : 'status-required'; ?>">
                                        <?php echo isset($uploaded_document_paths['dti_document']) ? 'Uploaded' : 'Required'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-3">Upload your Department of Trade and Industry certificate (JPG,
                                    PNG,
                                    PDF).
                                </p>
                                <input type="file" class="form-control custom-file-input mb-3" id="dti_document"
                                    name="dti_document" accept="image/*,.pdf" required
                                    data-filename="<?php echo htmlspecialchars(basename($uploaded_document_paths['dti_document'] ?? '')); ?>">
                                <div id="dti-preview-container" class="document-preview-container">
                                    <?php if (isset($uploaded_document_paths['dti_document'])): ?>
                                        <?php
                                        $dti_path = '../' . $uploaded_document_paths['dti_document'];
                                        $dti_ext = strtolower(pathinfo($dti_path, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="document-preview-item"
                                            onclick="openDocumentModal('<?php echo htmlspecialchars($dti_path); ?>', '<?php echo htmlspecialchars(basename($dti_path)); ?>', '<?php echo $dti_ext; ?>')">
                                            <?php if (in_array($dti_ext, ['jpg', 'jpeg', 'png'])): ?>
                                                <img src="<?php echo htmlspecialchars($dti_path); ?>" alt="DTI Preview">
                                            <?php else: ?>
                                                <i class="fas fa-file-pdf file-icon"></i>
                                            <?php endif; ?>
                                            <span class="file-name"><?php echo htmlspecialchars(basename($dti_path)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Business Permit Card -->
                            <div class="document-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5><i class="fas fa-building text-success"></i> Business Permit</h5>
                                    <span
                                        class="document-status <?php echo isset($uploaded_document_paths['business_permit_document']) ? 'status-uploaded' : 'status-required'; ?>">
                                        <?php echo isset($uploaded_document_paths['business_permit_document']) ? 'Uploaded' : 'Required'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-3">Upload your valid Business Permit (JPG, PNG, PDF).</p>
                                <input type="file" class="form-control custom-file-input mb-3" id="business_permit_document"
                                    name="business_permit_document" accept="image/*,.pdf" required
                                    data-filename="<?php echo htmlspecialchars(basename($uploaded_document_paths['business_permit_document'] ?? '')); ?>">
                                <div id="business-permit-preview-container" class="document-preview-container">
                                    <?php if (isset($uploaded_document_paths['business_permit_document'])): ?>
                                        <?php
                                        $bp_path = '../' . $uploaded_document_paths['business_permit_document'];
                                        $bp_ext = strtolower(pathinfo($bp_path, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="document-preview-item"
                                            onclick="openDocumentModal('<?php echo htmlspecialchars($bp_path); ?>', '<?php echo htmlspecialchars(basename($bp_path)); ?>', '<?php echo $bp_ext; ?>')">
                                            <?php if (in_array($bp_ext, ['jpg', 'jpeg', 'png'])): ?>
                                                <img src="<?php echo htmlspecialchars($bp_path); ?>" alt="Business Permit Preview">
                                            <?php else: ?>
                                                <i class="fas fa-file-pdf file-icon"></i>
                                            <?php endif; ?>
                                            <span class="file-name"><?php echo htmlspecialchars(basename($bp_path)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Barangay Clearance Card -->
                            <div class="document-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5><i class="fas fa-shield-alt text-warning"></i> Barangay Clearance</h5>
                                    <span
                                        class="document-status <?php echo isset($uploaded_document_paths['barangay_clearance_document']) ? 'status-uploaded' : 'status-required'; ?>">
                                        <?php echo isset($uploaded_document_paths['barangay_clearance_document']) ? 'Uploaded' : 'Required'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-3">Upload your Barangay Clearance (JPG, PNG, PDF).</p>
                                <input type="file" class="form-control custom-file-input mb-3"
                                    id="barangay_clearance_document" name="barangay_clearance_document"
                                    accept="image/*,.pdf" required
                                    data-filename="<?php echo htmlspecialchars(basename($uploaded_document_paths['barangay_clearance_document'] ?? '')); ?>">
                                <div id="barangay-clearance-preview-container" class="document-preview-container">
                                    <?php if (isset($uploaded_document_paths['barangay_clearance_document'])): ?>
                                        <?php
                                        $bc_path = '../' . $uploaded_document_paths['barangay_clearance_document'];
                                        $bc_ext = strtolower(pathinfo($bc_path, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="document-preview-item"
                                            onclick="openDocumentModal('<?php echo htmlspecialchars($bc_path); ?>', '<?php echo htmlspecialchars(basename($bc_path)); ?>', '<?php echo $bc_ext; ?>')">
                                            <?php if (in_array($bc_ext, ['jpg', 'jpeg', 'png'])): ?>
                                                <img src="<?php echo htmlspecialchars($bc_path); ?>"
                                                    alt="Barangay Clearance Preview">
                                            <?php else: ?>
                                                <i class="fas fa-file-pdf file-icon"></i>
                                            <?php endif; ?>
                                            <span class="file-name"><?php echo htmlspecialchars(basename($bc_path)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- BIR (TIN) Document Card -->
                            <div class="document-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5><i class="fas fa-file-invoice-dollar text-info"></i> BIR (TIN) Document</h5>
                                    <span
                                        class="document-status <?php echo isset($uploaded_document_paths['bir_tin_document']) ? 'status-uploaded' : 'status-required'; ?>">
                                        <?php echo isset($uploaded_document_paths['bir_tin_document']) ? 'Uploaded' : 'Required'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-3">Upload your Bureau of Internal Revenue (TIN) document (JPG, PNG,
                                    PDF).
                                </p>
                                <input type="file" class="form-control custom-file-input mb-3" id="bir_tin_document"
                                    name="bir_tin_document" accept="image/*,.pdf" required
                                    data-filename="<?php echo htmlspecialchars(basename($uploaded_document_paths['bir_tin_document'] ?? '')); ?>">
                                <div id="bir-tin-preview-container" class="document-preview-container">
                                    <?php if (isset($uploaded_document_paths['bir_tin_document'])): ?>
                                        <?php
                                        $bir_path = '../' . $uploaded_document_paths['bir_tin_document'];
                                        $bir_ext = strtolower(pathinfo($bir_path, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="document-preview-item"
                                            onclick="openDocumentModal('<?php echo htmlspecialchars($bir_path); ?>', '<?php echo htmlspecialchars(basename($bir_path)); ?>', '<?php echo $bir_ext; ?>')">
                                            <?php if (in_array($bir_ext, ['jpg', 'jpeg', 'png'])): ?>
                                                <img src="<?php echo htmlspecialchars($bir_path); ?>" alt="BIR (TIN) Preview">
                                            <?php else: ?>
                                                <i class="fas fa-file-pdf file-icon"></i>
                                            <?php endif; ?>
                                            <span class="file-name"><?php echo htmlspecialchars(basename($bir_path)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Sanitary Permit Card -->
                            <div class="document-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5><i class="fas fa-clipboard-check text-danger"></i> Sanitary Permit</h5>
                                    <span
                                        class="document-status <?php echo isset($uploaded_document_paths['sanitary_permit_document']) ? 'status-uploaded' : 'status-required'; ?>">
                                        <?php echo isset($uploaded_document_paths['sanitary_permit_document']) ? 'Uploaded' : 'Required'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-3">Upload your Sanitary Permit (JPG, PNG, PDF).</p>
                                <input type="file" class="form-control custom-file-input mb-3" id="sanitary_permit_document"
                                    name="sanitary_permit_document" accept="image/*,.pdf" required
                                    data-filename="<?php echo htmlspecialchars(basename($uploaded_document_paths['sanitary_permit_document'] ?? '')); ?>">
                                <div id="sanitary-permit-preview-container" class="document-preview-container">
                                    <?php if (isset($uploaded_document_paths['sanitary_permit_document'])): ?>
                                        <?php
                                        $sp_path = '../' . $uploaded_document_paths['sanitary_permit_document'];
                                        $sp_ext = strtolower(pathinfo($sp_path, PATHINFO_EXTENSION));
                                        ?>
                                        <div class="document-preview-item"
                                            onclick="openDocumentModal('<?php echo htmlspecialchars($sp_path); ?>', '<?php echo htmlspecialchars(basename($sp_path)); ?>', '<?php echo $sp_ext; ?>')">
                                            <?php if (in_array($sp_ext, ['jpg', 'jpeg', 'png'])): ?>
                                                <img src="<?php echo htmlspecialchars($sp_path); ?>" alt="Sanitary Permit Preview">
                                            <?php else: ?>
                                                <i class="fas fa-file-pdf file-icon"></i>
                                            <?php endif; ?>
                                            <span class="file-name"><?php echo htmlspecialchars(basename($sp_path)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="signup.php?step=2" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-arrow-right me-2"></i>Continue to Stall Selection
                                </button>
                            </div>
                        </div>

                    <?php elseif ($step === 4): ?>
                        <div class="form-section">
                            <h4><i class="fas fa-map-marker-alt"></i>Select Your Market Stall</h4>
                            <p class="text-muted mb-4">Click on any available stall to select your market spot</p>

                            <?php if ($selected_stall): ?>
                                <?php
                                $vendor_type = '';
                                if (strpos($selected_stall, 'F') === 0) {
                                    $vendor_type = ' (Fish Vendor)';
                                } elseif (strpos($selected_stall, 'M') === 0) {
                                    $vendor_type = ' (Meat Vendor)';
                                }
                                ?>
                                <div class="stall-selection-message">
                                    <strong>Selected Stall: <?php echo strtoupper($selected_stall) . $vendor_type; ?></strong>
                                    <br>Click 'Complete Registration' to confirm your market spot!
                                </div>
                            <?php endif; ?>

                            <div class="market-container">
                                <!-- Top row stalls -->
                                <?php for ($i = 1; $i <= 11; $i++):
                                    $stall_num = 'T' . $i;
                                    $is_available = in_array($stall_num, array_column($available_stalls, 'stall_number'));
                                    ?>
                                    <button type="submit" name="stall" value="<?php echo $stall_num; ?>"
                                        class="stall square-stall top-<?php echo $i; ?> <?php echo ($selected_stall == $stall_num) ? 'selected' : ''; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php echo $stall_num; ?>
                                    </button>
                                <?php endfor; ?>

                                <!-- Bottom row stalls -->
                                <?php for ($i = 1; $i <= 11; $i++):
                                    $stall_num = 'B' . $i;
                                    $is_available = in_array($stall_num, array_column($available_stalls, 'stall_number'));
                                    ?>
                                    <button type="submit" name="stall" value="<?php echo $stall_num; ?>"
                                        class="stall square-stall bottom-<?php echo $i; ?> <?php echo ($selected_stall == $stall_num) ? 'selected' : ''; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php echo $stall_num; ?>
                                    </button>
                                <?php endfor; ?>

                                <!-- Left column stalls -->
                                <?php for ($i = 1; $i <= 6; $i++):
                                    $stall_num = 'L' . $i;
                                    $is_available = in_array($stall_num, array_column($available_stalls, 'stall_number'));
                                    ?>
                                    <button type="submit" name="stall" value="<?php echo $stall_num; ?>"
                                        class="stall square-stall left-<?php echo $i; ?> <?php echo ($selected_stall == $stall_num) ? 'selected' : ''; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php echo $stall_num; ?>
                                    </button>
                                <?php endfor; ?>

                                <!-- Right column stalls -->
                                <?php for ($i = 1; $i <= 6; $i++):
                                    $stall_num = 'R' . $i;
                                    $is_available = in_array($stall_num, array_column($available_stalls, 'stall_number'));
                                    ?>
                                    <button type="submit" name="stall" value="<?php echo $stall_num; ?>"
                                        class="stall square-stall right-<?php echo $i; ?> <?php echo ($selected_stall == $stall_num) ? 'selected' : ''; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php echo $stall_num; ?>
                                    </button>
                                <?php endfor; ?>

                                <!-- Fish Vendors (Left Section) - F1 to F16 -->
                                <?php for ($i = 1; $i <= 16; $i++):
                                    $stall_num = 'F' . $i;
                                    $is_available = in_array($stall_num, array_column($available_stalls, 'stall_number'));
                                    ?>
                                    <button type="submit" name="stall" value="<?php echo $stall_num; ?>"
                                        class="stall fish-vendor fish-<?php echo $i; ?> <?php echo ($selected_stall == $stall_num) ? 'selected' : ''; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php echo $stall_num; ?>
                                    </button>
                                <?php endfor; ?>

                                <!-- Meat Vendors (Right Section) - M1 to M16 -->
                                <?php for ($i = 1; $i <= 16; $i++):
                                    $stall_num = 'M' . $i;
                                    $is_available = in_array($stall_num, array_column($available_stalls, 'stall_number'));
                                    ?>
                                    <button type="submit" name="stall" value="<?php echo $stall_num; ?>"
                                        class="stall meat-vendor meat-<?php echo $i; ?> <?php echo ($selected_stall == $stall_num) ? 'selected' : ''; ?>"
                                        <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php echo $stall_num; ?>
                                    </button>
                                <?php endfor; ?>

                                <!-- Center Circle -->
                                <div class="center-circle">
                                    Market<br>Center
                                </div>
                            </div>

                            <div class="legend mt-4">
                                <div class="legend-item">
                                    <div class="legend-color available"></div>
                                    <span>General Stalls</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #20b2aa; border-color: #1a9a91;">
                                    </div>
                                    <span>Fish Vendors</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #dc3545; border-color: #c82333;">
                                    </div>
                                    <span>Meat Vendors</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #6c757d; border-color: #5a6268;">
                                    </div>
                                    <span>Unavailable</span>
                                </div>
                                <?php if ($selected_stall): ?>
                                    <div class="legend-item">
                                        <div class="legend-color selected-legend"></div>
                                        <span>Selected Stall</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="signup.php?step=3" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                                <?php if ($selected_stall): ?>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check-circle me-2"></i>Complete Registration
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-info-circle me-2"></i>Select a stall first
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                </div>
            <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Document Modal -->
    <div id="documentModal" class="modal-document">
        <span class="modal-document-close" onclick="closeDocumentModal()">&times;</span>
        <div class="modal-document-content">
            <img id="modalImage" src="" alt="Document Preview">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Document modal functions
        function openDocumentModal(imagePath, fileName, fileExt) {
            const modal = document.getElementById('documentModal');
            const modalImage = document.getElementById('modalImage');
            const modalContent = document.querySelector('.modal-document-content');

            // Only show modal for image files
            if (['jpg', 'jpeg', 'png'].includes(fileExt.toLowerCase())) {
                modalImage.src = imagePath;
                modalImage.alt = fileName;
                modal.style.display = 'block';

                // Prevent body scroll when modal is open
                document.body.style.overflow = 'hidden';

                // Reset scroll position of modal content
                modalContent.scrollTop = 0;
                modalContent.scrollLeft = 0;

                // Add loading state
                modalImage.style.opacity = '0';
                modalImage.onload = function () {
                    modalImage.style.opacity = '1';
                    modalImage.style.transition = 'opacity 0.3s ease';
                };
            }
        }

        function closeDocumentModal() {
            const modal = document.getElementById('documentModal');
            modal.style.display = 'none';

            // Restore body scroll
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside the image
        document.getElementById('documentModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeDocumentModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDocumentModal();
            }
        });

        // Array of document input IDs and their corresponding preview container IDs
        const documentInputs = [
            { id: 'dti_document', previewContainerId: 'dti-preview-container' },
            { id: 'business_permit_document', previewContainerId: 'business-permit-preview-container' },
            { id: 'barangay_clearance_document', previewContainerId: 'barangay-clearance-preview-container' },
            { id: 'bir_tin_document', previewContainerId: 'bir-tin-preview-container' },
            { id: 'sanitary_permit_document', previewContainerId: 'sanitary-permit-preview-container' }
        ];

        documentInputs.forEach(doc => {
            const input = document.getElementById(doc.id);
            const previewContainer = document.getElementById(doc.previewContainerId);

            // Update custom file input label on file selection
            input?.addEventListener('change', function () {
                const fileName = this.files.length > 0 ? this.files[0].name : '';
                this.setAttribute('data-filename', fileName);

                // Clear previous previews
                previewContainer.innerHTML = '';

                if (this.files.length > 0) {
                    const file = this.files[0];
                    const item = document.createElement('div');
                    item.classList.add('document-preview-item');

                    const fileNameSpan = document.createElement('span');
                    fileNameSpan.classList.add('file-name');
                    fileNameSpan.textContent = file.name;

                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            item.appendChild(img);
                            item.appendChild(fileNameSpan);
                            previewContainer.appendChild(item);

                            // Add click handler for the new image
                            item.onclick = function () {
                                openDocumentModal(e.target.result, file.name, file.name.split('.').pop());
                            };
                        }
                        reader.readAsDataURL(file);
                    } else if (file.type === 'application/pdf') {
                        const icon = document.createElement('i');
                        icon.classList.add('fas', 'fa-file-pdf', 'file-icon');
                        item.appendChild(icon);
                        item.appendChild(fileNameSpan);
                        previewContainer.appendChild(item);
                    } else {
                        const icon = document.createElement('i');
                        icon.classList.add('fas', 'fa-file', 'file-icon'); // Generic file icon
                        item.appendChild(icon);
                        item.appendChild(fileNameSpan);
                        previewContainer.appendChild(item);
                    }
                }
            });

            // Set initial data-filename if a file was previously uploaded
            const initialFilename = input?.getAttribute('data-filename');
            if (initialFilename) {
                input.setAttribute('data-filename', initialFilename);
            }
        });
    </script>
</body>

</html>