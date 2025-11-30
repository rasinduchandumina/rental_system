<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$_SESSION['admin_id'] = $_SESSION['user_id'];
$_SESSION['admin_name'] = $_SESSION['user_name'];

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_item':
                $query = "INSERT INTO food_items (name, description, category_id, price, image_url, available) 
                         VALUES (:name, :description, :category_id, :price, :image_url, :available)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':category_id' => $_POST['category_id'],
                    ':price' => $_POST['price'],
                    ':image_url' => $_POST['image_url'],
                    ':available' => isset($_POST['available']) ? 1 : 0
                ]);
                $success = "Food item added successfully!";
                break;
                
            case 'edit_item':
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
                $stmt->execute([
                    ':id' => $_POST['item_id'],
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':category_id' => $_POST['category_id'],
                    ':price' => $_POST['price'],
                    ':image_url' => $_POST['image_url'],
                    ':available' => isset($_POST['available']) ? 1 : 0
                ]);
                $success = "Food item updated successfully!";
                break;
                
            case 'add_category':
                $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':name' => $_POST['category_name'],
                    ':description' => $_POST['category_description']
                ]);
                $success = "Category added successfully!";
                break;
        }
    }
}

// Handle item deletion
if(isset($_GET['delete_item'])) {
    try {
        $query = "DELETE FROM food_items WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $_GET['delete_item']]);
        $success = "Food item deleted successfully!";
    } catch(Exception $e) {
        $error = "Error deleting item: " . $e->getMessage();
    }
}

// Get food items and categories
try {
    $query = "SELECT f.*, c.name as category_name FROM food_items f LEFT JOIN categories c ON f.category_id = c.id ORDER BY f.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $query = "SELECT * FROM categories ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Error loading data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Items Management - Canteen System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-hamburger"></i> Food Items Management</h1>
            <p>Manage your canteen menu items</p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Food Item Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus"></i> Add New Food Item</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_item">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Item Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (Rs.) *</label>
                            <input type="number" id="price" name="price" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="image_url">Image URL</label>
                            <input type="url" id="image_url" name="image_url">
                        </div>
                        
                        <div class="form-group">
                            <label for="available">
                                <input type="checkbox" id="available" name="available" checked>
                                Available for Order
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Food Item
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Add Category Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-tags"></i> Add New Category</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_name">Category Name *</label>
                            <input type="text" id="category_name" name="category_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_description">Description</label>
                            <input type="text" id="category_description" name="category_description">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Food Items List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Menu Items (<?php echo count($items); ?> items)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <?php if(empty($items)): ?>
                        <p>No food items found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <?php if($item['description']): ?>
                                                <br><small><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?></td>
                                        <td><strong>Rs. <?php echo number_format($item['price'], 2); ?></strong></td>
                                        <td>
                                            <span class="<?php echo $item['available'] ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn btn-primary btn-small" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Edit Item Modal -->
    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Food Item</h2>
                <button class="close" onclick="closeModal('editItemModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_item">
                <input type="hidden" id="edit_item_id" name="item_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_name">Item Name *</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category_id">Category *</label>
                        <select id="edit_category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Price (Rs.) *</label>
                    <input type="number" id="edit_price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_image_url">Image URL</label>
                    <input type="url" id="edit_image_url" name="image_url">
                </div>
                
                <div class="form-group">
                    <label for="edit_available">
                        <input type="checkbox" id="edit_available" name="available">
                        Available for Order
                    </label>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editItemModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        function editItem(item) {
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_category_id').value = item.category_id || '';
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_image_url').value = item.image_url || '';
            document.getElementById('edit_available').checked = item.available == 1;
            
            document.getElementById('editItemModal').style.display = 'block';
        }
        
        function confirmDelete(itemId, itemName) {
            if(confirm(`Are you sure you want to delete "${itemName}"?`)) {
                window.location.href = `?delete_item=${itemId}`;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
    
    <style>
        .text-success { color: #27ae60; font-weight: bold; }
        .text-danger { color: #e74c3c; font-weight: bold; }
        .table-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn-small { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
        .modal-content { max-width: 600px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .table-actions { flex-direction: column; }
        }
    </style>
</body>
</html>
