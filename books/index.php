<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Books Management';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

try {
    $pdo = getDBConnection();
    
    // Get categories for filter
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Build query with search and filter
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category_filter > 0) {
        $where_conditions[] = "b.category_id = ?";
        $params[] = $category_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $query = "
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        $where_clause 
        ORDER BY b.title
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load books: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-book"></i> Books Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Book
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
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Books</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by title, author, or ISBN" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
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
    
    <!-- Books List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Books List (<?php echo count($books); ?> books found)</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php elseif (empty($books)): ?>
                        <p class="text-muted text-center">No books found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>ISBN</th>
                                        <th>Copies</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                            <br><small class="text-muted">
                                                <?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?> 
                                                (<?php echo $book['publication_year'] ?? 'N/A'; ?>)
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $book['status'] == 'available' ? 'success' : 
                                                    ($book['status'] == 'borrowed' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($book['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-outline-info" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this book?')">
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