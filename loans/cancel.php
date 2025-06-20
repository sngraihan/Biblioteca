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
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND status = 'active'");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch();
    
    if (!$loan) {
        header('Location: index.php?error=loan_not_found_or_not_active');
        exit();
    }
    
    $pdo->beginTransaction();
    
    // Cancel the loan (change status to cancelled)
    $stmt = $pdo->prepare("UPDATE loans SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$loan_id]);
    
    // Return book availability
    $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
    $stmt->execute([$loan['book_id']]);
    
    $pdo->commit();
    
    header('Location: index.php?success=loan_cancelled');
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: index.php?error=cancel_failed');
    exit();
}
?>
