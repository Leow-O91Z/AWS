<?php
// order-confirmation.php

$orderID = $_SESSION['last_order_id'] ?? null;
$orderTotal = $_SESSION['last_order_total'] ?? null;

// Clear the session variables after retrieving them
unset($_SESSION['last_order_id'], $_SESSION['last_order_total']);

// Check if the status indicates success and we have an order ID
$status = $_GET['status'] ?? 'failed';

?>

<div class="order-confirmation-page" style="text-align: center; padding: 40px 15px;">

    <?php if ($status === 'success' && $orderID): ?>
        <i class="fas fa-check-circle" style="font-size: 4em; color: #28a745; margin-bottom: 20px;"></i>
        <h1>Thank You For Your Order!</h1>
        <p>Your order has been placed successfully.</p>
        <p>Your Order ID is: <strong><?php echo htmlspecialchars($orderID); ?></strong></p>
        <?php if ($orderTotal !== null): ?>
            <p>Order Total: <strong>RM<?php echo number_format($orderTotal, 2); ?></strong></p>
        <?php endif; ?>
        <p>You will receive an email confirmation shortly (if email sending is configured).</p>
        <p>You can view your order details in your <a href="index.php?page=order_history">Order History</a>.</p>
        <div style="margin-top: 30px;">
            <a href="index.php?page=product-listing" class="btn btn-primary">Continue Shopping</a>
        </div>

    <?php else: ?>
        <i class="fas fa-exclamation-triangle" style="font-size: 4em; color: #dc3545; margin-bottom: 20px;"></i>
        <h1>Order Placement Issue</h1>
        <p>There seems to have been an issue placing your order.</p>
        <?php if (isset($_SESSION['checkout_error'])): ?>
            <p style="color: #dc3545;"><strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['checkout_error']); ?></p>
            <?php unset($_SESSION['checkout_error']); ?>
        <?php endif; ?>
        <p>Please <a href="index.php?page=checkout">try checking out again</a> or contact customer support if the problem persists.</p>
         <div style="margin-top: 30px;">
            <a href="index.php?page=shopping-cart" class="btn btn-secondary">Return to Cart</a>
        </div>
    <?php endif; ?>

</div>
