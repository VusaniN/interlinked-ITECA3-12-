<?php
$pageTitle = 'Manage Categories — Interlinked Admin';
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn() || !hasAnyRole(['admin'])) {
    redirect('admin/index.php');
}

$success = '';
$error   = '';

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $name      = trim($_POST['name'] ?? '');
        $slug      = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($name)));
        $parentId  = intval($_POST['parent_id'] ?? 0) ?: null;
        $sortOrder = intval($_POST['sort_order'] ?? 0);

        if (!$name) {
            $error = 'Category name is required.';
        } else {
            $stmt = $con->prepare("INSERT INTO categories (name, slug, parent_id, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $name, $slug, $parentId, $sortOrder);
            if ($stmt->execute()) {
                $success = "Category \"$name\" created successfully.";
            } else {
                $error = 'Failed to create category. Slug may already exist.';
            }
        }
    }
}

// Handle toggle active / delete via GET
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'toggle') {
        $stmt = $con->prepare("UPDATE categories SET is_active = NOT is_active WHERE category_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = 'Category status updated.';
    } elseif ($action === 'delete') {
        // Check if category has products
        $cStmt = $con->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $cStmt->bind_param("i", $id);
        $cStmt->execute();
        $count = $cStmt->get_result()->fetch_row()[0];
        if ($count > 0) {
            $error = "Cannot delete: $count products use this category. Reassign them first.";
        } else {
            $stmt = $con->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = 'Category deleted.';
            } else {
                $error = 'Failed to delete category.';
            }
        }
    }
}

// Fetch all categories
$categories = mysqli_query($con, "SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.category_id ORDER BY c.sort_order, c.name");
?>
<?php require_once dirname(__FILE__) . '/includes/admin_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h4 class="fw-800 mb-0" style="font-family:'Sora',sans-serif">🏷 Categories</h4>
    <div class="small">Organize your marketplace taxonomy.</div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.2); color:#10b981">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" style="background:rgba(244, 63, 94, 0.1); border:1px solid rgba(244, 63, 94, 0.2); color:#f43f5e">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h6 class="fw-700 mb-4" style="color:var(--accent)">Add New Category</h6>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label small fw-700">CATEGORY NAME</label>
                    <input type="text" class="form-control" name="name" placeholder="e.g. Electronics" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-700">PARENT CATEGORY</label>
                    <select class="form-select" name="parent_id">
                        <option value="0">None (Top Level)</option>
                        <?php
                        $topCats = mysqli_query($con, "SELECT category_id, name FROM categories WHERE parent_id IS NULL AND is_active=1 ORDER BY sort_order");
                        while ($tc = mysqli_fetch_assoc($topCats)):
                        ?>
                        <option value="<?= $tc['category_id'] ?>"><?= htmlspecialchars($tc['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-700">SORT ORDER</label>
                    <input type="number" class="form-control" name="sort_order" value="0" min="0">
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Category</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Slug</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td class="ps-4 fw-700"><?= htmlspecialchars($cat['name']) ?></td>
                            <td class="small"><?= htmlspecialchars($cat['slug']) ?></td>
                            <td class="small"><?= htmlspecialchars($cat['parent_name'] ?? '—') ?></td>
                            <td>
                                <?php if ($cat['is_active']): ?>
                                    <span class="status-badge" style="background:rgba(16,185,129,0.1);color:#10b981">Active</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background:rgba(244,63,94,0.1);color:#f43f5e">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="?action=toggle&id=<?= $cat['category_id'] ?>" class="btn btn-sm btn-outline-warning py-1 px-2" style="font-size:.7rem">
                                    <?= $cat['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <a href="?action=delete&id=<?= $cat['category_id'] ?>" class="btn btn-sm btn-link text-danger p-0 ms-2"
                                   onclick="return confirm('Delete this category?')">
                                    <i data-feather="trash-2" style="width:14px"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($categories) == 0): ?>
                        <tr><td colspan="5" class="text-center py-4">No categories found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/includes/admin_footer.php'; ?>
