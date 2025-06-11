<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

requireAuth();

$page_title = 'Dashboard';

try {
    $pdo = getDBConnection();
    
    // Get statistics
    $stats = [];
    
    // Total books
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM books");
    $stats['total_books'] = $stmt->fetch()['total'];
    
    // Available books
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM books WHERE status = 'available'");
    $stats['available_books'] = $stmt->fetch()['total'];
    
    // Total members
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
    $stats['total_members'] = $stmt->fetch()['total'];
    
    // Active loans
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM loans WHERE status = 'active'");
    $stats['active_loans'] = $stmt->fetch()['total'];
    
    // Overdue loans
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM loans WHERE status = 'active' AND due_date < CURDATE()");
    $stats['overdue_loans'] = $stmt->fetch()['total'];
    
    // Recent loans
    $stmt = $pdo->query("
        SELECT l.*, b.title as book_title, m.name as member_name 
        FROM loans l 
        JOIN books b ON l.book_id = b.id 
        JOIN members m ON l.member_id = m.id 
        ORDER BY l.created_at DESC 
        LIMIT 5
    ");
    $recent_loans = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load dashboard data';
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="bi bi-speedometer2"></i> Dashboard
            </h1>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $stats['total_books']; ?></h4>
                            <p class="mb-0">Total Books</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-book display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $stats['available_books']; ?></h4>
                            <p class="mb-0">Available</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $stats['total_members']; ?></h4>
                            <p class="mb-0">Members</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-people display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $stats['active_loans']; ?></h4>
                            <p class="mb-0">Active Loans</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-arrow-left-right display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?php echo $stats['overdue_loans']; ?></h4>
                            <p class="mb-0">Overdue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-exclamation-triangle display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Loans -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Recent Loans
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_loans)): ?>
                        <p class="text-muted">No recent loans found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Loan Code</th>
                                        <th>Book</th>
                                        <th>Member</th>
                                        <th>Loan Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_loans as $loan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($loan['loan_code']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['member_name']); ?></td>
                                        <td><?php echo formatDate($loan['loan_date']); ?></td>
                                        <td><?php echo formatDate($loan['due_date']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $loan['status'] == 'active' ? 'primary' : 
                                                    ($loan['status'] == 'returned' ? 'success' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($loan['status']); ?>
                                            </span>
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

<?php include 'includes/footer.php'; ?>