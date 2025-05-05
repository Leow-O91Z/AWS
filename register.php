<?php
/**
 * USER REGISTRATION SYSTEM
 * 
 * FUNCTIONS:
 * 1. Form Processing - Handles registration form submission
 * 2. Input Validation - Validates user input data
 * 3. Account Creation - Creates new customer accounts
 * 4. Security - Implements secure password handling
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    $name = trim($first_name . ' ' . $last_name);
    $errors = [];

    if (empty($name)) {
        $errors[] = "First and Last Name are required";
    } elseif (strlen($name) > 50) {
         $errors[] = "Combined name cannot exceed 50 characters";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (strlen($email) > 100) {
         $errors[] = "Email cannot exceed 100 characters";
    } else {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM customer WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Email already exists";
            }
        } catch (PDOException $e) {
             error_log("Registration DB Error (Email Check): " . $e->getMessage());
             $errors[] = "Database error checking email. Please try again later.";
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[!@#$%^&*()_+=\-[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one special character (e.g., !@#$%^&*).";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        try {
            $customerID = null;
            $maxAttempts = 10;
            $attempt = 0;
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

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($hashed_password === false) {
                throw new Exception("Password hashing failed.");
            }

            // Start transaction
            $db->beginTransaction();

            // Insert the user into the 'customer' table (user_actions.sql schema)
            $stmt = $db->prepare("INSERT INTO customer (customerID, name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$customerID, $name, $email, $hashed_password]);

            // Commit transaction
            $db->commit();
            $_SESSION['customerID'] = $customerID;
            $_SESSION['name'] = $name; // Store name from the 'name' column
            $_SESSION['email'] = $email; // Store email

            // Redirect to profile page after successful registration
            header("Location: index.php?page=profile&registration=success");
            exit;

        } catch (PDOException $e) {
             if ($db->inTransaction()) {
                $db->rollBack(); 
            }
            error_log("Registration Database Error: " . $e->getMessage());
            $errors[] = "An error occurred during registration. Please try again later.";
        } catch (Exception $e) {
             error_log("Registration Error: " . $e->getMessage());
             $errors[] = "An unexpected error occurred during registration. Please try again later.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form-container">
        <h2>Create an Account</h2>
        <p class="auth-subtitle">Join ShoeTopia today!</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=register" class="auth-form validate">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                     <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required placeholder="Enter your first name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                     <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required placeholder="Enter your last name">
                    </div>
                </div>
            </div>

            <!-- Username field removed -->

            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                 <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required placeholder="Enter your email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                 <div class="input-with-icon password-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                     <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
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
                <span>Already have an account? <a href="index.php?page=login">Login</a></span>
            </div>
        </form>
    </div>
</div>

<!-- Keep your existing JavaScript -->
<script>
function togglePasswordVisibility(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleButton = passwordField.parentNode.querySelector('.toggle-password i');

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
</script>

