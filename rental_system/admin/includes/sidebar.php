<nav class="admin-sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="food_items.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'food_items.php' ? 'active' : ''; ?>">
                <i class="fas fa-hamburger"></i>
                <span>Food Items</span>
            </a>
        </li>
        <li>
            <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
        </li>
        <li>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <li>
            <a href="../index.html" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>View Menu</span>
            </a>
        </li>
    </ul>
</nav>