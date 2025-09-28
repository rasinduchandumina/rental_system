-- Tool Rental System Database Setup
CREATE DATABASE IF NOT EXISTS tool_rental;
USE tool_rental;

-- Users table (for both customers and admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items/Tools table
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    price_per_day DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    available_quantity INT DEFAULT 1,
    total_quantity INT DEFAULT 1,
    specifications TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Rentals table
CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    item_id INT,
    rental_date DATE NOT NULL,
    return_date DATE NOT NULL,
    actual_return_date DATE NULL,
    quantity INT DEFAULT 1,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'ongoing', 'returned', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    item_id INT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- Contact inquiries table
CREATE TABLE contact_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert test data
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@toolrental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'customer');

INSERT INTO categories (name, description) VALUES
('Power Tools', 'Electric and battery powered tools'),
('Hand Tools', 'Manual tools and equipment'),
('Garden Tools', 'Gardening and landscaping equipment'),
('Construction', 'Heavy construction equipment'),
('Automotive', 'Car maintenance and repair tools');

INSERT INTO items (name, description, category_id, price_per_day, image_url, available_quantity, total_quantity, specifications) VALUES
('Electric Drill', 'High-power cordless drill with multiple bits', 1, 15.00, 'images/drill.jpg', 3, 3, '18V, Variable speed, Keyless chuck'),
('Hammer Set', 'Professional hammer set with different sizes', 2, 8.00, 'images/hammer.jpg', 5, 5, 'Set of 3 hammers: 16oz, 20oz, 24oz'),
('Lawn Mower', 'Gas-powered lawn mower for medium yards', 3, 25.00, 'images/mower.jpg', 2, 2, '21-inch cutting deck, Self-propelled'),
('Concrete Mixer', 'Portable concrete mixer for small projects', 4, 45.00, 'images/mixer.jpg', 1, 1, '3.5 cubic feet capacity, Electric motor'),
('Car Jack', 'Hydraulic car jack for vehicle maintenance', 5, 12.00, 'images/jack.jpg', 4, 4, '2-ton capacity, Safety lock mechanism');

INSERT INTO rentals (customer_id, item_id, rental_date, return_date, quantity, total_amount, status) VALUES
(2, 1, '2025-01-15', '2025-01-17', 1, 30.00, 'ongoing'),
(2, 3, '2025-01-20', '2025-01-22', 1, 50.00, 'confirmed');

INSERT INTO feedback (customer_id, item_id, rating, message) VALUES
(2, 1, 5, 'Great drill! Very powerful and easy to use.'),
(2, 2, 4, 'Good quality hammers, would rent again.');

INSERT INTO contact_inquiries (name, email, subject, message) VALUES
('Sarah Johnson', 'sarah@example.com', 'Bulk Rental Inquiry', 'Hi, I need to rent multiple tools for a construction project. Can you provide a quote?'),
('Mike Wilson', 'mike@example.com', 'Tool Availability', 'Do you have industrial sanders available for rent?');