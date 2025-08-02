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
                        <a class="dropdown-item text-white <?php echo $current_page == 'manage_sellers.php' ? 'active bg-primary' : ''; ?>"
                            href="manage_sellers.php">
                            <i class="bi bi-gear me-2"></i> Manage Sellers
                        </a>
                    </li>
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
                <i class="bi bi-person-plus me-2"></i> Add New Admin
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active bg-primary' : 'text-white'; ?>"
                href="settings.php">
                <i class="bi bi-gear-wide-connected me-2"></i> Profile Settings
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
        overflow-y: auto;
    }

    .sidebar .nav-link {
        border-radius: 5px;
        transition: all 0.3s;
        padding: 0.5rem 1rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        font-weight: bold;
        color: #fff;
        background-color: #0d6efd !important;
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

    /* Dropdown styles - Fixed positioning and animation */
    .sidebar .dropdown {
        position: relative;
        /* margin-bottom: 0.5rem; */
    }

    .sidebar .dropdown-menu {
        background-color: #343a40;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: static !important;
        float: none;
        width: 100%;
        margin-top: 0.5rem;
        border-radius: 5px;
        box-shadow: none;
        transform: none !important;
        animation: dropdownSlide 0.3s ease-out;
        z-index: 1000;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
    }

    .sidebar .dropdown-menu.show {
        display: block !important;
        max-height: 200px;
        opacity: 1;
        overflow: visible;
    }

    .sidebar .dropdown-item {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.5rem 1rem;
        border-radius: 3px;
        margin: 0.1rem 0.5rem;
        transition: all 0.2s ease;
        text-decoration: none;
        display: block;
    }

    .sidebar .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        transform: translateX(5px);
        text-decoration: none;
    }

    .sidebar .dropdown-item.active {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .sidebar .dropdown-toggle::after {
        margin-left: 0.5em;
        transition: transform 0.3s ease;
    }

    .sidebar .dropdown.show .dropdown-toggle::after {
        transform: rotate(180deg);
    }

    /* Dropdown animation */
    @keyframes dropdownSlide {
        from {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
        }

        to {
            opacity: 1;
            max-height: 200px;
            overflow: visible;
        }
    }

    /* Ensure dropdown doesn't overlap other items */
    .sidebar .nav-item {
        position: relative;
        margin-bottom: 0.5rem;
    }

    .sidebar .dropdown-menu.show {
        display: block !important;
        position: static !important;
        transform: none !important;
        width: 100%;
        margin: 0.5rem 0;
        padding: 0.5rem 0;
    }

    /* Prevent dropdown from going outside sidebar */
    .sidebar .dropdown-menu {
        inset: auto !important;
        top: auto !important;
        left: auto !important;
        right: auto !important;
        bottom: auto !important;
    }

    /* Smooth transition for dropdown items */
    .sidebar .dropdown-menu .dropdown-item {
        opacity: 0;
        animation: fadeInItem 0.2s ease-out forwards;
    }

    .sidebar .dropdown-menu .dropdown-item:nth-child(1) {
        animation-delay: 0.1s;
    }

    .sidebar .dropdown-menu .dropdown-item:nth-child(2) {
        animation-delay: 0.2s;
    }

    .sidebar .dropdown-menu .dropdown-item:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes fadeInItem {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Fix dropdown toggle behavior */
    .sidebar .dropdown-toggle[aria-expanded="true"] {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Ensure proper spacing */
    .sidebar .nav-item {
        margin-bottom: 0.5rem;
    }

    /* Hover effects for all nav items */
    .sidebar .nav-link:hover,
    .sidebar .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        transform: translateX(5px);
    }

    /* Additional dropdown styling */
    .sidebar .dropdown-open .dropdown-toggle {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Ensure proper sidebar layout */
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
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Prevent dropdown from affecting layout */
    .sidebar .nav {
        flex: 1;
        overflow: visible;
    }

    /* Ensure sidebar icons are always visible */
    .sidebar .nav-link i,
    .sidebar .dropdown-item i {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-family: "bootstrap-icons" !important;
    }

    /* Ensure sidebar has proper z-index */
    .sidebar {
        z-index: 1000 !important;
    }

    /* Prevent any CSS from hiding sidebar icons */
    .sidebar * {
        visibility: visible !important;
    }

    /* Force icon display */
    .bi {
        display: inline-block !important;
        font-family: "bootstrap-icons" !important;
        font-style: normal;
        font-weight: normal !important;
        font-variant: normal;
        text-transform: none;
        line-height: 1;
        vertical-align: middle;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
</style>

<script>
    // Ensure Bootstrap dropdown functionality works properly
    document.addEventListener('DOMContentLoaded', function () {
        // Wait for Bootstrap to be fully loaded
        if (typeof bootstrap !== 'undefined') {
            initializeDropdowns();
        } else {
            // If Bootstrap isn't loaded yet, wait a bit and try again
            setTimeout(function () {
                if (typeof bootstrap !== 'undefined') {
                    initializeDropdowns();
                } else {
                    // Fallback: try again after a longer delay
                    setTimeout(initializeDropdowns, 500);
                }
            }, 100);
        }

        function initializeDropdowns() {
            // Close any open dropdowns first
            closeAllDropdowns();

            // Destroy any existing dropdown instances first
            var existingDropdowns = document.querySelectorAll('.dropdown-toggle');
            existingDropdowns.forEach(function (toggle) {
                const existingInstance = bootstrap.Dropdown.getInstance(toggle);
                if (existingInstance) {
                    existingInstance.dispose();
                }
            });

            // Initialize Bootstrap dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.forEach(function (dropdownToggleEl) {
                try {
                    new bootstrap.Dropdown(dropdownToggleEl, {
                        autoClose: true,
                        boundary: 'viewport'
                    });
                } catch (error) {
                    console.log('Dropdown initialization error:', error);
                }
            });
        }

        function closeAllDropdowns() {
            // Close all open dropdowns
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });

            // Reset all dropdown toggles
            const toggles = document.querySelectorAll('.dropdown-toggle[aria-expanded="true"]');
            toggles.forEach(toggle => {
                toggle.setAttribute('aria-expanded', 'false');
            });

            // Remove dropdown-open class from all dropdowns
            const dropdownContainers = document.querySelectorAll('.dropdown');
            dropdownContainers.forEach(container => {
                container.classList.remove('dropdown-open');
            });
        }

        // Add custom styling for dropdown when open
        document.addEventListener('click', function (e) {
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');

                if (toggle && menu) {
                    if (toggle.getAttribute('aria-expanded') === 'true') {
                        dropdown.classList.add('dropdown-open');
                    } else {
                        dropdown.classList.remove('dropdown-open');
                    }
                }
            });
        });

        // Ensure dropdown closes when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown')) {
                closeAllDropdowns();
            }
        });

        // Close dropdowns when navigating to a new page
        document.addEventListener('beforeunload', function () {
            closeAllDropdowns();
        });

        // Re-initialize dropdowns when page content changes (for SPA-like behavior)
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList') {
                    // Close any open dropdowns first
                    closeAllDropdowns();

                    // Check if dropdown elements were added
                    const dropdowns = document.querySelectorAll('.dropdown-toggle');
                    dropdowns.forEach(function (dropdown) {
                        if (!bootstrap.Dropdown.getInstance(dropdown)) {
                            try {
                                new bootstrap.Dropdown(dropdown, {
                                    autoClose: true,
                                    boundary: 'viewport'
                                });
                            } catch (error) {
                                console.log('Dropdown re-initialization error:', error);
                            }
                        }
                    });
                }
            });
        });

        // Start observing the sidebar for changes
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            observer.observe(sidebar, {
                childList: true,
                subtree: true
            });
        }

        // Close dropdowns when clicking on navigation links
        document.addEventListener('click', function (e) {
            if (e.target.closest('a[href]') && !e.target.closest('.dropdown-toggle')) {
                // If clicking on a navigation link (not dropdown toggle), close dropdowns
                setTimeout(closeAllDropdowns, 100);
            }
        });

        // Force re-initialization when page becomes visible (for better cross-page compatibility)
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                setTimeout(initializeDropdowns, 100);
            }
        });
    });

    // Additional fallback: re-initialize dropdowns when window loads
    window.addEventListener('load', function () {
        if (typeof bootstrap !== 'undefined') {
            // Close any open dropdowns first
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });

            const dropdowns = document.querySelectorAll('.dropdown-toggle');
            dropdowns.forEach(function (dropdown) {
                if (!bootstrap.Dropdown.getInstance(dropdown)) {
                    try {
                        new bootstrap.Dropdown(dropdown, {
                            autoClose: true,
                            boundary: 'viewport'
                        });
                    } catch (error) {
                        console.log('Dropdown load initialization error:', error);
                    }
                }
            });
        }
    });
</script>