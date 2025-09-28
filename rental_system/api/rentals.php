<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getRentals($db);
        break;
    case 'POST':
        createRental($db);
        break;
    case 'PUT':
        updateRental($db);
        break;
    case 'DELETE':
        deleteRental($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getRentals($db) {
    try {
        $query = "SELECT r.*, i.name as item_name, i.price_per_day, u.full_name as customer_name, u.email as customer_email 
                 FROM rentals r 
                 LEFT JOIN items i ON r.item_id = i.id 
                 LEFT JOIN users u ON r.customer_id = u.id 
                 ORDER BY r.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'rentals' => $rentals
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching rentals: ' . $e->getMessage()
        ]);
    }
}

function createRental($db) {
    try {
        // Handle both JSON and form data
        if(isset($_POST['customerName'])) {
            $customerName = $_POST['customerName'];
            $customerEmail = $_POST['customerEmail'];
            $customerPhone = $_POST['customerPhone'] ?? '';
            $itemId = $_POST['itemId'];
            $rentalDate = $_POST['rentalDate'];
            $returnDate = $_POST['returnDate'];
            $quantity = $_POST['quantity'] ?? 1;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            $customerName = $data['customerName'];
            $customerEmail = $data['customerEmail'];
            $customerPhone = $data['customerPhone'] ?? '';
            $itemId = $data['itemId'];
            $rentalDate = $data['rentalDate'];
            $returnDate = $data['returnDate'];
            $quantity = $data['quantity'] ?? 1;
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Check if customer exists, if not create one
        $query = "SELECT id FROM users WHERE email = :email AND role = 'customer'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $customerEmail);
        $stmt->execute();
        $customer = $stmt->fetch();
        
        if(!$customer) {
            // Create new customer
            $query = "INSERT INTO users (username, email, full_name, phone, role) VALUES (:username, :email, :full_name, :phone, 'customer')";
            $stmt = $db->prepare($query);
            $username = strtolower(str_replace(' ', '_', $customerName)) . '_' . time();
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $customerEmail);
            $stmt->bindParam(':full_name', $customerName);
            $stmt->bindParam(':phone', $customerPhone);
            $stmt->execute();
            $customerId = $db->lastInsertId();
        } else {
            $customerId = $customer['id'];
        }
        
        // Get item details and calculate total
        $query = "SELECT price_per_day, available_quantity FROM items WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $itemId);
        $stmt->execute();
        $item = $stmt->fetch();
        
        if(!$item || $item['available_quantity'] < $quantity) {
            throw new Exception('Item not available in requested quantity');
        }
        
        // Calculate total amount
        $days = (strtotime($returnDate) - strtotime($rentalDate)) / (60 * 60 * 24);
        $totalAmount = $days * $item['price_per_day'] * $quantity;
        
        // Create rental
        $query = "INSERT INTO rentals (customer_id, item_id, rental_date, return_date, quantity, total_amount, status) 
                 VALUES (:customer_id, :item_id, :rental_date, :return_date, :quantity, :total_amount, 'pending')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->bindParam(':rental_date', $rentalDate);
        $stmt->bindParam(':return_date', $returnDate);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':total_amount', $totalAmount);
        
        if($stmt->execute()) {
            // Update item availability
            $newAvailability = $item['available_quantity'] - $quantity;
            $query = "UPDATE items SET available_quantity = :available_quantity WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':available_quantity', $newAvailability);
            $stmt->bindParam(':id', $itemId);
            $stmt->execute();
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Rental request created successfully',
                'rental_id' => $db->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to create rental');
        }
        
    } catch(Exception $e) {
        $db->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error creating rental: ' . $e->getMessage()
        ]);
    }
}

function updateRental($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "UPDATE rentals SET 
                 status = :status,
                 actual_return_date = :actual_return_date
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':actual_return_date', $data['actual_return_date']);
        
        if($stmt->execute()) {
            // If status is returned, update item availability
            if($data['status'] === 'returned') {
                $query = "SELECT item_id, quantity FROM rentals WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data['id']);
                $stmt->execute();
                $rental = $stmt->fetch();
                
                if($rental) {
                    $query = "UPDATE items SET available_quantity = available_quantity + :quantity WHERE id = :item_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':quantity', $rental['quantity']);
                    $stmt->bindParam(':item_id', $rental['item_id']);
                    $stmt->execute();
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Rental updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update rental']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating rental: ' . $e->getMessage()
        ]);
    }
}

function deleteRental($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'Rental ID required']);
            return;
        }
        
        $query = "DELETE FROM rentals WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Rental deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete rental']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting rental: ' . $e->getMessage()
        ]);
    }
}
?>