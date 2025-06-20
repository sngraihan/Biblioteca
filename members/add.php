<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Add New Member';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Validate required fields
    $required_fields = [
        'name' => $name
    ];
    
    $validation_errors = validateRequired($required_fields);
    if (!empty($validation_errors)) {
        $errors = array_merge($errors, $validation_errors);
    }
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Check email uniqueness if provided
    if (!empty($email)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate email';
        }
    }
    
    // If no errors, insert the member
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Generate unique member code
            do {
                $member_code = generateCode('MBR', 3);
                $stmt = $pdo->prepare("SELECT id FROM members WHERE member_code = ?");
                $stmt->execute([$member_code]);
            } while ($stmt->fetch());
            
            $stmt = $pdo->prepare("
                INSERT INTO members (member_code, name, email, phone, address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $member_code,
                $name,
                $email ?: null,
                $phone ?: null,
                $address ?: null
            ]);
            
            $success = "Member added successfully! Member Code: $member_code";
            
            // Clear form data
            $name = $email = $phone = $address = '';
            
        } catch (Exception $e) {
            $errors[] = 'Failed to add member: ' . $e->getMessage();
        }
    }
}