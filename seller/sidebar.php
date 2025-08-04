<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get seller information for display
$seller_id = $_SESSION['user_id'];
require_once '../includes/db_connect.php';

$stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

// Get seller application info for business name
$stmt = $pdo->prepare("SELECT business_name FROM seller_applications WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$seller_id]);
$application = $stmt->fetch();
$business_name = $application ? $application['business_name'] : ($seller['first_name'] . ' ' . $seller['last_name']);
?>

<style>
    :root {
        --primary: #ff6b35;
        --primary-dark: #f7931e;
        --secondary: #64748b;
        --success: #27ae60;
        --warning: #f39c12;
        --danger: #e74c3c;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #2d3436;
        --text-primary: #2d3436;
        --text-secondary: #636e72;
        --border: #e2e8f0;
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --border-radius: 8px;
    }

    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 280px;
        background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 2rem 0;
        z-index: 1000;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s ease;
    }

    .sidebar-brand {
        padding: 0 2rem 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 2rem;
        text-align: center;
    }

    .sidebar-brand img {
        max-width: 120px;
        height: auto;
        filter: brightness(1.1) contrast(1.2);
        background: rgba(255, 255, 255, 0.1);
        padding: 10px;
        border-radius: 10px;
        backdrop-filter: blur(5px);
    }

    .sidebar-nav {
        list-style: none;
        padding: 0 1rem;
    }

    .nav-item {
        margin-bottom: 0.5rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        border-radius: var(--border-radius);
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }

    .nav-link.active {
        background: linear-gradient(135deg, #22C55E 0%, #16a34a 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .nav-link i {
        margin-right: 1rem;
        font-size: 1.1rem;
        width: 20px;
    }



    /* Main Content */
    .main-content {
        margin-left: 280px;
        padding: 2rem;
        min-height: 100vh;
        transition: all 0.3s ease;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: 260px;
        }

        .main-content {
            margin-left: 0;
            padding: 1rem;
        }
    }
</style>

<!-- Modern Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/img/logo.png" alt="ORO Market Logo">
    </div>

    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i>
                <span>My Products</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" onclick="openAddProductModal()">
                <i class="bi bi-plus-circle"></i>
                <span>Add Product</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>Profile Settings</span>
            </a>
        </li>
    </ul>
</div>

<script>
    // Function to open add product modal (can be overridden by parent page)
    function openAddProductModal() {
        // Check if the modal exists on the current page
        const modal = document.getElementById('addProductModal');
        if (modal) {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        } else {
            // If modal doesn't exist, redirect to products page
            window.location.href = 'products.php';
        }
    }
</script>