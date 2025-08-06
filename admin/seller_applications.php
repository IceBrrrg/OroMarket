<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Admin approval process functions
function approveSeller($seller_id, $pdo)
{
    try {
        $pdo->beginTransaction();

        // Update seller status to approved
        $stmt = $pdo->prepare("UPDATE sellers SET status = 'approved', is_active = 1 WHERE id = ?");
        $stmt->execute([$seller_id]);

        // Update seller application status
        $stmt = $pdo->prepare("UPDATE seller_applications SET status = 'approved' WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // Get stall application and approve it
        $stmt = $pdo->prepare("SELECT stall_id FROM stall_applications WHERE seller_id = ? AND status = 'pending'");
        $stmt->execute([$seller_id]);
        $stall_application = $stmt->fetch();

        if ($stall_application) {
            // Update stall application status
            $stmt = $pdo->prepare("UPDATE stall_applications SET status = 'approved' WHERE seller_id = ?");
            $stmt->execute([$seller_id]);

            // Assign stall to seller
            $stmt = $pdo->prepare("UPDATE stalls SET status = 'occupied', current_seller_id = ? WHERE id = ?");
            $stmt->execute([$seller_id, $stall_application['stall_id']]);
        }

        // Send notification to seller
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_type, recipient_id, title, message, link) 
            VALUES ('seller', ?, 'Application Approved!', 'Your seller application has been approved. You can now start listing products.', 'dashboard.php')
        ");
        $stmt->execute([$seller_id]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error approving seller: " . $e->getMessage());
        return false;
    }
}

// When admin rejects a seller application:
function rejectSeller($seller_id, $pdo, $reason = '')
{
    try {
        $pdo->beginTransaction();

        // First, get all stall applications for this seller (not just pending ones)
        $stmt = $pdo->prepare("SELECT stall_id FROM stall_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        $stall_applications = $stmt->fetchAll();

        // Update seller status to rejected
        $stmt = $pdo->prepare("UPDATE sellers SET status = 'rejected', is_active = 0 WHERE id = ?");
        $stmt->execute([$seller_id]);

        // Update seller application status
        $stmt = $pdo->prepare("UPDATE seller_applications SET status = 'rejected', admin_notes = ? WHERE seller_id = ?");
        $stmt->execute([$reason, $seller_id]);

        // Free up ALL stalls associated with this seller
        if ($stall_applications) {
            foreach ($stall_applications as $stall_app) {
                // Update stall application status to rejected
                $stmt = $pdo->prepare("UPDATE stall_applications SET status = 'rejected' WHERE seller_id = ? AND stall_id = ?");
                $stmt->execute([$seller_id, $stall_app['stall_id']]);

                // Free up the stall - make it available and remove seller assignment
                $stmt = $pdo->prepare("UPDATE stalls SET status = 'available', current_seller_id = NULL WHERE id = ?");
                $stmt->execute([$stall_app['stall_id']]);

                // Log the stall update for debugging
                error_log("Freed up stall ID: " . $stall_app['stall_id'] . " for rejected seller ID: " . $seller_id);
            }
        }

        // Send notification to seller
        $message = "Your seller application has been reviewed and rejected. " . ($reason ? "Reason: $reason" : "Please contact support for more details.");
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_type, recipient_id, title, message, link) 
            VALUES ('seller', ?, 'Application Rejected', ?, 'application_status.php')
        ");
        $stmt->execute([$seller_id, $message]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error rejecting seller: " . $e->getMessage());
        return false;
    }
}

// Alternative: Complete deletion function (if you prefer to delete all data)
function deleteSeller($seller_id, $pdo, $reason = '')
{
    try {
        $pdo->beginTransaction();

        // Get all stall applications for this seller first
        $stmt = $pdo->prepare("SELECT stall_id FROM stall_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        $stall_applications = $stmt->fetchAll();

        // Free up all associated stalls
        if ($stall_applications) {
            foreach ($stall_applications as $stall_app) {
                $stmt = $pdo->prepare("UPDATE stalls SET status = 'available', current_seller_id = NULL WHERE id = ?");
                $stmt->execute([$stall_app['stall_id']]);
            }
        }

        // Delete in correct order to avoid foreign key constraints

        // 1. Delete notifications
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE recipient_type = 'seller' AND recipient_id = ?");
        $stmt->execute([$seller_id]);

        // 2. Delete products (if any)
        $stmt = $pdo->prepare("DELETE FROM products WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // 3. Delete stall applications
        $stmt = $pdo->prepare("DELETE FROM stall_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // 4. Delete seller application
        $stmt = $pdo->prepare("DELETE FROM seller_applications WHERE seller_id = ?");
        $stmt->execute([$seller_id]);

        // 5. Finally delete the seller account
        $stmt = $pdo->prepare("DELETE FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);

        // Optional: Log the deletion for audit purposes
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (admin_id, action_type, details, created_at) 
            VALUES (?, 'seller_deleted', ?, NOW())
        ");
        $admin_id = $_SESSION['user_id'] ?? 0;
        $details = "Deleted rejected seller ID: $seller_id" . ($reason ? " - Reason: $reason" : "");
        $stmt->execute([$admin_id, $details]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting seller: " . $e->getMessage());
        return false;
    }
}

// Updated handler for rejection - you can choose which function to use
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'reject') {
    $application_id = $_POST['application_id'];
    $admin_notes = $_POST['admin_notes'] ?? '';

    // Get application details
    $sql = "SELECT seller_id FROM seller_applications WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();

    if ($application) {
        // Choose one of these approaches:

        // Option 1: Just reject but keep data (with proper stall freeing)
        if (rejectSeller($application['seller_id'], $pdo, $admin_notes)) {
            echo json_encode(['success' => true, 'message' => 'Application rejected and stall freed successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error rejecting application. Please try again.']);
        }

        // Option 2: Complete deletion (uncomment this and comment above if you prefer deletion)
        /*
        if (deleteSeller($application['seller_id'], $pdo, $admin_notes)) {
            echo json_encode(['success' => true, 'message' => 'Application rejected and data deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting application. Please try again.']);
        }
        */
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found.']);
    }
    exit();
}

// Handle application approval/rejection via AJAX
if (isset($_POST['ajax_action']) && isset($_POST['application_id'])) {
    $application_id = $_POST['application_id'];
    $action = $_POST['ajax_action'];
    $admin_notes = $_POST['admin_notes'] ?? '';

    if ($action == 'approve') {
        // Get application details
        $sql = "SELECT * FROM seller_applications WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();

        if ($application) {
            // Use the new approval function
            if (approveSeller($application['seller_id'], $pdo)) {
                // Update admin notes if provided
                if ($admin_notes) {
                    $update_sql = "UPDATE seller_applications SET admin_notes = ? WHERE id = ?";
                    $stmt = $pdo->prepare($update_sql);
                    $stmt->execute([$admin_notes, $application_id]);
                }
                echo json_encode(['success' => true, 'message' => 'Application approved successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error approving application. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Application not found.']);
        }
    } elseif ($action == 'reject') {
        // Get application details
        $sql = "SELECT seller_id FROM seller_applications WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();

        if ($application) {
            // Use the new rejection function
            if (rejectSeller($application['seller_id'], $pdo, $admin_notes)) {
                echo json_encode(['success' => true, 'message' => 'Application rejected successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error rejecting application. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Application not found.']);
        }
    }
    exit();
}

// Get application details for modal
if (isset($_GET['get_application']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT sa.*, s.username, s.email as seller_email, s.phone, s.first_name, s.last_name, s.status as seller_status,
                   st.stall_number, st.section, st.floor_number, st.monthly_rent
            FROM seller_applications sa 
            LEFT JOIN sellers s ON sa.seller_id = s.id 
            LEFT JOIN stall_applications sta ON sa.seller_id = sta.seller_id AND sta.status IN ('pending', 'approved')
            LEFT JOIN stalls st ON sta.stall_id = st.id
            WHERE sa.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        // Parse documents if they exist
        if ($application['documents_submitted']) {
            $documents = json_decode($application['documents_submitted'], true);

            // Log for debugging
            error_log("Raw documents: " . $application['documents_submitted']);
            error_log("Decoded documents: " . print_r($documents, true));

            if (json_last_error() === JSON_ERROR_NONE && $documents) {
                $application['documents'] = $documents;
            } else {
                error_log("JSON decode error: " . json_last_error_msg());
                $application['documents'] = [];
            }
        } else {
            $application['documents'] = [];
        }

        echo json_encode($application);
    } else {
        echo json_encode(['error' => 'Application not found']);
    }
    exit();
}

// Count pending applications for notification badge
$pending_count_sql = "SELECT COUNT(*) FROM seller_applications WHERE status = 'pending'";
$pending_count = $pdo->query($pending_count_sql)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Applications - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/seller_applications.css">
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
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
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
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <h1 class="mb-2">Seller Applications</h1>
                            <p class="mb-0">Review and manage seller applications for marketplace access</p>
                        </div>
                    </div>
                    <?php if ($pending_count > 0): ?>
                        <div class="notification-badge">
                            <span class="badge bg-warning fs-6"><?php echo $pending_count; ?> Pending</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="inbox-container">
                <div class="inbox-header">
                    <div class="inbox-filters">
                        <button class="btn active" onclick="filterApplications('all', this)">
                            <i class="bi bi-inbox"></i> All
                        </button>
                        <button class="btn" onclick="filterApplications('pending', this)">
                            <i class="bi bi-clock"></i> Pending
                            <?php if ($pending_count > 0): ?>
                                <span class="notification-count"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="btn" onclick="filterApplications('approved', this)">
                            <i class="bi bi-check-circle"></i> Approved
                        </button>
                        <button class="btn" onclick="filterApplications('rejected', this)">
                            <i class="bi bi-x-circle"></i> Rejected
                        </button>
                    </div>
                </div>

                <div class="inbox-list" id="inboxList">
                    <?php
                    // Get all applications with seller status
                    $sql = "SELECT sa.*, s.username, s.email as seller_email, s.phone, s.first_name, s.last_name, s.status as seller_status,
                                   st.stall_number, st.section, st.floor_number
                            FROM seller_applications sa 
                            LEFT JOIN sellers s ON sa.seller_id = s.id 
                            LEFT JOIN stall_applications sta ON sa.seller_id = sta.seller_id AND sta.status IN ('pending', 'approved')
                            LEFT JOIN stalls st ON sta.stall_id = st.id
                            ORDER BY sa.created_at DESC";
                    $stmt = $pdo->query($sql);

                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            $is_new = (strtotime($row['created_at']) > strtotime('-24 hours'));
                            $read_status = ($row['status'] == 'pending') ? 'unread' : 'read';

                            $business_name = $row['business_name'] ?? $row['username'] ?? 'N/A';
                            $owner_name = $row['bank_account_name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: $row['username'] ?? 'N/A';
                            $email = $row['business_email'] ?? $row['seller_email'] ?? 'N/A';
                            $phone = $row['business_phone'] ?? $row['phone'] ?? 'N/A';

                            // Create preview text with seller status
                            $preview_parts = [];
                            if ($row['stall_number']) {
                                $preview_parts[] = "Stall: {$row['stall_number']} ({$row['section']})";
                            }
                            $preview_parts[] = "Phone: {$phone}";
                            if ($row['tax_id']) {
                                $preview_parts[] = "Tax ID: {$row['tax_id']}";
                            }
                            if ($row['seller_status']) {
                                $preview_parts[] = "Seller Status: " . ucfirst($row['seller_status']);
                            }
                            $preview_text = implode(' • ', $preview_parts);

                            // Format timestamp
                            $timestamp = date('M d', strtotime($row['created_at']));
                            if (date('Y-m-d') == date('Y-m-d', strtotime($row['created_at']))) {
                                $timestamp = date('g:i A', strtotime($row['created_at']));
                            }

                            echo "<div class='inbox-item {$read_status}' data-status='{$row['status']}' onclick='viewApplication({$row['id']})'>";
                            echo "  <div class='status-dot {$row['status']}'></div>";
                            echo "  <div class='sender-info'>";
                            echo "    <div class='sender-name'>{$owner_name}";
                            if ($is_new && $row['status'] == 'pending') {
                                echo "<span class='new-badge'>NEW</span>";
                            }
                            echo "    </div>";
                            echo "    <div class='sender-email'>{$email}</div>";
                            echo "  </div>";
                            echo "  <div class='subject-preview'>";
                            echo "    <div class='subject'>Seller Application: {$business_name}</div>";
                            echo "    <div class='preview-text'>{$preview_text}</div>";
                            echo "  </div>";
                            echo "  <div class='timestamp'>{$timestamp}</div>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='empty-inbox'>";
                        echo "  <i class='bi bi-inbox'></i>";
                        echo "  <h4>No applications yet</h4>";
                        echo "  <p>Seller applications will appear here when submitted.</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer" id="modalFooter">
                    <!-- Action buttons will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterApplications(status, buttonElement) {
            const items = document.querySelectorAll('.inbox-item');
            items.forEach(item => {
                const itemStatus = item.dataset.status;
                if (status === 'all' || itemStatus === status) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            // Update active button state
            document.querySelectorAll('.inbox-filters .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            buttonElement.classList.add('active');
        }

        function viewApplication(id) {
            const modalBody = document.getElementById('modalBody');
            const modalFooter = document.getElementById('modalFooter');

            // Show loading state
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading application details...</p>
                </div>
            `;
            modalFooter.innerHTML = '';

            const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
            modal.show();

            // Fetch application details
            fetch(`?get_application=1&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Application data received:', data);

                    if (data.error) {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }

                    // Build documents HTML
                    let documentsHtml = '';

                    // Check if documents exist and process them
                    if (data.documents) {
                        let hasDocuments = false;

                        if (Array.isArray(data.documents)) {
                            hasDocuments = data.documents.length > 0;
                        } else if (typeof data.documents === 'object') {
                            hasDocuments = Object.keys(data.documents).length > 0;
                        }

                        if (hasDocuments) {
                            documentsHtml = '<div class="document-preview">';

                            if (Array.isArray(data.documents)) {
                                // Handle array format (old format)
                                data.documents.forEach(doc => {
                                    if (doc && doc.trim() !== '') {
                                        const filename = doc.split('/').pop();
                                        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(filename);

                                        documentsHtml += `
                                            <div class="document-item">
                                                ${isImage ?
                                                `<img src="../${doc}" alt="Document" class="img-thumbnail" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                     <i class="bi bi-file-earmark-text fs-1 text-muted" style="display:none;"></i>` :
                                                `<i class="bi bi-file-earmark-text fs-1 text-muted"></i>`
                                            }
                                                <div class="flex-grow-1">
                                                    <strong>${filename}</strong><br>
                                                    <a href="../${doc}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View Document
                                                    </a>
                                                </div>
                                            </div>
                                        `;
                                    }
                                });
                            } else {
                                // Handle object format (new format)
                                Object.entries(data.documents).forEach(([docType, docPath]) => {
                                    if (docPath && docPath.trim() !== '') {
                                        const filename = docPath.split('/').pop();
                                        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(filename);

                                        // Clean up document type name
                                        let docLabel = docType.replace(/_/g, ' ').replace(/document/g, '').trim();
                                        docLabel = docLabel.toUpperCase();
                                        if (docLabel === '') docLabel = 'Document';

                                        documentsHtml += `
                                            <div class="document-item">
                                                ${isImage ?
                                                `<img src="../${docPath}" alt="Document" class="img-thumbnail" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                     <i class="bi bi-file-earmark-text fs-1 text-muted" style="display:none;"></i>` :
                                                `<i class="bi bi-file-earmark-text fs-1 text-muted"></i>`
                                            }
                                                <div class="flex-grow-1">
                                                    <strong>${docLabel}</strong><br>
                                                    <small class="text-muted">${filename}</small><br>
                                                    <a href="../${docPath}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View Document
                                                    </a>
                                                </div>
                                            </div>
                                        `;
                                    }
                                });
                            }
                            documentsHtml += '</div>';
                        } else {
                            documentsHtml = '<p class="text-muted">No documents submitted</p>';
                        }
                    } else {
                        documentsHtml = '<p class="text-muted">No documents submitted</p>';
                    }

                    // Stall information
                    let stallInfo = '';
                    if (data.stall_number) {
                        stallInfo = `
                            <div class="stall-info">
                                <h6><i class="bi bi-shop"></i> Requested Stall</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Stall Number:</strong> ${data.stall_number}</p>
                                        <p><strong>Section:</strong> ${data.section}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Floor:</strong> ${data.floor_number}</p>
                                        <p><strong>Monthly Rent:</strong> ₱${parseFloat(data.monthly_rent).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // Seller status badge
                    const getStatusBadge = (status) => {
                        const statusClasses = {
                            'pending': 'bg-warning',
                            'approved': 'bg-success',
                            'rejected': 'bg-danger'
                        };
                        return `<span class="badge ${statusClasses[status] || 'bg-secondary'}">${status ? status.toUpperCase() : 'UNKNOWN'}</span>`;
                    };

                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Stall Information</h6>
                                <p><strong>Store Name:</strong> ${data.business_name || 'N/A'}</p>
                                <p><strong>Business Phone Number:</strong> ${data.business_phone || 'N/A'}</p>
                                <p><strong>Tax ID:</strong> ${data.tax_id || 'N/A'}</p>
                                <p><strong>Registration Number:</strong> ${data.business_registration_number || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mt-3">Seller Information</h6>
                                <p><strong>Username:</strong> ${data.username || 'N/A'}</p>
                                <p><strong>Name:</strong> ${(data.first_name || '') + ' ' + (data.last_name || '') || 'N/A'}</p>
                                <p><strong>Email:</strong> ${data.seller_email || 'N/A'}</p>
                                <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                                <p><strong>Account Status:</strong> ${getStatusBadge(data.seller_status)}</p>
                            </div>
                        </div>
                        
                        ${stallInfo}
                        
                        <div class="mt-3">
                            <h6>Submitted Documents</h6>
                            ${documentsHtml}
                        </div>
                        
                        ${data.admin_notes ? `
                            <div class="mt-3">
                                <h6>Admin Notes</h6>
                                <div class="alert alert-info">${data.admin_notes}</div>
                            </div>
                        ` : ''}
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Applied:</strong> ${new Date(data.created_at).toLocaleString()}<br>
                                <strong>Application Status:</strong> ${getStatusBadge(data.status)}
                            </small>
                        </div>
                    `;

                    // Add action buttons for pending applications
                    if (data.status === 'pending') {
                        modalFooter.innerHTML = `
                            <div class="w-100">
                                <div class="mb-3">
                                    <label for="adminNotes" class="form-label">Admin Notes (Optional)</label>
                                    <textarea class="form-control" id="adminNotes" rows="2" placeholder="Add any notes about this decision..."></textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-danger" onclick="processApplication(${id}, 'reject')">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="processApplication(${id}, 'approve')">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        modalFooter.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Error loading application details.</div>';
                });
        }

        function processApplication(id, action) {
            const adminNotes = document.getElementById('adminNotes')?.value || '';
            const actionText = action === 'approve' ? 'approve' : 'reject';

            if (!confirm(`Are you sure you want to ${actionText} this application?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('application_id', id);
            formData.append('admin_notes', adminNotes);

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('applicationModal')).hide();

                        // Show success message
                        showToast(data.message, 'success');

                        // Reload page to reflect changes
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Error processing application', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error processing application', 'error');
                });
        }

        function showToast(message, type) {
            // Create toast element
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            // Add to toast container (create if doesn't exist)
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }

            toastContainer.insertAdjacentHTML('beforeend', toastHtml);

            // Show toast element
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();

            // Remove toast element after it's hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
    </script>
</body>

</html>