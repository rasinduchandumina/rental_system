<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Total items
    $query = "SELECT COUNT(*) as total_items FROM items";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalItems = $stmt->fetch()['total_items'];
    
    // Total rentals
    $query = "SELECT COUNT(*) as total_rentals FROM rentals";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalRentals = $stmt->fetch()['total_rentals'];
    
    // Total customers
    $query = "SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalCustomers = $stmt->fetch()['total_customers'];
    
    // Total revenue
    $query = "SELECT SUM(total_amount) as total_revenue FROM rentals WHERE status IN ('confirmed', 'ongoing', 'returned')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalRevenue = $stmt->fetch()['total_revenue'] ?: 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_items' => $totalItems,
            'total_rentals' => $totalRentals,
            'total_customers' => $totalCustomers,
            'total_revenue' => $totalRevenue
        ]
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats: ' . $e->getMessage()
    ]);
}
?>