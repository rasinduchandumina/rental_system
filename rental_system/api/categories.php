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
        getCategories($db);
        break;
    case 'POST':
        createCategory($db);
        break;
    case 'PUT':
        updateCategory($db);
        break;
    case 'DELETE':
        deleteCategory($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getCategories($db) {
    try {
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching categories: ' . $e->getMessage()
        ]);
    }
}

function createCategory($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'category_id' => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create category']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating category: ' . $e->getMessage()
        ]);
    }
}

function updateCategory($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update category']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating category: ' . $e->getMessage()
        ]);
    }
}

function deleteCategory($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'Category ID required']);
            return;
        }
        
        $query = "DELETE FROM categories WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting category: ' . $e->getMessage()
        ]);
    }
}
?>