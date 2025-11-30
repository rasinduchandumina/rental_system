<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only teachers and students can access this page
if($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user's pending orders
$query = "SELECT o.*, 
          GROUP_CONCAT(CONCAT(fi.name, ' x', oi.quantity) SEPARATOR ', ') as items
          FROM orders o
          JOIN order_items oi ON o.id = oi.order_id
          JOIN food_items fi ON oi.food_item_id = fi.id
          WHERE o.user_id = :user_id AND o.status IN ('pending', 'preparing', 'ready')
          GROUP BY o.id
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available food items
$query = "SELECT fi.*, c.name as category_name 
          FROM food_items fi 
          LEFT JOIN categories c ON fi.category_id = c.id 
          WHERE fi.available = 1
          ORDER BY c.name, fi.name";
$stmt = $db->prepare($query);
$stmt->execute();
$food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Food - Canteen Ordering System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-page {
            padding-top: 80px;
            min-height: 100vh;
            background: #f4f4f4;
        }
        
        .user-header {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info i {
            font-size: 1.5rem;
        }
        
        .user-info span {
            font-weight: bold;
        }
        
        .user-role {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-left: 1rem;
            transition: background 0.3s;
        }
        
        .header-actions a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .order-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .menu-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .menu-section h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }
        
        .category-tab {
            background: #f8f9fa;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-tab:hover,
        .category-tab.active {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
        }
        
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .food-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .food-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .food-card.selected {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        .food-icon {
            font-size: 2.5rem;
            color: #f39c12;
            margin-bottom: 0.5rem;
        }
        
        .food-card h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .food-price {
            color: #e74c3c;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .quantity-control button {
            width: 25px;
            height: 25px;
            border: none;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .quantity-control span {
            font-weight: bold;
        }
        
        .cart-section {
            position: sticky;
            top: 100px;
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        .cart-section h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-items {
            margin-bottom: 1rem;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-info h4 {
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .cart-item-info p {
            font-size: 0.8rem;
            color: #666;
        }
        
        .cart-item-remove {
            color: #e74c3c;
            cursor: pointer;
        }
        
        .cart-total {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .cart-total-row.final {
            font-weight: bold;
            font-size: 1.2rem;
            color: #e74c3c;
            border-top: 2px solid #ddd;
            padding-top: 0.5rem;
        }
        
        .place-order-btn {
            width: 100%;
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
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
            padding: 2rem;
            color: #666;
        }
        
        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        
        .pending-orders {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .pending-orders h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .order-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .order-code {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            letter-spacing: 2px;
        }
        
        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .status-preparing {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .status-ready {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        @media (max-width: 992px) {
            .order-container {
                grid-template-columns: 1fr;
            }
            
            .cart-section {
                position: relative;
                top: 0;
                max-height: none;
            }
        }
        
        /* Success Modal */
        .success-modal {
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
        
        .success-modal.show {
            display: flex;
        }
        
        .success-content {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
        }
        
        .success-content i {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }
        
        .success-content h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .order-code-display {
            font-size: 3rem;
            font-weight: bold;
            color: #e74c3c;
            letter-spacing: 5px;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .success-content p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .close-success-btn {
            background: linear-gradient(135deg, #e74c3c, #f39c12);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="user-header">
        <div class="user-info">
            <i class="fas fa-utensils"></i>
            <span>School Canteen</span>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
        </div>
        <div class="header-actions">
            <a href="my-orders.php"><i class="fas fa-receipt"></i> My Orders</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="order-page">
        <div class="order-container">
            <div>
                <?php if(!empty($pending_orders)): ?>
                <div class="pending-orders">
                    <h3><i class="fas fa-clock"></i> Your Active Orders</h3>
                    <?php foreach($pending_orders as $order): ?>
                    <div class="order-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></span>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div style="text-align: right;">
                                <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                                <p style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($order['items']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="menu-section">
                    <h2><i class="fas fa-utensils"></i> Menu</h2>
                    
                    <div class="category-tabs">
                        <button class="category-tab active" data-category="all">All</button>
                        <?php foreach($categories as $category): ?>
                        <button class="category-tab" data-category="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="food-grid" id="foodGrid">
                        <?php foreach($food_items as $item): ?>
                        <div class="food-card" data-id="<?php echo $item['id']; ?>" 
                             data-name="<?php echo htmlspecialchars($item['name']); ?>"
                             data-price="<?php echo $item['price']; ?>"
                             data-category="<?php echo $item['category_id']; ?>">
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
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="food-price">$<?php echo number_format($item['price'], 2); ?></div>
                            <div class="quantity-control" style="display: none;">
                                <button class="qty-btn minus">-</button>
                                <span class="qty-value">1</span>
                                <button class="qty-btn plus">+</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="cart-section">
                <h2><i class="fas fa-shopping-cart"></i> Your Order</h2>
                
                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Your cart is empty</p>
                        <p style="font-size: 0.8rem;">Click on items to add them</p>
                    </div>
                </div>
                
                <div class="cart-total" id="cartTotal" style="display: none;">
                    <div class="cart-total-row final">
                        <span>Total:</span>
                        <span id="totalAmount">$0.00</span>
                    </div>
                </div>
                
                <button class="place-order-btn" id="placeOrderBtn" disabled>
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="success-modal" id="successModal">
        <div class="success-content">
            <i class="fas fa-check-circle"></i>
            <h2>Order Placed Successfully!</h2>
            <p>Your order code is:</p>
            <div class="order-code-display" id="orderCodeDisplay">12345</div>
            <p>Please show this code when collecting your order.</p>
            <button class="close-success-btn" onclick="closeSuccessModal()">
                <i class="fas fa-thumbs-up"></i> Got it!
            </button>
        </div>
    </div>
    
    <script>
        let cart = [];
        
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
        
        // Food card click handling
        document.querySelectorAll('.food-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if(e.target.classList.contains('qty-btn')) return;
                
                const id = this.dataset.id;
                const name = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                
                // Check if already in cart
                const existingItem = cart.find(item => item.id === id);
                if(existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push({ id, name, price, quantity: 1 });
                }
                
                this.classList.add('selected');
                this.querySelector('.quantity-control').style.display = 'flex';
                updateQtyDisplay(this);
                updateCart();
            });
        });
        
        // Quantity buttons
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const card = this.closest('.food-card');
                const id = card.dataset.id;
                const item = cart.find(i => i.id === id);
                
                if(this.classList.contains('plus')) {
                    if(item) item.quantity++;
                } else {
                    if(item && item.quantity > 1) {
                        item.quantity--;
                    } else {
                        cart = cart.filter(i => i.id !== id);
                        card.classList.remove('selected');
                        card.querySelector('.quantity-control').style.display = 'none';
                    }
                }
                
                updateQtyDisplay(card);
                updateCart();
            });
        });
        
        function updateQtyDisplay(card) {
            const id = card.dataset.id;
            const item = cart.find(i => i.id === id);
            const qtySpan = card.querySelector('.qty-value');
            if(item && qtySpan) {
                qtySpan.textContent = item.quantity;
            }
        }
        
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const totalAmount = document.getElementById('totalAmount');
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            
            if(cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Your cart is empty</p>
                        <p style="font-size: 0.8rem;">Click on items to add them</p>
                    </div>
                `;
                cartTotal.style.display = 'none';
                placeOrderBtn.disabled = true;
                return;
            }
            
            let html = '';
            let total = 0;
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.name}</h4>
                            <p>$${item.price.toFixed(2)} x ${item.quantity}</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: bold;">$${itemTotal.toFixed(2)}</span>
                            <i class="fas fa-times-circle cart-item-remove" onclick="removeFromCart('${item.id}')"></i>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            totalAmount.textContent = '$' + total.toFixed(2);
            cartTotal.style.display = 'block';
            placeOrderBtn.disabled = false;
        }
        
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            
            const card = document.querySelector(`.food-card[data-id="${id}"]`);
            if(card) {
                card.classList.remove('selected');
                card.querySelector('.quantity-control').style.display = 'none';
            }
            
            updateCart();
        }
        
        // Place order
        document.getElementById('placeOrderBtn').addEventListener('click', async function() {
            if(cart.length === 0) return;
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            try {
                const response = await fetch('api/orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ items: cart })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    document.getElementById('orderCodeDisplay').textContent = data.order_code;
                    document.getElementById('successModal').classList.add('show');
                    
                    // Clear cart
                    cart = [];
                    document.querySelectorAll('.food-card').forEach(card => {
                        card.classList.remove('selected');
                        card.querySelector('.quantity-control').style.display = 'none';
                    });
                    updateCart();
                } else {
                    alert(data.message || 'Failed to place order. Please try again.');
                }
            } catch(error) {
                console.error('Error:', error);
                alert('Unable to connect to the server. Please check your internet connection and try again.');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
            }
        });
        
        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('show');
            location.reload();
        }
    </script>
</body>
</html>
