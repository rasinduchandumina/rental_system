<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getUsers($db);
        break;
    case 'POST':
        createUser($db);
        break;
    case 'PUT':
        updateUser($db);
        break;
    case 'DELETE':
        deleteUser($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getUsers($db) {
    try {
        $role = $_GET['role'] ?? null;
        
        if($role && in_array($role, ['admin', 'teacher', 'student'])) {
            $query = "SELECT id, username, email, full_name, phone, role, created_at FROM users WHERE role = :role ORDER BY full_name";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':role', $role);
        } else {
            $query = "SELECT id, username, email, full_name, phone, role, created_at FROM users ORDER BY role, full_name";
            $stmt = $db->prepare($query);
        }
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching users: ' . $e->getMessage()
        ]);
    }
}

function createUser($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        if(empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name']) || empty($data['role'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }
        
        if(!in_array($data['role'], ['admin', 'teacher', 'student'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            return;
        }
        
        // Check if username exists
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $data['username']);
        $stmt->execute();
        if($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }
        
        // Check if email exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        if($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, full_name, phone, role) 
                 VALUES (:username, :email, :password, :full_name, :phone, :role)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $data['full_name']);
        $phone = $data['phone'] ?? null;
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':role', $data['role']);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating user: ' . $e->getMessage()
        ]);
    }
}

function updateUser($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }
        
        // Build update query dynamically
        $updates = [];
        $params = [':id' => $data['id']];
        
        if(!empty($data['full_name'])) {
            $updates[] = "full_name = :full_name";
            $params[':full_name'] = $data['full_name'];
        }
        
        if(!empty($data['email'])) {
            // Check if email is taken by another user
            $query = "SELECT id FROM users WHERE email = :email AND id != :check_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':check_id', $data['id']);
            $stmt->execute();
            if($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already in use']);
                return;
            }
            $updates[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        
        if(!empty($data['phone'])) {
            $updates[] = "phone = :phone";
            $params[':phone'] = $data['phone'];
        }
        
        if(!empty($data['role']) && in_array($data['role'], ['admin', 'teacher', 'student'])) {
            $updates[] = "role = :role";
            $params[':role'] = $data['role'];
        }
        
        if(!empty($data['password'])) {
            $updates[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if(empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating user: ' . $e->getMessage()
        ]);
    }
}

function deleteUser($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }
        
        // Prevent deleting yourself
        if($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
            return;
        }
        
        // Check if user has any orders
        $query = "SELECT COUNT(*) as count FROM orders WHERE user_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete user with existing orders']);
            return;
        }
        
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting user: ' . $e->getMessage()
        ]);
    }
}
?>
