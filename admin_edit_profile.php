<?php
// admin_edit_profile.php

if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    $_SESSION['admin_login_notice'] = 'Admin access required to edit profile.';
    header('Location: index.php?page=admin_login&notice=admin_required');
    exit;
}

$adminID = $_SESSION['adminID'];
$admin = null;
$fetchError = null;
try {
    $stmt = $db->prepare("SELECT adminID, name, email FROM admin WHERE adminID = ?");
    $stmt->execute([$adminID]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin Edit Profile Fetch Error: " . $e->getMessage());
    $fetchError = "Could not load admin data for editing.";
}

if (!$admin && !$fetchError) {
    unset($_SESSION['adminID'], $_SESSION['adminName'], $_SESSION['isAdmin']);
    $_SESSION['admin_login_error'] = 'Admin profile not found. Please log in again.';
    header('Location: index.php?page=admin_login');
    exit;
}

$errors = $_SESSION['admin_edit_profile_error'] ?? null;
$formData = $_SESSION['admin_form_data'] ?? []; 
unset($_SESSION['admin_edit_profile_error'], $_SESSION['admin_form_data']); 
$display_name = $formData['name'] ?? $admin['name'] ?? '';
$display_email = $formData['email'] ?? $admin['email'] ?? '';

$currentPagePHP = 'admin_edit_profile';

?>

<div class="profile-container admin-page-container"> 
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <div class="profile-content admin-content">
        <?php if ($fetchError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <?php else: ?>
            <h1 class="section-title">Edit Admin Profile</h1>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php echo $errors; ?>
                </div>
            <?php endif; ?>

            <form action="index.php?page=admin_edit_profile" method="post" class="profile-form admin-form"> 
                <div class="form-group">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($display_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($display_email); ?>" required>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="index.php?page=admin_profile" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
