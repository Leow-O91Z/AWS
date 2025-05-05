<?php
// admin_login.php

$loginError = $_SESSION['admin_login_error'] ?? null;
$loginMessage = $_SESSION['admin_login_message'] ?? null;

unset($_SESSION['admin_login_error']);
unset($_SESSION['admin_login_message']);

$notice = null;
if (isset($_GET['notice']) && $_GET['notice'] === 'admin_required') {
    $notice = $_SESSION['admin_login_notice'] ?? "Admin privileges required.";
    unset($_SESSION['admin_login_notice']); 
}

?>

<div class="auth-container">

    <div class="form-box auth-form-container" id="admin-login-form" style="display: block;">
        <h2>Admin Login</h2>
        <p class="auth-subtitle">Access the ShoeTopia Dashboard</p>

        <?php if ($notice): ?>
            <div class="alert alert-info"><?= htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if ($loginError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($loginError); ?></div>
        <?php endif; ?>
         <?php if ($loginMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($loginMessage); ?></div>
        <?php endif; ?>

        <form action="index.php?page=admin_login" method="post" class="auth-form">
            <input type="hidden" name="admin_login" value="1">

            <div class="form-group">
                 <label for="admin_login_email">Email Address <span class="required">*</span></label>
                 <div class="input-with-icon">
                     <i class="fas fa-user-shield"></i>
                     <input type="email" id="admin_login_email" name="email" placeholder="Enter admin email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
                 </div>
            </div>
            <div class="form-group">
                 <label for="admin_login_password">Password <span class="required">*</span></label>
                 <div class="input-with-icon password-field">
                     <i class="fas fa-lock"></i>
                     <input type="password" id="admin_login_password" name="password" placeholder="Enter admin password" required>
                     <button type="button" class="toggle-password" onclick="togglePasswordVisibility('admin_login_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                 </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Login as Admin</button>
            </div>
            <div class="auth-links">
                <span>Not an admin? <a href="index.php?page=login_register&show=login">Customer Login</a></span>
            </div>
        </form>
    </div>

</div>

<script>
    function togglePasswordVisibility(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const toggleButton = passwordField.closest('.password-field').querySelector('.toggle-password i');

        if (passwordField && toggleButton) {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }
    }
</script>
