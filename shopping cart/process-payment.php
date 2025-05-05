<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = $_POST['card_number'];
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];

    // Basic validation (expand as needed)
    if ($cardNumber && $expiry && $cvv) {
        echo "<h1>Payment Successful</h1>";
        echo "<p>Thank you for your purchase!</p>";
        $_SESSION['cart'] = []; // Clear cart after successful payment
    } else {
        echo "<h1>Payment Failed</h1>";
        echo "<p>Please check your payment details and try again.</p>";
    }
} else {
    echo "<h1>Invalid Request</h1>";
}
?>
