<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAdmin(); // Only admin can access this module

$page_title = 'Admin Management';

try {
    $pdo = getDBConnection();
    
    // Get all admin users
    $stmt = $pdo->query("
        SELECT *, 
               (SELECT COUNT(*) FROM loans WHERE admin_id = admins.id) as total_loans
        FROM admins 
        ORDER BY role, full_name
    ");
    $admins = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load admin users: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-person-gear"></i> Admin Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Add New Admin
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Admin Users</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php elseif (empty($admins)): ?>
                        <p class="text-muted text-center">No admin users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Total Loans</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $admin['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo ucfirst($admin['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $admin['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($admin['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $admin['last_login'] ? formatDate($admin['last_login'], 'Y-m-d H:i') : 'Never'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $admin['total_loans']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit.php?id=<?php echo $admin['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                <a href="delete.php?id=<?php echo $admin['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this admin user?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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