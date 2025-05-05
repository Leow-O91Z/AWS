<?php
// process-payment.php :
header('Location: index.php?page=checkout');
exit;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = $_POST['card_number'];
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];

    if ($cardNumber && $expiry && $cvv) {
        $paymentSuccessful = true; // Assume success for now

        if ($paymentSuccessful) {
            $_SESSION['cart'] = [];
            header('Location: index.php?page=order-confirmation&status=success');
            exit;
        } else {
            $_SESSION['checkout_error'] = 'Payment failed.';
            header('Location: index.php?page=checkout');
            exit;
        }
    } else {
        $_SESSION['checkout_error'] = 'Please fill in all required payment details.';
        header('Location: index.php?page=checkout');
        exit;
    }
} else {
    header('Location: index.php?page=checkout');
    exit;
}

?>
