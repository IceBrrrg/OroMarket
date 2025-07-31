<?php
session_start();
require_once '../includes/db_connect.php';

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
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sellers WHERE username = ? OR email = ?");
                $stmt->execute([$_SESSION['signup_data']['username'], $_SESSION['signup_data']['email']]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Username or email already exists.";
                } else {
                    // Move to next step
                    header("Location: signup.php?step=2");
                    exit();
                }
            }
        } elseif ($current_step === 2) {
            // Business information
            $_SESSION['signup_data']['business_name'] = $_POST['business_name'] ?? '';
            $_SESSION['signup_data']['business_address'] = $_POST['business_address'] ?? '';
            $_SESSION['signup_data']['business_phone'] = $_POST['business_phone'] ?? '';
            $_SESSION['signup_data']['business_email'] = $_POST['business_email'] ?? '';
            $_SESSION['signup_data']['tax_id'] = $_POST['tax_id'] ?? '';
            $_SESSION['signup_data']['business_registration_number'] = $_POST['business_registration_number'] ?? '';

            // Validate step 2
            if (
                empty($_SESSION['signup_data']['business_name']) ||
                empty($_SESSION['signup_data']['business_address']) ||
                empty($_SESSION['signup_data']['business_phone']) ||
                empty($_SESSION['signup_data']['business_email'])
            ) {
                $error = "All business fields are required.";
            } else {
                // Move to next step
                header("Location: signup.php?step=3");
                exit();
            }
        } elseif ($current_step === 3) {
            // Bank information and documents
            $_SESSION['signup_data']['bank_account_name'] = $_POST['bank_account_name'] ?? '';
            $_SESSION['signup_data']['bank_account_number'] = $_POST['bank_account_number'] ?? '';
            $_SESSION['signup_data']['bank_name'] = $_POST['bank_name'] ?? '';
            $_SESSION['signup_data']['facebook_url'] = $_POST['facebook_url'] ?? '';

            // Handle document uploads
            $documents = [];
            if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
                $upload_dir = '../uploads/seller_documents/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['documents']['name'][$i];
                        $file_tmp = $_FILES['documents']['tmp_name'][$i];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        // Generate unique filename
                        $new_file_name = uniqid() . '.' . $file_ext;
                        $destination = $upload_dir . $new_file_name;

                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $documents[] = 'uploads/seller_documents/' . $new_file_name;
                        }
                    }
                }
            }

            $_SESSION['signup_data']['documents'] = $documents;

            // Validate step 3
            if (
                empty($_SESSION['signup_data']['bank_account_name']) ||
                empty($_SESSION['signup_data']['bank_account_number']) ||
                empty($_SESSION['signup_data']['bank_name'])
            ) {
                $error = "All bank fields are required.";
            } else {
                // Create seller account and application
                try {
                    $pdo->beginTransaction();

                    // Insert into sellers table
                    $stmt = $pdo->prepare("
                        INSERT INTO sellers (username, email, password, first_name, last_name, phone, facebook_url)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
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

                    // Insert into seller_applications table
                    $stmt = $pdo->prepare("
                        INSERT INTO seller_applications (
                            seller_id, business_name, business_address, business_phone, 
                            business_email, tax_id, business_registration_number,
                            bank_account_name, bank_account_number, bank_name, documents_submitted
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $documents_json = !empty($documents) ? json_encode($documents) : null;

                    $stmt->execute([
                        $seller_id,
                        $_SESSION['signup_data']['business_name'],
                        $_SESSION['signup_data']['business_address'],
                        $_SESSION['signup_data']['business_phone'],
                        $_SESSION['signup_data']['business_email'],
                        $_SESSION['signup_data']['tax_id'],
                        $_SESSION['signup_data']['business_registration_number'],
                        $_SESSION['signup_data']['bank_account_name'],
                        $_SESSION['signup_data']['bank_account_number'],
                        $_SESSION['signup_data']['bank_name'],
                        $documents_json
                    ]);

                    $pdo->commit();

                    // Clear session data
                    unset($_SESSION['signup_data']);

                    // Redirect to success page
                    header("Location: signup_success.php");
                    exit();

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "An error occurred. Please try again.";
                }
            }
        }
    }
}

