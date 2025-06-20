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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $status = sanitizeInput($_POST['status']);
    
    // Validate required fields
    $required_fields = [
        'name' => $name,
        'status' => $status
    ];
    
    $validation_errors = validateRequired($required_fields);
    if (!empty($validation_errors)) {
        $errors = array_merge($errors, $validation_errors);
    }
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate status
    if (!in_array($status, ['active', 'inactive', 'suspended'])) {
        $errors[] = 'Invalid status selected';
    }
    
    // Check email uniqueness if changed
    if (!empty($email) && $email !== $member['email']) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
            $stmt->execute([$email, $member_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate email';
        }
    }
    
    // If no errors, update the member
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE members 
                SET name = ?, email = ?, phone = ?, address = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $name,
                $email ?: null,
                $phone ?: null,
                $address ?: null,
                $status,
                $member_id
            ]);
            
            $success = 'Member updated successfully!';
            
            // Refresh member data
            $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
        } catch (Exception $e) {
            $errors[] = 'Failed to update member: ' . $e->getMessage();
        }
    }
}