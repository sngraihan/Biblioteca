<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Get member details
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        header('Location: index.php?error=member_not_found');
        exit();
    }
    
    // Check if member has active loans
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE member_id = ? AND status = 'active'");
    $stmt->execute([$member_id]);
    $active_loans = $stmt->fetch()['count'];
    
    if ($active_loans > 0) {
        header('Location: index.php?error=member_has_active_loans');
        exit();
    }
    
    // Delete the member
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    
    header('Location: index.php?success=member_deleted');
    exit();
    
} catch (Exception $e) {
    header('Location: index.php?error=delete_failed');
    exit();
}
?>
