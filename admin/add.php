<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = 'Add New Admin';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $role = sanitizeInput($_POST['role']);
    
    // Validate required fields
    $required_fields = [
        'username' => $username,
        'password' => $password,
        'full_name' => $full_name,
        'role' => $role
    ];
    
    $validation_errors = validateRequired($required_fields);
    if (!empty($validation_errors)) {
        $errors = array_merge($errors, $validation_errors);
    }
    
    // Validate username format
    if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores';
    }
    
    // Validate password
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate role
    if (!in_array($role, ['admin', 'staff'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Check username uniqueness
    if (!empty($username)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate username';
        }
    }
    
    // Check email uniqueness if provided
    if (!empty($email)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate email';
        }
    }
    
    // If no errors, insert the admin
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admins (username, password, full_name, email, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $username,
                hashPassword($password),
                $full_name,
                $email ?: null,
                $role
            ]);
            
            $success = 'Admin user added successfully!';
            
            // Clear form data
            $username = $full_name = $email = '';
            $role = 'staff';
            
        } catch (Exception $e) {
            $errors[] = 'Failed to add admin user: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-person-plus"></i> Add New Admin</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Admin
                </a>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <?php echo showAlert($success, 'success'); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                                    <div class="form-text">3-20 characters, letters, numbers, and underscores only</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="staff" <?php echo (isset($role) && $role == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo (isset($role) && $role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Add Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>