<?php
// login_register.php
if (isset($_GET['show']) && $_GET['show'] === 'register') {
    $activeForm = 'register';
} else {
    $activeForm = $_SESSION['active_form'] ?? 'login';
}

$loginError = $_SESSION['login_error'] ?? null;
$registerError = $_SESSION['register_error'] ?? null;
$loginMessage = $_SESSION['login_message'] ?? null;

unset($_SESSION['active_form']);
unset($_SESSION['login_error']);
unset($_SESSION['register_error']);
unset($_SESSION['login_message']);

$notice = null;
if (isset($_GET['notice']) && $_GET['notice'] === 'login_required') {
    $notice = $_SESSION['login_notice'] ?? "Please log in to access that page.";
    unset($_SESSION['login_notice']); 
}

?>
<div class="auth-container">

    <!-- Login Form -->
    <div class="form-box auth-form-container" id="login-form" style="<?= ($activeForm === 'login') ? 'display: block;' : 'display: none;'; ?>">
        <h2>Login</h2>
        <p class="auth-subtitle">Welcome back to ShoeTopia!</p>

        <?php if ($notice): ?>
            <div class="alert alert-info"><?= htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if ($loginError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($loginError); ?></div>
        <?php endif; ?>
         <?php if ($loginMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($loginMessage); ?></div>
        <?php endif; ?>

        <!-- Update form action -->
        <form action="index.php?page=login_register" method="post" class="auth-form">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                 <label for="login_email">Email Address <span class="required">*</span></label>
                 <div class="input-with-icon">
                     <i class="fas fa-envelope"></i>
                     <input type="email" id="login_email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
                 </div>
            </div>
            <div class="form-group">
                 <label for="login_password">Password <span class="required">*</span></label>
                 <div class="input-with-icon password-field">
                     <i class="fas fa-lock"></i>
                     <input type="password" id="login_password" name="password" placeholder="Enter your password" required>
                     <button type="button" class="toggle-password" onclick="togglePasswordVisibility('login_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                 </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
            <div class="auth-links">
                <span>Don't have an account? <a href="index.php?page=login_register&show=register" onclick="return showForm('register-form');">Register</a></span>
                <span style="display: block; margin-top: 10px;">Are you an admin? <a href="index.php?page=admin_login">Login here</a></span>
            </div>
        </form>
    </div>
    <div class="form-box auth-form-container" id="register-form" style="<?= ($activeForm === 'register') ? 'display: block;' : 'display: none;'; ?>">
        <h2>Create an Account</h2>
        <p class="auth-subtitle">Join ShoeTopia today!</p>

        <?php if ($registerError): ?>
            <div class="alert alert-danger">
                <?= nl2br(htmlspecialchars($registerError)); // Use nl2br for multi-line errors ?>
            </div>
        <?php endif; ?>

        <form action="index.php?page=login_register" method="post" class="auth-form">
             <input type="hidden" name="register" value="1"> <!-- Hidden input to identify registration -->

             <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                     <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? ''); ?>" required placeholder="Enter your first name">
                    </div>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                     <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? ''); ?>" required placeholder="Enter your last name">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="register_email">Email Address <span class="required">*</span></label>
                 <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="register_email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Enter your email">
                </div>
            </div>

            <div class="form-group">
                <label for="register_password">Password <span class="required">*</span></label>
                 <div class="input-with-icon password-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="register_password" name="password" required placeholder="Create a password">
                     <button type="button" class="toggle-password" onclick="togglePasswordVisibility('register_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                <small class="form-text text-muted">Password must contain at least one special character (e.g., !@#$%^&*).</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                 <div class="input-with-icon password-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                     <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </div>

            <div class="social-login">
                <p>Or sign up with</p>
                <div class="social-buttons">
                    <a href="#" class="social-button facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-button google"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-button twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div class="auth-links">
                <span>Already have an account? <a href="index.php?page=login_register&show=login" onclick="return showForm('login-form');">Login</a></span>
            </div>
        </form>
    </div>

</div>

<script>
    function showForm(formIdToShow) {
        const container = document.querySelector('.auth-container');
        container.querySelectorAll('.form-box').forEach(box => {
            box.style.display = 'none';
        });
        const formToShow = document.getElementById(formIdToShow);
        if(formToShow) {
            formToShow.style.display = 'block';
        }
        return false; 
    }

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


