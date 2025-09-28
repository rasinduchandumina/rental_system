<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_item':
                $query = "INSERT INTO items (name, description, category_id, price_per_day, image_url, available_quantity, total_quantity, specifications) 
                         VALUES (:name, :description, :category_id, :price_per_day, :image_url, :available_quantity, :total_quantity, :specifications)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':category_id' => $_POST['category_id'],
                    ':price_per_day' => $_POST['price_per_day'],
                    ':image_url' => $_POST['image_url'],
                    ':available_quantity' => $_POST['total_quantity'],
                    ':total_quantity' => $_POST['total_quantity'],
                    ':specifications' => $_POST['specifications']
                ]);
                $success = "Item added successfully!";
                break;
                
            case 'edit_item':
                $query = "UPDATE items SET 
                         name = :name,
                         description = :description,
                         category_id = :category_id,
                         price_per_day = :price_per_day,
                         image_url = :image_url,
                         total_quantity = :total_quantity,
                         specifications = :specifications,
                         updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id";
                $stmt = $db->prepare($query);
                
                // Calculate new available quantity
                $currentQuery = "SELECT total_quantity, available_quantity FROM items WHERE id = :id";
                $currentStmt = $db->prepare($currentQuery);
                $currentStmt->execute([':id' => $_POST['item_id']]);
                $currentItem = $currentStmt->fetch();
                
                $newAvailable = $currentItem['available_quantity'] + ($_POST['total_quantity'] - $currentItem['total_quantity']);
                $newAvailable = max(0, $newAvailable); // Ensure not negative
                
                $updateAvailableQuery = "UPDATE items SET available_quantity = :available_quantity WHERE id = :id";
                $updateAvailableStmt = $db->prepare($updateAvailableQuery);
                $updateAvailableStmt->execute([
                    ':available_quantity' => $newAvailable,
                    ':id' => $_POST['item_id']
                ]);
                
                $stmt->execute([
                    ':id' => $_POST['item_id'],
                    ':name' => $_POST['name'],
                    ':description' => $_POST['description'],
                    ':category_id' => $_POST['category_id'],
                    ':price_per_day' => $_POST['price_per_day'],
                    ':image_url' => $_POST['image_url'],
                    ':total_quantity' => $_POST['total_quantity'],
                    ':specifications' => $_POST['specifications']
                ]);
                $success = "Item updated successfully!";
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

// Handle item deletion with proper constraint handling
if(isset($_GET['delete_item'])) {
    try {
        $db->beginTransaction();
        
        $itemId = $_GET['delete_item'];
        
        // Check if item has active rentals
        $checkQuery = "SELECT COUNT(*) as active_rentals FROM rentals WHERE item_id = :id AND status IN ('pending', 'confirmed', 'ongoing')";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([':id' => $itemId]);
        $activeRentals = $checkStmt->fetch()['active_rentals'];
        
        if($activeRentals > 0) {
            $error = "Cannot delete item: There are active rentals for this item. Please complete or cancel active rentals first.";
        } else {
            // Safe to delete - related records will be handled by CASCADE/SET NULL
            $query = "DELETE FROM items WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $itemId]);
            
            $db->commit();
            $success = "Item deleted successfully!";
        }
        
    } catch(Exception $e) {
        $db->rollback();
        $error = "Error deleting item: " . $e->getMessage();
    }
}

// Get items and categories
try {
    $query = "SELECT i.*, c.name as category_name FROM items i LEFT JOIN categories c ON i.category_id = c.id ORDER BY i.name";
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
    <title>Inventory Management - Tool Rental System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Inventory Management</h1>
            <p>Manage your tools and categories</p>
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
        
        <!-- Add Item Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-plus"></i> Add New Item</h3>
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
                            <label for="price_per_day">Price per Day ($) *</label>
                            <input type="number" id="price_per_day" name="price_per_day" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="total_quantity">Total Quantity *</label>
                            <input type="number" id="total_quantity" name="total_quantity" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="specifications">Specifications</label>
                        <textarea id="specifications" name="specifications" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image_url">Image URL</label>
                        <input type="url" id="image_url" name="image_url">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Item
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
        
        <!-- Items List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Current Inventory (<?php echo count($items); ?> items)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <?php if(empty($items)): ?>
                        <p>No items found in inventory.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price/Day</th>
                                    <th>Available</th>
                                    <th>Total</th>
                                    <th>Updated</th>
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
                                        <td>$<?php echo number_format($item['price_per_day'], 2); ?></td>
                                        <td>
                                            <span class="<?php echo $item['available_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $item['available_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $item['total_quantity']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn btn-primary btn-small" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-small" onclick="confirmDeleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
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
                <h2>Edit Item</h2>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_price_per_day">Price per Day ($) *</label>
                        <input type="number" id="edit_price_per_day" name="price_per_day" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_total_quantity">Total Quantity *</label>
                        <input type="number" id="edit_total_quantity" name="total_quantity" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_specifications">Specifications</label>
                    <textarea id="edit_specifications" name="specifications" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_image_url">Image URL</label>
                    <input type="url" id="edit_image_url" name="image_url">
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
            // Populate the edit form with item data
            document.getElementById('edit_item_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_category_id').value = item.category_id || '';
            document.getElementById('edit_price_per_day').value = item.price_per_day;
            document.getElementById('edit_total_quantity').value = item.total_quantity;
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_specifications').value = item.specifications || '';
            document.getElementById('edit_image_url').value = item.image_url || '';
            
            // Show the modal
            document.getElementById('editItemModal').style.display = 'block';
        }
        
        function confirmDeleteItem(itemId, itemName) {
            if(confirm(`Are you sure you want to delete "${itemName}"?\n\nThis will also delete all related rental history and feedback for this item.`)) {
                window.location.href = `?delete_item=${itemId}`;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
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
        
        .modal-content { max-width: 800px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .table-actions { flex-direction: column; }
        }
    </style>
</body>
</html>
