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
        $sql = "SELECT * FROM seller_applications WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();

        if ($application) {
            // Update the existing seller record - only update fields that exist
            $update_sql = "UPDATE sellers SET is_active = 1 WHERE id = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$application['seller_id']]);

            // Update application status
            $update_sql = "UPDATE seller_applications SET status = 'approved' WHERE id = ?";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$application_id]);
            
            $success_message = "Application approved successfully!";
        }
    } elseif ($action == 'reject') {
        $update_sql = "UPDATE seller_applications SET status = 'rejected' WHERE id = ?";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([$application_id]);
        
        $success_message = "Application rejected successfully!";
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

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Seller ID</th>
                                    <th>Business Name</th>
                                    <th>Account Holder</th>
                                    <th>Business Email</th>
                                    <th>Business Phone</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Join with sellers table to get seller information
                                $sql = "SELECT sa.*, s.username, s.email as seller_email, s.phone, s.first_name, s.last_name 
                                       FROM seller_applications sa 
                                       LEFT JOIN sellers s ON sa.seller_id = s.id 
                                       ORDER BY sa.created_at DESC";
                                $stmt = $pdo->query($sql);

                                while ($row = $stmt->fetch()) {
                                    $status_class =
                                        $row['status'] == 'pending' ? 'bg-warning' :
                                        ($row['status'] == 'approved' ? 'bg-success' : 'bg-danger');

                                    echo "<tr class='application-row' data-status='{$row['status']}'>";
                                    echo "<td>{$row['id']}</td>";
                                    echo "<td>{$row['seller_id']}</td>";
                                    
                                    // Business name (this is the "shop name")
                                    $business_name = $row['business_name'] ?? $row['username'] ?? 'N/A';
                                    echo "<td>" . htmlspecialchars($business_name) . "</td>";
                                    
                                    // Bank account name (closest to owner name) or fallback to seller's name
                                    $owner_name = $row['bank_account_name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: $row['username'] ?? 'N/A';
                                    echo "<td>" . htmlspecialchars($owner_name) . "</td>";
                                    
                                    // Business email
                                    $email = $row['business_email'] ?? $row['seller_email'] ?? 'N/A';
                                    echo "<td>" . htmlspecialchars($email) . "</td>";
                                    
                                    // Business phone
                                    $contact = $row['business_phone'] ?? $row['phone'] ?? 'N/A';
                                    echo "<td>" . htmlspecialchars($contact) . "</td>";
                                    
                                    echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                    echo "<td><span class='badge {$status_class} status-badge'>" . ucfirst($row['status']) . "</span></td>";
                                    echo "<td class='action-buttons'>";

                                    if ($row['status'] == 'pending') {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='application_id' value='{$row['id']}'>";
                                        echo "<input type='hidden' name='action' value='approve'>";
                                        echo "<button type='submit' class='btn btn-sm btn-success' title='Approve' onclick='return confirm(\"Are you sure you want to approve this application?\")'>";
                                        echo "<i class='bi bi-check-lg'></i></button></form> ";

                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='application_id' value='{$row['id']}'>";
                                        echo "<input type='hidden' name='action' value='reject'>";
                                        echo "<button type='submit' class='btn btn-sm btn-danger' title='Reject' onclick='return confirm(\"Are you sure you want to reject this application?\")'>";
                                        echo "<i class='bi bi-x-lg'></i></button></form> ";
                                    }

                                    echo "<button class='btn btn-sm btn-info' title='View Details' onclick='viewDetails({$row['id']})'>";
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
                <div class="modal-body" id="modalBody">
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
                const rowStatus = row.dataset.status;
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update active button state
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function viewDetails(id) {
            // Basic implementation - you can enhance this with AJAX
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading application details...</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            modal.show();
            
            // Here you would typically make an AJAX call to fetch full details
            // For now, show available application details
            setTimeout(() => {
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Business Information</h6>
                            <p><strong>Application ID:</strong> ${id}</p>
                            <p><small class="text-muted">Complete details would require an AJAX endpoint to fetch full application data including business address, tax ID, registration number, bank details, documents, selected stall, and admin notes.</small></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Implementation Note</h6>
                            <p><small class="text-muted">To show full details, create a PHP endpoint that returns JSON data for the application with all fields from the seller_applications table.</small></p>
                        </div>
                    </div>
                `;
            }, 1000);
        }
    </script>
</body>

</html>