<?php
// checkout.php (Ensure filename is lowercase)
if (!isset($_SESSION['customerID'])) {
    $_SESSION['redirect_after_login'] = 'index.php?page=checkout'; 
    $_SESSION['login_notice'] = 'Please log in to proceed to checkout.';
    header('Location: index.php?page=login_register&notice=login_required');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: index.php?page=shopping-cart&notice=cart_empty');
    exit;
}

$cartItemsDetails = [];
$totalPrice = 0;
$fetchError = null;
$checkoutNotice = []; 
$sessionCartMessage = $_SESSION['cart_message'] ?? null;
$sessionCartError = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_message'], $_SESSION['cart_error']);

if ($sessionCartMessage) $checkoutNotice[] = $sessionCartMessage;

if (!empty($_SESSION['cart'])) {
    $productIDs = array_keys($_SESSION['cart']);
    if (!empty($productIDs)) {
        $placeholders = implode(',', array_fill(0, count($productIDs), '?'));

        try {
            $stmt = $db->prepare("SELECT productID, pname, price, stockquantity, image_path FROM product WHERE productID IN ($placeholders)");
            $stmt->execute($productIDs);
            $productsFromDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $productMap = [];
            foreach ($productsFromDB as $p) {
                $productMap[$p['productID']] = $p;
            }

            foreach ($_SESSION['cart'] as $id => $quantity) {
                if (isset($productMap[$id])) {
                    $productData = $productMap[$id];
                    $actualQuantity = $quantity;
                    if ($actualQuantity <= 0 || ($productData['stockquantity'] ?? 0) <= 0) {
                        unset($_SESSION['cart'][$id]);
                        $checkoutNotice[] = "Item '".htmlspecialchars($productData['pname'])."' removed (unavailable or invalid quantity).";
                        continue; 
                    }

                    $imageSrc = 'images/product-placeholder.jpg';
                    if (!empty($productData['image_path']) && file_exists($productData['image_path'])) {
                        $imageSrc = htmlspecialchars($productData['image_path']) . '?t=' . time();
                    } elseif (!empty($productData['image_path'])) {
                        error_log("Checkout - Missing image file for product ID {$id}: {$productData['image_path']}");
                    }


                    $cartItemsDetails[$id] = [
                        'id' => $id,
                        'name' => $productData['pname'],
                        'price' => $productData['price'],
                        'image' => $imageSrc,
                        'quantity' => $actualQuantity,
                        'subtotal' => $productData['price'] * $actualQuantity
                    ];
                    $totalPrice += $cartItemsDetails[$id]['subtotal'];
                } else {
                    unset($_SESSION['cart'][$id]);
                    $checkoutNotice[] = "An item was removed as it's no longer available.";
                }
            }

             if (empty($cartItemsDetails)) {
                 $fetchError = "Your cart is now empty after final validation. Please add items to proceed.";
                 $_SESSION['cart'] = [];
             }

        } catch (PDOException $e) {
            error_log("Checkout Cart Fetch DB Error: " . $e->getMessage());
            $fetchError = "Could not load cart details. Please try again later or return to your cart.";
             $cartItemsDetails = [];
             $totalPrice = 0;
        }
    } else {
         $fetchError = "Your shopping cart is empty.";
         $cartItemsDetails = [];
         $totalPrice = 0;
    }
} else {
    $fetchError = "Your shopping cart is empty.";
    $cartItemsDetails = [];
    $totalPrice = 0;
}

$customerID = $_SESSION['customerID'];
$customerInfo = null;
try {
    $stmtCust = $db->prepare("SELECT name, email, address, phone FROM customer WHERE customerID = ?");
    $stmtCust->execute([$customerID]);
    $customerInfo = $stmtCust->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Checkout Customer Fetch DB Error: " . $e->getMessage());
    $fetchError = ($fetchError ? $fetchError . "<br>" : "") . "Could not load saved customer details.";
}

$checkoutError = $_SESSION['checkout_error'] ?? $sessionCartError ?? null; 
unset($_SESSION['checkout_error']); 

?>

<div class="checkout-page-container">
    <h1>Checkout</h1>

    <?php if ($fetchError && strpos($fetchError, 'customer details') === false): ?>
        <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($fetchError)); ?></div>
        <?php if (strpos($fetchError, 'empty') !== false): ?>
             <p><a href="index.php?page=product-listing" class="btn btn-secondary">Continue Shopping</a></p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($checkoutError): ?>
        <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($checkoutError)); ?></div>
    <?php endif; ?>
    <?php if (!empty($checkoutNotice)): ?>
        <div class="alert alert-warning">
            <strong>Please note:</strong><br>
            <?php echo implode("<br>", array_map('htmlspecialchars', $checkoutNotice)); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($cartItemsDetails)): ?>
        <div class="checkout-layout">
            <!-- Order Summary -->
            <div class="order-summary-section">
                <h2>Order Summary</h2>
                <ul class="order-summary-items">
                    <?php foreach ($cartItemsDetails as $item): ?>
                        <li>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="summary-item-image">
                            <div class="summary-item-details">
                                <span class="summary-item-name"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                                <span class="summary-item-price">RM<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="order-summary-total">
                    <strong>Total: RM<?php echo number_format($totalPrice, 2); ?></strong>
                </div>
            </div>

            <!-- Shipping & Payment Forms -->
            <div class="checkout-form-section">
                <form action="index.php?page=process-payment" method="post" id="checkout-form">
                    <section class="checkout-section">
                        <h2>Shipping Information</h2>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($customerInfo['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Shipping Address</label>
                            <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($customerInfo['address'] ?? ''); ?></textarea>
                        </div>
                         <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customerInfo['phone'] ?? ''); ?>" placeholder="Optional">
                        </div>
                    </section>

                    <!-- Payment Information -->
                    <section class="checkout-section">
                        <h2>Payment Details</h2>
                        <p class="text-muted"><small>Note: This is a simulated payment form. Do not enter real card details.</small></p>
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" required placeholder="XXXXXXXXXXXX" pattern="\d{12}" title="Enter exactly 12 digits" maxlength="12">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" id="expiry" name="expiry" required placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/\d{2}" title="Enter date in MM/YY format">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" required placeholder="XXX" pattern="\d{3,4}" title="Enter 3 or 4 digit CVV">
                            </div>
                        </div>
                    </section>

                    <button type="submit" class="btn btn-primary btn-block">
                        Place Order Now
                    </button>
                </form>

            </div>
        </div>
    <?php elseif (!$fetchError || strpos($fetchError, 'customer details') !== false):
       echo '<p><a href="index.php?page=shopping-cart" class="btn btn-secondary">Return to Cart</a></p>';
    endif; 
    ?>
</div>