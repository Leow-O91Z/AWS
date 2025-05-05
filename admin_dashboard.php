<?php
// admin_dashboard.php

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    
    //message
    $_SESSION['admin_login_notice'] = 'Admin access required for the dashboard.';

    header('Location: index.php?page=admin_login&notice=admin_required');
    exit;
}

$adminName = $_SESSION['adminName'] ?? 'Admin';

?>


<div class="admin-dashboard-container">
    <h1>Admin Dashboard</h1>
    <p class="welcome-message">Welcome, <?php echo htmlspecialchars($adminName); ?>!</p>

    <div class="dashboard-widgets">
        <div class="widget">
            <i class="fas fa-boxes widget-icon"></i>
            <h2>Manage Products</h2>
            <p>View, add, edit, or remove products from the inventory.</p>
            <a href="index.php?page=admin_manage_products" class="btn btn-primary">Go to Products</a>
        </div>

        <div class="widget">
            <i class="fas fa-users widget-icon"></i>
            <h2>Manage Customers</h2>
            <p>View customer details and manage accounts.</p>
            <a href="index.php?page=admin_manage_customers" class="btn btn-primary">Go to Customers</a>
        </div>

        <div class="widget">
             <i class="fas fa-receipt widget-icon"></i>
             <h2>Manage Orders</h2>
             <p>View and update order statuses.</p>
             <a href="index.php?page=admin_manage_orders" class="btn btn-primary">Go to Orders</a>
        </div>

    </div>

    <div class="admin-quick-actions">
        <a href="index.php?page=logout" class="btn btn-secondary">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

</div>

