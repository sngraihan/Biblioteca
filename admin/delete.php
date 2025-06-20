<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAdmin();

$admin_id = (int)($_GET['id'] ?? 0);

if ($admin_id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

// Prevent self-deletion
if ($admin_id == $_SESSION['admin_id']) {
    header('Location: index.php?error=cannot_delete_self');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get admin details
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        header('Location: index.php?error=admin_not_found');
        exit();
    }
    
    // Check if admin has processed loans
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $loan_count = $stmt->fetch()['count'];
    
    if ($loan_count > 0) {
        header('Location: index.php?error=admin_has_loans');
        exit();
    }
    
    // Delete the admin
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    
    header('Location: index.php?success=admin_deleted');
    exit();
    
} catch (Exception $e) {
    header('Location: index.php?error=delete_failed');
    exit();
}
?>
