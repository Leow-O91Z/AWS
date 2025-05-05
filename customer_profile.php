<?php
if (!isset($_SESSION['customerID'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'database.php'; 

$customerID = $_SESSION['customerID'];
$stmt = $db->prepare("SELECT * FROM customer WHERE customerID = ?");
$stmt->execute([$customerID]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

$successMessage = null;
$errorMessage = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'assets/images/profile_pictures/';

    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            $errorMessage = "Failed to create upload directory.";
        }
    }

    if (!isset($errorMessage)) {
        $fileName = $customerID . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
        $uploadFile = $uploadDir . $fileName;
        $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        $check = @getimagesize($_FILES['profile_picture']['tmp_name']);
        if ($check !== false) {
            if ($_FILES['profile_picture']['size'] <= 5000000) {
                if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    // Upload file
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
                        try {
                            $hasColumnInCustomer = false;
                            try {
                                $stmtCheck = $db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customer' AND column_name = 'profile_picture' LIMIT 1");
                                if ($stmtCheck && $stmtCheck->fetchColumn()) {
                                    $hasColumnInCustomer = true;
                                }
                            } catch (PDOException $e) { }

                            if ($hasColumnInCustomer) {
                                $stmtUpdate = $db->prepare("UPDATE customer SET profile_picture = ? WHERE customerID = ?");
                            } else {
                                throw new Exception("Profile picture column not found in expected tables.");
                            }

                            $stmtUpdate->execute([$uploadFile, $customerID]);
                            $customer['profile_picture'] = $uploadFile;
                            $successMessage = "Profile picture updated successfully.";
                        } catch (PDOException $e) {
                            error_log("Profile Pic DB Update Error: " . $e->getMessage());
                            $errorMessage = "Database error updating profile picture.";
                        } catch (Exception $e) {
                            error_log("Profile Pic Logic Error: " . $e->getMessage());
                            $errorMessage = "Configuration error updating profile picture.";
                        }
                    } else {
                        $errorMessage = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $errorMessage = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $errorMessage = "Sorry, your file is too large. Maximum size is 5MB.";
            }
        } else {
            $errorMessage = "File is not an image.";
        }
    }
}

if (!$successMessage && isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']); 
} elseif (!$successMessage && isset($_GET['update']) && $_GET['update'] === 'success') {
    $successMessage = 'Your profile has been updated successfully.';
} elseif (!$successMessage && isset($_GET['password']) && $_GET['password'] === 'changed') {
    $successMessage = 'Your password has been changed successfully.';
}

?>

<div class="profile-container">
    <div class="profile-sidebar">
        <form id="profile-pic-form" action="index.php?page=profile" method="post" enctype="multipart/form-data">
            <div class="profile-picture-container">
                <?php if (!empty($customer['profile_picture']) && file_exists($customer['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($customer['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                <?php else: ?>
                    <div class="default-profile">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <input type="file" name="profile_picture" id="profile-pic-input" class="file-upload" accept="image/*">
            </div>
        </form>

        <h2 class="profile-name"><?php echo htmlspecialchars($customer['name']); ?></h2>
        <p class="profile-email"><?php echo htmlspecialchars($customer['email']); ?></p>

        <ul class="profile-nav">
            <li class="profile-nav-item">
                <a href="index.php?page=customer_profile" class="profile-nav-link <?php echo ($currentPagePHP === 'customer_profile') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile Information
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="index.php?page=edit_profile" class="profile-nav-link <?php echo ($currentPagePHP === 'edit_profile') ? 'active' : ''; ?>">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="index.php?page=change_password" class="profile-nav-link <?php echo ($currentPagePHP === 'change_password') ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i> Change Password
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="index.php?page=order_history" class="profile-nav-link <?php echo ($currentPagePHP === 'order_history') ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Order History
                </a>
            </li>
        </ul>
    </div>

    <div class="profile-content">
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <h1 class="section-title">Profile Information</h1>

        <div class="profile-info">
            <div class="info-group">
                <span class="info-label">Customer ID</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['customerID']); ?></span>
            </div>

            <div class="info-group">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['name']); ?></span>
            </div>

            <div class="info-group">
                <span class="info-label">Email Address</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
            </div>
            <?php if (isset($customer['address'])): ?>
            <div class="info-group">
                <span class="info-label">Address</span>
                <span class="info-value">
                    <?php echo !empty($customer['address']) ? htmlspecialchars($customer['address']) : 'Not provided'; ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if (isset($customer['birthdate'])): ?>
            <div class="info-group">
                <span class="info-label">Birth Date</span>
                <span class="info-value">
                    <?php echo !empty($customer['birthdate']) ? date('F j, Y', strtotime($customer['birthdate'])) : 'Not provided'; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="index.php?page=edit_profile" class="btn btn-primary">Edit Profile</a>
            <a href="index.php?page=change_password" class="btn btn-secondary">Change Password</a>
        </div>
    </div>
</div>
<script>
</script>
