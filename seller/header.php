<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

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
    .top-header {
        position: fixed;
        top: 0;
        right: 0;
        left: 280px;
        height: 70px;
        background: white;
        border-bottom: 1px solid #e2e8f0;
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    /* Adjust header when sidebar is collapsed */
    body.sidebar-collapsed .top-header {
        left: 80px;
    }

    .header-left {
        display: flex;
        align-items: center;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-info-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        overflow: hidden;
        border: 2px solid #ff6f33;
    }

    .user-avatar.default-avatar {
        background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-details-header {
        display: flex;
        flex-direction: column;
    }

    .user-name-header {
        font-weight: 600;
        font-size: 0.9rem;
        color: #2d3436;
        line-height: 1.2;
    }

    .user-role-header {
        font-size: 0.75rem;
        color: #636e72;
        line-height: 1.2;
    }

    .logout-btn-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #ff6b35;
        color: white;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .logout-btn-header:hover {
        background: #f7931e;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 107, 53, 0.3);
    }

    .logout-btn-header i {
        font-size: 1rem;
    }

    /* Adjust main content to account for header */
    .main-content {
        margin-top: 70px;
    }

    @media (max-width: 768px) {
        .top-header {
            left: 0;
            padding: 0 1rem;
        }

        body.sidebar-collapsed .top-header {
            left: 0;
        }
    }
</style>

<div class="top-header">
    <div class="header-left">
        <!-- Left side can be used for breadcrumbs or page title -->
    </div>

    <div class="header-right">
        <div class="user-info-header">
            <div class="user-avatar <?php echo empty($seller['profile_image']) ? 'default-avatar' : ''; ?>">
                <?php if (!empty($seller['profile_image'])): ?>
                    <img src="../<?php echo htmlspecialchars($seller['profile_image']); ?>" alt="Profile Image">
                <?php else: ?>
                    <?php echo strtoupper(substr($business_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="user-details-header">
                <div class="user-name-header"><?php echo htmlspecialchars($business_name); ?></div>
                <div class="user-role-header">Seller Account</div>
            </div>
        </div>

        <a href="../logout.php" class="logout-btn-header">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>
    </div>
</div>