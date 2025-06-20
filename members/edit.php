<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Edit Member';
$errors = [];
$success = '';
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
    
} catch (Exception $e) {
    $errors[] = 'Failed to load member details';
}