<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireAuth();

$page_title = 'Members Management';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

try {
    $pdo = getDBConnection();
    
    // Build query with search and filter
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ? OR email LIKE ? OR member_code LIKE ? OR phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $query = "SELECT * FROM members $where_clause ORDER BY name";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Failed to load members: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-people"></i> Members Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Add New Member
                </a>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <label for="search" class="form-label">Search Members</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name, email, member code, or phone" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
   