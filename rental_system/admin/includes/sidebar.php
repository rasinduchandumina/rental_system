<nav class="admin-sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span>Inventory</span>
            </a>
        </li>
        <li>
            <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
        </li>
        <li>
            <a href="feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i>
                <span>Feedback</span>
            </a>
        </li>
        <li>
            <a href="inquiries.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                <span>Inquiries</span>
            </a>
        </li>
        <li>
            <a href="../index.html" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>View Website</span>
            </a>
        </li>
    </ul>
</nav>