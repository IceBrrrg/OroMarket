<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../authenticator.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $is_active = (int)($_POST['is_active'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $id]);
            $success = 'Announcement status updated successfully!';
        } catch (Exception $e) {
            $error = 'Error updating announcement status.';
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Announcement deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting announcement.';
        }
    }
    
    if ($action === 'toggle_pin') {
        $id = (int)($_POST['id'] ?? 0);
        $is_pinned = (int)($_POST['is_pinned'] ?? 0);
        
        try {
            $pdo->beginTransaction();
            
            // If pinning this announcement, unpin all others
            if ($is_pinned) {
                $stmt = $pdo->prepare("UPDATE announcements SET is_pinned = 0");
                $stmt->execute();
            }
            
            // Update the selected announcement
            $stmt = $pdo->prepare("UPDATE announcements SET is_pinned = ? WHERE id = ?");
            $stmt->execute([$is_pinned, $id]);
            
            $pdo->commit();
            $success = $is_pinned ? 'Announcement pinned successfully!' : 'Announcement unpinned successfully!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error updating pin status.';
        }
    }
}

// Fetch announcements with admin details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, ad.username as created_by_name 
        FROM announcements a 
        LEFT JOIN admins ad ON a.created_by = ad.id 
        ORDER BY a.is_pinned DESC, a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $announcements = [];
    $error = 'Error fetching announcements.';
}