// Get stored data from session
$data = $_SESSION['signup_data'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Signup - ORO Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 0 5px;
            position: relative;
        }

        .step.active {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed {
            background-color: #198754;
            color: white;
        }

        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -15px;
            width: 30px;
            height: 2px;
            background-color: #dee2e6;
            z-index: -1;
        }

        .step.completed:not(:last-child):after {
            background-color: #198754;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .document-preview {
            max-width: 150px;
            max-height: 150px;
            margin: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="form-container">
            <h2 class="text-center mb-4">Seller Registration</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="step-indicator">
                <div class="step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                    <i class="fas fa-user"></i> Basic Info
                </div>
                <div class="step <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                    <i class="fas fa-store"></i> Business Info
                </div>
                <div class="step <?php echo $step === 3 ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Documents
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="step" value="<?php echo $step; ?>">

                <?php if ($step === 1): ?>
                    <!-- Step 1: Basic Information -->
                    <h4 class="mb-3">Basic Information</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo $data['username'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo $data['email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                value="<?php echo $data['first_name'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                value="<?php echo $data['last_name'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                            value="<?php echo $data['phone'] ?? ''; ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>

                <?php elseif ($step === 2): ?>
                    <!-- Step 2: Business Information -->
                    <h4 class="mb-3">Business Information</h4>
                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business Name *</label>
                        <input type="text" class="form-control" id="business_name" name="business_name"
                            value="<?php echo $data['business_name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="business_address" class="form-label">Business Address *</label>
                        <textarea class="form-control" id="business_address" name="business_address" rows="3"
                            required><?php echo $data['business_address'] ?? ''; ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="business_phone" class="form-label">Business Phone *</label>
                            <input type="text" class="form-control" id="business_phone" name="business_phone"
                                value="<?php echo $data['business_phone'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="business_email" class="form-label">Business Email *</label>
                            <input type="email" class="form-control" id="business_email" name="business_email"
                                value="<?php echo $data['business_email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tax_id" class="form-label">Tax ID</label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id"
                                value="<?php echo $data['tax_id'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="business_registration_number" class="form-label">Business Registration
                                Number</label>
                            <input type="text" class="form-control" id="business_registration_number"
                                name="business_registration_number"
                                value="<?php echo $data['business_registration_number'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="signup.php?step=1" class="btn btn-secondary">Previous</a>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>

                <?php elseif ($step === 3): ?>
                    <!-- Step 3: Bank Information and Documents -->
                    <h4 class="mb-3">Bank Information</h4>
                    <div class="mb-3">
                        <label for="bank_account_name" class="form-label">Bank Account Name *</label>
                        <input type="text" class="form-control" id="bank_account_name" name="bank_account_name"
                            value="<?php echo $data['bank_account_name'] ?? ''; ?>" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bank_account_number" class="form-label">Bank Account Number *</label>
                            <input type="text" class="form-control" id="bank_account_number" name="bank_account_number"
                                value="<?php echo $data['bank_account_number'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">Bank Name *</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name"
                                value="<?php echo $data['bank_name'] ?? ''; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="facebook_url" class="form-label">Facebook Profile URL</label>
                        <input type="url" class="form-control" id="facebook_url" name="facebook_url"
                            value="<?php echo $data['facebook_url'] ?? ''; ?>"
                            placeholder="https://facebook.com/yourprofile">
                        <small class="text-muted">This will be used for customers to contact you directly.</small>
                    </div>

                    <h4 class="mb-3 mt-4">Business Documents</h4>
                    <div class="mb-3">
                        <label for="documents" class="form-label">Upload Business Documents</label>
                        <input type="file" class="form-control" id="documents" name="documents[]" multiple>
                        <small class="text-muted">Upload business registration, permits, and other relevant documents (PDF,
                            JPG, PNG).</small>
                    </div>

                    <div id="document-preview" class="d-flex flex-wrap mb-3"></div>

                    <div class="d-flex justify-content-between">
                        <a href="signup.php?step=2" class="btn btn-secondary">Previous</a>
                        <button type="submit" class="btn btn-success">Submit Application</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Document preview
        document.getElementById('documents')?.addEventListener('change', function (e) {
            const preview = document.getElementById('document-preview');
            preview.innerHTML = '';

            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('document-preview');
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                } else {
                    const div = document.createElement('div');
                    div.classList.add('document-preview');
                    div.innerHTML = `<i class="fas fa-file-pdf fa-3x"></i><br>${file.name}`;
                    preview.appendChild(div);
                }
            }
        });
    </script>
</body>

</html>