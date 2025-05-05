<?php
// order_details.php

// Start session if not already started (Good practice if this file could be accessed directly, though unlikely via index.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (either customer or admin)
$isLoggedIn = isset($_SESSION['customerID']) || (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true);

if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Store intended destination
    $_SESSION['login_notice'] = 'Please log in to view order details.';
    header('Location: index.php?page=login_register&notice=login_required'); // Point to combined login/register
    exit;
}

// Check for database connection (assuming $db is included via index.php)
if (!isset($db) || !$db instanceof PDO) {
    // Display error within the page structure if possible, or exit
    // Using echo here might break layout if header isn't sent yet.
    // Consider a more robust error display within your template.
    $fetchError = "Database connection error. Cannot load order details.";
    // We'll display this error later in the HTML
}

$orderID = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$customerID = $_SESSION['customerID'] ?? null; // Get customer ID if logged in as customer
$isAdmin = isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true; // Check if admin

$order = null;
$orderItems = [];
$fetchError = $fetchError ?? null; // Preserve potential DB connection error

if (!$fetchError && !$orderID) {
    $fetchError = "No Order ID specified.";
}

if (!$fetchError && $orderID) {
    try {
        // Prepare the base query
        $sqlOrder = "SELECT * FROM orders WHERE orderID = :orderID";
        $paramsOrder = [':orderID' => $orderID];

        // If NOT an admin, ensure the order belongs to the logged-in customer
        if (!$isAdmin && $customerID) {
            $sqlOrder .= " AND customerID = :customerID";
            $paramsOrder[':customerID'] = $customerID;
        } elseif (!$isAdmin && !$customerID) {
            // This case shouldn't happen due to the initial login check, but as a safeguard:
            throw new Exception("Customer ID not found in session.");
        }

        $stmtOrder = $db->prepare($sqlOrder);
        $stmtOrder->execute($paramsOrder);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            // If admin couldn't find it, or customer couldn't find *their* order
            $fetchError = "Order not found or you do not have permission to view it.";
            http_response_code(404); // Set appropriate HTTP status
        } else {
            // Fetch order items if the order was found
            $stmtItems = $db->prepare("
                SELECT oi.quantity, oi.price_at_purchase, p.pname, p.image_path, p.productID
                FROM order_items oi
                JOIN product p ON oi.productID = p.productID
                WHERE oi.orderID = :orderID
            ");
            $stmtItems->bindParam(':orderID', $orderID);
            $stmtItems->execute();
            $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $logCustID = $customerID ?? 'N/A';
        error_log("Order Details Fetch Error (OrderID: {$orderID}, CustID: {$logCustID}, IsAdmin: {$isAdmin}): " . $e->getMessage());
        $fetchError = "Could not load order details due to a database issue. Please try again later.";
    } catch (Exception $e) { // Catch other potential errors
        error_log("Order Details General Error: " . $e->getMessage());
        $fetchError = "An unexpected error occurred.";
    }
}

$currentPagePHP = 'order_details'; // Used for sidebar active state

?>

<div class="profile-container <?php echo $isAdmin ? 'admin-page-container' : ''; // Add admin class if needed ?>">

    <?php
    // *** ADDED: Include appropriate sidebar ***
    if ($isAdmin) {
        include 'admin_sidebar.php'; // Include admin sidebar
    } else {
        // Assuming you have a customer sidebar snippet like 'customer_sidebar.php'
        // If not, you might need to create one based on customer_profile.php's sidebar
        // include 'customer_sidebar.php';
        // Or reuse the one from customer_profile.php if it's suitable
        
    }
    ?>

    <div class="profile-content <?php echo $isAdmin ? 'admin-content' : ''; ?>">
        <div class="mb-3" style="text-align: left;">
            <?php // Back button depends on user type ?>
            <?php if ($isAdmin): ?>
                 <a href="index.php?page=admin_manage_orders" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Manage Orders
                </a>
            <?php else: ?>
                 <a href="index.php?page=order_history" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Order History
                </a>
            <?php endif; ?>
        </div>

        <h1 class="section-title">Order Details</h1>

        <?php if ($fetchError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <?php elseif ($order): ?>
            <div class="order-details-summary card mb-4">
                <div class="card-header">
                    Order Summary
                </div>
                <div class="card-body">
                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['orderID']); ?></p>
                    <p><strong>Date Placed:</strong> <?php echo date('M j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                    <p><strong>Order Total:</strong> RM<?php echo number_format($order['total_amount'], 2); // *** FIXED: Changed $ to RM *** ?></p>
                    <p><strong>Status:</strong>
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $order['status'])); // Basic status styling ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </p>
                    <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="order-details-shipping card mb-4">
                 <div class="card-header">
                    Shipping Information
                </div>
                 <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                    <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <?php if (!empty($order['shipping_phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                    <?php endif; ?>
                    <?php // Display tracking only if admin or if status implies shipped (optional)
                        $canShowTracking = $isAdmin || in_array($order['status'], ['Shipped', 'Delivered']);
                    ?>
                    <?php if ($canShowTracking && !empty($order['tracking_number'])): ?>
                        <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?> <?php /* Add tracking link if possible */ ?></p>
                    <?php elseif ($canShowTracking && empty($order['tracking_number']) && $order['status'] === 'Shipped'): ?>
                         <p><strong>Tracking Number:</strong> Not yet available</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-details-items card">
                 <div class="card-header">
                    Items Ordered
                </div>
                 <div class="card-body">
                    <?php if (!empty($orderItems)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th colspan="2">Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <?php
                                            // Image source logic
                                            $imageSrc = 'images/product-placeholder.jpg'; // Default placeholder
                                            if (!empty($item['image_path']) && file_exists($item['image_path'])) {
                                                $imageSrc = htmlspecialchars($item['image_path']) . '?t=' . time(); // Add cache buster
                                            } elseif (!empty($item['image_path'])) {
                                                error_log("Order Details - Missing image file for product ID {$item['productID']}: {$item['image_path']}");
                                            }
                                            $itemSubtotal = $item['price_at_purchase'] * $item['quantity'];
                                        ?>
                                        <tr>
                                            <td style="width: 80px;">
                                                <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($item['pname']); ?>" style="width: 60px; height: auto; border: 1px solid #eee;">
                                            </td>
                                            <td>
                                                <a href="index.php?page=product-detail&id=<?php echo htmlspecialchars($item['productID']); ?>">
                                                    <?php echo htmlspecialchars($item['pname']); ?>
                                                </a>
                                            </td>
                                            <td>RM<?php echo number_format($item['price_at_purchase'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>RM<?php echo number_format($itemSubtotal, 2); // *** ADDED: RM symbol *** ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>Could not retrieve items for this order.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; // End if ($order) ?>
    </div> <!-- /profile-content -->
</div> <!-- /profile-container -->
