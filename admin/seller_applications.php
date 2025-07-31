<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle application approval/rejection
if (isset($_POST['action']) && isset($_POST['application_id'])) {
    $application_id = $_POST['application_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        // Get application details
        $sql = "SELECT * FROM seller_applications WHERE application_id = $application_id";
        $result = mysqli_query($conn, $sql);
        $application = mysqli_fetch_assoc($result);

        // Insert into sellers table
        $insert_sql = "INSERT INTO sellers (shop_name, owner_name, email, contact_number, address, status) 
                      VALUES ('" . $application['shop_name'] . "', 
                              '" . $application['owner_name'] . "', 
                              '" . $application['email'] . "', 
                              '" . $application['contact_number'] . "', 
                              '" . $application['address'] . "', 
                              'active')";
        mysqli_query($conn, $insert_sql);

        // Update application status
        $update_sql = "UPDATE seller_applications SET status = 'approved' WHERE application_id = $application_id";
        mysqli_query($conn, $update_sql);
    } elseif ($action == 'reject') {
        $update_sql = "UPDATE seller_applications SET status = 'rejected' WHERE application_id = $application_id";
        mysqli_query($conn, $update_sql);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Applications - Admin Dashboard</title>
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
                <h1 class="h2">Seller Applications</h1>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary" onclick="filterApplications('all')">All</button>
                    <button class="btn btn-outline-warning" onclick="filterApplications('pending')">Pending</button>
                    <button class="btn btn-outline-success" onclick="filterApplications('approved')">Approved</button>
                    <button class="btn btn-outline-danger" onclick="filterApplications('rejected')">Rejected</button>
                </div>
            </div>

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
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM seller_applications ORDER BY created_at DESC";
                                $result = mysqli_query($conn, $sql);

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $status_class =
                                        $row['status'] == 'pending' ? 'bg-warning' :
                                        ($row['status'] == 'approved' ? 'bg-success' : 'bg-danger');

                                    echo "<tr class='application-row' data-status='{$row['status']}'>";
                                    echo "<td>{$row['application_id']}</td>";
                                    echo "<td>{$row['shop_name']}</td>";
                                    echo "<td>{$row['owner_name']}</td>";
                                    echo "<td>{$row['email']}</td>";
                                    echo "<td>{$row['contact_number']}</td>";
                                    echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                    echo "<td><span class='badge {$status_class} status-badge'>{$row['status']}</span></td>";
                                    echo "<td class='action-buttons'>";

                                    if ($row['status'] == 'pending') {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='application_id' value='{$row['application_id']}'>";
                                        echo "<input type='hidden' name='action' value='approve'>";
                                        echo "<button type='submit' class='btn btn-sm btn-success' onclick='return confirm(\"Are you sure you want to approve this application?\")'>";
                                        echo "<i class='bi bi-check-lg'></i></button></form> ";

                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='application_id' value='{$row['application_id']}'>";
                                        echo "<input type='hidden' name='action' value='reject'>";
                                        echo "<button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to reject this application?\")'>";
                                        echo "<i class='bi bi-x-lg'></i></button></form> ";
                                    }

                                    echo "<button class='btn btn-sm btn-info' onclick='viewDetails({$row['application_id']})'>";
                                    echo "<i class='bi bi-eye'></i></button>";
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
                    <h5 class="modal-title">Application Details</h5>
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
        function filterApplications(status) {
            const rows = document.querySelectorAll('.application-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function viewDetails(id) {
            // Implement AJAX call to fetch application details
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
        }
    </script>
</body>

</html>