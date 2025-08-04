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
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

// Get seller application info for business name
$stmt = $pdo->prepare("SELECT business_name FROM seller_applications WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$seller_id]);
$application = $stmt->fetch();
$business_name = $application ? $application['business_name'] : ($seller['first_name'] . ' ' . $seller['last_name']);

// Get total products
$query = "SELECT COUNT(*) as total FROM products WHERE seller_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$row = $stmt->fetch();
$total_products = $row['total'];

// Get total orders
$query = "SELECT COUNT(*) as total FROM orders WHERE seller_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$row = $stmt->fetch();
$total_orders = $row['total'];

// Get total revenue
$query = "SELECT SUM(total_amount) as total FROM orders WHERE seller_id = ? AND payment_status = 'paid'";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id]);
$row = $stmt->fetch();
$total_revenue = $row['total'] ? $row['total'] : 0;

// Get recent activities (last 5)
$query = "SELECT 'order' as type, order_number as title, created_at FROM orders WHERE seller_id = ? 
          UNION ALL 
          SELECT 'product' as type, name as title, created_at FROM products WHERE seller_id = ? 
          ORDER BY created_at DESC LIMIT 5";
$stmt = $pdo->prepare($query);
$stmt->execute([$seller_id, $seller_id]);
$recent_activities = $stmt->fetchAll();

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Modern Dashboard CSS -->
    <link rel="stylesheet" href="../seller/assets/css/dashboard.css">
</head>

<body>
    <!-- Modern Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="bi bi-shop"></i>ORO Market</h2>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="add_product.php" class="nav-link">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Product</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="bi bi-cart-check"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="revenue.php" class="nav-link">
                    <i class="bi bi-graph-up"></i>
                    <span>Revenue</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link">
                    <i class="bi bi-person"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: 2rem;">
                <a href="../logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <h1>Welcome back, <?php echo htmlspecialchars($business_name); ?>! 👋</h1>
                <p>Here's what's happening with your store today. Keep up the great work!</p>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon products">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($total_products); ?></div>
                            <div class="stat-label">Total Products</div>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-arrow-right me-2"></i>Manage Products
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon orders">
                                <i class="bi bi-cart"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                            <div class="stat-label">Total Orders</div>
                            <a href="orders.php" class="btn btn-success">
                                <i class="bi bi-eye me-2"></i>View Orders
                            </a>
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
                            <a href="revenue.php" class="btn btn-warning">
                                <i class="bi bi-graph-up me-2"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Recent Activity -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="quick-actions">
                        <h4><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h4>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <a href="add_product.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-plus-lg"></i>
                                    </div>
                                    <h5>Add New Product</h5>
                                    <p class="text-muted mb-0">Create a new product listing to expand your inventory</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="products.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-box"></i>
                                    </div>
                                    <h5>Manage Products</h5>
                                    <p class="text-muted mb-0">Edit, update, or remove your existing products</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="orders.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-cart-check"></i>
                                    </div>
                                    <h5>Process Orders</h5>
                                    <p class="text-muted mb-0">Review and fulfill customer orders efficiently</p>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="profile.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-person-gear"></i>
                                    </div>
                                    <h5>Update Profile</h5>
                                    <p class="text-muted mb-0">Manage your shop information and settings</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="recent-activity">
                        <h4><i class="bi bi-clock-history me-2"></i>Recent Activity</h4>
                        
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.5;"></i>
                                <p class="text-muted mt-2">No recent activity</p>
                                <p class="text-muted small">Start by adding some products!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="activity-icon" style="background: <?php echo $activity['type'] == 'order' ? 'var(--success)' : 'var(--primary)'; ?>;">
                                            <i class="bi bi-<?php echo $activity['type'] == 'order' ? 'cart-check' : 'box-seam'; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">
                                                <?php if ($activity['type'] == 'order'): ?>
                                                    New Order: <?php echo htmlspecialchars($activity['title']); ?>
                                                <?php else: ?>
                                                    Product Added: <?php echo htmlspecialchars($activity['title']); ?>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="text-muted mb-0">
                                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Tips -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="quick-actions">
                        <h4><i class="bi bi-lightbulb me-2"></i>Tips to Boost Your Sales</h4>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="action-btn" style="cursor: default; border-color: var(--success);">
                                    <div class="action-icon" style="background: var(--success);">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                    <h5>High-Quality Photos</h5>
                                    <p class="text-muted mb-0">Use clear, well-lit images to showcase your products effectively</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="action-btn" style="cursor: default; border-color: var(--info);">
                                    <div class="action-icon" style="background: var(--info);">
                                        <i class="bi bi-star"></i>
                                    </div>
                                    <h5>Detailed Descriptions</h5>
                                    <p class="text-muted mb-0">Write comprehensive product descriptions to help customers decide</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="action-btn" style="cursor: default; border-color: var(--warning);">
                                    <div class="action-icon" style="background: var(--warning);">
                                        <i class="bi bi-lightning"></i>
                                    </div>
                                    <h5>Quick Response</h5>
                                    <p class="text-muted mb-0">Respond to orders and inquiries promptly to build trust</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate counter numbers
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.innerText.replace(/[^\d]/g, ''));
                const increment = target / 100;
                let current = 0;
                
                if (target > 0) {
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        
                        if (counter.innerText.includes('₱')) {
                            counter.innerText = '₱' + Math.floor(current).toLocaleString() + (target > 99 ? '.00' : '');
                        } else {
                            counter.innerText = Math.floor(current).toLocaleString();
                        }
                    }, 20);
                }
            });

            // Add hover effects to dashboard cards
            const dashboardCards = document.querySelectorAll('.dashboard-card');
            dashboardCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add click animation to action buttons
            const actionBtns = document.querySelectorAll('.action-btn[href]');
            actionBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(99, 102, 241, 0.3);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        if (ripple.parentNode) {
                            ripple.remove();
                        }
                    }, 600);
                });
            });

            // Add notification system (if you want to show success messages)
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    box-shadow: var(--shadow-lg);
                    border: none;
                    border-radius: var(--border-radius);
                `;
                notification.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }
            
            // Add loading states to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.href && !this.href.includes('#')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="loading"></span> Loading...';
                        this.disabled = true;
                        
                        // Re-enable after a short delay if navigation doesn't happen
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }, 3000);
                    }
                });
            });

            // Add smooth scrolling for better UX
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });

        // Add CSS animation for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .alert {
                animation: slideInRight 0.3s ease-out;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            /* Loading spinner animation */
            .loading {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>