<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Edit Loan';
$errors = [];
$success = '';
$loan_id = (int)($_GET['id'] ?? 0);

if ($loan_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get loan details
    $stmt = $pdo->prepare("
        SELECT l.*, b.title as book_title, m.name as member_name 
        FROM loans l 
        JOIN books b ON l.book_id = b.id 
        JOIN members m ON l.member_id = m.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        header('Location: index.php?error=loan_not_found');
        exit();
    }
    
} catch (Exception $e) {
    $errors[] = 'Failed to load loan details';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $due_date = sanitizeInput($_POST['due_date']);
    $fine_amount = (float)$_POST['fine_amount'];
    $notes = sanitizeInput($_POST['notes']);
    
    // Validate due date
    if (empty($due_date)) {
        $errors[] = 'Due date is required';
    } else {
        $due_datetime = new DateTime($due_date);
        $loan_datetime = new DateTime($loan['loan_date']);
        
        if ($due_datetime <= $loan_datetime) {
            $errors[] = 'Due date must be after loan date';
        }
    }
    
    // Validate fine amount
    if ($fine_amount < 0) {
        $errors[] = 'Fine amount cannot be negative';
    }
    
    // If no errors, update the loan
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE loans 
                SET due_date = ?, fine_amount = ?, notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$due_date, $fine_amount, $notes ?: null, $loan_id]);
            
            $success = 'Loan updated successfully!';
            
            // Refresh loan data
            $stmt = $pdo->prepare("
                SELECT l.*, b.title as book_title, m.name as member_name 
                FROM loans l 
                JOIN books b ON l.book_id = b.id 
                JOIN members m ON l.member_id = m.id 
                WHERE l.id = ?
            ");
            $stmt->execute([$loan_id]);
            $loan = $stmt->fetch();
            
        } catch (Exception $e) {
            $errors[] = 'Failed to update loan: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-pencil"></i> Edit Loan</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Loan Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Loan Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Loan Code:</strong> <?php echo htmlspecialchars($loan['loan_code']); ?></p>
                            <p><strong>Book:</strong> <?php echo htmlspecialchars($loan['book_title']); ?></p>
                            <p><strong>Member:</strong> <?php echo htmlspecialchars($loan['member_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Loan Date:</strong> <?php echo formatDate($loan['loan_date']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $loan['status'] == 'active' ? 'primary' : 
                                        ($loan['status'] == 'returned' ? 'success' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($loan['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Loan Details</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <?php echo showAlert($success, 'success'); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" 
                                           value="<?php echo htmlspecialchars($loan['due_date']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fine_amount" class="form-label">Fine Amount (IDR)</label>
                                    <input type="number" class="form-control" id="fine_amount" name="fine_amount" 
                                           min="0" step="0.01" value="<?php echo $loan['fine_amount']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($loan['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Loan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
