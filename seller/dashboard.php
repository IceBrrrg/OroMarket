<?php
session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || $_SESSION['is_seller'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Get seller information
$seller_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM sellers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$seller = mysqli_fetch_assoc($result);

// Get total products
$query = "SELECT COUNT(*) as total FROM products WHERE seller_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_products = $row['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .card-icon.products {
            background: linear-gradient(45deg, var(--primary), #0a58ca);
        }

        .card-icon.orders {
            background: linear-gradient(45deg, var(--success), #157347);
        }

        .card-icon.revenue {
            background: linear-gradient(45deg, var(--warning), #ffca2c);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--secondary);
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .action-btn {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.1);
            height: 100%;
        }

        .action-btn:hover {
            background-color: var(--light);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .welcome-header {
            background: linear-gradient(45deg, var(--primary), #0a58ca);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .recent-activity {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .activity-item {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item:hover {
            background-color: var(--light);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            margin-right: 15px;
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
            <div class="welcome-header">
                <h1 class="mb-1">Welcome, <?php echo htmlspecialchars($seller['business_name']); ?>!</h1>
                <p class="mb-0">Here's your shop's performance overview.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_products; ?></div>
                            <div class="stat-label">Total Products</div>
                            <a href="products.php" class="btn btn-primary">Manage Products</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon orders">
                                <i class="bi bi-cart"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                            <a href="orders.php" class="btn btn-success">View Orders</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon revenue">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stat-number">₱<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                            <a href="revenue.php" class="btn btn-warning">View Details</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="quick-actions">
                        <h4 class="mb-4">Quick Actions</h4>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <a href="add_product.php" class="action-btn d-block">
                                    <div class="action-icon">
                                        <i class="bi bi-plus-lg"></i>
                                    </div>
                                    <h5>Add New Product</h5>
                                    <p class="text-muted mb-0">Create a new product listing</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="products.php" class="action-btn d-block">
                                    <div class="action-icon">
                                        <i class="bi bi-box"></i>
                                    </div>
                                    <h5>Manage Products</h5>
                                    <p class="text-muted mb-0">Edit or delete your products</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="orders.php" class="action-btn d-block">
                                    <div class="action-icon">
                                        <i class="bi bi-cart-check"></i>
                                    </div>
                                    <h5>View Orders</h5>
                                    <p class="text-muted mb-0">Check and process orders</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="profile.php" class="action-btn d-block">
                                    <div class="action-icon">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <h5>Edit Profile</h5>
                                    <p class="text-muted mb-0">Update your shop information</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="recent-activity">
                        <h4 class="mb-4">Recent Activity</h4>
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon" style="background: var(--primary);">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">New Product Added</h6>
                                    <p class="text-muted mb-0">2 hours ago</p>
                                </div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon" style="background: var(--success);">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">New Order Received</h6>
                                    <p class="text-muted mb-0">5 hours ago</p>
                                </div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon" style="background: var(--warning);">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Payment Received</h6>
                                    <p class="text-muted mb-0">1 day ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>