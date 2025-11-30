<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only teachers and students can access this page
if($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all user's orders
$query = "SELECT o.*, 
          GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
          FROM orders o
          JOIN order_items oi ON o.id = oi.order_id
          JOIN food_items fi ON oi.food_item_id = fi.id
          WHERE o.user_id = :user_id
          GROUP BY o.id
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Canteen Ordering System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            min-height: 100vh;
        }
        
        .user-header {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info i {
            font-size: 1.5rem;
        }
        
        .user-info span {
            font-weight: bold;
        }
        
        .user-role {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-left: 1rem;
            transition: background 0.3s;
        }
        
        .header-actions a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 2rem;
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-code {
            font-size: 2rem;
            font-weight: bold;
            color: #e74c3c;
            letter-spacing: 3px;
            font-family: monospace;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .status-preparing {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .status-ready {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .status-completed {
            background: #e0e0e0;
            color: #666;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #e74c3c;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .order-items {
            color: #555;
        }
        
        .order-items h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .order-total {
            text-align: right;
        }
        
        .order-total .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .order-time {
            color: #999;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .no-orders {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-orders i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .btn-order {
            display: inline-block;
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-total {
                text-align: left;
                margin-top: 1rem;
            }
            
            .user-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="user-header">
        <div class="user-info">
            <i class="fas fa-utensils"></i>
            <span>School Canteen</span>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
        </div>
        <div class="header-actions">
            <a href="order.php"><i class="fas fa-plus"></i> New Order</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-receipt"></i> My Orders</h1>
        
        <?php if(empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-shopping-basket"></i>
                <h2>No orders yet</h2>
                <p>You haven't placed any orders yet.</p>
                <a href="order.php" class="btn-order"><i class="fas fa-utensils"></i> Order Now</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></span>
                                <div class="order-time">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php 
                                $statusLabels = [
                                    'pending' => 'Pending',
                                    'preparing' => 'Preparing',
                                    'ready' => 'Ready for Pickup!',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled'
                                ];
                                echo $statusLabels[$order['status']] ?? ucfirst($order['status']);
                                ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <div class="order-items">
                                <h4><i class="fas fa-list"></i> Items:</h4>
                                <p><?php echo htmlspecialchars($order['items']); ?></p>
                            </div>
                            <div class="order-total">
                                <p>Total Amount:</p>
                                <span class="amount">$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
