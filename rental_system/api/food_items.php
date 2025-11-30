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
        getFoodItems($db);
        break;
    case 'POST':
        createFoodItem($db);
        break;
    case 'PUT':
        updateFoodItem($db);
        break;
    case 'DELETE':
        deleteFoodItem($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getFoodItems($db) {
    try {
        $query = "SELECT fi.*, c.name as category_name 
                 FROM food_items fi 
                 LEFT JOIN categories c ON fi.category_id = c.id 
                 ORDER BY c.name, fi.name";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching items: ' . $e->getMessage()
        ]);
    }
}

function createFoodItem($db) {
    try {
        session_start();
        if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "INSERT INTO food_items (name, description, category_id, price, image_url, available) 
                 VALUES (:name, :description, :category_id, :price, :image_url, :available)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $available = isset($data['available']) ? $data['available'] : 1;
        $stmt->bindParam(':available', $available);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Food item created successfully',
                'item_id' => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create item']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating item: ' . $e->getMessage()
        ]);
    }
}

function updateFoodItem($db) {
    try {
        session_start();
        if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Item ID required']);
            return;
        }
        
        // Check if this is just a toggle availability request
        if(isset($data['toggle_availability'])) {
            $query = "UPDATE food_items SET available = :available, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':available', $data['available']);
        } else {
            // Full update
            $query = "UPDATE food_items SET 
                     name = :name,
                     description = :description,
                     category_id = :category_id,
                     price = :price,
                     image_url = :image_url,
                     available = :available,
                     updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':image_url', $data['image_url']);
            $stmt->bindParam(':available', $data['available']);
        }
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Food item updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update item']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating item: ' . $e->getMessage()
        ]);
    }
}

function deleteFoodItem($db) {
    try {
        session_start();
        if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'Item ID required']);
            return;
        }
        
        $query = "DELETE FROM food_items WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Food item deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting item: ' . $e->getMessage()
        ]);
    }
}
?>
