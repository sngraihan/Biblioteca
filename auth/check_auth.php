<?php
require_once '../config/session.php';

// This file can be used to check authentication status via AJAX
header('Content-Type: application/json');

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo json_encode([
        'authenticated' => true,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Not authenticated'
    ]);
}
?>
