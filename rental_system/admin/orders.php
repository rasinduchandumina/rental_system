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

// Handle order status updates
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'update_status') {
        try {
            $query = "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':status' => $_POST['status'], ':id' => $_POST['order_id']]);
            $success = "Order status updated successfully!";
        } catch(Exception $e) {
            $error = "Error updating order: " . $e->getMessage();
        }
    } elseif($_POST['action'] == 'mark_given') {
        try {
            $query = "UPDATE orders SET status = 'given', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $_POST['order_id']]);
            $success = "Order marked as given and removed from queue!";
        } catch(Exception $e) {
            $error = "Error updating order: " . $e->getMessage();
        }
    }
}

// Handle order deletion
if(isset($_GET['delete_order'])) {
    try {
        $query = "DELETE FROM orders WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $_GET['delete_order']]);
        $success = "Order deleted successfully!";
    } catch(Exception $e) {
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchCode = isset($_GET['order_code']) ? trim($_GET['order_code']) : '';

// Validate order code format (must be exactly 5 digits)
if($searchCode && !preg_match('/^[0-9]{5}$/', $searchCode)) {
    $searchCode = '';
    $error = "Invalid order code format. Please enter exactly 5 digits.";
}

// Build query with filters
$whereClause = "WHERE 1=1";
$params = [];

if($statusFilter) {
    $whereClause .= " AND o.status = :status";
    $params[':status'] = $statusFilter;
}

if($searchCode) {
    $whereClause .= " AND o.order_code = :order_code";
    $params[':order_code'] = $searchCode;
}

// Get orders
try {
    $query = "SELECT o.*, u.full_name as customer_name, u.role as customer_role, u.email as customer_email,
              (SELECT GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') 
               FROM order_items oi 
               JOIN food_items fi ON oi.food_item_id = fi.id 
               WHERE oi.order_id = o.id) as items
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              $whereClause
              ORDER BY 
                CASE o.status 
                    WHEN 'pending' THEN 1 
                    WHEN 'preparing' THEN 2 
                    WHEN 'ready' THEN 3 
                    WHEN 'given' THEN 4 
                    WHEN 'cancelled' THEN 5 
                END,
                o.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Error loading orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Canteen System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
            <p>Search orders by code, update status and manage the queue</p>
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
        
        <!-- Search by Order Code -->
        <div class="card order-search-card">
            <div class="card-header">
                <h3><i class="fas fa-search"></i> Search by Order Code</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="order-code-search">
                    <div class="search-input-group">
                        <input type="text" name="order_code" id="orderCodeInput" 
                               placeholder="Enter 5-digit order code" 
                               value="<?php echo htmlspecialchars($searchCode); ?>"
                               maxlength="5" pattern="[0-9]{5}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if($searchCode): ?>
                            <a href="orders.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <?php if($searchCode && count($orders) == 1): ?>
                    <div class="found-order">
                        <h4>Order Found!</h4>
                        <div class="order-details">
                            <div class="order-code-display"><?php echo htmlspecialchars($orders[0]['order_code']); ?></div>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($orders[0]['customer_name']); ?> (<?php echo ucfirst($orders[0]['customer_role']); ?>)</p>
                            <p><strong>Items:</strong> <?php echo htmlspecialchars($orders[0]['items']); ?></p>
                            <p><strong>Total:</strong> Rs. <?php echo number_format($orders[0]['total_amount'], 2); ?></p>
                            <p><strong>Status:</strong> <span class="status status-<?php echo $orders[0]['status']; ?>"><?php echo ucfirst($orders[0]['status']); ?></span></p>
                            
                            <?php if($orders[0]['status'] != 'given' && $orders[0]['status'] != 'cancelled'): ?>
                                <form method="POST" action="" style="margin-top: 15px;">
                                    <input type="hidden" name="action" value="mark_given">
                                    <input type="hidden" name="order_id" value="<?php echo $orders[0]['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-large">
                                        <i class="fas fa-check"></i> Mark as Given & Remove from Queue
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif($searchCode && count($orders) == 0): ?>
                    <div class="not-found">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Order code <strong><?php echo htmlspecialchars($searchCode); ?></strong> not found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="preparing" <?php echo $statusFilter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="ready" <?php echo $statusFilter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="given" <?php echo $statusFilter == 'given' ? 'selected' : ''; ?>>Given</option>
                            <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Orders List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Order Queue (<?php echo count($orders); ?> orders)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <?php if(empty($orders) && !$searchCode): ?>
                        <p>No orders found.</p>
                    <?php elseif(!$searchCode): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order Code</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orders as $order): ?>
                                    <tr class="order-row status-row-<?php echo $order['status']; ?>">
                                        <td>
                                            <span class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                            <small class="role-badge role-<?php echo $order['customer_role']; ?>">
                                                <?php echo ucfirst($order['customer_role']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['items']); ?></td>
                                        <td><strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, h:i A', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <?php if($order['status'] != 'given' && $order['status'] != 'cancelled'): ?>
                                                    <button class="btn btn-success btn-small" onclick="markGiven(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-check"></i> Given
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-warning btn-small" onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                    <i class="fas fa-edit"></i> Status
                                                </button>
                                                <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
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
        </div>
    </main>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Order Status</h2>
                <button class="close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" id="modal_order_id" name="order_id">
                
                <div class="form-group">
                    <label for="modal_status">New Status</label>
                    <select id="modal_status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="preparing">Preparing</option>
                        <option value="ready">Ready for Pickup</option>
                        <option value="given">Given</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Mark as Given Form (hidden) -->
    <form id="markGivenForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="mark_given">
        <input type="hidden" id="givenOrderId" name="order_id">
    </form>
    
    <script src="js/admin.js"></script>
    <script>
        function updateStatus(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function markGiven(orderId) {
            if(confirm('Mark this order as given and remove from queue?')) {
                document.getElementById('givenOrderId').value = orderId;
                document.getElementById('markGivenForm').submit();
            }
        }
        
        function confirmDelete(orderId) {
            if(confirm('Are you sure you want to delete this order?')) {
                window.location.href = '?delete_order=' + orderId;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .order-search-card {
            border: 2px solid #667eea;
        }
        
        .order-code-search {
            display: flex;
            align-items: center;
        }
        
        .search-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-input-group input {
            font-size: 1.5rem;
            padding: 15px 20px;
            width: 200px;
            text-align: center;
            letter-spacing: 10px;
            font-weight: bold;
            border: 2px solid #667eea;
            border-radius: 8px;
        }
        
        .found-order {
            margin-top: 20px;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 10px;
            border: 2px solid #27ae60;
        }
        
        .order-code-display {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 15px;
            margin: 10px 0;
        }
        
        .not-found {
            margin-top: 20px;
            padding: 20px;
            background: #ffebee;
            border-radius: 10px;
            border: 2px solid #e74c3c;
            text-align: center;
            color: #c0392b;
        }
        
        .not-found i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .order-code {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            letter-spacing: 3px;
        }
        
        .status-row-pending { border-left: 4px solid #f39c12; }
        .status-row-preparing { border-left: 4px solid #3498db; }
        .status-row-ready { border-left: 4px solid #27ae60; }
        .status-row-given { border-left: 4px solid #95a5a6; opacity: 0.7; }
        .status-row-cancelled { border-left: 4px solid #e74c3c; opacity: 0.5; }
        
        .role-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        .role-student { background: #3498db; color: white; }
        .role-teacher { background: #9b59b6; color: white; }
        
        .table-actions { 
            display: flex; 
            gap: 0.3rem; 
            flex-wrap: wrap; 
            justify-content: center;
        }
        .btn-small { 
            padding: 0.4rem 0.6rem; 
            font-size: 0.8rem;
        }
        .btn-large {
            padding: 15px 30px;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .table-actions { flex-direction: column; }
            .search-input-group { flex-direction: column; }
            .search-input-group input { width: 100%; }
        }
    </style>
</body>
</html>
