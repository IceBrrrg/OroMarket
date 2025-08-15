<?php
session_start();

// Include database connection for announcements
require_once '../includes/db_connect.php';

// Fetch announcements for customers (all users and customers specifically)
try {
    $stmt = $pdo->prepare("
        SELECT a.*, ad.username as created_by_name 
        FROM announcements a 
        LEFT JOIN admins ad ON a.created_by = ad.id 
        WHERE a.is_active = 1 
        AND (a.target_audience = 'all' OR a.target_audience = 'customers')
        AND (a.expiry_date IS NULL OR a.expiry_date > NOW())
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $announcements = [];
}

// Count total announcements for display
$announcement_count = count($announcements);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Oroquieta Marketplace</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="../assets/lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <link href="css/sellers.css" rel="stylesheet">
    <link href="css/view_product.css" rel="stylesheet">
    <link href="css/view_stall.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/img/logo-removebg.png" rel="icon">
    <link rel="stylesheet" href="css/index.css">


    <style>
        .navbar .nav-link {
            color: #81c408;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
        }

        .navbar .nav-link:hover {
            color: #45a049;
        }

        .navbar .nav-link i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .navbar .d-flex {
                flex-direction: column;
                align-items: start;
                max-height: 80vh;
                overflow-y: auto;
                width: 100%;
            }

            .navbar .nav-link {
                margin: 5px 0;
                width: 100%;
                padding: 8px 15px;
            }

            #navbarCollapse {
                max-height: 80vh;
                overflow-y: auto;
            }
        }

        /* Enhanced Complaint Modal Styles */
        .complaint-fab {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background-color: #81c408;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1050;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .complaint-fab:hover {
            background-color: #45a049;
            transform: scale(1.1);
            color: white;
        }

        /* Custom Modal Styles */
        #complaintModal .modal-dialog {
            max-width: 600px;
            margin: 1.75rem auto;
        }

        #complaintModal .modal-content {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        #complaintModal .modal-header {
            background: #81c408;
            border-bottom: none;
            padding: 20px 30px;
        }

        #complaintModal .modal-header .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #complaintModal .modal-body {
            padding: 30px;
            background: #fff;
            max-height: 70vh;
            overflow-y: auto;
        }

        #complaintModal .modal-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        /* Alert Styles */
        #complaintModal .alert-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-left: 4px solid #2196f3;
            color: #1565c0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 25px;
        }

        /* Seller Info Card */
        .seller-info-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
            border-left: 4px solid #81c408;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .seller-info-card .seller-name {
            font-weight: 700;
            color: #495057;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .seller-info-card .seller-details {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        /* Form Styling */
        #complaintModal .form-label {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        #complaintModal .form-label .text-danger {
            font-weight: 700;
        }

        #complaintModal .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #fff;
        }

        #complaintModal .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        #complaintModal .form-control::placeholder {
            color: #adb5bd;
            font-style: italic;
        }

        #complaintModal textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Button Styling */
        #complaintModal .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border: none;
        }

        #complaintModal .btn-secondary {
            background: #6c757d;
            color: white;
        }

        #complaintModal .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        #complaintModal .btn-primary {
            background: #81c408;
            color: white;
        }

        #complaintModal .btn-primary:hover {
            background: #81c408;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        /* Form Group Spacing */
        #complaintModal .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            #complaintModal .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100vw - 1rem);
            }

            #complaintModal .modal-body {
                padding: 20px;
                max-height: 60vh;
            }

            #complaintModal .modal-header {
                padding: 15px 20px;
            }

            #complaintModal .modal-footer {
                padding: 15px 20px;
                flex-direction: column-reverse;
                gap: 10px;
            }

            #complaintModal .btn {
                width: 100%;
                justify-content: center;
            }

            .complaint-fab {
                width: 50px;
                height: 50px;
                font-size: 20px;
                bottom: 20px;
                right: 20px;
            }

            .seller-info-card {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            #complaintModal .modal-header .modal-title {
                font-size: 1.25rem;
            }

            #complaintModal .modal-body {
                padding: 15px;
            }

            .seller-info-card {
                padding: 12px;
            }
        }

        /* Animation */
        #complaintModal.fade .modal-dialog {
            transform: translateY(-50px);
            transition: transform 0.3s ease-out;
        }

        #complaintModal.show .modal-dialog {
            transform: translateY(0);
        }

        /* Custom scrollbar for modal */
        #complaintModal .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        #complaintModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #complaintModal .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        #complaintModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Seller info card styling */
        .seller-info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #81c408;
        }

        .seller-info-card .seller-name {
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .seller-info-card .seller-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div id="spinner"
        class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status"></div>
    </div>
    <div class="container-fluid fixed-top">
        <div class="container topbar bg-primary d-none d-lg-block">
            <div class="d-flex justify-content-between">
                <div class="top-info ps-2">
                    <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i> <a href="#"
                            class="text-white">Barrientos St, Oroquieta City, Misamis Occidental</a></small>
                </div>
            </div>
        </div>
        <div class="container px-0">
            <nav class="navbar navbar-light bg-white navbar-expand-xl">
                <a href="../index.php" class="navbar-brand"><img src="../assets/img/logo-removebg.png" alt="logo"
                        width="100px"></a>
                <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars text-primary"></span>
                </button>
                <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                    <div class="navbar-nav mx-auto">
                    </div>
                    <div class="d-flex m-3 me-0">
                        <a href="../index.php" class="nav-link me-4">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                        <a href="../customer/index.php" class="nav-link me-4">
                            <i class="fas fa-shopping-basket me-1"></i>Market
                        </a>
                        <div class="dropdown">
                            <a href="#" class="my-auto position-relative" id="announcementsDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell fa-2x text-primary"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg"
                                style="width: 350px; max-height: 400px; overflow-y: auto;">
                                <li class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-megaphone me-2"></i>Announcements</span>
                                    <span class="badge bg-primary"><?php echo count($announcements); ?></span>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <?php if (empty($announcements)): ?>
                                    <li class="px-3 py-4 text-center text-muted">
                                        <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
                                        <small>No announcements available</small>
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <?php
                                        $isNew = strtotime($announcement['created_at']) > strtotime('-3 days');
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-3 border-start border-3 border-primary" href="#"
                                                onclick="showAnnouncementModal(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="fas fa-megaphone text-primary me-2"></i>
                                                            <h6 class="mb-0 fw-bold">
                                                                <?php echo htmlspecialchars(substr($announcement['title'], 0, 30)); ?>
                                                                <?php if (strlen($announcement['title']) > 30)
                                                                    echo '...'; ?>
                                                            </h6>
                                                            <?php if ($announcement['is_pinned']): ?>
                                                                <i class="fas fa-thumbtack text-warning ms-2"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="mb-1 text-muted small">
                                                            <?php echo htmlspecialchars(substr($announcement['content'], 0, 60)); ?>
                                                            <?php if (strlen($announcement['content']) > 60)
                                                                echo '...'; ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo date('M j, g:i A', strtotime($announcement['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($isNew): ?>
                                                        <span class="badge bg-success ms-2">New</span>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </li>
                                        <?php if ($announcement !== end($announcements)): ?>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div class="input-group w-75 mx-auto d-flex">
                        <input type="search" class="form-control p-3" placeholder="keywords"
                            aria-describedby="search-icon-1">
                        <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center" id="announcementModalLabel">
                        <i class="fas fa-megaphone me-2"></i>
                        <span id="modalAnnouncementTitle">Announcement Details</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-megaphone text-primary me-2"></i>
                                <span id="modalPinned" class="badge bg-warning ms-2" style="display: none;">
                                    <i class="fas fa-thumbtack me-1"></i>Pinned
                                </span>
                                <span id="modalNew" class="badge bg-success ms-2" style="display: none;">New</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <span id="modalCreatedBy">Admin</span>
                            </small>
                        </div>
                    </div>

                    <div class="announcement-content">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-align-left me-2"></i>Content
                        </h6>
                        <div id="modalContent" class="border-start border-3 border-primary ps-3 mb-4">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>Details
                                    </h6>
                                    <div class="mb-2">
                                        <strong>Target Audience:</strong>
                                        <span id="modalTargetAudience" class="badge bg-secondary ms-1">All Users</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Posted:</strong>
                                        <span id="modalCreatedAt" class="text-muted">-</span>
                                    </div>
                                    <div id="modalExpiryContainer" style="display: none;">
                                        <strong>Expires:</strong>
                                        <span id="modalExpiryDate" class="text-muted">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2">
                                        <i class="fas fa-chart-line me-2 text-success"></i>Status
                                    </h6>
                                    <div class="mb-2">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Active
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <a class="complaint-fab" onclick="openComplaintModal()" title="Report a Complaint">
        <i class="fas fa-flag"></i>
    </a>

    <div class="modal fade" id="complaintModal" tabindex="-1" aria-labelledby="complaintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="complaintModalLabel">
                        <i class="fas fa-exclamation-triangle"></i>
                        Report Complaint
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="complaintForm" action="submit_complaint.php" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please provide details about your complaint. Our admin team will review it promptly.
                        </div>

                        <!-- Seller Information Card -->
                        <div id="sellerInfoCard" class="seller-info-card" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-tie me-3 text-primary fs-4"></i>
                                <div>
                                    <div class="seller-name">Filing complaint against: <span
                                            id="selectedSellerName"></span></div>
                                    <div class="seller-details" id="selectedSellerDetails"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden input for seller_id -->
                        <input type="hidden" id="seller_id" name="seller_id" value="">

                        <div class="mb-3">
                            <label for="complainant_name" class="form-label">
                                Your Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="complainant_name" name="complainant_name"
                                required placeholder="Enter your full name">
                        </div>

                        <div class="mb-3">
                            <label for="complainant_email" class="form-label">
                                Your Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="complainant_email" name="complainant_email"
                                required placeholder="Enter your email address">
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Complaint Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="title" name="title" required
                                placeholder="Brief summary of your complaint">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                placeholder="Please describe your complaint in detail. Include specific incidents, dates, and any relevant information."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Complaint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function showAnnouncementModal(announcement) {
            // Set modal title
            document.getElementById('modalAnnouncementTitle').textContent = announcement.title;

            // Show/hide pinned badge
            const pinnedBadge = document.getElementById('modalPinned');
            if (announcement.is_pinned == '1') {
                pinnedBadge.style.display = 'inline-block';
            } else {
                pinnedBadge.style.display = 'none';
            }

            // Show/hide new badge
            const newBadge = document.getElementById('modalNew');
            const createdDate = new Date(announcement.created_at);
            const threeDaysAgo = new Date();
            threeDaysAgo.setDate(threeDaysAgo.getDate() - 3);

            if (createdDate > threeDaysAgo) {
                newBadge.style.display = 'inline-block';
            } else {
                newBadge.style.display = 'none';
            }

            // Set content
            document.getElementById('modalContent').innerHTML = announcement.content.replace(/\n/g, '<br>');

            // Set created by
            document.getElementById('modalCreatedBy').textContent = announcement.created_by_name || 'Admin';

            // Set target audience
            const targetBadge = document.getElementById('modalTargetAudience');
            const audienceText = announcement.target_audience === 'all' ? 'All Users' :
                announcement.target_audience.charAt(0).toUpperCase() + announcement.target_audience.slice(1);
            targetBadge.textContent = audienceText;

            // Set created date
            const createdAtDate = new Date(announcement.created_at);
            document.getElementById('modalCreatedAt').textContent = createdAtDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Set expiry date
            const expiryContainer = document.getElementById('modalExpiryContainer');
            if (announcement.expiry_date) {
                const expiryDate = new Date(announcement.expiry_date);
                document.getElementById('modalExpiryDate').textContent = expiryDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                expiryContainer.style.display = 'block';
            } else {
                expiryContainer.style.display = 'none';
            }

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
            modal.show();
        }

        // Function to open complaint modal and fetch seller info if on product page
        function openComplaintModal() {
            // Check if we're on a product page and get seller info
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('id');

            if (productId && window.location.pathname.includes('view_product.php')) {
                // Fetch seller information for this product
                fetchSellerInfo(productId);
            } else {
                // Show modal without seller info (general complaint)
                document.getElementById('sellerInfoCard').style.display = 'none';
                const modal = new bootstrap.Modal(document.getElementById('complaintModal'));
                modal.show();
            }
        }

        // Function to fetch seller information
        function fetchSellerInfo(productId) {
            fetch('get_seller_info.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: productId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.seller) {
                        // Populate seller information
                        document.getElementById('seller_id').value = data.seller.seller_id;
                        document.getElementById('selectedSellerName').textContent = data.seller.seller_name;
                        document.getElementById('selectedSellerDetails').textContent = data.seller.details;
                        document.getElementById('sellerInfoCard').style.display = 'block';
                    } else {
                        // Hide seller info card if no seller found
                        document.getElementById('sellerInfoCard').style.display = 'none';
                    }

                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('complaintModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching seller info:', error);
                    // Show modal without seller info
                    document.getElementById('sellerInfoCard').style.display = 'none';
                    const modal = new bootstrap.Modal(document.getElementById('complaintModal'));
                    modal.show();
                });
        }

        // Auto-refresh announcements every 5 minutes
        setInterval(function () {
            // Reload the page to get fresh announcements
            if (!document.querySelector('.modal.show')) { // Only reload if no modal is open
                location.reload();
            }
        }, 300000); // 5 minutes
    </script>