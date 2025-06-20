<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = 'Edit Admin';
$errors = [];
$success = '';
$admin_id = (int)($_GET['id'] ?? 0);

if ($admin_id <= 0) {
    header('Location: index.php');
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
    
} catch (Exception $e) {
    $errors[] = 'Failed to load admin details';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $username = sanitizeInput($_POST['username']);
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $role = sanitizeInput($_POST['role']);
    $status = sanitizeInput($_POST['status']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    $required_fields = [
        'username' => $username,
        'full_name' => $full_name,
        'role' => $role,
        'status' => $status
    ];
    
    $validation_errors = validateRequired($required_fields);
    if (!empty($validation_errors)) {
        $errors = array_merge($errors, $validation_errors);
    }
    
    // Validate username format
    if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores';
    }
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate role
    if (!in_array($role, ['admin', 'staff'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        $errors[] = 'Invalid status selected';
    }
    
    // Validate password if provided
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    // Check username uniqueness if changed
    if (!empty($username) && $username !== $admin['username']) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmt->execute([$username, $admin_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate username';
        }
    }
    
    // Check email uniqueness if changed
    if (!empty($email) && $email !== $admin['email']) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to validate email';
        }
    }
    
    // If no errors, update the admin
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with password
                $stmt = $pdo->prepare("
                    UPDATE admins 
                    SET username = ?, password = ?, full_name = ?, email = ?, role = ?, status = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $username,
                    hashPassword($password),
                    $full_name,
                    $email ?: null,
                    $role,
                    $status,
                    $admin_id
                ]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("
                    UPDATE admins 
                    SET username = ?, full_name = ?, email = ?, role = ?, status = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $username,
                    $full_name,
                    $email ?: null,
                    $role,
                    $status,
                    $admin_id
                ]);
            }
            
            $success = 'Admin updated successfully!';
            
            // Refresh admin data
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();
            
        } catch (Exception $e) {
            $errors[] = 'Failed to update admin: ' . $e->getMessage();
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
                <h1><i class="bi bi-pencil"></i> Edit Admin</h1>
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
                                           value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                    <div class="form-text">3-20 characters, letters, numbers, and underscores only</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="staff" <?php echo $admin['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo $admin['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $admin['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $admin['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                        </div>
                        
                        <hr>
                        <h6>Change Password (leave blank to keep current password)</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
