<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Return Book';
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
        SELECT l.*, b.title as book_title, b.author as book_author, 
               m.name as member_name, m.member_code,
               DATEDIFF(CURDATE(), l.due_date) as days_overdue
        FROM loans l 
        JOIN books b ON l.book_id = b.id 
        JOIN members m ON l.member_id = m.id 
        WHERE l.id = ? AND l.status = 'active'
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
    $return_date = sanitizeInput($_POST['return_date']);
    $fine_amount = (float)$_POST['fine_amount'];
    $notes = sanitizeInput($_POST['notes']);
    
    // Validate return date
    if (empty($return_date)) {
        $errors[] = 'Return date is required';
    } else {
        $return_datetime = new DateTime($return_date);
        $loan_datetime = new DateTime($loan['loan_date']);
        
        if ($return_datetime < $loan_datetime) {
            $errors[] = 'Return date cannot be before loan date';
        }
    }
    
    // Validate fine amount
    if ($fine_amount < 0) {
        $errors[] = 'Fine amount cannot be negative';
    }
    
    // If no errors, process return
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update loan record
            $stmt = $pdo->prepare("
                UPDATE loans 
                SET status = 'returned', return_date = ?, fine_amount = ?, notes = CONCAT(COALESCE(notes, ''), ?) 
                WHERE id = ?
            ");
            
            $return_notes = $notes ? "\n[Return] " . $notes : '';
            $stmt->execute([$return_date, $fine_amount, $return_notes, $loan_id]);
            
            // Update book availability
            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
            $stmt->execute([$loan['book_id']]);
            
            $pdo->commit();
            
            header('Location: index.php?success=book_returned');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to process return: ' . $e->getMessage();
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
                <h1><i class="bi bi-check-circle"></i> Return Book</h1>
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
                    <h5 class="mb-0">Loan Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Loan Code:</strong> <?php echo htmlspecialchars($loan['loan_code']); ?></p>
                            <p><strong>Book:</strong> <?php echo htmlspecialchars($loan['book_title']); ?></p>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($loan['book_author']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Member:</strong> <?php echo htmlspecialchars($loan['member_name']); ?></p>
                            <p><strong>Member Code:</strong> <?php echo htmlspecialchars($loan['member_code']); ?></p>
                            <p><strong>Loan Date:</strong> <?php echo formatDate($loan['loan_date']); ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Due Date:</strong> <?php echo formatDate($loan['due_date']); ?>
                                <?php if ($loan['days_overdue'] > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $loan['days_overdue']; ?> days overdue</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Return Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Return Information</h5>
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
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="return_date" name="return_date" 
                                           value="<?php echo htmlspecialchars($return_date ?? date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fine_amount" class="form-label">Fine Amount (IDR)</label>
                                    <input type="number" class="form-control" id="fine_amount" name="fine_amount" 
                                           min="0" step="0.01" value="<?php echo $loan['days_overdue'] > 0 ? $loan['days_overdue'] * 1000 : 0; ?>">
                                    <div class="form-text">
                                        <?php if ($loan['days_overdue'] > 0): ?>
                                            Suggested fine: IDR <?php echo number_format($loan['days_overdue'] * 1000); ?> 
                                            (IDR 1,000 per day overdue)
                                        <?php else: ?>
                                            No fine required - returned on time
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Return Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional notes about the return..."></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Process Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>