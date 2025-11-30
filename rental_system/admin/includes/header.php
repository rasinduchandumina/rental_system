<header class="admin-header">
    <div class="admin-logo">
        <i class="fas fa-utensils"></i>
        <span>Canteen Admin</span>
    </div>
    
    <div class="admin-user">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>