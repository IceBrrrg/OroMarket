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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Product
    if (isset($_POST['add_product'])) {
        $product_name = trim($_POST['product_name']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category']);
        $status = $_POST['status'];
        $unit = trim($_POST['unit']);
        $description = trim($_POST['description']);

        // Validate inputs
        if (empty($product_name) || $price <= 0 || empty($category_id) || empty($unit)) {
            $message = "Please fill in all required fields correctly.";
            $message_type = "danger";
        } else {
            // Handle image upload
            $image_data = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (in_array($_FILES['product_image']['type'], $allowed_types) && $_FILES['product_image']['size'] <= $max_size) {
                    // Read image file and store as BLOB
                    $image_data = file_get_contents($_FILES['product_image']['tmp_name']);
                }
            }

            // Insert product into database
            $query = "INSERT INTO products (seller_id, category_id, name, price, unit, description, status, image, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$seller_id, $category_id, $product_name, $price, $unit, $description, $status, $image_data]);

            if ($stmt->rowCount() > 0) {
                $message = "Product added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding product. Please try again.";
                $message_type = "danger";
            }
        }
    }

    // Update Product
    elseif (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id']);
        $product_name = trim($_POST['product_name']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category']);
        $status = $_POST['status'];
        $unit = trim($_POST['unit']);
        $description = trim($_POST['description']);

        // Validate inputs
        if (empty($product_name) || $price <= 0 || empty($category_id) || empty($unit)) {
            $message = "Please fill in all required fields correctly.";
            $message_type = "danger";
        } else {
            // Verify product belongs to this seller
            $check_query = "SELECT id FROM products WHERE id = ? AND seller_id = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$product_id, $seller_id]);

            if ($check_stmt->fetch()) {
                // Handle image upload
                $image_data = null;
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    if (in_array($_FILES['product_image']['type'], $allowed_types) && $_FILES['product_image']['size'] <= $max_size) {
                        // Read image file and store as BLOB
                        $image_data = file_get_contents($_FILES['product_image']['tmp_name']);
                    }
                }

                // Update product in database
                if ($image_data !== null) {
                    $query = "UPDATE products SET category_id = ?, name = ?, price = ?, unit = ?, description = ?, status = ?, image = ?, updated_at = NOW() WHERE id = ? AND seller_id = ?";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$category_id, $product_name, $price, $unit, $description, $status, $image_data, $product_id, $seller_id]);
                } else {
                    $query = "UPDATE products SET category_id = ?, name = ?, price = ?, unit = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ? AND seller_id = ?";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$category_id, $product_name, $price, $unit, $description, $status, $product_id, $seller_id]);
                }

                if ($stmt->rowCount() > 0) {
                    $message = "Product updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating product. Please try again.";
                    $message_type = "danger";
                }
            } else {
                $message = "Product not found or access denied.";
                $message_type = "danger";
            }
        }
    }

    // Delete Product
    elseif (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);

        // Verify product belongs to this seller
        $check_query = "SELECT id FROM products WHERE id = ? AND seller_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$product_id, $seller_id]);

        if ($check_stmt->fetch()) {
            // Delete the product (image is stored as BLOB in products table)
            $delete_sql = "DELETE FROM products WHERE id = ? AND seller_id = ?";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([$product_id, $seller_id]);

            if ($delete_stmt->rowCount() > 0) {
                $message = "Product deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting product. Please try again.";
                $message_type = "danger";
            }
        } else {
            $message = "Product not found or access denied.";
            $message_type = "danger";
        }
    }
}

// Get seller's products with category
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.seller_id = ? 
          ORDER BY p.created_at DESC";
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
    <link rel="stylesheet" href="assets/css/products.css">

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
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>"
                                            class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars($product['category_name'] ? $product['category_name'] : 'Uncategorized'); ?>
                                    </p>
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
                                    <?php
                                    $cat_query = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
                                    $cat_stmt = $pdo->query($cat_query);
                                    while ($category = $cat_stmt->fetch()) {
                                        echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                    }
                                    ?>
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

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <input type="hidden" id="edit_product_id" name="product_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_product_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="edit_product_name" name="product_name"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_price" class="form-label">Price (₱) *</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01"
                                    min="0" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_category" class="form-label">Category *</label>
                                <select class="form-select" id="edit_category" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    // Reuse the same category query
                                    $cat_stmt->execute();
                                    while ($category = $cat_stmt->fetch()) {
                                        echo "<option value='{$category['id']}'>{$category['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status *</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_unit" class="form-label">Unit *</label>
                                <select class="form-select" id="edit_unit" name="unit" required>
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
                                <label for="edit_product_image" class="form-label">Product Image</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" id="edit_product_image" name="product_image" accept="image/*"
                                        onchange="previewEditImage(this)">
                                    <label for="edit_product_image" class="file-upload-label">
                                        <i class="bi bi-cloud-upload me-2"></i>
                                        Choose New Image
                                    </label>
                                </div>
                                <img id="editImagePreview" class="preview-image d-none" alt="Preview">
                                <div id="currentImage" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"
                                placeholder="Describe your product..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Form -->
    <form id="deleteProductForm" method="POST" style="display: none;">
        <input type="hidden" id="delete_product_id" name="product_id">
        <input type="hidden" name="delete_product" value="1">
    </form>

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

        function previewEditImage(input) {
            const preview = document.getElementById('editImagePreview');
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
            // Fetch product data via AJAX
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;

                        // Populate the edit form
                        document.getElementById('edit_product_id').value = product.id;
                        document.getElementById('edit_product_name').value = product.name;
                        document.getElementById('edit_price').value = product.price;
                        document.getElementById('edit_category').value = product.category_id;
                        document.getElementById('edit_status').value = product.status;
                        document.getElementById('edit_unit').value = product.unit;
                        document.getElementById('edit_description').value = product.description;

                        // Show current image if exists
                        const currentImageDiv = document.getElementById('currentImage');
                        if (product.image_path) {
                            currentImageDiv.innerHTML = `<img src="../${product.image_path}" class="img-thumbnail" style="max-width: 100px;" alt="Current Image">`;
                        } else {
                            currentImageDiv.innerHTML = '<small class="text-muted">No image uploaded</small>';
                        }

                        // Show the modal
                        const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
                        modal.show();
                    } else {
                        alert('Error loading product data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading product data');
                });
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                document.getElementById('delete_product_id').value = productId;
                document.getElementById('deleteProductForm').submit();
            }
        }
    </script>
</body>

</html>