<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get filter
$status_filter = $_GET['status'] ?? 'active';

// Get orders
if($status_filter === 'active') {
    $query = "SELECT o.*, u.full_name, u.role as user_role,
              GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
              FROM orders o 
              JOIN users u ON o.user_id = u.id
              JOIN order_items oi ON o.id = oi.order_id
              JOIN food_items fi ON oi.food_item_id = fi.id
              WHERE o.status IN ('pending', 'preparing', 'ready')
              GROUP BY o.id
              ORDER BY o.created_at DESC";
} else {
    $query = "SELECT o.*, u.full_name, u.role as user_role,
              GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
              FROM orders o 
              JOIN users u ON o.user_id = u.id
              JOIN order_items oi ON o.id = oi.order_id
              JOIN food_items fi ON oi.food_item_id = fi.id
              GROUP BY o.id
              ORDER BY o.created_at DESC
              LIMIT 100";
}

$stmt = $db->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Canteen Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .search-order {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-order h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
        }
        
        .search-form input {
            flex: 1;
            max-width: 200px;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        .search-form button {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .order-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .order-tab {
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            font-size: 1rem;
        }
        
        .order-tab:hover,
        .order-tab.active {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
        }
        
        .search-result {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .search-result.found {
            border-left: 4px solid #27ae60;
        }
        
        .search-result.not-found {
            border-left: 4px solid #e74c3c;
        }
        
        .order-code {
            font-size: 1.25rem;
            font-weight: bold;
            color: #e74c3c;
            letter-spacing: 2px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .role-teacher {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .role-student {
            background: #fff3e0;
            color: #f39c12;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-receipt"></i> Orders Management</h1>
            <p>View and manage all orders</p>
        </div>
        
        <!-- Search by Order Code -->
        <div class="search-order">
            <h3><i class="fas fa-search"></i> Search Order by Code</h3>
            <div class="search-form">
                <input type="text" id="orderCodeSearch" placeholder="Enter 5-digit code" maxlength="5">
                <button onclick="searchOrder()">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div id="searchResult"></div>
        </div>
        
        <div class="order-tabs">
            <a href="?status=active" class="order-tab <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Active Orders
            </a>
            <a href="?status=all" class="order-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All Orders
            </a>
        </div>
        
        <div class="activity-section">
            <div class="table-container">
                <?php if(empty($orders)): ?>
                    <p class="no-data">No orders found.</p>
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
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                                <tr>
                                    <td><span class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></span></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><span class="role-badge role-<?php echo $order['user_role']; ?>"><?php echo ucfirst($order['user_role']); ?></span></td>
                                    <td style="max-width: 250px;"><?php echo htmlspecialchars($order['items']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <?php if($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
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
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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
                resultDiv.innerHTML = '<div class="search-result not-found"><p style="color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Please enter a valid 5-digit code</p></div>';
                return;
            }
            
            try {
                const response = await fetch(`../api/orders.php?code=${code}`);
                const data = await response.json();
                
                if(data.success && data.orders.length > 0) {
                    const order = data.orders[0];
                    let actionButtons = '';
                    
                    if(order.status !== 'completed' && order.status !== 'cancelled') {
                        if(order.status === 'pending') {
                            actionButtons += `<button onclick="updateOrderStatus(${order.id}, 'preparing')" class="btn-small btn-info"><i class="fas fa-fire"></i> Start Preparing</button> `;
                        }
                        if(order.status === 'preparing') {
                            actionButtons += `<button onclick="updateOrderStatus(${order.id}, 'ready')" class="btn-small btn-success"><i class="fas fa-check"></i> Mark Ready</button> `;
                        }
                        if(order.status === 'ready') {
                            actionButtons += `<button onclick="updateOrderStatus(${order.id}, 'completed')" class="btn-small btn-primary"><i class="fas fa-handshake"></i> Mark Completed (Given)</button> `;
                        }
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="search-result found">
                            <h4><i class="fas fa-check-circle" style="color: #27ae60;"></i> Order Found: <span class="order-code">${order.order_code}</span></h4>
                            <p><strong>Customer:</strong> ${order.full_name} <span class="role-badge role-${order.user_role}">${order.user_role}</span></p>
                            <p><strong>Items:</strong> ${order.items}</p>
                            <p><strong>Total:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                            <p><strong>Status:</strong> <span class="status status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></p>
                            <div style="margin-top: 1rem;">${actionButtons}</div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<div class="search-result not-found"><p style="color: #e74c3c;"><i class="fas fa-times-circle"></i> Order not found</p></div>';
                }
            } catch(error) {
                resultDiv.innerHTML = '<div class="search-result not-found"><p style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Error searching for order</p></div>';
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
