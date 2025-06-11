<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$book_id = (int)($_GET['id'] ?? 0);

if ($book_id <= 0) {
    header('Location: index.php?error=invalid_id');
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
    
    // Check if book has active loans
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE book_id = ? AND status = 'active'");
    $stmt->execute([$book_id]);
    $active_loans = $stmt->fetch()['count'];
    
    if ($active_loans > 0) {
        header('Location: index.php?error=book_has_active_loans');
        exit();
    }
    
    // Delete the book
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    
    header('Location: index.php?success=book_deleted');
    exit();
    
} catch (Exception $e) {
    header('Location: index.php?error=delete_failed');
    exit();
}
?>