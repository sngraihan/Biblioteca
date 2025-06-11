<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$category_id = (int)($_GET['id'] ?? 0);

if ($category_id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get category details
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        header('Location: index.php?error=category_not_found');
        exit();
    }
    
    // Check if category has books
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $book_count = $stmt->fetch()['count'];
    
    if ($book_count > 0) {
        header('Location: index.php?error=category_has_books');
        exit();
    }
    
    // Delete the category
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    
    header('Location: index.php?success=category_deleted');
    exit();
    
} catch (Exception $e) {
    header('Location: index.php?error=delete_failed');
    exit();
}
?>
