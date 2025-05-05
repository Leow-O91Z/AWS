<?php
// Start session
session_start();

// Include database connection
require_once 'database.php';

// Define the page variable
$page = $_GET['page'] ?? 'home';

// Include header
require_once 'header.php';

// Route to the appropriate page
switch ($page) {
    case 'profile':
        include 'customer_profile.php';
        break;
    case 'edit_profile':
        include 'edit_profile.php';
        break;
    case 'change_password':
        include 'change_password.php';
        break;
    case 'mycart':
        include 'mycart.php';
        break;
    case 'login':
        include 'login.php';
        break;
    case 'register':
        include 'register.php';
        break;
    case 'logout':
        // Destroy session and redirect to home
        session_destroy();
        header('Location: index.php');
        exit;
    default:
        include 'home.php';
        break;
}

// Include footer
require_once 'footer.php';
?>