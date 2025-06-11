<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Categories Management';

try {
    $pdo = getDBConnection();
    
    // Get categories with book count
    $stmt = $pdo->query("
        SELECT c.*, COUNT(b.id) as book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load categories: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-tags"></i> Categories Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Category
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Categories List</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php elseif (empty($categories)): ?>
                        <p class="text-muted text-center">No categories found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Books Count</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $category['book_count']; ?> books
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($category['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit.php?id=<?php echo $category['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($category['book_count'] == 0): ?>
                                                <a href="delete.php?id=<?php echo $category['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this category?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                <button class="btn btn-outline-secondary" disabled title="Cannot delete - has books">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>