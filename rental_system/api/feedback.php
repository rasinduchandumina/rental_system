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
        getFeedback($db);
        break;
    case 'POST':
        createFeedback($db);
        break;
    case 'DELETE':
        deleteFeedback($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getFeedback($db) {
    try {
        $query = "SELECT f.*, u.full_name as customer_name, u.email as customer_email, i.name as item_name 
                 FROM feedback f 
                 LEFT JOIN users u ON f.customer_id = u.id 
                 LEFT JOIN items i ON f.item_id = i.id 
                 ORDER BY f.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'feedback' => $feedback
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching feedback: ' . $e->getMessage()
        ]);
    }
}

function createFeedback($db) {
    try {
        // Handle both JSON and form data
        if(isset($_POST['feedbackName'])) {
            $customerName = $_POST['feedbackName'];
            $customerEmail = $_POST['feedbackEmail'];
            $rating = $_POST['rating'];
            $message = $_POST['message'];
            $itemId = $_POST['itemId'] ?? null;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            $customerName = $data['feedbackName'];
            $customerEmail = $data['feedbackEmail'];
            $rating = $data['rating'];
            $message = $data['message'];
            $itemId = $data['itemId'] ?? null;
        }
        
        // Check if customer exists, if not create one
        $query = "SELECT id FROM users WHERE email = :email AND role = 'customer'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $customerEmail);
        $stmt->execute();
        $customer = $stmt->fetch();
        
        if(!$customer) {
            // Create new customer
            $query = "INSERT INTO users (username, email, full_name, role) VALUES (:username, :email, :full_name, 'customer')";
            $stmt = $db->prepare($query);
            $username = strtolower(str_replace(' ', '_', $customerName)) . '_' . time();
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $customerEmail);
            $stmt->bindParam(':full_name', $customerName);
            $stmt->execute();
            $customerId = $db->lastInsertId();
        } else {
            $customerId = $customer['id'];
        }
        
        // Create feedback
        $query = "INSERT INTO feedback (customer_id, item_id, rating, message) VALUES (:customer_id, :item_id, :rating, :message)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':item_id', $itemId);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':message', $message);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully',
                'feedback_id' => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error submitting feedback: ' . $e->getMessage()
        ]);
    }
}

function deleteFeedback($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'Feedback ID required']);
            return;
        }
        
        $query = "DELETE FROM feedback WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Feedback deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete feedback']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting feedback: ' . $e->getMessage()
        ]);
    }
}
?>