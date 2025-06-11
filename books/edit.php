<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Edit Book';
$errors = [];
$success = '';
$book_id = (int)($_GET['id'] ?? 0);

if ($book_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get book details
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header('Location: index.php?error=book_not_found');
        exit();
    }
    
    // Get categories
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $errors[] = 'Failed to load book details';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $title = sanitizeInput($_POST['title']);
    $author = sanitizeInput($_POST['author']);
    $publisher = sanitizeInput($_POST['publisher']);
    $publication_year = sanitizeInput($_POST['publication_year']);
    $isbn = sanitizeInput($_POST['isbn']);
    $category_id = (int)$_POST['category_id'];
    $total_copies = (int)$_POST['total_copies'];
    $status = sanitizeInput($_POST['status']);
    
    // Validate required fields
    $required_fields = [
        'title' => $title,
        'author' => $author,
        'total_copies' => $total_copies
    ];
    
    $validation_errors = validateRequired($required_fields);
    if (!empty($validation_errors)) {
        $errors = array_merge($errors, $validation_errors);
    }
    
    // Validate year
    if (!empty($publication_year) && (!is_numeric($publication_year) || $publication_year < 1000 || $publication_year > date('Y'))) {
        $errors[] = 'Please enter a valid publication year';
    }
    
    // Validate copies
    if ($total_copies < 1) {
        $errors[] = 'Total copies must be at least 1';
    }
    
    // Check if we can reduce total copies (not below borrowed copies)
    $borrowed_copies = $book['total_copies'] - $book['available_copies'];
    if ($total_copies < $borrowed_copies) {
        $errors[] = "Cannot reduce total copies below $borrowed_copies (currently borrowed copies)";
    }
    
    // Check ISBN uniqueness if changed
    if (!empty($isbn) && $isbn !== $book['isbn']) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
            $stmt->execute([$isbn, $book_id]);
            if ($stmt->fetch()) {
                $errors[] = 'ISBN already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate ISBN';
        }
    }
    
    // If no errors, update the book
    if (empty($errors)) {
        try {
            // Calculate new available copies
            $new_available_copies = $total_copies - $borrowed_copies;
            
            $stmt = $pdo->prepare("
                UPDATE books 
                SET title = ?, author = ?, publisher = ?, publication_year = ?, isbn = ?, 
                    category_id = ?, total_copies = ?, available_copies = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $title,
                $author,
                $publisher ?: null,
                $publication_year ?: null,
                $isbn ?: null,
                $category_id ?: null,
                $total_copies,
                $new_available_copies,
                $status,
                $book_id
            ]);
            
            $success = 'Book updated successfully!';
            
            // Refresh book data
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
        } catch (Exception $e) {
            $errors[] = 'Failed to update book: ' . $e->getMessage();
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
                <h1><i class="bi bi-pencil"></i> Edit Book</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Books
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
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $book['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="author" name="author" 
                                           value="<?php echo htmlspecialchars($book['author']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" class="form-control" id="publisher" name="publisher" 
                                           value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="publication_year" class="form-label">Publication Year</label>
                                    <input type="number" class="form-control" id="publication_year" name="publication_year" 
                                           min="1000" max="<?php echo date('Y'); ?>"
                                           value="<?php echo htmlspecialchars($book['publication_year'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" 
                                           value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="total_copies" class="form-label">Total Copies <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="total_copies" name="total_copies" 
                                           min="<?php echo $book['total_copies'] - $book['available_copies']; ?>" 
                                           value="<?php echo $book['total_copies']; ?>" required>
                                    <div class="form-text">
                                        Available: <?php echo $book['available_copies']; ?> | 
                                        Borrowed: <?php echo $book['total_copies'] - $book['available_copies']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="available" <?php echo $book['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="borrowed" <?php echo $book['status'] == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                        <option value="maintenance" <?php echo $book['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>