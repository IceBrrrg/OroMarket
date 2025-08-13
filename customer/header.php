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

// Count urgent announcements
$urgent_count = count(array_filter($announcements, function($a) { return $a['priority'] === 'urgent'; }));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Oroquieta Marketplace</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../assets/lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/sellers.css" rel="stylesheet">
    <link href="css/view_product.css" rel="stylesheet">
    <link href="css/view_stall.css" rel="stylesheet">
    <!-- Template Stylesheet -->
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
                flex-wrap: wrap;
                justify-content: center;
            }

            .navbar .nav-link {
                margin: 5px 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner"
        class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status"></div>
    </div>
    <!-- Spinner End -->

    <!-- Navbar start -->
    <div class="container-fluid fixed-top">
        <div class="container topbar bg-primary d-none d-lg-block">
            <div class="d-flex justify-content-between">
                <div class="top-info ps-2">
                    <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i> <a href="#"
                            class="text-white">Barrientos St, Oroquieta City, Misamis Occidental</a></small>
                </div>
                <div class="top-link pe-2">
                    <i class="fas fa-solid fa-flag text-warning"></i><a href="submit_complaint.php" class="text-white" data-bs-toggle="modal" data-bs-target="#complaintModal"><small class="text-white mx-2">Report Complaint</small></a>
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
                        <!-- Announcements Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="my-auto position-relative" id="announcementsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell fa-2x text-primary"></i>
                                <?php if ($urgent_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $urgent_count; ?>
                                        <span class="visually-hidden">urgent announcements</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 350px; max-height: 400px; overflow-y: auto;">
                                <li class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-megaphone me-2"></i>Announcements</span>
                                    <span class="badge bg-primary"><?php echo count($announcements); ?></span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <?php if (empty($announcements)): ?>
                                    <li class="px-3 py-4 text-center text-muted">
                                        <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
                                        <small>No announcements available</small>
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <?php
                                        $priorityClass = '';
                                        $priorityIcon = '';
                                        switch ($announcement['priority']) {
                                            case 'urgent':
                                                $priorityClass = 'border-danger';
                                                $priorityIcon = 'fas fa-exclamation-triangle text-danger';
                                                break;
                                            case 'high':
                                                $priorityClass = 'border-warning';
                                                $priorityIcon = 'fas fa-exclamation-circle text-warning';
                                                break;
                                            case 'medium':
                                                $priorityClass = 'border-info';
                                                $priorityIcon = 'fas fa-info-circle text-info';
                                                break;
                                            default:
                                                $priorityClass = 'border-secondary';
                                                $priorityIcon = 'fas fa-circle text-secondary';
                                        }
                                        $isNew = strtotime($announcement['created_at']) > strtotime('-3 days');
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-3 border-start border-3 <?php echo $priorityClass; ?>" 
                                               href="#" 
                                               onclick="showAnnouncementModal(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-1">
                                                            <i class="<?php echo $priorityIcon; ?> me-2"></i>
                                                            <h6 class="mb-0 fw-bold">
                                                                <?php echo htmlspecialchars(substr($announcement['title'], 0, 30)); ?>
                                                                <?php if (strlen($announcement['title']) > 30) echo '...'; ?>
                                                            </h6>
                                                            <?php if ($announcement['is_pinned']): ?>
                                                                <i class="fas fa-thumbtack text-warning ms-2"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="mb-1 text-muted small">
                                                            <?php echo htmlspecialchars(substr($announcement['content'], 0, 60)); ?>
                                                            <?php if (strlen($announcement['content']) > 60) echo '...'; ?>
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
                                            <li><hr class="dropdown-divider my-1"></li>
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
    <!-- Navbar End -->

    <!-- Modal Search Start -->
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
    <!-- Modal Search End -->

    <!-- Announcement Details Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title d-flex align-items-center" id="announcementModalLabel">
                        <i class="fas fa-megaphone me-2"></i>
                        <span id="modalAnnouncementTitle">Announcement Details</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <i id="modalPriorityIcon" class="fas fa-info-circle text-info me-2"></i>
                                <span id="modalPriority" class="badge bg-info">Medium</span>
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
                            <!-- Content will be inserted here -->
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
                                    <div class="mb-2">
                                        <strong>Priority Level:</strong>
                                        <span id="modalPriorityText" class="text-muted">Medium</span>
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
    <!-- Announcement Details Modal End -->

    <!-- Complaint Modal Start -->
    <div class="modal fade" id="complaintModal" tabindex="-1" aria-labelledby="complaintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-dark">
                    <h5 class="modal-title" id="complaintModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Report Complaint
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="complaintForm" action="submit_complaint.php" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please provide details about your complaint. Our admin team will review it promptly.
                        </div>
                        
                        <div class="mb-3">
                            <label for="complainant_name" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="complainant_name" name="complainant_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="complainant_email" class="form-label">Your Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="complainant_email" name="complainant_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="seller_id" class="form-label">Select Seller <span class="text-danger">*</span></label>
                            <select class="form-select" id="seller_id" name="seller_id" required>
                                <option value="">Choose a seller...</option>
                                <?php
                                // Fetch sellers for dropdown
                                if (isset($pdo)) {
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT s.id, s.first_name, s.last_name, sa.business_name 
                                            FROM sellers s 
                                            LEFT JOIN seller_applications sa ON s.id = sa.seller_id 
                                            WHERE s.status = 'approved' AND s.is_active = 1 
                                            ORDER BY s.first_name, s.last_name
                                        ");
                                        $stmt->execute();
                                        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($sellers as $seller) {
                                            echo '<option value="' . $seller['id'] . '">';
                                            echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']);
                                            if ($seller['business_name']) {
                                                echo ' - ' . htmlspecialchars($seller['business_name']);
                                            }
                                            echo '</option>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<option value="">No sellers available</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Complaint Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Brief summary of your complaint">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Please describe your complaint in detail..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Submit Complaint
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Complaint Modal End -->

    <script>
        function showAnnouncementModal(announcement) {
            // Set modal title
            document.getElementById('modalAnnouncementTitle').textContent = announcement.title;
            
            // Set priority icon and badge
            const priorityIcon = document.getElementById('modalPriorityIcon');
            const priorityBadge = document.getElementById('modalPriority');
            const priorityText = document.getElementById('modalPriorityText');
            
            switch (announcement.priority) {
                case 'urgent':
                    priorityIcon.className = 'fas fa-exclamation-triangle text-danger me-2';
                    priorityBadge.className = 'badge bg-danger';
                    priorityBadge.textContent = 'Urgent';
                    priorityText.textContent = 'Urgent';
                    break;
                case 'high':
                    priorityIcon.className = 'fas fa-exclamation-circle text-warning me-2';
                    priorityBadge.className = 'badge bg-warning';
                    priorityBadge.textContent = 'High';
                    priorityText.textContent = 'High';
                    break;
                case 'medium':
                    priorityIcon.className = 'fas fa-info-circle text-info me-2';
                    priorityBadge.className = 'badge bg-info';
                    priorityBadge.textContent = 'Medium';
                    priorityText.textContent = 'Medium';
                    break;
                default:
                    priorityIcon.className = 'fas fa-circle text-secondary me-2';
                    priorityBadge.className = 'badge bg-secondary';
                    priorityBadge.textContent = 'Low';
                    priorityText.textContent = 'Low';
            }
            
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

        // Auto-refresh announcements every 5 minutes
        setInterval(function() {
            // Reload the page to get fresh announcements
            if (!document.querySelector('.modal.show')) { // Only reload if no modal is open
                location.reload();
            }
        }, 300000); // 5 minutes
    </script>