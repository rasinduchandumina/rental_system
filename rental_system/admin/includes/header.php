<header class="admin-header">
    <div class="admin-logo">
        <i class="fas fa-tools"></i>
        <span>ToolRent Admin</span>
    </div>
    
    <div class="admin-user">
        <span>Welcome, <?php echo $_SESSION['admin_name']; ?></span>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>