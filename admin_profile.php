<?php
// admin_profile.php
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    $_SESSION['admin_login_notice'] = 'Admin access required for profile.';
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
    error_log("Admin Profile Fetch Error: " . $e->getMessage());
    $fetchError = "Could not load admin profile data.";
}

if (!$admin && !$fetchError) {
    unset($_SESSION['adminID'], $_SESSION['adminName'], $_SESSION['isAdmin']);
    $_SESSION['admin_login_error'] = 'Admin profile not found. Please log in again.';
    header('Location: index.php?page=admin_login');
    exit;
}

$successMessage = $_SESSION['admin_success_message'] ?? null;
unset($_SESSION['admin_success_message']);

$currentPagePHP = 'admin_profile';

?>

<div class="profile-container admin-page-container">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Profile Content -->
    <div class="profile-content admin-content">
        <?php if ($fetchError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <h1 class="section-title">Admin Profile Information</h1>

        <?php if ($admin): ?>
            <div class="profile-info">
                <div class="info-group">
                    <span class="info-label">Admin ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($admin['adminID']); ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($admin['name']); ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="index.php?page=admin_edit_profile" class="btn btn-primary">Edit Profile</a>
                <a href="index.php?page=admin_change_password" class="btn btn-secondary">Change Password</a>
            </div>
        <?php endif; ?>
    </div>
</div>