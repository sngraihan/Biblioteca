<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Add New Book';
$errors = [];
$success = '';

// Get categories
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $errors[] = 'Failed to load categories';
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
    
    // Check ISBN uniqueness if provided
    if (!empty($isbn)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetch()) {
                $errors[] = 'ISBN already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate ISBN';
        }
    }
    
    // If no errors, insert the book
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO books (title, author, publisher, publication_year, isbn, category_id, total_copies, available_copies) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $title,
                $author,
                $publisher ?: null,
                $publication_year ?: null,
                $isbn ?: null,
                $category_id ?: null,
                $total_copies,
                $total_copies // Initially all copies are available
            ]);
            
            $success = 'Book added successfully!';
            
            // Clear form data
            $title = $author = $publisher = $publication_year = $isbn = '';
            $category_id = $total_copies = 0;
            
        } catch (Exception $e) {
            $errors[] = 'Failed to add book: ' . $e->getMessage();
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
                <h1><i class="bi bi-plus-circle"></i> Add New Book</h1>
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
                                           value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
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
                                           value="<?php echo htmlspecialchars($author ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" class="form-control" id="publisher" name="publisher" 
                                           value="<?php echo htmlspecialchars($publisher ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="publication_year" class="form-label">Publication Year</label>
                                    <input type="number" class="form-control" id="publication_year" name="publication_year" 
                                           min="1000" max="<?php echo date('Y'); ?>"
                                           value="<?php echo htmlspecialchars($publication_year ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" 
                                           value="<?php echo htmlspecialchars($isbn ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="total_copies" class="form-label">Total Copies <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="total_copies" name="total_copies" 
                                           min="1" value="<?php echo htmlspecialchars($total_copies ?? 1); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Add Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>