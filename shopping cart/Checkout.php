<?php

session_start();

$products = [
    1 => ['name' => 'Product A', 'price' => 10.00],
    2 => ['name' => 'Product B', 'price' => 20.00],
    3 => ['name' => 'Product C', 'price' => 30.00],
];

// Calculate total
$total = 0;
if ($_SESSION['cart']) {
    foreach ($_SESSION['cart'] as $id => $quantity) {
        $total += $products[$id]['price'] * $quantity;
    }
}

// Display checkout
echo "<h1>Checkout</h1>";
if ($_SESSION['cart']) {
    echo "<p>Total: $ {$total}</p>";
    echo "<p>Order created successfully!</p>";
    $_SESSION['cart'] = []; // Clear cart after checkout
} else {
    echo "<p>Your cart is empty.</p>";
}
?>