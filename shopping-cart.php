<?php
// shopping-cart.php

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItemsDetails = [];
$totalPrice = 0;
$fetchError = null;
$cartMessage = $_SESSION['cart_message'] ?? null;
$cartError = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_message'], $_SESSION['cart_error']); // Clear messages

if (!empty($_SESSION['cart'])) {
    $productIDs = array_keys($_SESSION['cart']);
    if (!empty($productIDs)) {
        $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
        try {
            $stmt = $db->prepare("SELECT productID, pname, price, stockquantity, image_path FROM product WHERE productID IN ($placeholders)");
            $stmt->execute($productIDs);
            $productsFromDB = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productsFromDB[$row['productID']] = $row;
            }

            foreach ($_SESSION['cart'] as $id => $quantity) {
                if (isset($productsFromDB[$id])) {
                    $productData = $productsFromDB[$id];
                    $actualQuantity = $_SESSION['cart'][$id];
                    if ($actualQuantity <= 0 || ($productData['stockquantity'] ?? 0) <= 0) {
                         unset($_SESSION['cart'][$id]);
                         $cartMessage = ($cartMessage ? $cartMessage."<br>" : "") . "Item '".htmlspecialchars($productData['pname'])."' removed (out of stock or invalid quantity).";
                         continue; 
                    }

                    // Determine image source
                    $imageSrc = 'images/product-placeholder.jpg'; 
                    if (!empty($productData['image_path']) && file_exists($productData['image_path'])) {
                        $imageSrc = htmlspecialchars($productData['image_path']) . '?t=' . time(); 
                    } elseif (!empty($productData['image_path'])) {
                        error_log("Shopping Cart - Missing image file for product ID {$id}: {$productData['image_path']}");
                    }


                    $cartItemsDetails[$id] = [
                        'id' => $id,
                        'name' => $productData['pname'],
                        'price' => $productData['price'],
                        'image' => $imageSrc, 
                        'stock' => $productData['stockquantity'] ?? 0, 
                        'quantity' => $actualQuantity,
                        'subtotal' => $productData['price'] * $actualQuantity
                    ];
                    $totalPrice += $cartItemsDetails[$id]['subtotal'];
                } else {
                    unset($_SESSION['cart'][$id]);
                    $cartMessage = ($cartMessage ? $cartMessage."<br>" : "") . "An item was removed as it's no longer available.";
                }
            }
        } catch (PDOException $e) {
            error_log("Shopping Cart DB Error: " . $e->getMessage());
            $fetchError = "Could not load cart details. Please try again later.";
            $_SESSION['cart'] = [];
            $cartItemsDetails = [];
            $totalPrice = 0;
        }
    } else {
         $_SESSION['cart'] = [];
    }
}

?>

<div class="cart-page-container">
    <h1>Your Shopping Cart</h1>

    <?php if ($fetchError): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
    <?php endif; ?>
    <?php if ($cartMessage): ?>
        <div class="alert alert-warning"><?php echo nl2br(htmlspecialchars($cartMessage)); ?></div>
    <?php endif; ?>
     <?php if ($cartError): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($cartError); ?></div>
    <?php endif; ?>


    <?php if (!empty($cartItemsDetails)): ?>
        <div class="cart-items-list">
            <table>
                <thead>
                    <tr>
                        <th colspan="2">Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItemsDetails as $item): ?>
                        <tr>
                            <td class="cart-item-image">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </td>
                            <td class="cart-item-name">
                                <a href="index.php?page=product-detail&id=<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </td>
                            <td class="cart-item-price">RM<?php echo number_format($item['price'], 2); ?></td>
                            <td class="cart-item-quantity">
                                <form action="index.php?page=shopping-cart" method="post" class="quantity-form">
                                    <input type="hidden" name="update_quantity_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input" aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
                                    <button type="submit" class="btn-update-qty">Update</button>
                                </form>
                            </td>
                            <td class="cart-item-subtotal">RM<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td class="cart-item-action">
                                <form action="index.php?page=shopping-cart" method="post">
                                    <input type="hidden" name="remove_item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-summary">
            <div class="cart-total">
                <strong>Total: RM<?php echo number_format($totalPrice, 2); ?></strong>
            </div>
            <div class="cart-actions">
                <form action="index.php?page=shopping-cart" method="post" style="display: inline;">
                    <input type="hidden" name="clear_cart" value="1">
                    <button type="submit" class="btn btn-outline">Clear Cart</button>
                </form>
                <a href="index.php?page=product-listing" class="btn btn-secondary">Continue Shopping</a>
                <a href="index.php?page=checkout" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        </div>

    <?php else: ?>
        <div class="cart-empty">
            <p>Your shopping cart is currently empty.</p>
            <a href="index.php?page=product-listing" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>