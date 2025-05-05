<?php
$sidebarAdminName = $_SESSION['adminName'] ?? 'Administrator';
function isAdminNavLinkActive(string $pageName, string $currentPagePHP): string {
    return ($currentPagePHP === $pageName) ? 'active' : '';
}

?>
<div class="profile-sidebar admin-sidebar">
    <div class="admin-sidebar-header">
        <a href="index.php?page=admin_dashboard" class="sidebar-brand-link">
             <span class="sidebar-brand-text">GradGlow Admin</span>
    </div>

    <div class="admin-user-info">
         <i class="fas fa-user-shield admin-user-icon"></i>
         <h2 class="profile-name"><?php echo htmlspecialchars($sidebarAdminName); ?></h2>
         <p class="profile-role">Administrator</p>
    </div>

    <ul class="profile-nav">
       
        <!-- Management -->
        <li class="profile-nav-item nav-separator"></li>
        <li class="profile-nav-item nav-heading">Management</li>

        <li class="profile-nav-item">
            <a href="index.php?page=admin_manage_products" class="profile-nav-link <?php echo isAdminNavLinkActive('admin_manage_products', $currentPagePHP); ?>">
                <i class="fas fa-boxes fa-fw"></i> Manage Products
            </a>
        </li>
        <li class="profile-nav-item">
            <a href="index.php?page=admin_manage_customers" class="profile-nav-link <?php echo isAdminNavLinkActive('admin_manage_customers', $currentPagePHP); ?>">
                <i class="fas fa-users fa-fw"></i> Manage Customers
            </a>
        </li>
        <li class="profile-nav-item">
            <a href="index.php?page=admin_manage_orders" class="profile-nav-link <?php echo isAdminNavLinkActive('admin_manage_orders', $currentPagePHP); ?>">
                <i class="fas fa-receipt fa-fw"></i> Manage Orders
            </a>
        </li>

        <!-- Admin Profile -->
        <li class="profile-nav-item nav-separator"></li>
        <li class="profile-nav-item nav-heading">Account</li>

        <li class="profile-nav-item">
            <a href="index.php?page=admin_profile" class="profile-nav-link <?php echo isAdminNavLinkActive('admin_profile', $currentPagePHP); ?>">
                <i class="fas fa-user-cog fa-fw"></i> Admin Profile
            </a>
        </li>
         <li class="profile-nav-item">
            <a href="index.php?page=admin_edit_profile" class="profile-nav-link <?php echo isAdminNavLinkActive('admin_edit_profile', $currentPagePHP); ?>">
                <i class="fas fa-edit fa-fw"></i> Edit Profile
            </a>
        </li>
        <li class="profile-nav-item">
            <a href="index.php?page=admin_change_password" class="profile-nav-link <?php echo isAdminNavLinkActive('admin_change_password', $currentPagePHP); ?>">
                <i class="fas fa-key fa-fw"></i> Change Password
            </a>
        </li>

        <!-- Logout -->
        <li class="profile-nav-item nav-separator"></li>
        <li class="profile-nav-item">
          
        </li>
    </ul>
</div>