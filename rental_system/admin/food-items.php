<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all food items
$query = "SELECT fi.*, c.name as category_name 
          FROM food_items fi 
          LEFT JOIN categories c ON fi.category_id = c.id 
          ORDER BY c.name, fi.name";
$stmt = $db->prepare($query);
$stmt->execute();
$foodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Items - Canteen Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .category-tab {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .category-tab:hover,
        .category-tab.active {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
        }
        
        .add-item-btn {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .food-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .food-card.unavailable {
            opacity: 0.6;
        }
        
        .food-icon {
            font-size: 2.5rem;
            color: #f39c12;
            margin-bottom: 1rem;
        }
        
        .food-card h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .food-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .food-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .food-category {
            display: inline-block;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .food-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-available {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .status-unavailable {
            background: #ffebee;
            color: #e74c3c;
        }
        
        .food-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .food-actions button {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-toggle {
            background: #27ae60;
            color: white;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h2 {
            color: #2c3e50;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #e74c3c;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
        }
        
        .btn-cancel {
            background: #f8f9fa;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1><i class="fas fa-utensils"></i> Food Items Management</h1>
            <p>Add, edit, and manage food items</p>
        </div>
        
        <button class="add-item-btn" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New Item
        </button>
        
        <div class="category-tabs">
            <button class="category-tab active" data-category="all">All</button>
            <?php foreach($categories as $category): ?>
                <button class="category-tab" data-category="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="food-grid" id="foodGrid">
            <?php foreach($foodItems as $item): ?>
                <div class="food-card <?php echo $item['available'] ? '' : 'unavailable'; ?>" 
                     data-category="<?php echo $item['category_id']; ?>"
                     data-id="<?php echo $item['id']; ?>">
                    <div class="food-icon">
                        <?php
                        $icons = [
                            'Breakfast' => 'fa-egg',
                            'Lunch' => 'fa-bowl-food',
                            'Snacks' => 'fa-cookie',
                            'Beverages' => 'fa-glass-water',
                            'Desserts' => 'fa-ice-cream'
                        ];
                        $icon = $icons[$item['category_name']] ?? 'fa-utensils';
                        ?>
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <span class="food-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                    <span class="food-status <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                        <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                    </span>
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                    <div class="food-price">$<?php echo number_format($item['price'], 2); ?></div>
                    <div class="food-actions">
                        <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($item); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-toggle" onclick="toggleAvailability(<?php echo $item['id']; ?>, <?php echo $item['available'] ? 0 : 1; ?>)">
                            <i class="fas fa-<?php echo $item['available'] ? 'eye-slash' : 'eye'; ?>"></i>
                        </button>
                        <button class="btn-delete" onclick="deleteItem(<?php echo $item['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
    <!-- Add/Edit Item Modal -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus"></i> Add Food Item</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="itemForm">
                <input type="hidden" id="itemId" name="id">
                
                <div class="form-group">
                    <label for="itemName">Name *</label>
                    <input type="text" id="itemName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="itemDescription">Description</label>
                    <textarea id="itemDescription" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="itemCategory">Category *</label>
                    <select id="itemCategory" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="itemPrice">Price ($) *</label>
                    <input type="number" id="itemPrice" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="itemImage">Image URL</label>
                    <input type="url" id="itemImage" name="image_url" placeholder="https://...">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="itemAvailable" name="available" value="1" checked>
                        Available for ordering
                    </label>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="submitBtn">Save Item</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/admin.js"></script>
    <script>
        let isEditMode = false;
        
        // Category filtering
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                document.querySelectorAll('.food-card').forEach(card => {
                    if(category === 'all' || card.dataset.category === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        function openAddModal() {
            isEditMode = false;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Food Item';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            document.getElementById('itemAvailable').checked = true;
            document.getElementById('submitBtn').textContent = 'Add Item';
            document.getElementById('itemModal').classList.add('show');
        }
        
        function openEditModal(item) {
            isEditMode = true;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Food Item';
            document.getElementById('itemId').value = item.id;
            document.getElementById('itemName').value = item.name;
            document.getElementById('itemDescription').value = item.description || '';
            document.getElementById('itemCategory').value = item.category_id;
            document.getElementById('itemPrice').value = item.price;
            document.getElementById('itemImage').value = item.image_url || '';
            document.getElementById('itemAvailable').checked = item.available == 1;
            document.getElementById('submitBtn').textContent = 'Update Item';
            document.getElementById('itemModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('itemModal').classList.remove('show');
        }
        
        document.getElementById('itemForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                id: document.getElementById('itemId').value || null,
                name: document.getElementById('itemName').value,
                description: document.getElementById('itemDescription').value,
                category_id: document.getElementById('itemCategory').value,
                price: document.getElementById('itemPrice').value,
                image_url: document.getElementById('itemImage').value,
                available: document.getElementById('itemAvailable').checked ? 1 : 0
            };
            
            const method = isEditMode ? 'PUT' : 'POST';
            
            try {
                const response = await fetch('../api/food_items.php', {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Failed to save item');
                }
            } catch(error) {
                alert('Error saving item');
            }
        });
        
        async function toggleAvailability(id, available) {
            try {
                const response = await fetch('../api/food_items.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        id: id, 
                        available: available,
                        toggle_availability: true
                    })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update availability');
                }
            } catch(error) {
                alert('Error updating availability');
            }
        }
        
        async function deleteItem(id) {
            if(!confirm('Are you sure you want to delete this item?')) return;
            
            try {
                const response = await fetch(`../api/food_items.php?id=${id}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete item');
                }
            } catch(error) {
                alert('Error deleting item');
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('itemModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
