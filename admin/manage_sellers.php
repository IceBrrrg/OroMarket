<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle seller status updates
if (isset($_POST['action']) && isset($_POST['seller_id'])) {
    $seller_id = $_POST['seller_id'];
    $action = $_POST['action'];

    if ($action == 'activate') {
        $sql = "UPDATE sellers SET status = 'active' WHERE seller_id = $seller_id";
        mysqli_query($conn, $sql);
    } elseif ($action == 'deactivate') {
        $sql = "UPDATE sellers SET status = 'inactive' WHERE seller_id = $seller_id";
        mysqli_query($conn, $sql);
    } elseif ($action == 'delete') {
        // First check if seller has any products
        $check_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = $seller_id";
        $result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($result);

        if ($row['count'] > 0) {
            $error = "Cannot delete seller with existing products";
        } else {
            $sql = "DELETE FROM sellers WHERE seller_id = $seller_id";
            mysqli_query($conn, $sql);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers - Admin Dashboard</title>
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
                <h1 class="h2">Manage Sellers</h1>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="filterSellers('all')">All</button>
                    <button class="btn btn-outline-success" onclick="filterSellers('active')">Active</button>
                    <button class="btn btn-outline-danger" onclick="filterSellers('inactive')">Inactive</button>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Shop Name</th>
                                    <th>Owner Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT s.*, COUNT(p.product_id) as product_count 
                                       FROM sellers s 
                                       LEFT JOIN products p ON s.seller_id = p.seller_id 
                                       GROUP BY s.seller_id 
                                       ORDER BY s.seller_id DESC";
                                $result = mysqli_query($conn, $sql);

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $status_class = $row['status'] == 'active' ? 'bg-success' : 'bg-danger';

                                    echo "<tr class='seller-row' data-status='{$row['status']}'>";
                                    echo "<td>{$row['seller_id']}</td>";
                                    echo "<td>{$row['shop_name']}</td>";
                                    echo "<td>{$row['owner_name']}</td>";
                                    echo "<td>{$row['email']}</td>";
                                    echo "<td>{$row['contact_number']}</td>";
                                    echo "<td>{$row['product_count']}</td>";
                                    echo "<td><span class='badge {$status_class} status-badge'>{$row['status']}</span></td>";
                                    echo "<td class='action-buttons'>";

                                    if ($row['status'] == 'inactive') {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='seller_id' value='{$row['seller_id']}'>";
                                        echo "<input type='hidden' name='action' value='activate'>";
                                        echo "<button type='submit' class='btn btn-sm btn-success' title='Activate'>";
                                        echo "<i class='bi bi-check-lg'></i></button></form> ";
                                    } else {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='seller_id' value='{$row['seller_id']}'>";
                                        echo "<input type='hidden' name='action' value='deactivate'>";
                                        echo "<button type='submit' class='btn btn-sm btn-warning' title='Deactivate'>";
                                        echo "<i class='bi bi-pause-fill'></i></button></form> ";
                                    }

                                    echo "<button class='btn btn-sm btn-info' onclick='viewDetails({$row['seller_id']})' title='View Details'>";
                                    echo "<i class='bi bi-eye'></i></button> ";

                                    if ($row['product_count'] == 0) {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='seller_id' value='{$row['seller_id']}'>";
                                        echo "<input type='hidden' name='action' value='delete'>";
                                        echo "<button type='submit' class='btn btn-sm btn-danger' title='Delete' onclick='return confirm(\"Are you sure you want to delete this seller?\")'>";
                                        echo "<i class='bi bi-trash'></i></button></form>";
                                    }

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
                    <h5 class="modal-title">Seller Details</h5>
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
        function filterSellers(status) {
            const rows = document.querySelectorAll('.seller-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function viewDetails(id) {
            // Implement AJAX call to fetch seller details
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        }
    </script>
</body>

</html>