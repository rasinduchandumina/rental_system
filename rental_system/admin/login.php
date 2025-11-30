<?php
session_start();

// If already logged in as admin, redirect to dashboard
if(isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'admin') {
    header('Location: dashboard.php');
    exit();
}

// If logged in but not admin, redirect to main site
if(isset($_SESSION['user_id']) && $_SESSION['user_role'] != 'admin') {
    header('Location: ../order.php');
    exit();
}

require_once '../config/database.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $query = "SELECT * FROM users WHERE username = :username AND role = 'admin'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['user_username'] = $admin['username'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['user_name'] = $admin['full_name'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['user_role'] = 'admin';
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password';
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
    <title>Admin Login - Canteen System</title>
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
        }
        
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
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
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
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
            transition: transform 0.3s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #ff6b6b;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .demo-credentials h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user-shield"></i>
            <h2>Admin Login</h2>
            <p>Canteen Management System</p>
        </div>
        
        <div class="demo-credentials">
            <h4>Demo Credentials:</h4>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> password</p>
        </div>
        
        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
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
        
        <div class="back-link">
            <a href="../index.html">
                <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
            <br><br>
            <a href="../login.php">
                <i class="fas fa-users"></i> Student/Teacher Login
            </a>
        </div>
    </div>
</body>
</html>