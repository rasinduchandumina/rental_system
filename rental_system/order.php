<?php
session_start();

// Check if logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Admin should go to admin dashboard
if($_SESSION['user_role'] == 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$order_code = '';

// Handle order submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $cart = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : [];
    
    if(empty($cart)) {
        $error = 'Your cart is empty. Please add items to order.';
    } else {
        try {
            $db->beginTransaction();
            
            // Generate unique 5-digit order code
            do {
                $order_code = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                $check = $db->prepare("SELECT id FROM orders WHERE order_code = :code");
                $check->bindParam(':code', $order_code);
                $check->execute();
            } while($check->rowCount() > 0);
            
            // Calculate total
            $total = 0;
            foreach($cart as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            // Create order
            $query = "INSERT INTO orders (order_code, user_id, total_amount, status) VALUES (:code, :user_id, :total, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':code', $order_code);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':total', $total);
            $stmt->execute();
            
            $order_id = $db->lastInsertId();
            
            // Add order items
            $query = "INSERT INTO order_items (order_id, food_item_id, quantity, price) VALUES (:order_id, :food_item_id, :quantity, :price)";
            $stmt = $db->prepare($query);
            
            foreach($cart as $item) {
                $stmt->bindParam(':order_id', $order_id);
                $stmt->bindParam(':food_item_id', $item['id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':price', $item['price']);
                $stmt->execute();
            }
            
            $db->commit();
            $success = "Order placed successfully!";
            
        } catch(Exception $e) {
            $db->rollBack();
            $error = 'Failed to place order: ' . $e->getMessage();
        }
    }
}

// Get food items and categories
try {
    $query = "SELECT * FROM categories ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $query = "SELECT f.*, c.name as category_name FROM food_items f 
              LEFT JOIN categories c ON f.category_id = c.id 
              WHERE f.available = 1 
              ORDER BY c.name, f.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's recent orders
    $query = "SELECT o.*, 
              (SELECT GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') 
               FROM order_items oi 
               JOIN food_items fi ON oi.food_item_id = fi.id 
               WHERE oi.order_id = o.id) as items
              FROM orders o 
              WHERE o.user_id = :user_id 
              ORDER BY o.created_at DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = 'Error loading data: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Food - Canteen System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-page {
            padding-top: 80px;
            min-height: 100vh;
            background: #f4f4f4;
        }
        
        .order-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }
        
        .menu-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .menu-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .category-filter {
            margin-bottom: 20px;
        }
        
        .category-filter button {
            padding: 8px 16px;
            margin: 5px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-filter button:hover,
        .category-filter button.active {
            background: #667eea;
            color: white;
        }
        
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .food-card {
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .food-image {
            height: 150px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .food-info {
            padding: 15px;
        }
        
        .food-info h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .food-info .category {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        
        .food-info .price {
            color: #e74c3c;
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 10px;
            background: #27ae60;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .add-to-cart:hover {
            background: #219a52;
        }
        
        .cart-section {
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .cart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cart-card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-items {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .cart-item-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-controls button {
            width: 30px;
            height: 30px;
            border: none;
            background: #667eea;
            color: white;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .quantity-controls .remove-item {
            background: #e74c3c;
        }
        
        .cart-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #667eea;
        }
        
        .cart-total h3 {
            display: flex;
            justify-content: space-between;
            color: #2c3e50;
            font-size: 1.3rem;
        }
        
        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 15px;
            transition: transform 0.3s;
        }
        
        .place-order-btn:hover {
            transform: translateY(-2px);
        }
        
        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-cart {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        .empty-cart i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 10px;
        }
        
        .order-success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .order-success-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        }
        
        .order-code-display {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 10px;
            margin: 20px 0;
        }
        
        .recent-orders {
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .recent-orders h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .order-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-code {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .order-status.pending { background: #f39c12; color: white; }
        .order-status.preparing { background: #3498db; color: white; }
        .order-status.ready { background: #27ae60; color: white; }
        .order-status.given { background: #95a5a6; color: white; }
        .order-status.cancelled { background: #e74c3c; color: white; }
        
        .user-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-header .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-header .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-header .user-info span {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .user-header .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .user-header .logout-btn:hover {
            background: #c0392b;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c0392b;
            border: 1px solid #c0392b;
        }
        
        .alert-success {
            background: #efe;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        @media (max-width: 900px) {
            .order-container {
                grid-template-columns: 1fr;
            }
            
            .cart-section {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 100;
            }
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- User Header -->
    <header class="user-header">
        <div class="logo">
            <i class="fas fa-utensils"></i>
            <span>School Canteen</span>
        </div>
        <div class="user-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)</span>
            <a href="index.html" class="nav-link"><i class="fas fa-home"></i> Menu</a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    
    <div class="order-page">
        <div class="order-container">
            <!-- Menu Section -->
            <div class="menu-section">
                <h2><i class="fas fa-utensils"></i> Food Menu</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="category-filter">
                    <button class="active" onclick="filterCategory('all')">All</button>
                    <?php foreach($categories as $category): ?>
                        <button onclick="filterCategory('<?php echo htmlspecialchars($category['name']); ?>')">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <div class="food-grid" id="foodGrid">
                    <?php foreach($food_items as $item): ?>
                        <div class="food-card" data-category="<?php echo htmlspecialchars($item['category_name']); ?>">
                            <div class="food-image">
                                <i class="fas fa-hamburger"></i>
                            </div>
                            <div class="food-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="category"><?php echo htmlspecialchars($item['category_name']); ?></p>
                                <p class="price">Rs. <?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <button class="add-to-cart" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['price']; ?>)">
                                <i class="fas fa-plus"></i> Add to Cart
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Recent Orders -->
                <?php if(!empty($recent_orders)): ?>
                <div class="recent-orders">
                    <h3><i class="fas fa-history"></i> Your Recent Orders</h3>
                    <?php foreach($recent_orders as $order): ?>
                        <div class="order-item">
                            <div>
                                <span class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></span>
                                <small style="margin-left: 10px;"><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div>
                                <span>Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
                                <span class="order-status <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Cart Section -->
            <div class="cart-section">
                <div class="cart-card">
                    <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
                    
                    <div id="cartItems" class="cart-items">
                        <div class="empty-cart">
                            <i class="fas fa-shopping-basket"></i>
                            <p>Your cart is empty</p>
                        </div>
                    </div>
                    
                    <div class="cart-total">
                        <h3>
                            <span>Total:</span>
                            <span id="cartTotal">Rs. 0.00</span>
                        </h3>
                    </div>
                    
                    <form method="POST" action="" id="orderForm">
                        <input type="hidden" name="cart" id="cartInput">
                        <button type="submit" name="place_order" class="place-order-btn" id="placeOrderBtn" disabled>
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Success Modal -->
    <?php if($success && $order_code): ?>
    <div class="order-success-modal" id="successModal" style="display: flex;">
        <div class="order-success-content">
            <i class="fas fa-check-circle" style="font-size: 4rem; color: #27ae60;"></i>
            <h2>Order Placed Successfully!</h2>
            <p>Show this code when collecting your order:</p>
            <div class="order-code-display"><?php echo htmlspecialchars($order_code); ?></div>
            <p>Save this code! You'll need it to collect your food.</p>
            <button onclick="closeModal()" class="place-order-btn" style="margin-top: 20px;">
                <i class="fas fa-check"></i> Got it!
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        let cart = [];
        
        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if(existing) {
                existing.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            updateCartDisplay();
            showNotification('Added to cart: ' + name);
        }
        
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCartDisplay();
        }
        
        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === id);
            if(item) {
                item.quantity += change;
                if(item.quantity <= 0) {
                    removeFromCart(id);
                } else {
                    updateCartDisplay();
                }
            }
        }
        
        function updateCartDisplay() {
            const cartItemsEl = document.getElementById('cartItems');
            const cartTotalEl = document.getElementById('cartTotal');
            const cartInput = document.getElementById('cartInput');
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            
            if(cart.length === 0) {
                cartItemsEl.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Your cart is empty</p>
                    </div>
                `;
                cartTotalEl.textContent = 'Rs. 0.00';
                placeOrderBtn.disabled = true;
            } else {
                let html = '';
                let total = 0;
                
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    html += `
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <h4>${item.name}</h4>
                                <p>Rs. ${item.price.toFixed(2)} x ${item.quantity}</p>
                            </div>
                            <div class="quantity-controls">
                                <button onclick="updateQuantity(${item.id}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)">+</button>
                                <button class="remove-item" onclick="removeFromCart(${item.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                cartItemsEl.innerHTML = html;
                cartTotalEl.textContent = 'Rs. ' + total.toFixed(2);
                cartInput.value = JSON.stringify(cart);
                placeOrderBtn.disabled = false;
            }
        }
        
        function filterCategory(category) {
            const buttons = document.querySelectorAll('.category-filter button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const cards = document.querySelectorAll('.food-card');
            cards.forEach(card => {
                if(category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
            cart = [];
            updateCartDisplay();
        }
        
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: #27ae60;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                z-index: 3000;
                animation: slideIn 0.3s ease;
            `;
            notification.innerHTML = '<i class="fas fa-check"></i> ' + message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 2000);
        }
    </script>
</body>
</html>
