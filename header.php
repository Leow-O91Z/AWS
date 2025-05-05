<?php
// Determine the correct link for the logo/brand based on login status
$homeLink = 'index.php?page=home'; // Default link for guests and customers
if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true) {
    $homeLink = 'index.php?page=admin_dashboard'; // Link for admins
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GradGlow – Your Ultimate Graduation Store</title>
    <link rel="shortcut icon" href="/images/favicon.jpg">
    <link rel="stylesheet" href="styles.css">

</head>
<body>
<header>
        <div class="container">
            <nav class="navbar">
                <!-- ***** MODIFIED THIS LINK ***** -->
                <a class="navbar-brand" href="<?php echo $homeLink; ?>">
                    GradGlow
                </a>

                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true):  ?>
                        <!-- Admin -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'admin_dashboard') ? 'active' : ''; ?>" href="index.php?page=admin_dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                           <a class="nav-link <?php echo in_array($currentPagePHP, ['admin_profile', 'admin_edit_profile', 'admin_change_password']) ? 'active' : ''; ?>" href="index.php?page=admin_profile">
                                <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['adminName'] ?? 'Admin'); ?>
                           </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>

                    <?php elseif (isset($_SESSION['isCustomer']) && $_SESSION['isCustomer'] === true): ?>
                        <!-- Customer -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'home') ? 'active' : ''; ?>" href="index.php?page=home"><i class="fas fa-home"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'product-listing') ? 'active' : ''; ?>" href="index.php?page=product-listing"><i class="fas fa-store"></i> Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'shopping-cart') ? 'active' : ''; ?>" href="index.php?page=shopping-cart">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <?php
                                    //Display cart count
                                    $cartCount = 0;
                                    if (!empty($_SESSION['cart'])) {
                                        $cartCount = array_sum($_SESSION['cart']);
                                    }
                                    if ($cartCount > 0) {
                                        echo '<span class="badge badge-pill badge-danger cart-count">' . $cartCount . '</span>';
                                    }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown"> <!-- Added dropdown class for potential styling -->
                            <a class="nav-link <?php echo in_array($currentPagePHP, ['customer_profile', 'edit_profile', 'change_password', 'order_history', 'order_details']) ? 'active' : ''; ?>" href="index.php?page=customer_profile">
                                <?php
                                    $profilePicture = null;
                                    if(isset($_SESSION['customerID']) && isset($db)) {
                                        try {
                                            // Check if column exists before querying (optional optimization)
                                            $stmtCheckCol = $db->query("SHOW COLUMNS FROM customer LIKE 'profile_picture'");
                                            if ($stmtCheckCol->rowCount() > 0) {
                                                $stmtPic = $db->prepare("SELECT profile_picture FROM customer WHERE customerID = ?");
                                                $stmtPic->execute([$_SESSION['customerID']]);
                                                $profilePicture = $stmtPic->fetchColumn();
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Header Profile Pic Fetch Error: " . $e->getMessage());
                                            $profilePicture = null; // Ensure it's null on error
                                        }
                                    }
                                ?>
                                <?php if (!empty($profilePicture) && file_exists($profilePicture)): ?>
                                    <div class="header-profile-pic" style="background-image: url('<?php echo htmlspecialchars($profilePicture); ?>?t=<?php echo time(); ?>');"></div>
                                <?php else: ?>
                                    <span class="user-icon-placeholder">
                                        <?php // Display first letter of name, fallback to 'U' ?>
                                        <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                                    </span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($_SESSION['name'] ?? 'Profile'); ?>
                            </a>
                            <!-- Optional: Add dropdown menu for profile links here -->
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>

                    <?php else: ?>
                        <!-- Logged Out -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'home') ? 'active' : ''; ?>" href="index.php?page=home"><i class="fas fa-home"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'product-listing') ? 'active' : ''; ?>" href="index.php?page=product-listing"><i class="fas fa-store"></i> Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'shopping-cart') ? 'active' : ''; ?>" href="index.php?page=shopping-cart">
                                <i class="fas fa-shopping-cart"></i> Cart
                                 <?php
                                    //Display cart count even when logged out
                                    $cartCount = 0;
                                    if (!empty($_SESSION['cart'])) {
                                        $cartCount = array_sum($_SESSION['cart']);
                                    }
                                    if ($cartCount > 0) {
                                        echo '<span class="badge badge-pill badge-danger cart-count">' . $cartCount . '</span>';
                                    }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'login_register' && ($_GET['show'] ?? 'login') === 'login') ? 'active' : ''; ?>" href="index.php?page=login_register&show=login">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPagePHP === 'login_register' && ($_GET['show'] ?? '') === 'register') ? 'active' : ''; ?>" href="index.php?page=login_register&show=register">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php
        // Consolidate message display logic
        $messageTypes = [
            'login_message' => 'success', 'admin_login_message' => 'success', 'cart_message' => 'info',
            'success_message' => 'success', 'newsletter_message' => 'info', // Assuming info for newsletter
            'login_error' => 'danger', 'admin_login_error' => 'danger', 'cart_error' => 'warning',
            'edit_profile_error' => 'danger', 'contact_error' => 'danger', // Added contact error
            'admin_product_success' => 'success', 'admin_product_error' => 'danger', // Added product messages
            'admin_customer_success' => 'success', 'admin_customer_error' => 'danger', // Added customer messages
            'admin_order_success' => 'success', 'admin_order_error' => 'danger', // Added order messages
            'admin_success_message' => 'success', 'admin_edit_profile_error' => 'danger', // Added admin profile messages
            'admin_change_password_error' => 'danger', // Added admin password error
            'checkout_error' => 'danger', // Added checkout error
            'contact_success' => 'success', // Added contact success
        ];

        foreach ($messageTypes as $sessionKey => $alertType) {
            if (isset($_SESSION[$sessionKey])) {
                echo '<div class="alert alert-' . $alertType . ' alert-dismissible fade show global-message" role="alert">'
                   . nl2br(htmlspecialchars($_SESSION[$sessionKey])) // Use nl2br for potential multi-line errors
                   // Optional: Add a close button
                   // . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                   . '</div>';
                unset($_SESSION[$sessionKey]);
            }
        }
        ?>
