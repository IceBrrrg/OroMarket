<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$seller_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    $unit = trim($_POST['unit']);
    $description = trim($_POST['description']);

    // Validate inputs
    if (empty($product_name) || $price <= 0 || empty($category) || empty($unit)) {
        $message = "Please fill in all required fields correctly.";
        $message_type = "danger";
    } else {
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (in_array($_FILES['product_image']['type'], $allowed_types) && $_FILES['product_image']['size'] <= $max_size) {
                $upload_dir = '../uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    $image_path = 'uploads/products/' . $filename;
                }
            }
        }

        // Insert product into database
        $query = "INSERT INTO products (seller_id, name, price, category, status, unit, description, image_path, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$seller_id, $product_name, $price, $category, $status, $unit, $description, $image_path]);

        if ($stmt->rowCount() > 0) {
            $message = "Product added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding product. Please try again.";
            $message_type = "danger";
        }
    }
}

// Get seller's products
$query = "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$products_result = $stmt;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .product-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .btn-add-product {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-add-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: block;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-label:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-dark">Products</h1>
                    <p class="text-muted">Manage your product inventory</p>
                </div>
                <button class="btn btn-add-product text-white" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Product
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Products Grid -->
            <div class="row">
                <?php if ($products_result->rowCount() > 0): ?>
                    <?php while ($product = $products_result->fetch()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card product-card">
                                <div class="position-relative">
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" class="product-image"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <span
                                        class="badge status-badge <?php echo $product['status'] == 'available' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($product['category']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span
                                            class="h5 text-primary mb-0">₱<?php echo number_format($product['price'], 2); ?></span>
                                        <small class="text-muted">per <?php echo htmlspecialchars($product['unit']); ?></small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-outline-primary btn-sm"
                                            onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm"
                                            onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-box-seam text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">No products yet</h4>
                            <p class="text-muted">Start by adding your first product to your inventory.</p>
                            <button class="btn btn-add-product text-white" data-bs-toggle="modal"
                                data-bs-target="#addProductModal">
                                <i class="bi bi-plus-circle me-2"></i>Add Your First Product
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                                    required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Fruits">Fruits</option>
                                    <option value="Vegetables">Vegetables</option>
                                    <option value="Grains">Grains</option>
                                    <option value="Dairy">Dairy</option>
                                    <option value="Meat">Meat</option>
                                    <option value="Seafood">Seafood</option>
                                    <option value="Herbs">Herbs</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Unit *</label>
                                <select class="form-select" id="unit" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="kilogram">Kilogram (kg)</option>
                                    <option value="gram">Gram (g)</option>
                                    <option value="piece">Piece</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="dozen">Dozen</option>
                                    <option value="liter">Liter (L)</option>
                                    <option value="milliliter">Milliliter (mL)</option>
                                    <option value="pack">Pack</option>
                                    <option value="box">Box</option>
                                    <option value="bag">Bag</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="product_image" class="form-label">Product Image</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" id="product_image" name="product_image" accept="image/*"
                                        onchange="previewImage(this)">
                                    <label for="product_image" class="file-upload-label">
                                        <i class="bi bi-cloud-upload me-2"></i>
                                        Choose Image
                                    </label>
                                </div>
                                <img id="imagePreview" class="preview-image d-none" alt="Preview">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                placeholder="Describe your product..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('d-none');
            }
        }

        function editProduct(productId) {
            // TODO: Implement edit functionality
            alert('Edit functionality will be implemented here for product ID: ' + productId);
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                // TODO: Implement delete functionality
                alert('Delete functionality will be implemented here for product ID: ' + productId);
            }
        }
    </script>
</body>

</html>