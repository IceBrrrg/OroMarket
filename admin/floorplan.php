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

// Database connection (adjust these credentials as needed)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'oroquieta_marketplace';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get stall statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
    SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
    FROM stalls";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get all stalls with seller information
$stalls_query = "SELECT s.*, 
    se.first_name, se.last_name, se.email, se.phone,
    sa.business_name, sa.business_phone
    FROM stalls s
    LEFT JOIN sellers se ON s.current_seller_id = se.id
    LEFT JOIN seller_applications sa ON se.id = sa.seller_id
    ORDER BY s.stall_number";
$stalls = $pdo->query($stalls_query)->fetchAll(PDO::FETCH_ASSOC);

// Create stalls array indexed by stall_number for easy lookup
$stalls_data = [];
foreach ($stalls as $stall) {
    $stalls_data[$stall['stall_number']] = $stall;
}

// Handle AJAX request for stall details
if (isset($_GET['ajax']) && $_GET['ajax'] == 'stall_details' && isset($_GET['stall'])) {
    $stall_number = $_GET['stall'];
    $stall = $stalls_data[$stall_number] ?? null;
    
    if ($stall) {
        header('Content-Type: application/json');
        echo json_encode($stall);
        exit;
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Floorplan - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/floorplan.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
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
            background-color: #0d6efd !important;
        }

        .sidebar-footer {
            margin-top: auto;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar-header img {
            max-width: 100%;
            height: auto;
            margin-left: 50px;
        }

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

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Color-coded stat cards to match legend */
        .stat-card.total {
            border-left: 5px solid #0d6efd;
        }

        .stat-card.available {
            border-left: 5px solid #198754;
        }

        .stat-card.occupied {
            border-left: 5px solid #dc3545;
        }

        .stat-card.reserved {
            border-left: 5px solid #ffc107;
        }

        .stat-card.maintenance {
            border-left: 5px solid #6c757d;
        }

        /* Add click functionality to existing stalls */
        .stall {
            cursor: pointer;
        }

        .stall:hover {
            transform: scale(1.1);
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        /* Status-based styling for existing stall classes */
        .stall.occupied {
            background-color: #dc3545 !important;
            border-color: #c82333 !important;
        }

        .stall.reserved {
            background-color: #ffc107 !important;
            border-color: #e0a800 !important;
            color: #212529 !important;
        }

        .stall.maintenance {
            background-color: #6c757d !important;
            border-color: #545b62 !important;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar bg-dark text-white">
        <div class="sidebar-header p-3 border-bottom border-secondary">
            <a href="dashboard.php">
                <img src="../assets/img/logo-removebg.png" alt="logo" width="100px">
            </a>
        </div>
        <ul class="nav flex-column p-3">
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : 'text-white'; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" id="sellerDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-people me-2"></i> Sellers
                    </a>
                    <ul class="dropdown-menu bg-dark" aria-labelledby="sellerDropdown">
                        <li>
                            <a class="dropdown-item text-white <?php echo $current_page == 'seller_applications.php' ? 'active' : ''; ?>" href="seller_applications.php">
                                <i class="bi bi-file-earmark-text me-2"></i> Applications
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-white <?php echo $current_page == 'floorplan.php' ? 'active' : ''; ?>" href="floorplan.php">
                                <i class="bi bi-grid me-2"></i> Floorplan
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-white <?php echo $current_page == 'manage_sellers.php' ? 'active' : ''; ?>" href="manage_sellers.php">
                                <i class="bi bi-gear me-2"></i> Manage Sellers
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_page == 'manage_products.php' ? 'active' : 'text-white'; ?>" href="manage_products.php">
                    <i class="bi bi-box-seam me-2"></i> Manage Products
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_page == 'signup.php' ? 'active' : 'text-white'; ?>" href="signup.php">
                    <i class="bi bi-person-plus me-2"></i> Add New Admin
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : 'text-white'; ?>" href="settings.php">
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
                    <small class="text-muted">Administrator</small>
                </div>
            </div>
            <a href="../logout.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4"><i class="bi bi-grid me-2"></i>Market Floorplan</h2>
                    
                    <!-- Market Floorplan -->
                    <div class="market-container">
                        <!-- Top row stalls -->
                        <?php for ($i = 1; $i <= 11; $i++): 
                            $stall_num = "T$i";
                            $stall_data = $stalls_data[$stall_num] ?? null;
                            $status_class = $stall_data ? $stall_data['status'] : 'available';
                        ?>
                            <button type="button" 
                                class="stall square-stall top-<?php echo $i; ?> <?php echo $status_class; ?>"
                                data-stall="<?php echo $stall_num; ?>"
                                onclick="showStallDetails('<?php echo $stall_num; ?>')">
                                <?php echo $stall_num; ?>
                            </button>
                        <?php endfor; ?>

                        <!-- Bottom row stalls -->
                        <?php for ($i = 1; $i <= 11; $i++): 
                            $stall_num = "B$i";
                            $stall_data = $stalls_data[$stall_num] ?? null;
                            $status_class = $stall_data ? $stall_data['status'] : 'available';
                        ?>
                            <button type="button" 
                                class="stall square-stall bottom-<?php echo $i; ?> <?php echo $status_class; ?>"
                                data-stall="<?php echo $stall_num; ?>"
                                onclick="showStallDetails('<?php echo $stall_num; ?>')">
                                <?php echo $stall_num; ?>
                            </button>
                        <?php endfor; ?>

                        <!-- Left column stalls -->
                        <?php for ($i = 1; $i <= 6; $i++): 
                            $stall_num = "L$i";
                            $stall_data = $stalls_data[$stall_num] ?? null;
                            $status_class = $stall_data ? $stall_data['status'] : 'available';
                        ?>
                            <button type="button" 
                                class="stall square-stall left-<?php echo $i; ?> <?php echo $status_class; ?>"
                                data-stall="<?php echo $stall_num; ?>"
                                onclick="showStallDetails('<?php echo $stall_num; ?>')">
                                <?php echo $stall_num; ?>
                            </button>
                        <?php endfor; ?>

                        <!-- Right column stalls -->
                        <?php for ($i = 1; $i <= 6; $i++): 
                            $stall_num = "R$i";
                            $stall_data = $stalls_data[$stall_num] ?? null;
                            $status_class = $stall_data ? $stall_data['status'] : 'available';
                        ?>
                            <button type="button" 
                                class="stall square-stall right-<?php echo $i; ?> <?php echo $status_class; ?>"
                                data-stall="<?php echo $stall_num; ?>"
                                onclick="showStallDetails('<?php echo $stall_num; ?>')">
                                <?php echo $stall_num; ?>
                            </button>
                        <?php endfor; ?>

                        <!-- Fish Vendors (Left Section) - F1 to F16 -->
                        <?php for ($i = 1; $i <= 16; $i++): 
                            $stall_num = "F$i";
                            $stall_data = $stalls_data[$stall_num] ?? null;
                            $status_class = $stall_data ? $stall_data['status'] : 'available';
                        ?>
                            <button type="button" 
                                class="stall fish-vendor fish-<?php echo $i; ?> <?php echo $status_class; ?>"
                                data-stall="<?php echo $stall_num; ?>"
                                onclick="showStallDetails('<?php echo $stall_num; ?>')">
                                <?php echo $stall_num; ?>
                            </button>
                        <?php endfor; ?>

                        <!-- Meat Vendors (Right Section) - M1 to M16 -->
                        <?php for ($i = 1; $i <= 16; $i++): 
                            $stall_num = "M$i";
                            $stall_data = $stalls_data[$stall_num] ?? null;
                            $status_class = $stall_data ? $stall_data['status'] : 'available';
                        ?>
                            <button type="button" 
                                class="stall meat-vendor meat-<?php echo $i; ?> <?php echo $status_class; ?>"
                                data-stall="<?php echo $stall_num; ?>"
                                onclick="showStallDetails('<?php echo $stall_num; ?>')">
                                <?php echo $stall_num; ?>
                            </button>
                        <?php endfor; ?>

                        <!-- Center Circle -->
                        <div class="center-circle">
                            Market<br>Center
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color available"></div>
                            <span>General Stalls</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #20b2aa; border-color: #1a9a91;"></div>
                            <span>Fish Vendors</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #dc3545; border-color: #c82333;"></div>
                            <span>Meat Vendors</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #dc3545; border-color: #c82333;"></div>
                            <span>Occupied</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #ffc107; border-color: #e0a800;"></div>
                            <span>Reserved</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #6c757d; border-color: #545b62;"></div>
                            <span>Maintenance</span>
                        </div>
                    </div>

                    <!-- Statistics Cards (Moved Below Floorplan) -->
                    <div class="stats-cards">
                        <div class="stat-card total">
                            <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                            <div class="text-muted">Total Stalls</div>
                        </div>
                        <div class="stat-card available">
                            <div class="stat-number text-success"><?php echo $stats['available']; ?></div>
                            <div class="text-muted">Available</div>
                        </div>
                        <div class="stat-card occupied">
                            <div class="stat-number text-danger"><?php echo $stats['occupied']; ?></div>
                            <div class="text-muted">Occupied</div>
                        </div>
                        <div class="stat-card reserved">
                            <div class="stat-number text-warning"><?php echo $stats['reserved']; ?></div>
                            <div class="text-muted">Reserved</div>
                        </div>
                        <div class="stat-card maintenance">
                            <div class="stat-number text-secondary"><?php echo $stats['maintenance']; ?></div>
                            <div class="text-muted">Maintenance</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stall Details Modal -->
    <div class="modal fade" id="stallDetailsModal" tabindex="-1" aria-labelledby="stallDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stallDetailsModalLabel">Stall Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="stallDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showStallDetails(stallNumber) {
            // Show loading state
            document.getElementById('stallDetailsContent').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('stallDetailsModal'));
            modal.show();
            
            // Fetch stall details
            fetch(`?ajax=stall_details&stall=${stallNumber}`)
                .then(response => response.json())
                .then(data => {
                    let content = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-info-circle me-2"></i>Stall Information</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Stall Number:</strong></td><td>${data.stall_number}</td></tr>
                                    <tr><td><strong>Section:</strong></td><td>${data.section}</td></tr>
                                    <tr><td><strong>Size:</strong></td><td>${data.size} sq.m</td></tr>
                                    <tr><td><strong>Monthly Rent:</strong></td><td>₱${parseFloat(data.monthly_rent).toLocaleString()}</td></tr>
                                    <tr><td><strong>Status:</strong></td><td><span class="badge bg-${getStatusColor(data.status)}">${data.status.toUpperCase()}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-person me-2"></i>Occupant Information</h6>
                    `;
                    
                    if (data.current_seller_id && data.first_name) {
                        content += `
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td>${data.first_name} ${data.last_name || ''}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${data.email || 'N/A'}</td></tr>
                                <tr><td><strong>Phone:</strong></td><td>${data.phone || 'N/A'}</td></tr>
                                <tr><td><strong>Business Name:</strong></td><td>${data.business_name || 'N/A'}</td></tr>
                                <tr><td><strong>Business Phone:</strong></td><td>${data.business_phone || 'N/A'}</td></tr>
                            </table>
                        `;
                    } else {
                        content += `
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                This stall is currently ${data.status}. No occupant information available.
                            </div>
                        `;
                    }
                    
                    content += `
                            </div>
                        </div>
                    `;
                    
                    if (data.description) {
                        content += `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6><i class="bi bi-card-text me-2"></i>Description</h6>
                                    <p class="text-muted">${data.description}</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    document.getElementById('stallDetailsContent').innerHTML = content;
                    document.getElementById('stallDetailsModalLabel').innerHTML = `<i class="bi bi-building me-2"></i>Stall ${stallNumber} Details`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('stallDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading stall details.</div>';
                });
        }
        
        function getStatusColor(status) {
            switch(status) {
                case 'available': return 'success';
                case 'occupied': return 'danger';
                case 'reserved': return 'warning';
                case 'maintenance': return 'secondary';
                default: return 'primary';
            }
        }
        
        // Add hover effect for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const stalls = document.querySelectorAll('.stall');
            stalls.forEach(stall => {
                stall.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.zIndex = '10';
                });
                
                stall.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.zIndex = '1';
                });
            });
        });
    </script>
</body>
</html>