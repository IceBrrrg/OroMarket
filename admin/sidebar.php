<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar bg-dark text-white">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <a href="dashboard.php">
            <img src="../assets/img/logo-removebg.png" alt="logo" width="100px">
        </a>
    </div>
    <ul class="nav flex-column p-3">
        <li class="nav-item mb-2">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active bg-primary' : 'text-white'; ?>"
                href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-2">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" role="button" id="sellerDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-people me-2"></i> Sellers
                </a>
                <ul class="dropdown-menu bg-dark" aria-labelledby="sellerDropdown">
                    <li>
                        <a class="dropdown-item text-white <?php echo $current_page == 'seller_applications.php' ? 'active bg-primary' : ''; ?>"
                            href="seller_applications.php">
                            <i class="bi bi-file-earmark-text me-2"></i> Applications
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-white <?php echo $current_page == 'floorplan.php' ? 'active bg-primary' : ''; ?>"
                            href="floorplan.php">
                            <i class="bi bi-grid me-2"></i> Floorplan
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-white <?php echo $current_page == 'manage_sellers.php' ? 'active bg-primary' : ''; ?>"
                            href="manage_sellers.php">
                            <i class="bi bi-gear me-2"></i> Manage Sellers
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?php echo $current_page == 'manage_products.php' ? 'active bg-primary' : 'text-white'; ?>"
                href="manage_products.php">
                <i class="bi bi-box-seam me-2"></i> Manage Products
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?php echo $current_page == 'signup.php' ? 'active bg-primary' : 'text-white'; ?>"
                href="signup.php">
                <i class="bi bi-gear me-2"></i> Add New Admin
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active bg-primary' : 'text-white'; ?>"
                href="settings.php">
                <i class="bi bi-gear me-2"></i> Settings
            </a>
        </li>
    </ul>
    <div class="sidebar-footer p-3 border-top border-secondary mt-auto">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="bi bi-person-circle fs-4"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <small class="text">Administrator</small>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<style>
    .sidebar {
        min-height: 100vh;
        width: 250px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 100;
        display: flex;
        flex-direction: column;
        padding-top: 0;
        background-color: #343a40;
    }

    .sidebar .nav-link {
        border-radius: 5px;
        transition: all 0.3s;
        padding: 0.5rem 1rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .sidebar .nav-link.active {
        font-weight: bold;
        color: #fff;
    }

    .sidebar-footer {
        margin-top: auto;
        background-color: rgba(0, 0, 0, 0.2);
    }

    /* Ensure main content doesn't overlap with sidebar */
    .content {
        margin-left: 250px;
        padding: 20px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            position: relative;
            height: auto;
        }

        .content {
            margin-left: 0;
        }
    }

    /* Fix for the logo */
    .sidebar-header img {
        max-width: 100%;
        height: auto;
        margin-left: 50px;
    }

    /* Make links more visible */
    .sidebar .nav-link.text-white {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .sidebar .nav-link.active {
        background-color: #0d6efd !important;
        color: white !important;
    }

    /* Dropdown styles */
    .dropdown-menu {
        background-color: #343a40;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .dropdown-item {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.5rem 1rem;
    }

    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .dropdown-item.active {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .dropdown-toggle::after {
        margin-left: 0.5em;
    }
</style>