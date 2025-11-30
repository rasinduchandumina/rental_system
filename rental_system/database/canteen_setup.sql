-- Canteen Ordering System Database Setup
-- For use with XAMPP MySQL

-- Create database
CREATE DATABASE IF NOT EXISTS canteen_system;
USE canteen_system;

-- Users table (for admin, teacher, and student)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Food categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food items table
CREATE TABLE IF NOT EXISTS food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(5) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'given', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table (for multiple items in one order)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE CASCADE
);

-- Insert test data

-- Test users with hashed passwords (password is 'password' for all)
-- Hash generated using PHP password_hash('password', PASSWORD_DEFAULT)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('admin', 'admin@canteen.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', '0771234567', 'admin'),
('teacher1', 'teacher1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', '0772345678', 'teacher'),
('teacher2', 'teacher2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Doe', '0773456789', 'teacher'),
('student1', 'student1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Johnson', '0774567890', 'student'),
('student2', 'student2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Williams', '0775678901', 'student');

-- Food categories
INSERT INTO categories (name, description) VALUES
('Rice & Curry', 'Traditional rice dishes with various curries'),
('Short Eats', 'Quick snacks and pastries'),
('Beverages', 'Hot and cold drinks'),
('Desserts', 'Sweet treats and desserts'),
('Combo Meals', 'Value meal combinations');

-- Food items
INSERT INTO food_items (name, description, category_id, price, image_url, available) VALUES
('Rice & Chicken Curry', 'Steamed rice with delicious chicken curry', 1, 350.00, 'images/rice_chicken.jpg', 1),
('Rice & Fish Curry', 'Steamed rice with tasty fish curry', 1, 300.00, 'images/rice_fish.jpg', 1),
('Rice & Vegetable Curry', 'Steamed rice with mixed vegetable curry', 1, 200.00, 'images/rice_veg.jpg', 1),
('Fried Rice', 'Chicken fried rice with vegetables', 1, 400.00, 'images/fried_rice.jpg', 1),
('Egg Roll', 'Crispy egg roll with vegetables', 2, 80.00, 'images/egg_roll.jpg', 1),
('Vegetable Roll', 'Crispy vegetable roll', 2, 60.00, 'images/veg_roll.jpg', 1),
('Fish Bun', 'Soft bun filled with fish mixture', 2, 70.00, 'images/fish_bun.jpg', 1),
('Chicken Sandwich', 'Fresh sandwich with chicken filling', 2, 150.00, 'images/sandwich.jpg', 1),
('Hot Tea', 'Fresh brewed hot tea', 3, 30.00, 'images/tea.jpg', 1),
('Hot Coffee', 'Fresh brewed coffee', 3, 50.00, 'images/coffee.jpg', 1),
('Fresh Juice', 'Seasonal fruit juice', 3, 100.00, 'images/juice.jpg', 1),
('Bottled Water', 'Mineral water 500ml', 3, 50.00, 'images/water.jpg', 1),
('Ice Cream', 'Vanilla ice cream cup', 4, 80.00, 'images/ice_cream.jpg', 1),
('Fruit Salad', 'Fresh mixed fruit salad', 4, 120.00, 'images/fruit_salad.jpg', 1),
('Lunch Combo', 'Rice, curry, drink and dessert', 5, 450.00, 'images/combo.jpg', 1),
('Snack Combo', '2 short eats and a drink', 5, 180.00, 'images/snack_combo.jpg', 1);

-- Sample orders
INSERT INTO orders (order_code, user_id, total_amount, status) VALUES
('12345', 4, 430.00, 'pending'),
('23456', 5, 350.00, 'preparing'),
('34567', 3, 500.00, 'ready');

-- Sample order items
INSERT INTO order_items (order_id, food_item_id, quantity, price) VALUES
(1, 1, 1, 350.00),
(1, 9, 1, 30.00),
(1, 10, 1, 50.00),
(2, 1, 1, 350.00),
(3, 4, 1, 400.00),
(3, 11, 1, 100.00);
