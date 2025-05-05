<?php
if (!isset($_SESSION['customerID'])) {
    $_SESSION['redirect_after_login'] = 'index.php?page=change_password';
    $_SESSION['login_notice'] = 'Please log in to change your password.';
    header('Location: index.php?page=login&notice=login_required');
    exit;
}

$customerID = $_SESSION['customerID'];
$errorMessage = null;
$successMessage = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Basic validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "All password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New password and confirmation password do not match.";
    } elseif (strlen($newPassword) < 8) { //length check
        $errorMessage = "New password must be at least 8 characters long.";
    } else {
        try {
            $stmt = $db->prepare("SELECT password FROM customer WHERE customerID = ?");
            $stmt->execute([$customerID]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($currentPassword, $user['password'])) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmtUpdate = $db->prepare("UPDATE customer SET password = ? WHERE customerID = ?");
                $stmtUpdate->execute([$newPasswordHash, $customerID]);

                $_SESSION['success_message'] = 'Your password has been changed successfully.';
                header('Location: index.php?page=profile&password=changed');
                exit;

            } else {
                // Incorrect password
                $errorMessage = "The current password you entered is incorrect.";
            }
        } catch (PDOException $e) {
            error_log("Change Password DB Error: " . $e->getMessage());
            $errorMessage = "An error occurred while changing your password. Please try again later.";
        }
    }
}

?>

<div class="profile-container"> 
    <div class="profile-sidebar">
         <h2 class="profile-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></h2>
         <ul class="profile-nav">
            <li class="profile-nav-item"><a href="index.php?page=customer_profile" class="profile-nav-link"><i class="fas fa-user"></i> Profile</a></li>
            <li class="profile-nav-item"><a href="index.php?page=edit_profile" class="profile-nav-link"><i class="fas fa-edit"></i> Edit Profile</a></li>
            <li class="profile-nav-item"><a href="index.php?page=change_password" class="profile-nav-link active"><i class="fas fa-key"></i> Change Password</a></li>
          
    </div>

    <!-- Change Password -->
    <div class="profile-content">
        <h1 class="section-title">Change Password</h1>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <form action="index.php?page=change_password" method="post" class="profile-form">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <!-- Added pattern, title, and updated small tag -->
                <input type="password" id="new_password" name="new_password" required minlength="8" pattern="(?=.*[!@#$%^&*()_+=\-[\]{};':&quot;\\|,.<>\/?]).{8,}" title="Password must be at least 8 characters long and contain at least one special character.">
                <small>Must be at least 8 characters long and include a special character (e.g., !@#$%^&*).</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Password</button>
                <a href="index.php?page=profile" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>