<?php
// admin_change_password.php

//Ensure admin is logged in
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    $_SESSION['admin_login_notice'] = 'Admin access required to change password.';
    header('Location: index.php?page=admin_login&notice=admin_required');
    exit;
}

$errorMessage = $_SESSION['admin_change_password_error'] ?? null;
unset($_SESSION['admin_change_password_error']); 

$currentPagePHP = 'admin_change_password';

?>

<div class="profile-container admin-page-container">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Change Password -->
    <div class="profile-content admin-content">
        <h1 class="section-title">Change Admin Password</h1>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

            
        
        <form action="index.php?page=admin_change_password" method="post" class="profile-form admin-form">

            <div class="form-group">
                <label for="current_password">Current Password <span class="required">*</span></label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password <span class="required">*</span></label>
                <input type="password" id="new_password" name="new_password" required minlength="8" pattern="(?=.*[!@#$%^&*()_+=\-[\]{};':&quot;\\|,.<>\/?]).{8,}" title="Password must be at least 8 characters long and contain at least one special character.">
                <small class="form-text text-muted">Must be at least 8 characters long and include a special character.</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Change Password</button>
                <a href="index.php?page=admin_profile" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

