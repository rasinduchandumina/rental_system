<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
try {
    // Total food items
    $query = "SELECT COUNT(*) as total FROM food_items";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalItems = $stmt->fetch()['total'];
    
    // Total orders today
    $query = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $ordersToday = $stmt->fetch()['total'];
    
    // Pending orders
    $query = "SELECT COUNT(*) as total FROM orders WHERE status IN ('pending', 'preparing')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pendingOrders = $stmt->fetch()['total'];
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalUsers = $stmt->fetch()['total'];
    
    // Revenue today
    $query = "SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $revenueToday = $stmt->fetch()['total'] ?: 0;
    
    // Recent orders
    $query = "SELECT o.*, u.full_name, u.role as user_role,
              GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
              FROM orders o 
              JOIN users u ON o.user_id = u.id
              JOIN order_items oi ON o.id = oi.order_id
              JOIN food_items fi ON oi.food_item_id = fi.id
              WHERE o.status IN ('pending', 'preparing', 'ready')
              GROUP BY o.id
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
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #f39c12);">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalItems; ?></h3>
                    <p>Food Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2ecc71);">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $ordersToday; ?></h3>
                    <p>Orders Today</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e74c3c);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pendingOrders; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #3498db);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($revenueToday, 2); ?></h3>
                    <p>Revenue Today</p>
                </div>
            </div>
        </div>
        
        <!-- Order Search -->
        <div class="activity-section">
            <h2><i class="fas fa-search"></i> Search Order by Code</h2>
            <div class="search-order-form">
                <input type="text" id="orderCodeSearch" placeholder="Enter 5-digit order code" maxlength="5" pattern="[0-9]{5}">
                <button onclick="searchOrder()" class="btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div id="searchResult" style="margin-top: 1rem;"></div>
        </div>
        
        <!-- Recent Orders -->
        <div class="activity-section">
            <h2><i class="fas fa-clock"></i> Active Orders</h2>
            <div class="table-container">
                <?php if(empty($recentOrders)): ?>
                    <p class="no-data">No active orders at the moment.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Customer</th>
                                <th>Role</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentOrders as $order): ?>
                                <tr id="order-<?php echo $order['id']; ?>">
                                    <td><span class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></span></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><span class="role-badge role-<?php echo $order['user_role']; ?>"><?php echo ucfirst($order['user_role']); ?></span></td>
                                    <td style="max-width: 200px;"><?php echo htmlspecialchars($order['items']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($order['status'] === 'pending'): ?>
                                                <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'preparing')" class="btn-small btn-info" title="Start Preparing">
                                                    <i class="fas fa-fire"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if($order['status'] === 'preparing'): ?>
                                                <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'ready')" class="btn-small btn-success" title="Mark Ready">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if($order['status'] === 'ready'): ?>
                                                <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')" class="btn-small btn-primary" title="Mark Completed">
                                                    <i class="fas fa-handshake"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')" class="btn-small btn-danger" title="Cancel">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="js/admin.js"></script>
    <script>
        async function searchOrder() {
            const code = document.getElementById('orderCodeSearch').value;
            const resultDiv = document.getElementById('searchResult');
            
            if(code.length !== 5) {
                resultDiv.innerHTML = '<p style="color: #e74c3c;">Please enter a valid 5-digit code</p>';
                return;
            }
            
            try {
                const response = await fetch(`../api/orders.php?code=${code}`);
                const data = await response.json();
                
                if(data.success && data.orders.length > 0) {
                    const order = data.orders[0];
                    resultDiv.innerHTML = `
                        <div class="order-result">
                            <h4>Order Found: <span class="order-code">${order.order_code}</span></h4>
                            <p><strong>Customer:</strong> ${order.full_name} (${order.user_role})</p>
                            <p><strong>Items:</strong> ${order.items}</p>
                            <p><strong>Total:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                            <p><strong>Status:</strong> <span class="status status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
                            ${order.status !== 'completed' && order.status !== 'cancelled' ? `
                                <button onclick="updateOrderStatus(${order.id}, 'completed')" class="btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-check"></i> Mark as Completed (Given)
                                </button>
                            ` : ''}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<p style="color: #e74c3c;">Order not found</p>';
                }
            } catch(error) {
                resultDiv.innerHTML = '<p style="color: #e74c3c;">Error searching for order</p>';
            }
        }
        
        async function updateOrderStatus(orderId, status) {
            try {
                const response = await fetch('../api/orders.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId, status: status })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update order');
                }
            } catch(error) {
                alert('Error updating order');
            }
        }
        
        // Allow Enter key to search
        document.getElementById('orderCodeSearch').addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                searchOrder();
            }
        });
    </script>
</body>
</html>