<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle inquiry status updates
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'update_status') {
        $query = "UPDATE contact_inquiries SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':status' => $_POST['status'],
            ':id' => $_POST['inquiry_id']
        ]);
        $success = "Inquiry status updated successfully!";
    }
}

// Handle inquiry deletion
if(isset($_GET['delete_inquiry'])) {
    $query = "DELETE FROM contact_inquiries WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_GET['delete_inquiry']]);
    $success = "Inquiry deleted successfully!";
}

// Get inquiries with filters
$whereClause = '';
$params = [];

if(isset($_GET['status']) && !empty($_GET['status'])) {
    $whereClause .= " AND status = :status";
    $params[':status'] = $_GET['status'];
}

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $whereClause .= " AND (name LIKE :search OR email LIKE :search2 OR subject LIKE :search3 OR message LIKE :search4)";
    $params[':search'] = '%' . $_GET['search'] . '%';
    $params[':search2'] = '%' . $_GET['search'] . '%';
    $params[':search3'] = '%' . $_GET['search'] . '%';
    $params[':search4'] = '%' . $_GET['search'] . '%';
}

try {
    $query = "SELECT * FROM contact_inquiries WHERE 1=1 $whereClause ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get stats
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count
              FROM contact_inquiries";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch();
    
} catch(Exception $e) {
    $error = "Error loading inquiries: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Inquiries - Tool Rental System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Contact Inquiries</h1>
            <p>Manage customer inquiries and messages</p>
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
        
        <!-- Inquiry Stats -->
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Inquiries</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['new_count']; ?></h3>
                    <p>New Inquiries</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['read_count']; ?></h3>
                    <p>Read</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['replied_count']; ?></h3>
                    <p>Replied</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="form-group">
                        <label for="search">Search Inquiries</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search" name="search" placeholder="Search by name, email, subject, or message..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Filter by Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="new" <?php echo ($_GET['status'] ?? '') == 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="read" <?php echo ($_GET['status'] ?? '') == 'read' ? 'selected' : ''; ?>>Read</option>
                            <option value="replied" <?php echo ($_GET['status'] ?? '') == 'replied' ? 'selected' : ''; ?>>Replied</option>
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
        
        <!-- Inquiries List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-inbox"></i> Contact Inquiries (<?php echo count($inquiries); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if(empty($inquiries)): ?>
                    <p>No inquiries found matching your criteria.</p>
                <?php else: ?>
                    <div class="inquiries-list">
                        <?php foreach($inquiries as $inquiry): ?>
                            <div class="inquiry-item <?php echo $inquiry['status']; ?>">
                                <div class="inquiry-header">
                                    <div class="inquiry-info">
                                        <h4><?php echo htmlspecialchars($inquiry['name']); ?></h4>
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($inquiry['email']); ?></p>
                                        <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($inquiry['subject']); ?></p>
                                    </div>
                                    
                                    <div class="inquiry-meta">
                                        <span class="status status-<?php echo $inquiry['status']; ?>">
                                            <?php echo ucfirst($inquiry['status']); ?>
                                        </span>
                                        <span class="inquiry-date"><?php echo date('M d, Y H:i', strtotime($inquiry['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="inquiry-actions">
                                        <button class="btn btn-warning btn-small" onclick="updateInquiryStatus(<?php echo $inquiry['id']; ?>, '<?php echo $inquiry['status']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="mailto:<?php echo urlencode($inquiry['email']); ?>?subject=Re: <?php echo urlencode($inquiry['subject']); ?>" class="btn btn-success btn-small">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <button class="btn btn-danger btn-small" onclick="deleteInquiry(<?php echo $inquiry['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="inquiry-message">
                                    <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Inquiry Status</h2>
                <button class="close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" id="modal_inquiry_id" name="inquiry_id">
                
                <div class="form-group">
                    <label for="modal_status">New Status</label>
                    <select id="modal_status" name="status" required>
                        <option value="new">New</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                    </select>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        function updateInquiryStatus(inquiryId, currentStatus) {
            document.getElementById('modal_inquiry_id').value = inquiryId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function deleteInquiry(id) {
            if(confirm('Are you sure you want to delete this inquiry?')) {
                window.location.href = '?delete_inquiry=' + id;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .inquiries-list {
            max-height: none;
        }
        
        .inquiry-item {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s;
        }
        
        .inquiry-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .inquiry-item.new {
            border-left: 4px solid #e74c3c;
        }
        
        .inquiry-item.read {
            border-left: 4px solid #f39c12;
        }
        
        .inquiry-item.replied {
            border-left: 4px solid #27ae60;
        }
        
        .inquiry-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .inquiry-info h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .inquiry-info p {
            color: #666;
            margin: 0.2rem 0;
            font-size: 0.9rem;
        }
        
        .inquiry-info i {
            width: 15px;
            margin-right: 0.5rem;
        }
        
        .inquiry-meta {
            text-align: center;
        }
        
        .inquiry-date {
            display: block;
            color: #999;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .inquiry-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .inquiry-message {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            line-height: 1.6;
            color: #555;
        }
        
        @media (max-width: 768px) {
            .inquiry-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .inquiry-actions {
                justify-content: center;
            }
        }
    </style>
</body>
</html>