// Get statistics
$total_announcements = count($announcements);
$active_announcements = count(array_filter($announcements, function($a) { return $a['is_active']; }));
$pinned_announcements = count(array_filter($announcements, function($a) { return $a['is_pinned']; }));
$expired_announcements = count(array_filter($announcements, function($a) { 
    return $a['expiry_date'] && strtotime($a['expiry_date']) < time(); 
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Oroquieta Marketplace</title>
    <link href="../assets/img/logo-removebg.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, .1);
            --border-radius: 8px;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .stats-cards {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.total { border-left-color: var(--primary-color); }
        .stat-card.active { border-left-color: var(--success-color); }
        .stat-card.pinned { border-left-color: var(--warning-color); }
        .stat-card.expired { border-left-color: var(--danger-color); }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .announcements-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .announcement-card {
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
            transition: background-color 0.2s ease;
        }

        .announcement-card:hover {
            background-color: #f8f9fa;
        }

        .announcement-card:last-child {
            border-bottom: none;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .announcement-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .announcement-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .priority-low { background-color: #d1ecf1; color: #0c5460; }
        .priority-medium { background-color: #fff3cd; color: #856404; }
        .priority-high { background-color: #f8d7da; color: #721c24; }
        .priority-urgent { background-color: #721c24; color: white; }

        .audience-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            background-color: var(--secondary-color);
            color: white;
        }

        .announcement-content {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .announcement-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .pin-indicator {
            color: var(--warning-color);
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .expired-indicator {
            color: var(--danger-color);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .announcement-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .announcement-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-megaphone me-2"></i>Announcements</h1>
                    <p class="mb-0 opacity-75">Manage marketplace announcements and notifications</p>
                </div>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#announcementModal">
                    <i class="bi bi-plus-circle me-2"></i>Create Announcement
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card total">
                        <div class="stat-number text-primary"><?php echo $total_announcements; ?></div>
                        <div class="stat-label">Total Announcements</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card active">
                        <div class="stat-number text-success"><?php echo $active_announcements; ?></div>
                        <div class="stat-label">Active Announcements</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card pinned">
                        <div class="stat-number text-warning"><?php echo $pinned_announcements; ?></div>
                        <div class="stat-label">Pinned Announcements</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card expired">
                        <div class="stat-number text-danger"><?php echo $expired_announcements; ?></div>
                        <div class="stat-label">Expired Announcements</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements List -->
        <div class="announcements-container">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="bi bi-megaphone"></i>
                    <h4>No Announcements Yet</h4>
                    <p>Create your first announcement to communicate with marketplace users.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#announcementModal">
                        <i class="bi bi-plus-circle me-2"></i>Create First Announcement
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <?php
                    $isExpired = $announcement['expiry_date'] && strtotime($announcement['expiry_date']) < time();
                    $priorityClass = 'priority-' . $announcement['priority'];
                    ?>
                    <div class="announcement-card <?php echo !$announcement['is_active'] ? 'opacity-50' : ''; ?>">
                        <div class="announcement-header">
                            <div class="flex-grow-1">
                                <div class="announcement-title">
                                    <?php if ($announcement['is_pinned']): ?>
                                        <i class="bi bi-pin-fill pin-indicator"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                    <?php if ($isExpired): ?>
                                        <span class="expired-indicator ms-2">(EXPIRED)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="announcement-meta">
                                    <span><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Unknown'); ?></span>
                                    <span><i class="bi bi-calendar me-1"></i><?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?></span>
                                    <?php if ($announcement['expiry_date']): ?>
                                        <span><i class="bi bi-clock me-1"></i>Expires: <?php echo date('M j, Y', strtotime($announcement['expiry_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-2">
                                    <span class="priority-badge <?php echo $priorityClass; ?>">
                                        <?php echo ucfirst($announcement['priority']); ?> Priority
                                    </span>
                                    <span class="audience-badge ms-2">
                                        <?php echo ucfirst($announcement['target_audience']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                        
                        <div class="announcement-actions">
                            <!-- Toggle Active Status -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $announcement['is_active'] ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $announcement['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <i class="bi bi-<?php echo $announcement['is_active'] ? 'pause' : 'play'; ?>-fill me-1"></i>
                                    <?php echo $announcement['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            
                            <!-- Toggle Pin Status -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle_pin">
                                <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                <input type="hidden" name="is_pinned" value="<?php echo $announcement['is_pinned'] ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $announcement['is_pinned'] ? 'btn-secondary' : 'btn-outline-warning'; ?>">
                                    <i class="bi bi-pin<?php echo $announcement['is_pinned'] ? '-fill' : ''; ?> me-1"></i>
                                    <?php echo $announcement['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                </button>
                            </form>
                            
                            <!-- Delete -->
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Announcement Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="announcementModalLabel">
                        <i class="bi bi-megaphone-fill me-2"></i>Create New Announcement
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="announcementForm" action="create_announcement.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="announcementTitle" class="form-label fw-bold">
                                        <i class="bi bi-type text-primary me-1"></i>Announcement Title
                                    </label>
                                    <input type="text" class="form-control" id="announcementTitle" name="title" 
                                           placeholder="Enter announcement title..." maxlength="200" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="announcementContent" class="form-label fw-bold">
                                        <i class="bi bi-text-paragraph text-primary me-1"></i>Content
                                    </label>
                                    <textarea class="form-control" id="announcementContent" name="content" rows="6" 
                                              placeholder="Enter announcement content..." maxlength="2000" required></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="announcementPriority" class="form-label fw-bold">
                                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>Priority
                                    </label>
                                    <select class="form-select" id="announcementPriority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="announcementTarget" class="form-label fw-bold">
                                        <i class="bi bi-people text-info me-1"></i>Target Audience
                                    </label>
                                    <select class="form-select" id="announcementTarget" name="target_audience">
                                        <option value="all" selected>All Users</option>
                                        <option value="sellers">Sellers Only</option>
                                        <option value="customers">Customers Only</option>
                                        <option value="admins">Admins Only</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="expiryDate" class="form-label fw-bold">
                                        <i class="bi bi-calendar-event text-secondary me-1"></i>Expiry Date (Optional)
                                    </label>
                                    <input type="datetime-local" class="form-control" id="expiryDate" name="expiry_date">
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sendEmail" name="send_email" value="1">
                                    <label class="form-check-label fw-bold" for="sendEmail">
                                        <i class="bi bi-envelope text-primary me-1"></i>Send Email Notification
                                    </label>
                                    <div class="form-text">Send this announcement via email to the target audience</div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="pinAnnouncement" name="pin_announcement" value="1">
                                    <label class="form-check-label fw-bold" for="pinAnnouncement">
                                        <i class="bi bi-pin text-warning me-1"></i>Pin Announcement
                                    </label>
                                    <div class="form-text">Pin this announcement to the top (unpins other pinned announcements)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-megaphone me-1"></i>Create Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and character counters
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('announcementTitle');
            const contentInput = document.getElementById('announcementContent');
            
            // Add character counters
            addCharacterCounter(titleInput, 200);
            addCharacterCounter(contentInput, 2000);
            
            // Form submission handling
            const form = document.getElementById('announcementForm');
            form.addEventListener('submit', function(e) {
                const title = titleInput.value.trim();
                const content = contentInput.value.trim();
                
                if (!title || !content) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return;
                }
                
                if (title.length > 200) {
                    e.preventDefault();
                    alert('Title must not exceed 200 characters.');
                    return;
                }
                
                if (content.length > 2000) {
                    e.preventDefault();
                    alert('Content must not exceed 2000 characters.');
                    return;
                }
            });
        });
        
        function addCharacterCounter(input, maxLength) {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.style.marginTop = '5px';
            input.parentNode.appendChild(counter);
            
            function updateCounter() {
                const remaining = maxLength - input.value.length;
                counter.textContent = `${input.value.length}/${maxLength} characters`;
                counter.className = remaining < 50 ? 'form-text text-end text-warning' : 'form-text text-end text-muted';
            }
            
            input.addEventListener('input', updateCounter);
            updateCounter();
        }
    </script>
</body>
</html>
