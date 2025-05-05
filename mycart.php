<?php

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

// Handle actions (Uses GET - Not recommended)
if (isset($_GET['add'])) {
    addToCart($_GET['add'], $products);
    // Missing redirect
}

if (isset($_GET['remove'])) {
    removeFromCart($_GET['remove']);
}

if (isset($_GET['clear'])) {
    clearCart();
}
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
</head>
<body>
    <h1>Shopping Cart</h1>
    <?php if (!empty($_SESSION['cart'])): ?>
        <?php foreach ($_SESSION['cart'] as $id => $quantity): ?>
            <?php
                $product = $products[$id] ?? null;
            ?>
            <?php if ($product): ?>
                <p>
                    <?= htmlspecialchars($product['name']) ?> - $ <?= number_format($product['price'], 2) ?> x <?= $quantity ?>
                    <a href="index.php?page=shopping-cart&remove=<?= $id ?>">Remove</a> 
                </p>
            <?php else: ?>
                 <p>Item with ID <?= htmlspecialchars($id) ?> not found (Quantity: <?= $quantity ?>) <a href="index.php?page=shopping-cart&remove=<?= $id ?>">Remove</a></p>
            <?php endif; ?>
        <?php endforeach; ?>
        <a href="index.php?page=checkout">Proceed to Checkout</a> 
        <br><a href="index.php?page=shopping-cart&clear=true"><button>Clear Cart</button></a> 
        <br><a href="index.php?page=home"><button>Back to Homepage</button></a>
    <?php else: ?>
        <p>Your cart is empty.</p>
        <br><a href="index.php?page=product-listing"><button>Start Shopping</button></a>
    <?php endif; ?>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
?>