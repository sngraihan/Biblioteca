<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Member Details';
$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get member details
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        header('Location: index.php?error=member_not_found');
        exit();
    }
    
    // Get member's loan history
    $stmt = $pdo->prepare("
        SELECT l.*, b.title as book_title, b.author as book_author, a.full_name as admin_name
        FROM loans l 
        JOIN books b ON l.book_id = b.id 
        JOIN admins a ON l.admin_id = a.id 
        WHERE l.member_id = ? 
        ORDER BY l.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$member_id]);
    $loan_history = $stmt->fetchAll();
    
    // Get member statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM loans WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $total_loans = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM loans WHERE member_id = ? AND status = 'active'");
    $stmt->execute([$member_id]);
    $active_loans = $stmt->fetch()['active'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as overdue FROM loans WHERE member_id = ? AND status = 'active' AND due_date < CURDATE()");
    $stmt->execute([$member_id]);
    $overdue_loans = $stmt->fetch()['overdue'];
    
} catch (Exception $e) {
    $error = 'Failed to load member details';
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-person"></i> Member Details</h1>
                <div>
                    <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Member
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Members
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <?php echo showAlert($error, 'danger'); ?>
    <?php else: ?>
    
    <div class="row mb-4">
        <!-- Member Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Member Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Member Code:</strong> <?php echo htmlspecialchars($member['member_code']); ?></p>
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($member['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Join Date:</strong> <?php echo formatDate($member['join_date']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $member['status'] == 'active' ? 'success' : 
                                        ($member['status'] == 'inactive' ? 'secondary' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </p>
                            <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($member['address'] ?? 'N/A')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
 
                <!-- Statistics -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Loan Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="mb-3">
                            <h4 class="text-primary"><?php echo $total_loans; ?></h4>
                            <small class="text-muted">Total Loans</small>
                        </div>
                        <div class="mb-3">
                            <h4 class="text-warning"><?php echo $active_loans; ?></h4>
                            <small class="text-muted">Active Loans</small>
                        </div>
                        <div class="mb-3">
                            <h4 class="text-danger"><?php echo $overdue_loans; ?></h4>
                            <small class="text-muted">Overdue Loans</small>
                        </div>
                        
                        <?php if ($member['status'] == 'active'): ?>
                            <a href="../loans/add.php?member_id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> New Loan
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
 