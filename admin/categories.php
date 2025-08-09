<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = null;

// Create or update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($action === 'create') {
        if ($name === '') {
            $errors[] = 'Name is required';
        }
        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO categories (name, icon, description, is_active) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $icon ?: null, $description ?: null, $is_active]);
            $success = 'Category created';
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid category';
        }
        if ($name === '') {
            $errors[] = 'Name is required';
        }
        if (!$errors) {
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, icon = ?, description = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$name, $icon ?: null, $description ?: null, $is_active, $id]);
            $success = 'Category updated';
        }
    }
}

// Toggle active
if (isset($_POST['action']) && $_POST['action'] === 'toggle' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $active = (int) ($_POST['active'] ?? 0);
    $stmt = $pdo->prepare('UPDATE categories SET is_active = ? WHERE id = ?');
    $stmt->execute([$active ? 1 : 0, $id]);
    $success = 'Category status updated';
}

// Delete (prevent delete when in use)
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $inUse = (int) $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?')->execute([$id]) ?? 0;
    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $stmtCount->execute([$id]);
    $inUse = (int) $stmtCount->fetchColumn();
    if ($inUse > 0) {
        $errors[] = 'Cannot delete: category is used by products.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $success = 'Category deleted';
    }
}

// Fetch categories
$categories = [];
try {
    $stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
$total_categories = is_array($categories) ? count($categories) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, .1);
        }

        body {
            background-color: var(--light-bg);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
        }

        .card-shadow {
            box-shadow: 0 4px 10px rgba(0, 0, 0, .08)
        }

        .status-pill {
            padding: .25rem .6rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: .75rem
        }

        .status-on {
            background: #e8f7ef;
            color: #27ae60
        }

        .status-off {
            background: #fdecea;
            color: #e74c3c
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-tags me-2"></i>Categories</h1>
                    <p class="mb-0">Organize products by category</p>
                </div>
                <div class="text-end">
                    <h3 class="mb-0"><?php echo $total_categories; ?></h3>
                    <small>Total Categories</small>
                </div>
            </div>
        </div>
        <div class="mb-3 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i
                    class="bi bi-plus-lg me-1"></i>Add Category</button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>

        <div class="card card-shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Icon</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$categories): ?>
                                <tr>
                                    <td colspan="5" class="text-center p-4 text-muted">No categories found.</td>
                                </tr>
                            <?php else:
                                foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td><?php echo htmlspecialchars($cat['icon'] ?? ''); ?></td>
                                        <td class="text-muted" style="max-width:420px">
                                            <?php echo htmlspecialchars($cat['description'] ?? ''); ?>
                                        </td>
                                        <td>
                                            <span
                                                class="status-pill <?php echo $cat['is_active'] ? 'status-on' : 'status-off'; ?>"><?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                                data-bs-target="#editModal" data-id="<?php echo (int) $cat['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                                data-icon="<?php echo htmlspecialchars($cat['icon'] ?? ''); ?>"
                                                data-description="<?php echo htmlspecialchars($cat['description'] ?? ''); ?>"
                                                data-active="<?php echo (int) $cat['is_active']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="post" class="d-inline"
                                                onsubmit="return confirm('Delete this category?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $cat['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo (int) $cat['id']; ?>">
                                                <input type="hidden" name="active"
                                                    value="<?php echo $cat['is_active'] ? 0 : 1; ?>">
                                                <button
                                                    class="btn btn-sm <?php echo $cat['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                                                    <?php echo $cat['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control"
                            required></div>
                    <div class="mb-3"><label class="form-label">Icon (emoji/short text)</label><input name="icon"
                            class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description"
                            class="form-control" rows="3"></textarea></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active"
                            id="createActive" checked><label class="form-check-label" for="createActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal"
                        type="button">Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3"><label class="form-label">Name</label><input name="name" id="editName"
                            class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Icon</label><input name="icon" id="editIcon"
                            class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description"
                            id="editDescription" class="form-control" rows="3"></textarea></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active"
                            id="editActive"><label class="form-check-label" for="editActive">Active</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal"
                        type="button">Cancel</button><button class="btn btn-primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            document.getElementById('editId').value = button.getAttribute('data-id');
            document.getElementById('editName').value = button.getAttribute('data-name');
            document.getElementById('editIcon').value = button.getAttribute('data-icon') || '';
            document.getElementById('editDescription').value = button.getAttribute('data-description') || '';
            document.getElementById('editActive').checked = button.getAttribute('data-active') === '1';
        });
    </script>
</body>

</html>