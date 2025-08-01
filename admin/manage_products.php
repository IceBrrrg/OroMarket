<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle product status updates
if (isset($_POST['action']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $sql = "UPDATE products SET status = 'approved' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    } elseif ($action == 'reject') {
        $sql = "UPDATE products SET status = 'rejected' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    } elseif ($action == 'delete') {
        // Delete the product (image is stored as BLOB in products table)

        // Then delete the product
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        .action-buttons .btn {
            margin: 0 2px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Manage Products</h1>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="filterProducts('all')">All</button>
                    <button class="btn btn-outline-success" onclick="filterProducts('approved')">Approved</button>
                    <button class="btn btn-outline-warning" onclick="filterProducts('pending')">Pending</button>
                    <button class="btn btn-outline-danger" onclick="filterProducts('rejected')">Rejected</button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Seller</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT p.*, c.name as category_name, s.shop_name 
                                       FROM products p 
                                       LEFT JOIN categories c ON p.category_id = c.id
                                       LEFT JOIN sellers s ON p.seller_id = s.id 
                                       ORDER BY p.id DESC";
                                $stmt = $pdo->query($sql);

                                while ($row = $stmt->fetch()) {
                                    $status_class = '';
                                    switch ($row['status']) {
                                        case 'approved':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'pending':
                                            $status_class = 'bg-warning text-dark';
                                            break;
                                        case 'rejected':
                                            $status_class = 'bg-danger';
                                            break;
                                    }

                                    echo "<tr class='product-row' data-status='{$row['status']}'>";
                                    echo "<td>{$row['id']}</td>";
                                    echo "<td>";
                                    if (!empty($row['image'])) {
                                        echo "<img src='data:image/jpeg;base64," . base64_encode($row['image']) . "' class='product-image' alt='Product'>";
                                    } else {
                                        echo "<div class='product-image bg-light d-flex align-items-center justify-content-center'>";
                                        echo "<i class='bi bi-image text-muted'></i>";
                                        echo "</div>";
                                    }
                                    echo "</td>";
                                    echo "<td>{$row['name']}</td>";
                                    echo "<td>" . ($row['category_name'] ? $row['category_name'] : 'Uncategorized') . "</td>";
                                    echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                                    echo "<td>" . ($row['shop_name'] ? $row['shop_name'] : 'Unknown Seller') . "</td>";
                                    echo "<td><span class='badge {$status_class} status-badge'>{$row['status']}</span></td>";
                                    echo "<td class='action-buttons'>";

                                    if ($row['status'] != 'approved') {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
                                        echo "<input type='hidden' name='action' value='approve'>";
                                        echo "<button type='submit' class='btn btn-sm btn-success' title='Approve'>";
                                        echo "<i class='bi bi-check-lg'></i></button></form> ";
                                    }

                                    if ($row['status'] != 'rejected') {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='action' value='reject'>";
                                        echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
                                        echo "<button type='submit' class='btn btn-sm btn-warning' title='Reject'>";
                                        echo "<i class='bi bi-x-lg'></i></button></form> ";
                                    }

                                    echo "<button class='btn btn-sm btn-info' onclick='viewDetails({$row['id']})' title='View Details'>";
                                    echo "<i class='bi bi-eye'></i></button> ";

                                    echo "<form method='POST' style='display:inline;'>";
                                    echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
                                    echo "<input type='hidden' name='action' value='delete'>";
                                    echo "<button type='submit' class='btn btn-sm btn-danger' title='Delete' onclick='return confirm(\"Are you sure you want to delete this product?\")'>";
                                    echo "<i class='bi bi-trash'></i></button></form>";

                                    echo "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterProducts(status) {
            const rows = document.querySelectorAll('.product-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function viewDetails(id) {
            // Implement AJAX call to fetch product details
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        }
    </script>
</body>

</html>