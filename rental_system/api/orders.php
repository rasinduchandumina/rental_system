<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getOrders($db);
        break;
    case 'POST':
        createOrder($db);
        break;
    case 'PUT':
        updateOrder($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function generateOrderCode($db) {
    // Generate a unique 5-digit code
    $maxRetries = 100;
    $retries = 0;
    
    do {
        $code = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $query = "SELECT id FROM orders WHERE order_code = :code AND status NOT IN ('completed', 'cancelled')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        $retries++;
        
        if ($retries >= $maxRetries) {
            // If we've exceeded retries, use timestamp-based code as fallback
            $code = substr(str_pad(time() % 100000, 5, '0', STR_PAD_LEFT), 0, 5);
            break;
        }
    } while($stmt->fetch());
    
    return $code;
}

function getOrders($db) {
    try {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }
        
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        // Admin can see all orders, others see only their own
        if($role === 'admin') {
            // Check if searching by order code
            if(isset($_GET['code'])) {
                $code = $_GET['code'];
                $query = "SELECT o.*, u.full_name, u.role as user_role,
                          GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id
                          JOIN order_items oi ON o.id = oi.order_id
                          JOIN food_items fi ON oi.food_item_id = fi.id
                          WHERE o.order_code = :code
                          GROUP BY o.id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':code', $code);
            } else {
                // Get status filter
                $status = $_GET['status'] ?? 'active';
                
                if($status === 'active') {
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
                              LIMIT 50";
                }
                $stmt = $db->prepare($query);
            }
        } else {
            $query = "SELECT o.*, 
                      GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id
                      JOIN food_items fi ON oi.food_item_id = fi.id
                      WHERE o.user_id = :user_id
                      GROUP BY o.id
                      ORDER BY o.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'orders' => $orders
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching orders: ' . $e->getMessage()
        ]);
    }
}

function createOrder($db) {
    try {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
            return;
        }
        
        // Only teachers and students can place orders
        if($_SESSION['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Admins cannot place orders']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'No items in order']);
            return;
        }
        
        $db->beginTransaction();
        
        // Calculate total
        $total = 0;
        foreach($data['items'] as $item) {
            $query = "SELECT price FROM food_items WHERE id = :id AND available = 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $item['id']);
            $stmt->execute();
            $food = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$food) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Invalid or unavailable item']);
                return;
            }
            
            $total += $food['price'] * $item['quantity'];
        }
        
        // Generate unique order code
        $order_code = generateOrderCode($db);
        
        // Create order
        $query = "INSERT INTO orders (order_code, user_id, total_amount, status) VALUES (:code, :user_id, :total, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $order_code);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':total', $total);
        $stmt->execute();
        
        $order_id = $db->lastInsertId();
        
        // Add order items
        foreach($data['items'] as $item) {
            $query = "SELECT price FROM food_items WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $item['id']);
            $stmt->execute();
            $food = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $query = "INSERT INTO order_items (order_id, food_item_id, quantity, price) VALUES (:order_id, :food_id, :qty, :price)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':food_id', $item['id']);
            $stmt->bindParam(':qty', $item['quantity']);
            $stmt->bindParam(':price', $food['price']);
            $stmt->execute();
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order_id,
            'order_code' => $order_code
        ]);
        
    } catch(Exception $e) {
        if($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Error creating order: ' . $e->getMessage()
        ]);
    }
}

function updateOrder($db) {
    try {
        // Check if admin
        if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(empty($data['order_id']) || empty($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'Order ID and status required']);
            return;
        }
        
        $validStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
        if(!in_array($data['status'], $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            return;
        }
        
        $completed_at = ($data['status'] === 'completed') ? date('Y-m-d H:i:s') : null;
        
        $query = "UPDATE orders SET status = :status, completed_at = :completed_at WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':completed_at', $completed_at);
        $stmt->bindParam(':id', $data['order_id']);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Order updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating order: ' . $e->getMessage()
        ]);
    }
}
?>
