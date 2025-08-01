<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Get total sellers
$query = "SELECT COUNT(*) as total FROM sellers";
$stmt = $pdo->query($query);
$row = $stmt->fetch();
$total_sellers = $row['total'];

// Get total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $pdo->query($query);
$row = $stmt->fetch();
$total_products = $row['total'];


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ORO Market</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
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
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            overflow: hidden;
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

        .card-icon.sellers {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        }

        .card-icon.products {
            background: linear-gradient(45deg, var(--success-color), #157347);
        }

        .card-icon.orders {
            background: linear-gradient(45deg, var(--warning-color), #ffca2c);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .action-btn {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .action-btn:hover {
            background-color: var(--light-bg);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .welcome-header {
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
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="welcome-header">
                <h1 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p class="mb-0">Here's what's happening with your marketplace today.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon sellers">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_sellers; ?></div>
                            <div class="stat-label">Total Sellers</div>
                            <a href="manage_sellers.php" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_products; ?></div>
                            <div class="stat-label">Total Products</div>
                            <a href="manage_products.php" class="btn btn-success">View Details</a>
                        </div>
                    </div>
                </div>

            </div>


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>