<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Set admin session variables for compatibility
$_SESSION['admin_id'] = $_SESSION['user_id'];
$_SESSION['admin_name'] = $_SESSION['user_name'];

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
try {
    // Total food items
    $query = "SELECT COUNT(*) as total_items FROM food_items";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalItems = $stmt->fetch()['total_items'];
    
    // Total orders today
    $query = "SELECT COUNT(*) as total_orders FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $todayOrders = $stmt->fetch()['total_orders'];
    
    // Total users (students + teachers)
    $query = "SELECT COUNT(*) as total_users FROM users WHERE role IN ('student', 'teacher')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalUsers = $stmt->fetch()['total_users'];
    
    // Pending orders
    $query = "SELECT COUNT(*) as pending FROM orders WHERE status IN ('pending', 'preparing', 'ready')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pendingOrders = $stmt->fetch()['pending'];
    
    // Today's revenue
    $query = "SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $todayRevenue = $stmt->fetch()['revenue'] ?: 0;
    
    // Recent orders
    $query = "SELECT o.*, u.full_name as customer_name, u.role as customer_role,
              (SELECT GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') 
               FROM order_items oi 
               JOIN food_items fi ON oi.food_item_id = fi.id 
               WHERE oi.order_id = o.id) as items
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              ORDER BY o.created_at DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Error loading dashboard: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Canteen System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-hamburger"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalItems; ?></h3>
                    <p>Food Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e74c3c);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $todayOrders; ?></h3>
                    <p>Orders Today</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pendingOrders; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
        </div>
        
        <!-- Today's Revenue -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3><i class="fas fa-money-bill-wave"></i> Today's Revenue</h3>
            </div>
            <div class="card-body">
                <h2 style="color: #27ae60; font-size: 2.5rem;">Rs. <?php echo number_format($todayRevenue, 2); ?></h2>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-grid">
            <div class="activity-section" style="grid-column: 1 / -1;">
                <h2><i class="fas fa-history"></i> Recent Orders</h2>
                <div class="table-container">
                    <?php if(empty($recentOrders)): ?>
                        <p>No recent orders found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order Code</th>
                                    <th>Customer</th>
                                    <th>Role</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong style="font-size: 1.1rem; color: #667eea;"><?php echo htmlspecialchars($order['order_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $order['customer_role']; ?>">
                                                <?php echo ucfirst($order['customer_role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['items']); ?></td>
                                        <td><strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <style>
        .role-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .role-student { background: #3498db; color: white; }
        .role-teacher { background: #9b59b6; color: white; }
        .role-admin { background: #e74c3c; color: white; }
    </style>
    
    <script src="js/admin.js"></script>
</body>
</html>