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
        getItems($db);
        break;
    case 'POST':
        createItem($db);
        break;
    case 'PUT':
        updateItem($db);
        break;
    case 'DELETE':
        deleteItem($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getItems($db) {
    try {
        $query = "SELECT i.*, c.name as category_name 
                 FROM items i 
                 LEFT JOIN categories c ON i.category_id = c.id 
                 ORDER BY i.name";
        
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

function createItem($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "INSERT INTO items (name, description, category_id, price_per_day, image_url, available_quantity, total_quantity, specifications) 
                 VALUES (:name, :description, :category_id, :price_per_day, :image_url, :available_quantity, :total_quantity, :specifications)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':price_per_day', $data['price_per_day']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':available_quantity', $data['available_quantity']);
        $stmt->bindParam(':total_quantity', $data['total_quantity']);
        $stmt->bindParam(':specifications', $data['specifications']);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item created successfully',
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

function updateItem($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "UPDATE items SET 
                 name = :name,
                 description = :description,
                 category_id = :category_id,
                 price_per_day = :price_per_day,
                 image_url = :image_url,
                 available_quantity = :available_quantity,
                 total_quantity = :total_quantity,
                 specifications = :specifications,
                 updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':price_per_day', $data['price_per_day']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':available_quantity', $data['available_quantity']);
        $stmt->bindParam(':total_quantity', $data['total_quantity']);
        $stmt->bindParam(':specifications', $data['specifications']);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item updated successfully'
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

function deleteItem($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'Item ID required']);
            return;
        }
        
        $query = "DELETE FROM items WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Item deleted successfully'
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