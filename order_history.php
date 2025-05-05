<?php
// order_history.php
if (!isset($_SESSION['customerID'])) {
    $_SESSION['redirect_after_login'] = 'index.php?page=order_history'; // Store intended destination
    $_SESSION['login_notice'] = 'Please log in to view your order history.';
    header('Location: index.php?page=login_register&notice=login_required'); // Point to combined login/register
    exit;
}
if (!isset($db) || !$db instanceof PDO) {
    echo "<div class='container alert alert-danger'>Database connection error. Cannot load order history.</div>";
    exit;
}

$customerID = $_SESSION['customerID'];
$orders = [];
$fetchError = null;


    try {
        $stmt = $db->prepare("
            SELECT orderID, order_date, total_amount, status
            FROM orders
            WHERE customerID = :customerID
            ORDER BY order_date DESC
        ");
        $stmt->bindParam(':customerID', $customerID);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

} catch (PDOException $e) {
    error_log("Order History Fetch Error (CustomerID: {$customerID}): " . $e->getMessage());
    $fetchError = "Could not load your order history. Please try again later.";
}

$currentPagePHP = 'order_history';

?>
<div class="profile-container">
    <?php
    ?>
    <div class="profile-content"> 
        <h1 class="section-title">My Order History</h1>

        <?php if ($fetchError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <?php endif; ?>

        <?php if (empty($orders) && !$fetchError): ?>
            <div class="alert alert-info">You haven't placed any orders yet.</div>
            <a href="index.php?page=product-listing" class="btn btn-primary">Start Shopping</a>
        <?php elseif (!empty($orders)): ?>
            <div class="order-history-list table-responsive"> 
                <table class="table table-striped table-bordered table-hover"> 
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date Placed</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['orderID']); ?></td>
                                <td><?php echo date('M j, Y, g:i a', strtotime($order['order_date'])); // More readable date format ?></td>
                                <td>RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $order['status'])); // Basic status styling ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?page=order_details&id=<?php echo htmlspecialchars($order['orderID']); ?>" class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>