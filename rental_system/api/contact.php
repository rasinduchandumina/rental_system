<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        getContactInquiries($db);
        break;
    case 'POST':
        createContactInquiry($db);
        break;
    case 'DELETE':
        deleteContactInquiry($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getContactInquiries($db) {
    try {
        $query = "SELECT * FROM contact_inquiries ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'inquiries' => $inquiries
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching inquiries: ' . $e->getMessage()
        ]);
    }
}

function createContactInquiry($db) {
    try {
        // Handle both JSON and form data
        if(isset($_POST['firstName'])) {
            $firstName = $_POST['firstName'];
            $lastName = $_POST['lastName'];
            $email = $_POST['email'];
            $phone = $_POST['phone'] ?? '';
            $subject = $_POST['subject'];
            $message = $_POST['message'];
            $name = $firstName . ' ' . $lastName;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            $firstName = $data['firstName'];
            $lastName = $data['lastName'];
            $email = $data['email'];
            $phone = $data['phone'] ?? '';
            $subject = $data['subject'];
            $message = $data['message'];
            $name = $firstName . ' ' . $lastName;
        }
        
        $query = "INSERT INTO contact_inquiries (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Inquiry submitted successfully',
                'inquiry_id' => $db->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit inquiry']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error submitting inquiry: ' . $e->getMessage()
        ]);
    }
}

function deleteContactInquiry($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            echo json_encode(['success' => false, 'message' => 'Inquiry ID required']);
            return;
        }
        
        $query = "DELETE FROM contact_inquiries WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Inquiry deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete inquiry']);
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting inquiry: ' . $e->getMessage()
        ]);
    }
}
?>