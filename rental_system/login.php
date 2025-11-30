<?php
session_start();

// If already logged in, redirect based on role
if(isset($_SESSION['user_id'])) {
    if($_SESSION['user_role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: order.php');
    }
    exit();
}

require_once 'config/database.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if(empty($username) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $query = "SELECT * FROM users WHERE username = :username AND role = :role";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect based on role
                if($role == 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: order.php');
                }
                exit();
            } else {
                $error = 'Invalid username, password or role selection';
            }
            
        } catch(Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Canteen Ordering System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .login-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .role-selection {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
        }
        
        .role-option input[type="radio"] {
            display: none;
        }
        
        .role-option label {
            display: block;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-option label i {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .role-option input[type="radio"]:checked + label {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .role-option input[type="radio"]:checked + label i {
            color: white;
        }
        
        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .error {
            background: #ff6b6b;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .demo-credentials h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .demo-credentials p {
            margin: 0.3rem 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-utensils"></i>
            <h2>Login</h2>
            <p>Canteen Ordering System</p>
        </div>
        
        <div class="demo-credentials">
            <h4>Demo Accounts (Password: password):</h4>
            <p><strong>Admin:</strong> admin</p>
            <p><strong>Teacher:</strong> teacher1</p>
            <p><strong>Student:</strong> student1</p>
        </div>
        
        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['registered'])): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> Account created successfully! Please login.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Select Your Role:</label>
                <div class="role-selection">
                    <div class="role-option">
                        <input type="radio" id="admin" name="role" value="admin">
                        <label for="admin">
                            <i class="fas fa-user-shield"></i>
                            Admin
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="teacher" name="role" value="teacher">
                        <label for="teacher">
                            <i class="fas fa-chalkboard-teacher"></i>
                            Teacher
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="student" name="role" value="student" checked>
                        <label for="student">
                            <i class="fas fa-user-graduate"></i>
                            Student
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-button">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="links">
            <a href="register.php">
                <i class="fas fa-user-plus"></i> Create Account
            </a>
            <a href="index.html">
                <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
        </div>
    </div>
</body>
</html>
