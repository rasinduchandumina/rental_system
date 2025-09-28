<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle order status updates
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'update_status') {
        $db->beginTransaction();
        try {
            $query = "UPDATE rentals SET status = :status";
            $params = [':status' => $_POST['status'], ':id' => $_POST['rental_id']];
            
            if($_POST['status'] == 'returned') {
                $query .= ", actual_return_date = CURRENT_DATE";
                // Update item availability
                $query2 = "UPDATE items SET available_quantity = available_quantity + (SELECT quantity FROM rentals WHERE id = :rental_id) WHERE id = (SELECT item_id FROM rentals WHERE id = :rental_id2)";
                $stmt2 = $db->prepare($query2);
                $stmt2->execute([':rental_id' => $_POST['rental_id'], ':rental_id2' => $_POST['rental_id']]);
            } elseif($_POST['status'] == 'cancelled') {
                // Return items to inventory if cancelled
                $query2 = "UPDATE items SET available_quantity = available_quantity + (SELECT quantity FROM rentals WHERE id = :rental_id) WHERE id = (SELECT item_id FROM rentals WHERE id = :rental_id2)";
                $stmt2 = $db->prepare($query2);
                $stmt2->execute([':rental_id' => $_POST['rental_id'], ':rental_id2' => $_POST['rental_id']]);
            }
            
            $query .= " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $db->commit();
            $success = "Order status updated successfully!";
        } catch(Exception $e) {
            $db->rollback();
            $error = "Error updating order: " . $e->getMessage();
        }
    }
}

// Handle order deletion
if(isset($_GET['delete_order'])) {
    try {
        $db->beginTransaction();
        
        $orderId = $_GET['delete_order'];
        
        // Get order details before deletion to return items to inventory
        $query = "SELECT item_id, quantity, status FROM rentals WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();
        
        if($order && in_array($order['status'], ['pending', 'confirmed', 'ongoing'])) {
            // Return items to inventory
            $query2 = "UPDATE items SET available_quantity = available_quantity + :quantity WHERE id = :item_id";
            $stmt2 = $db->prepare($query2);
            $stmt2->execute([':quantity' => $order['quantity'], ':item_id' => $order['item_id']]);
        }
        
        // Delete the rental order
        $query = "DELETE FROM rentals WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $orderId]);
        
        $db->commit();
        $success = "Order deleted successfully and items returned to inventory!";
        
    } catch(Exception $e) {
        $db->rollback();
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Get orders with filters
$whereClause = '';
$params = [];

if(isset($_GET['status']) && !empty($_GET['status'])) {
    $whereClause .= " AND r.status = :status";
    $params[':status'] = $_GET['status'];
}

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $whereClause .= " AND (u.full_name LIKE :search OR i.name LIKE :search2)";
    $params[':search'] = '%' . $_GET['search'] . '%';
    $params[':search2'] = '%' . $_GET['search'] . '%';
}

try {
    $query = "SELECT r.*, i.name as item_name, i.price_per_day, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone 
             FROM rentals r 
             JOIN items i ON r.item_id = i.id 
             JOIN users u ON r.customer_id = u.id 
             WHERE 1=1 $whereClause
             ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Error loading orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Tool Rental System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Orders Management</h1>
            <p>Manage customer rental orders</p>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="search">Search Orders</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search" name="search" placeholder="Search by customer or item..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo ($_GET['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="ongoing" <?php echo ($_GET['status'] ?? '') == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="returned" <?php echo ($_GET['status'] ?? '') == 'returned' ? 'selected' : ''; ?>>Returned</option>
                            <option value="cancelled" <?php echo ($_GET['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Orders List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-shopping-cart"></i> Rental Orders (<?php echo count($rentals); ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <?php if(empty($rentals)): ?>
                        <p>No orders found matching your criteria.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Item</th>
                                    <th>Dates</th>
                                    <th>Quantity</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($rentals as $rental): ?>
                                    <tr>
                                        <td><strong>#<?php echo $rental['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rental['customer_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($rental['customer_email']); ?></small>
                                            <?php if($rental['customer_phone']): ?>
                                                <br><small><?php echo htmlspecialchars($rental['customer_phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rental['item_name']); ?></strong><br>
                                            <small>$<?php echo number_format($rental['price_per_day'], 2); ?>/day</small>
                                        </td>
                                        <td>
                                            <strong>From:</strong> <?php echo date('M d, Y', strtotime($rental['rental_date'])); ?><br>
                                            <strong>To:</strong> <?php echo date('M d, Y', strtotime($rental['return_date'])); ?>
                                            <?php if($rental['actual_return_date']): ?>
                                                <br><strong>Returned:</strong> <?php echo date('M d, Y', strtotime($rental['actual_return_date'])); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $rental['quantity']; ?></td>
                                        <td><strong>$<?php echo number_format($rental['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="status status-<?php echo $rental['status']; ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn btn-warning btn-small" onclick="updateOrderStatus(<?php echo $rental['id']; ?>, '<?php echo $rental['status']; ?>')">
                                                    <i class="fas fa-edit"></i> Status
                                                </button>
                                                <button class="btn btn-danger btn-small" onclick="confirmDeleteOrder(<?php echo $rental['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
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
                <input type="hidden" id="modal_rental_id" name="rental_id">
                
                <div class="form-group">
                    <label for="modal_status">New Status</label>
                    <select id="modal_status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="returned">Returned</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> Marking as "Returned" or "Cancelled" will return the items to inventory.
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        function updateOrderStatus(rentalId, currentStatus) {
            document.getElementById('modal_rental_id').value = rentalId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function confirmDeleteOrder(orderId) {
            if(confirm('Are you sure you want to delete this order?\n\nThis action cannot be undone. Items will be returned to inventory if the order was active.')) {
                window.location.href = '?delete_order=' + orderId;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .table-actions { 
            display: flex; 
            gap: 0.3rem; 
            flex-wrap: wrap; 
            justify-content: center;
        }
        .btn-small { 
            padding: 0.4rem 0.6rem; 
            font-size: 0.8rem;
            min-width: 70px;
        }
        
        @media (max-width: 768px) {
            .table-actions { flex-direction: column; }
        }
    </style>
</body>
</html>
