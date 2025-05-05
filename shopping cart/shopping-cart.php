<?php

session_start();

$products = [
    1 => ['name' => 'Product A', 'price' => 10.00],
    2 => ['name' => 'Product B', 'price' => 20.00],
    3 => ['name' => 'Product C', 'price' => 30.00],
];

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart
function addToCart($id, $products) {
    if (isset($products[$id])) {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    }
}

// Remove product from cart
function removeFromCart($id) {
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
}

// Clear cart
function clearCart() {
    $_SESSION['cart'] = [];
}

// Handle actions
if (isset($_GET['add'])) {
    addToCart($_GET['add'], $products);
}

if (isset($_GET['remove'])) {
    removeFromCart($_GET['remove']);
}

if (isset($_GET['clear'])) {
    clearCart();
}

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
</head>
<body>
    <h1>Shopping Cart</h1>
    <?php if ($_SESSION['cart']): ?>
        <?php foreach ($_SESSION['cart'] as $id => $quantity): ?>
            <?php $product = $products[$id]; ?>
            <p>
                <?= htmlspecialchars($product['name']) ?> - $ <?= number_format($product['price'], 2) ?> x <?= $quantity ?>
                <a href="shopping-cart.php?remove=<?= $id ?>">Remove</a>
            </p>
        <?php endforeach; ?>
        <a href="checkout.php"><button>Proceed to Checkout</button></a>
        <br><a href="shopping-cart.php?clear=true"><button>Clear Cart</button></a>
        <br><a href="index.php"><button>Back to Homepage</button></a>
    <?php else: ?>
        <p>Your cart is empty.</p>
        <br><a href="index.php"><button>Back to Homepage</button></a>
    <?php endif; ?>
</body>
</html>
<?php
// End output buffering and output the content
$content = ob_get_clean();
echo $content;
?>