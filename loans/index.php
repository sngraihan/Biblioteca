<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Loans Management';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with search and filter
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(l.loan_code LIKE ? OR b.title LIKE ? OR m.name LIKE ? OR m.member_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "l.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $query = "
        SELECT l.*, b.title as book_title, b.author as book_author, 
               m.name as member_name, m.member_code,
               a.full_name as admin_name,
               CASE 
                   WHEN l.status = 'active' AND l.due_date < CURDATE() THEN 'overdue'
                   ELSE l.status 
               END as display_status,
               DATEDIFF(CURDATE(), l.due_date) as days_overdue
        FROM loans l 
        JOIN books b ON l.book_id = b.id 
        JOIN members m ON l.member_id = m.id 
        JOIN admins a ON l.admin_id = a.id 
        $where_clause 
        ORDER BY l.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $loans = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load loans: ' . $e->getMessage();
}

// Handle success/error messages
$message = '';
$message_type = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'loan_deleted':
            $message = 'Loan record deleted permanently!';
            $message_type = 'success';
            break;
        case 'loan_cancelled':
            $message = 'Loan cancelled successfully!';
            $message_type = 'success';
            break;
        case 'book_returned':
            $message = 'Book returned successfully!';
            $message_type = 'success';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'loan_not_found':
            $message = 'Loan record not found!';
            $message_type = 'danger';
            break;
        case 'delete_failed':
            $message = 'Failed to delete loan record!';
            $message_type = 'danger';
            break;
        case 'cancel_failed':
            $message = 'Failed to cancel loan!';
            $message_type = 'danger';
            break;
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-arrow-left-right"></i> Loans Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Loan
                </a>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <label for="search" class="form-label">Search Loans</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by loan code, book title, or member name" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                                <option value="overdue" <?php echo $status_filter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loans List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Loans List (<?php echo count($loans); ?> loans found)</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <?php echo showAlert($message, $message_type); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php elseif (empty($loans)): ?>
                        <p class="text-muted text-center">No loans found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Loan Code</th>
                                        <th>Book</th>
                                        <th>Member</th>
                                        <th>Loan Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                    <tr class="<?php echo $loan['display_status'] == 'overdue' ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($loan['loan_code']); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($loan['book_title']); ?></strong>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($loan['book_author']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($loan['member_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($loan['member_code']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($loan['loan_date']); ?></td>
                                        <td>
                                            <?php echo formatDate($loan['due_date']); ?>
                                            <?php if ($loan['display_status'] == 'overdue'): ?>
                                                <br><small class="text-danger">
                                                    <?php echo $loan['days_overdue']; ?> days overdue
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $loan['return_date'] ? formatDate($loan['return_date']) : '-'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $loan['display_status'] == 'active' ? 'primary' : 
                                                    ($loan['display_status'] == 'returned' ? 'success' : 
                                                    ($loan['display_status'] == 'overdue' ? 'danger' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst($loan['display_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($loan['status'] == 'active'): ?>
                                                <a href="return.php?id=<?php echo $loan['id']; ?>" 
                                                   class="btn btn-outline-success" title="Return Book">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                <a href="cancel.php?id=<?php echo $loan['id']; ?>" 
                                                   class="btn btn-outline-warning" title="Cancel Loan"
                                                   onclick="return confirm('Are you sure you want to cancel this loan? The book will be returned to available status.')">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="edit.php?id=<?php echo $loan['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $loan['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Delete Permanently"
                                                   onclick="return confirm('Are you sure you want to DELETE this loan record permanently? This action cannot be undone!')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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
