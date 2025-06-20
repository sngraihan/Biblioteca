<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Member Details';
$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header('Location: index.php');
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
    
    // Get member's loan history
    $stmt = $pdo->prepare("
        SELECT l.*, b.title as book_title, b.author as book_author, a.full_name as admin_name
        FROM loans l 
        JOIN books b ON l.book_id = b.id 
        JOIN admins a ON l.admin_id = a.id 
        WHERE l.member_id = ? 
        ORDER BY l.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$member_id]);
    $loan_history = $stmt->fetchAll();
    
    // Get member statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM loans WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $total_loans = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM loans WHERE member_id = ? AND status = 'active'");
    $stmt->execute([$member_id]);
    $active_loans = $stmt->fetch()['active'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as overdue FROM loans WHERE member_id = ? AND status = 'active' AND due_date < CURDATE()");
    $stmt->execute([$member_id]);
    $overdue_loans = $stmt->fetch()['overdue'];
    
} catch (Exception $e) {
    $error = 'Failed to load member details';
}
