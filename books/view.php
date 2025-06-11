<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Book Details';
$book_id = (int)($_GET['id'] ?? 0);

if ($book_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get book details
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header('Location: index.php?error=book_not_found');
        exit();
    }
    
    // Get loan history
    $stmt = $pdo->prepare("
        SELECT l.*, m.name as member_name, m.member_code, a.full_name as admin_name
        FROM loans l 
        JOIN members m ON l.member_id = m.id 
        JOIN admins a ON l.admin_id = a.id 
        WHERE l.book_id = ? 
        ORDER BY l.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$book_id]);
    $loan_history = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load book details';
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-book"></i> Book Details</h1>
                <div>
                    <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Book
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Books
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <?php echo showAlert($error, 'danger'); ?>
    <?php else: ?>
    
    <!-- Book Information -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Book Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($book['title']); ?></p>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                            <p><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?></p>
                            <p><strong>Publication Year:</strong> <?php echo $book['publication_year'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></p>
                            <p><strong>Category:</strong> 
                                <?php if ($book['category_name']): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Uncategorized</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Total Copies:</strong> <?php echo $book['total_copies']; ?></p>
                            <p><strong>Available Copies:</strong> 
                                <span class="badge bg-<?php echo $book['available_copies'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo $book['available_copies']; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Status</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="badge bg-<?php 
                            echo $book['status'] == 'available' ? 'success' : 
                                ($book['status'] == 'borrowed' ? 'warning' : 'danger'); 
                        ?> fs-6">
                            <?php echo ucfirst($book['status']); ?>
                        </span>
                    </div>
                    
                    <?php if ($book['available_copies'] > 0): ?>
                        <a href="../loans/add.php?book_id=<?php echo $book['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Loan
                        </a>
                    <?php else: ?>
                        <p class="text-muted">No copies available for loan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loan History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Loan History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($loan_history)): ?>
                        <p class="text-muted text-center">No loan history found for this book.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Loan Code</th>
                                        <th>Member</th>
                                        <th>Loan Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loan_history as $loan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($loan['loan_code']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($loan['member_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($loan['member_code']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($loan['loan_date']); ?></td>
                                        <td><?php echo formatDate($loan['due_date']); ?></td>
                                        <td><?php echo $loan['return_date'] ? formatDate($loan['return_date']) : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $loan['status'] == 'active' ? 'primary' : 
                                                    ($loan['status'] == 'returned' ? 'success' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($loan['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($loan['admin_name']); ?></td>
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
    
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>