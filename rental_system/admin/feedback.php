<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle feedback deletion
if(isset($_GET['delete_feedback'])) {
    $query = "DELETE FROM feedback WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_GET['delete_feedback']]);
    $success = "Feedback deleted successfully!";
}

// Get feedback with filters
$whereClause = '';
$params = [];

if(isset($_GET['rating']) && !empty($_GET['rating'])) {
    $whereClause .= " AND f.rating = :rating";
    $params[':rating'] = $_GET['rating'];
}

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $whereClause .= " AND (u.full_name LIKE :search OR f.message LIKE :search2)";
    $params[':search'] = '%' . $_GET['search'] . '%';
    $params[':search2'] = '%' . $_GET['search'] . '%';
}

try {
    $query = "SELECT f.*, u.full_name as customer_name, u.email as customer_email, i.name as item_name 
             FROM feedback f 
             LEFT JOIN users u ON f.customer_id = u.id 
             LEFT JOIN items i ON f.item_id = i.id 
             WHERE 1=1 $whereClause
             ORDER BY f.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get average rating
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_feedback FROM feedback";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch();
    
} catch(Exception $e) {
    $error = "Error loading feedback: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Tool Rental System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Customer Feedback</h1>
            <p>View and manage customer feedback</p>
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
        
        <!-- Feedback Stats -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['avg_rating'], 1); ?></h3>
                    <p>Average Rating</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_feedback']; ?></h3>
                    <p>Total Feedback</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="search">Search Feedback</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search" name="search" placeholder="Search by customer or message..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Filter by Rating</label>
                        <select id="rating" name="rating">
                            <option value="">All Ratings</option>
                            <option value="5" <?php echo ($_GET['rating'] ?? '') == '5' ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5 stars)</option>
                            <option value="4" <?php echo ($_GET['rating'] ?? '') == '4' ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4 stars)</option>
                            <option value="3" <?php echo ($_GET['rating'] ?? '') == '3' ? 'selected' : ''; ?>>⭐⭐⭐ (3 stars)</option>
                            <option value="2" <?php echo ($_GET['rating'] ?? '') == '2' ? 'selected' : ''; ?>>⭐⭐ (2 stars)</option>
                            <option value="1" <?php echo ($_GET['rating'] ?? '') == '1' ? 'selected' : ''; ?>>⭐ (1 star)</option>
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
        
        <!-- Feedback List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-comments"></i> Customer Feedback (<?php echo count($feedback); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if(empty($feedback)): ?>
                    <p>No feedback found matching your criteria.</p>
                <?php else: ?>
                    <div class="feedback-list">
                        <?php foreach($feedback as $fb): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <div>
                                        <strong><?php echo htmlspecialchars($fb['customer_name'] ?: 'Anonymous'); ?></strong>
                                        <?php if($fb['item_name']): ?>
                                            <small> - <?php echo htmlspecialchars($fb['item_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="feedback-rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $fb['rating'] ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                        <span>(<?php echo $fb['rating']; ?>/5)</span>
                                    </div>
                                    
                                    <div>
                                        <span class="feedback-date"><?php echo date('M d, Y H:i', strtotime($fb['created_at'])); ?></span>
                                        <button class="btn btn-danger btn-small" onclick="deleteFeedback(<?php echo $fb['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="feedback-message">
                                    <?php echo htmlspecialchars($fb['message']); ?>
                                </div>
                                
                                <?php if($fb['customer_email']): ?>
                                    <div class="feedback-contact">
                                        <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($fb['customer_email']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="js/admin.js"></script>
    <script>
        function deleteFeedback(id) {
            if(confirm('Are you sure you want to delete this feedback?')) {
                window.location.href = '?delete_feedback=' + id;
            }
        }
    </script>
</body>
</html>