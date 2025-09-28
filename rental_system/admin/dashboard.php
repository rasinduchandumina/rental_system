<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
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
    
    // Recent rentals
    $query = "SELECT r.*, i.name as item_name, u.full_name as customer_name 
             FROM rentals r 
             JOIN items i ON r.item_id = i.id 
             JOIN users u ON r.customer_id = u.id 
             ORDER BY r.created_at DESC 
             LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentRentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent feedback
    $query = "SELECT f.*, u.full_name as customer_name, i.name as item_name 
             FROM feedback f 
             LEFT JOIN users u ON f.customer_id = u.id 
             LEFT JOIN items i ON f.item_id = i.id 
             ORDER BY f.created_at DESC 
             LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentFeedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Error loading dashboard: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tool Rental System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?php echo $_SESSION['admin_name']; ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalItems; ?></h3>
                    <p>Total Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalRentals; ?></h3>
                    <p>Total Rentals</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalCustomers; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-grid">
            <div class="activity-section">
                <h2>Recent Rentals</h2>
                <div class="table-container">
                    <?php if(empty($recentRentals)): ?>
                        <p>No recent rentals found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Item</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentRentals as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($rental['item_name']); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $rental['status']; ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($rental['rental_date'])); ?></td>
                                        <td>$<?php echo number_format($rental['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="activity-section">
                <h2>Recent Feedback</h2>
                <div class="feedback-list">
                    <?php if(empty($recentFeedback)): ?>
                        <p>No recent feedback found.</p>
                    <?php else: ?>
                        <?php foreach($recentFeedback as $feedback): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <strong><?php echo htmlspecialchars($feedback['customer_name'] ?: 'Anonymous'); ?></strong>
                                    <div class="feedback-rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="feedback-date"><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></span>
                                </div>
                                <div class="feedback-message">
                                    <?php echo htmlspecialchars(substr($feedback['message'], 0, 100)); ?>
                                    <?php if(strlen($feedback['message']) > 100): ?>...<?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script src="js/admin.js"></script>
</body>
</html>