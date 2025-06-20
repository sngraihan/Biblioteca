<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'New Loan';
$errors = [];
$success = '';

try {
    $pdo = getDBConnection();
    
    // Get available books
    $stmt = $pdo->query("
        SELECT id, title, author, available_copies 
        FROM books 
        WHERE status = 'available' AND available_copies > 0 
        ORDER BY title
    ");
    $available_books = $stmt->fetchAll();
    
    // Get active members
    $stmt = $pdo->query("
        SELECT id, name, member_code 
        FROM members 
        WHERE status = 'active' 
        ORDER BY name
    ");
    $active_members = $stmt->fetchAll();
    
} catch (Exception $e) {
    $errors[] = 'Failed to load data: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $book_id = (int)$_POST['book_id'];
    $member_id = (int)$_POST['member_id'];
    $loan_date = sanitizeInput($_POST['loan_date']);
    $due_date = sanitizeInput($_POST['due_date']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Validate required fields
    if ($book_id <= 0) {
        $errors[] = 'Please select a book';
    }
    
    if ($member_id <= 0) {
        $errors[] = 'Please select a member';
    }
    
    if (empty($loan_date)) {
        $errors[] = 'Loan date is required';
    }
    
    if (empty($due_date)) {
        $errors[] = 'Due date is required';
    }
    
    // Validate dates
    if (!empty($loan_date) && !empty($due_date)) {
        $loan_datetime = new DateTime($loan_date);
        $due_datetime = new DateTime($due_date);
        
        if ($due_datetime <= $loan_datetime) {
            $errors[] = 'Due date must be after loan date';
        }
    }
    
    // Check book availability
    if ($book_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ? AND status = 'available'");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            if (!$book || $book['available_copies'] <= 0) {
                $errors[] = 'Selected book is not available for loan';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to check book availability';
        }
    }
    
    // Check member status
    if ($member_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT status FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
            if (!$member || $member['status'] !== 'active') {
                $errors[] = 'Selected member is not active';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to check member status';
        }
    }
    
    // If no errors, create the loan
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate unique loan code
            do {
                $loan_code = generateCode('LN', 4);
                $stmt = $pdo->prepare("SELECT id FROM loans WHERE loan_code = ?");
                $stmt->execute([$loan_code]);
            } while ($stmt->fetch());
            
            // Insert loan record
            $stmt = $pdo->prepare("
                INSERT INTO loans (loan_code, book_id, member_id, admin_id, loan_date, due_date, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $loan_code,
                $book_id,
                $member_id,
                $_SESSION['admin_id'],
                $loan_date,
                $due_date,
                $notes ?: null
            ]);
            
            // Update book availability
            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt->execute([$book_id]);
            
            $pdo->commit();
            
            $success = "Loan created successfully! Loan Code: $loan_code";
            
            // Clear form data
            $book_id = $member_id = 0;
            $loan_date = $due_date = $notes = '';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create loan: ' . $e->getMessage();
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
                <h1><i class="bi bi-plus-circle"></i> New Loan</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
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
                                    <label for="book_id" class="form-label">Book <span class="text-danger">*</span></label>
                                    <select class="form-select" id="book_id" name="book_id" required>
                                        <option value="">Select Book</option>
                                        <?php foreach ($available_books as $book): ?>
                                        <option value="<?php echo $book['id']; ?>" 
                                                <?php echo (isset($book_id) && $book_id == $book['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>
                                            (<?php echo $book['available_copies']; ?> available)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="member_id" class="form-label">Member <span class="text-danger">*</span></label>
                                    <select class="form-select" id="member_id" name="member_id" required>
                                        <option value="">Select Member</option>
                                        <?php foreach ($active_members as $member): ?>
                                        <option value="<?php echo $member['id']; ?>" 
                                                <?php echo (isset($member_id) && $member_id == $member['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['member_code']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="loan_date" class="form-label">Loan Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="loan_date" name="loan_date" 
                                           value="<?php echo htmlspecialchars($loan_date ?? date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" 
                                           value="<?php echo htmlspecialchars($due_date ?? date('Y-m-d', strtotime('+14 days'))); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Create Loan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate due date when loan date changes
document.getElementById('loan_date').addEventListener('change', function() {
    const loanDate = new Date(this.value);
    const dueDate = new Date(loanDate);
    dueDate.setDate(dueDate.getDate() + 14); // Default 14 days loan period
    
    document.getElementById('due_date').value = dueDate.toISOString().split('T')[0];
});
</script>

<?php include '../includes/footer.php'; ?>