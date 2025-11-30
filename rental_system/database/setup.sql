-- Canteen Ordering System Database Setup
CREATE DATABASE IF NOT EXISTS canteen_system;
USE canteen_system;

-- Users table (for admin, teacher, and student)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food items table
CREATE TABLE food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(5) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table (individual items in an order)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_items(id)
);

-- Insert test users with hashed passwords (password is 'password123' for all test accounts)
-- Hash generated using password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@canteen.com', '$2y$10$203U35MFXNM1USiHwxQtd.PwRuvTKiGTW/fq2L57sY4k.tbJcqolC', 'System Administrator', 'admin'),
('teacher1', 'teacher1@school.com', '$2y$10$203U35MFXNM1USiHwxQtd.PwRuvTKiGTW/fq2L57sY4k.tbJcqolC', 'John Smith', 'teacher'),
('student1', 'student1@school.com', '$2y$10$203U35MFXNM1USiHwxQtd.PwRuvTKiGTW/fq2L57sY4k.tbJcqolC', 'Jane Doe', 'student');

-- Insert food categories
INSERT INTO categories (name, description) VALUES
('Breakfast', 'Morning meals and snacks'),
('Lunch', 'Main course meals'),
('Snacks', 'Light snacks and appetizers'),
('Beverages', 'Drinks and refreshments'),
('Desserts', 'Sweet treats and desserts');

-- Insert food items
INSERT INTO food_items (name, description, category_id, price, image_url, available) VALUES
('Egg Sandwich', 'Fresh egg sandwich with vegetables', 1, 3.50, NULL, 1),
('Pancakes', 'Fluffy pancakes with maple syrup', 1, 4.00, NULL, 1),
('Rice and Curry', 'Traditional rice with mixed curry', 2, 5.50, NULL, 1),
('Fried Rice', 'Special fried rice with chicken', 2, 6.00, NULL, 1),
('Chicken Burger', 'Grilled chicken burger with fries', 2, 7.50, NULL, 1),
('Samosa', 'Crispy vegetable samosa (2 pieces)', 3, 2.00, NULL, 1),
('French Fries', 'Crispy golden french fries', 3, 2.50, NULL, 1),
('Spring Rolls', 'Vegetable spring rolls (3 pieces)', 3, 3.00, NULL, 1),
('Fresh Juice', 'Freshly squeezed fruit juice', 4, 2.50, NULL, 1),
('Soft Drink', 'Chilled soft drink', 4, 1.50, NULL, 1),
('Tea', 'Hot tea with milk', 4, 1.00, NULL, 1),
('Coffee', 'Fresh brewed coffee', 4, 1.50, NULL, 1),
('Ice Cream', 'Vanilla ice cream scoop', 5, 2.00, NULL, 1),
('Chocolate Cake', 'Rich chocolate cake slice', 5, 3.50, NULL, 1);

-- Insert sample orders
INSERT INTO orders (order_code, user_id, total_amount, status) VALUES
('12345', 2, 8.00, 'pending'),
('67890', 3, 11.50, 'ready');

-- Insert order items
INSERT INTO order_items (order_id, food_item_id, quantity, price) VALUES
(1, 3, 1, 5.50),
(1, 9, 1, 2.50),
(2, 5, 1, 7.50),
(2, 7, 1, 2.50),
(2, 10, 1, 1.50);