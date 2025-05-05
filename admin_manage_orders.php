<?php
// admin_manage_orders.php
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    $_SESSION['admin_login_notice'] = 'Admin access required to manage orders.';
    header('Location: index.php?page=admin_login&notice=admin_required');
    exit;
}
$fetchError = null;
$orders = [];
$possibleStatuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled', 'Pending Payment', 'On Hold', 'Paid', 'Unpaid']; // Keep consistent with index.php

$successMessage = $_SESSION['admin_order_success'] ?? null;
$errorMessage = $_SESSION['admin_order_error'] ?? null;
unset($_SESSION['admin_order_success'], $_SESSION['admin_order_error']);

try {
    $stmt = $db->query("
        SELECT o.orderID, o.order_date, o.total_amount, o.status, o.shipping_name, o.shipping_address,
               c.name as customer_name, c.email as customer_email
        FROM orders o -- <<< CHANGED FROM porder
        LEFT JOIN customer c ON o.customerID = c.customerID
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin Manage Orders DB Error: " . $e->getMessage());
    $fetchError = "Error fetching order data. Please try again.";
}

$currentPagePHP = 'admin_manage_orders';
?>

<div class="admin-page-container">
    <?php include 'admin_sidebar.php'; ?>

    <div class="admin-content">
        <h1>Manage Orders</h1>
        <?php if ($successMessage): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div><?php endif; ?>
        <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div><?php endif; ?>
        <?php if ($fetchError): ?><div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div><?php endif; ?>

        <?php if (!empty($orders)): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['orderID']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name'] ?? $order['shipping_name'] ?? 'N/A'); ?>
                                <?php if (!empty($order['customer_email'])): ?>
                                    <br><small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($order['shipping_address'])): ?>
                                     <br><small><i>Addr: <?php echo htmlspecialchars(substr($order['shipping_address'], 0, 50)) . '...'; ?></i></small>
                                <?php endif; ?>
                            </td>
                            <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <form action="index.php?page=admin_manage_orders" method="post" class="form-inline">
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="orderID" value="<?php echo htmlspecialchars($order['orderID']); ?>">
                                    <select name="status" class="form-control form-control-sm mr-2" style="min-width: 120px;">
                                        <?php foreach ($possibleStatuses as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo ($order['status'] === $status) ? 'selected' : ''; ?>>
                                                <?php echo $status; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                          
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!$fetchError): ?>
            <div class="alert alert-info">No orders found.</div>
        <?php endif; ?>

    </div>
</div>


