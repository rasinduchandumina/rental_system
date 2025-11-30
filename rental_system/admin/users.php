<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$_SESSION['admin_id'] = $_SESSION['user_id'];
$_SESSION['admin_name'] = $_SESSION['user_name'];

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_user':
                // Check if username or email exists
                $check = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
                $check->execute([':username' => $_POST['username'], ':email' => $_POST['email']]);
                
                if($check->rowCount() > 0) {
                    $error = "Username or email already exists!";
                } else {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (username, email, password, full_name, phone, role) 
                             VALUES (:username, :email, :password, :full_name, :phone, :role)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':username' => $_POST['username'],
                        ':email' => $_POST['email'],
                        ':password' => $hashed_password,
                        ':full_name' => $_POST['full_name'],
                        ':phone' => $_POST['phone'],
                        ':role' => $_POST['role']
                    ]);
                    $success = "User added successfully!";
                }
                break;
                
            case 'edit_user':
                $query = "UPDATE users SET 
                         username = :username,
                         email = :email,
                         full_name = :full_name,
                         phone = :phone,
                         role = :role,
                         updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':id' => $_POST['user_id'],
                    ':username' => $_POST['username'],
                    ':email' => $_POST['email'],
                    ':full_name' => $_POST['full_name'],
                    ':phone' => $_POST['phone'],
                    ':role' => $_POST['role']
                ]);
                
                // Update password if provided
                if(!empty($_POST['password'])) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = :password WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':password' => $hashed_password, ':id' => $_POST['user_id']]);
                }
                
                $success = "User updated successfully!";
                break;
        }
    }
}

// Handle user deletion
if(isset($_GET['delete_user'])) {
    $userId = $_GET['delete_user'];
    
    // Don't allow deleting own account
    if($userId == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        try {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $userId]);
            $success = "User deleted successfully!";
        } catch(Exception $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$whereClause = "WHERE 1=1";
$params = [];

if($roleFilter) {
    $whereClause .= " AND role = :role";
    $params[':role'] = $roleFilter;
}

if($searchTerm) {
    $whereClause .= " AND (username LIKE :search OR full_name LIKE :search2 OR email LIKE :search3)";
    $params[':search'] = "%$searchTerm%";
    $params[':search2'] = "%$searchTerm%";
    $params[':search3'] = "%$searchTerm%";
}

// Get users
try {
    $query = "SELECT * FROM users $whereClause ORDER BY role, full_name";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Error loading data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Canteen System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <p>Manage admin, teacher and student accounts</p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="search">Search Users</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search" name="search" placeholder="Search by name, username or email..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Filter by Role</label>
                        <select id="role" name="role">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="teacher" <?php echo $roleFilter == 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                            <option value="student" <?php echo $roleFilter == 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Add User Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="add_role">Role *</label>
                            <select id="add_role" name="role" required>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Users List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> All Users (<?php echo count($users); ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <?php if(empty($users)): ?>
                        <p>No users found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn btn-primary btn-small" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_full_name">Full Name *</label>
                        <input type="text" id="edit_full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_username">Username *</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">Email *</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="tel" id="edit_phone" name="phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="edit_password" name="password" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_role">Role *</label>
                        <select id="edit_role" name="role" required>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = '';
            
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function confirmDelete(userId, userName) {
            if(confirm(`Are you sure you want to delete "${userName}"?\n\nThis will also delete all their orders.`)) {
                window.location.href = `?delete_user=${userId}`;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
    
    <style>
        .role-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .role-student { background: #3498db; color: white; }
        .role-teacher { background: #9b59b6; color: white; }
        .role-admin { background: #e74c3c; color: white; }
        .table-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn-small { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
        .modal-content { max-width: 700px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .table-actions { flex-direction: column; }
        }
    </style>
</body>
</html>
