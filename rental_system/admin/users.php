<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all users
$query = "SELECT id, username, email, full_name, phone, role, created_at FROM users ORDER BY role, full_name";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by role
$admins = array_filter($users, function($u) { return $u['role'] === 'admin'; });
$teachers = array_filter($users, function($u) { return $u['role'] === 'teacher'; });
$students = array_filter($users, function($u) { return $u['role'] === 'student'; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Canteen Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .user-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .user-tab {
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .user-tab:hover,
        .user-tab.active {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
        }
        
        .user-tab span {
            background: rgba(0,0,0,0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: 0.5rem;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-admin {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .role-teacher {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .role-student {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .add-user-btn {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .add-user-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h2 {
            color: #2c3e50;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
        }
        
        .btn-cancel {
            background: #f8f9fa;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <p>Manage all user accounts</p>
        </div>
        
        <button class="add-user-btn" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
        
        <div class="user-tabs">
            <button class="user-tab active" data-role="all">
                All <span><?php echo count($users); ?></span>
            </button>
            <button class="user-tab" data-role="admin">
                Admins <span><?php echo count($admins); ?></span>
            </button>
            <button class="user-tab" data-role="teacher">
                Teachers <span><?php echo count($teachers); ?></span>
            </button>
            <button class="user-tab" data-role="student">
                Students <span><?php echo count($students); ?></span>
            </button>
        </div>
        
        <div class="activity-section">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach($users as $user): ?>
                            <tr data-role="<?php echo $user['role']; ?>">
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                                <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='openEditModal(<?php echo json_encode($user); ?>)' class="btn-small btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-small btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Add/Edit User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Add New User</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="fullName">Full Name *</label>
                    <input type="text" id="fullName" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username * <small id="usernameNote">(cannot be changed)</small></label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span id="passwordNote">*</span></label>
                    <input type="password" id="password" name="password">
                    <small id="passwordHint" style="color: #666;"></small>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="submitBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        let isEditMode = false;
        
        // Tab filtering
        document.querySelectorAll('.user-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const role = this.dataset.role;
                document.querySelectorAll('#usersTableBody tr').forEach(row => {
                    if(role === 'all' || row.dataset.role === role) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        function openAddModal() {
            isEditMode = false;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('username').readOnly = false;
            document.getElementById('usernameNote').style.display = 'none';
            document.getElementById('password').required = true;
            document.getElementById('passwordNote').textContent = '*';
            document.getElementById('passwordHint').textContent = '';
            document.getElementById('submitBtn').textContent = 'Add User';
            document.getElementById('userModal').classList.add('show');
        }
        
        function openEditModal(user) {
            isEditMode = true;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('fullName').value = user.full_name;
            document.getElementById('username').value = user.username;
            document.getElementById('username').readOnly = true;
            document.getElementById('usernameNote').style.display = 'inline';
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordNote').textContent = '';
            document.getElementById('passwordHint').textContent = 'Leave blank to keep current password';
            document.getElementById('submitBtn').textContent = 'Update User';
            document.getElementById('userModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('show');
        }
        
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                id: document.getElementById('userId').value || null,
                full_name: document.getElementById('fullName').value,
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                role: document.getElementById('role').value,
                password: document.getElementById('password').value
            };
            
            const method = isEditMode ? 'PUT' : 'POST';
            
            try {
                const response = await fetch('../api/users.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Failed to save user');
                }
            } catch(error) {
                alert('Error saving user');
            }
        });
        
        async function deleteUser(id) {
            if(!confirm('Are you sure you want to delete this user?')) return;
            
            try {
                const response = await fetch(`../api/users.php?id=${id}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete user');
                }
            } catch(error) {
                alert('Error deleting user');
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('userModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
