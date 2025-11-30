# Canteen Ordering System

A simple web-based canteen ordering system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### Public Features (No Login Required)
- **Browse Menu**: View all available food items on the homepage
- **Search & Filter**: Search food items by name or filter by category

### User Features (Login Required)
- **Place Orders**: Students and teachers can order food items
- **View Orders**: Track order status with unique 5-digit order codes
- **Order History**: View all past and current orders

### User Roles
- **Admin**: Full system management
- **Teacher**: Can place and view orders
- **Student**: Can place and view orders

### Admin Features
- **Dashboard**: Overview of orders, users, and revenue
- **Food Items Management**: Add, edit, delete, and toggle availability of food items
- **Order Management**: View all orders, search by 5-digit code, update order status
- **User Management**: View, edit, and delete user accounts (admin, teacher, student)

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ (XAMPP recommended)
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Icons**: Font Awesome 6.0
- **Security**: Password hashing with PHP's `password_hash()`

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher (XAMPP recommended)
- Web server (Apache)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/rasinduchandumina/rental_system.git
   cd rental_system/rental_system
   ```

2. **Start XAMPP**
   - Start Apache and MySQL services

3. **Create the database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database/setup.sql` file
   - Or run the SQL commands manually

4. **Configure database connection**
   - Edit `config/database.php` if your MySQL credentials differ from default

5. **Access the application**
   - Homepage: `http://localhost/rental_system/rental_system/index.html`
   - Login: `http://localhost/rental_system/rental_system/login.php`
   - Admin: `http://localhost/rental_system/rental_system/admin/dashboard.php`

## Test Accounts

All test accounts use the password: `password123`

| Role    | Username  | Password    |
|---------|-----------|-------------|
| Admin   | admin     | password123 |
| Teacher | teacher1  | password123 |
| Student | student1  | password123 |

## How It Works

### Ordering Process
1. Browse the menu on the homepage (no login required)
2. Login as a student or teacher to place an order
3. Select food items and quantities
4. Submit the order and receive a unique 5-digit order code
5. Use the order code to track and collect your order

### Admin Order Management
1. Login as admin and go to the Dashboard or Orders page
2. View all active orders or search by the 5-digit order code
3. Update order status: Pending → Preparing → Ready → Completed
4. When the order is given to the customer, mark it as "Completed"

## Project Structure

```
rental_system/
├── admin/
│   ├── css/
│   ├── includes/
│   ├── js/
│   ├── dashboard.php
│   ├── food-items.php
│   ├── orders.php
│   └── users.php
├── api/
│   ├── categories.php
│   ├── food_items.php
│   ├── orders.php
│   └── users.php
├── config/
│   └── database.php
├── css/
│   └── style.css
├── database/
│   └── setup.sql
├── js/
│   └── script.js
├── index.html
├── login.php
├── register.php
├── order.php
├── my-orders.php
├── logout.php
└── README.md
```

## Security Features

- Password hashing using PHP's `password_hash()` and `password_verify()`
- Prepared statements to prevent SQL injection
- Session-based authentication
- Role-based access control
- Input sanitization with `htmlspecialchars()`

## License

This project is for educational purposes.