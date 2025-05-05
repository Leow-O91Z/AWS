<?php

require_once 'database.php';

if (isset($_POST['register'])) {
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = trim($first_name . ' ' . $last_name);
    $errors = [];

    if (empty($name)) {
        $errors[] = "First and Last Name are required";
    } elseif (strlen($name) > 50) {
         $errors[] = "Combined name cannot exceed 50 characters";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } elseif (strlen($email) > 100) {
         $errors[] = "Email cannot exceed 100 characters";
    } else {
        try {
            $stmt = $db->prepare("SELECT email FROM customer WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Email is already registered!';
            }
        } catch (PDOException $e) {
             error_log("Login Page Reg DB Error (Email Check): " . $e->getMessage());
             $errors[] = "Database error checking email. Please try again later.";
        }
    }

    if (empty($password_input)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password_input) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[!@#$%^&*()_+=\-[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one special character (e.g., !@#$%^&*).";
    }
    if ($password_input !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        $customerID = null;
        $maxAttempts = 10;
        $attempt = 0;
        try {
            do {
                $randomNumber = mt_rand(100000, 999999);
                $potentialID = "CUS" . $randomNumber;
                if (strlen($potentialID) > 30) {
                     throw new Exception("Generated customerID exceeds 30 characters.");
                }

                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM customer WHERE customerID = ?");
                $stmtCheck->execute([$potentialID]);
                if ($stmtCheck->fetchColumn() == 0) {
                    $customerID = $potentialID;
                }
                $attempt++;
            } while ($customerID === null && $attempt < $maxAttempts);

            if ($customerID === null) {
                 throw new Exception("Failed to generate a unique customerID after $maxAttempts attempts.");
            }

            $password = password_hash($password_input, PASSWORD_DEFAULT);
            if ($password === false) {
                throw new Exception("Password hashing failed.");
            }

            $db->beginTransaction();
            $stmt_insert = $db->prepare("INSERT INTO customer (customerID, name, email, password) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$customerID, $name, $email, $password]);
            $db->commit();

            $_SESSION['login_message'] = 'Registration successful! Please log in.';
            $_SESSION['active_form'] = 'login';
            header("Location: index.php?page=login&registration=success");
            exit();

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Login Page Reg DB Error (Insert): " . $e->getMessage());
            $_SESSION['register_error'] = 'Registration failed. Please try again later.';
            $_SESSION['active_form'] = 'register';
        } catch (Exception $e) {
             error_log("Login Page Reg Error: " . $e->getMessage());
             $_SESSION['register_error'] = 'An unexpected error occurred during registration.';
             $_SESSION['active_form'] = 'register';
        }

    } else {
        $_SESSION['register_error'] = implode("<br>", $errors);
        $_SESSION['active_form'] = 'register';
    }

    header("Location: index.php?page=login");
    exit();
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password_input = $_POST['password'];
    $login_error_message = 'Incorrect email or password.';

    if (empty($email) || empty($password_input)) {
        $_SESSION['login_error'] = $login_error_message;
        $_SESSION['active_form'] = 'login';
        header("Location: index.php?page=login");
        exit();
    }

    try {
        $stmt = $db->prepare("SELECT customerID, name, email, password FROM customer WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password_input, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['customerID'] = $user['customerID'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];

                $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php?page=home';
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect_url);
                exit();
            }
        }

    } catch (PDOException $e) {
        error_log("Login DB Error: " . $e->getMessage());
        $login_error_message = 'An error occurred during login. Please try again.';
    }

    $_SESSION['login_error'] = $login_error_message;
    $_SESSION['active_form'] = 'login';
    header("Location: index.php?page=login");
    exit();
}

if (isset($_GET['page']) && $_GET['page'] === 'register' && !isset($_SESSION['active_form'])) {
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
    $notice = "Please log in to access that page.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - ShoeTopia</title>

</head>
<body>
    <div class="auth-container">

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

            <form action="index.php?page=login" method="post" class="auth-form">
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
                    <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
                </div>
                <div class="auth-links">
                    <span>Don't have an account? <a onclick="return showForm('register-form');">Register</a></span>
                </div>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="form-box auth-form-container" id="register-form" style="<?= ($activeForm === 'register') ? 'display: block;' : 'display: none;'; ?>">
            <h2>Create an Account</h2>
            <p class="auth-subtitle">Join ShoeTopia today!</p>

            <?php if ($registerError): ?>
                <div class="alert alert-danger">
                    <?= nl2br(htmlspecialchars($registerError)); ?>
                </div>
            <?php endif; ?>

            <form action="index.php?page=login" method="post" class="auth-form">
                 <input type="hidden" name="register" value="1"> 

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
                    <span>Already have an account? <a onclick="return showForm('login-form');">Login</a></span>
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

</body>
</html>
