<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$loan_id = (int)($_GET['id'] ?? 0);

if ($loan_id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get loan details
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        header('Location: index.php?error=loan_not_found');
        exit();
    }
    
    $pdo->beginTransaction();
    
    // If loan is active, return book availability first
    if ($loan['status'] == 'active') {
        $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt->execute([$loan['book_id']]);
    }
    
    // Delete the loan record completely
    $stmt = $pdo->prepare("DELETE FROM loans WHERE id = ?");
    $stmt->execute([$loan_id]);
    
    $pdo->commit();
    
    header('Location: index.php?success=loan_deleted');
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: index.php?error=delete_failed');
    exit();
}
?>
