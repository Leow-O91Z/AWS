<?php
// admin_manage_customers.php 
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    $_SESSION['admin_login_notice'] = 'Admin access required to manage customers.';
    header('Location: index.php?page=admin_login&notice=admin_required');
    exit;
}

if (!isset($db) || !$db instanceof PDO) {
    echo "<div class='alert alert-danger'>Database connection error.</div>"; exit;
}

$sortableFields = [
    'customerID' => 'ID',
    'name'       => 'Name',
    'email'      => 'Email',
    'phone'      => 'Phone',
    'address'    => 'Address',
];

$sortColumn = $_GET['sort'] ?? 'name';
if (!array_key_exists($sortColumn, $sortableFields)) {
    $sortColumn = 'name'; 
}

$sortDir = $_GET['dir'] ?? 'asc'; 
if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
    $sortDir = 'asc';
}
$sortDir = strtolower($sortDir); 
function table_headers(array $fields, string $currentSort, string $currentDir): string {
    $output = '';
    foreach ($fields as $field => $label) {
        $order = 'asc'; 
        $icon = '';

        if ($field === $currentSort) {
            $order = ($currentDir === 'asc') ? 'desc' : 'asc';
            $icon = ($currentDir === 'asc') ? ' ▴' : ' ▾';
        }

        $urlParams = http_build_query([
            'page' => 'admin_manage_customers', 
            'sort' => $field,
            'dir' => $order
        ]);

        $output .= "<th><a href=\"index.php?{$urlParams}\">" . htmlspecialchars($label) . $icon . "</a></th>";
    }
    return $output;
}

$fetchError = null;
$customers = [];

$successMessage = $_SESSION['admin_customer_success'] ?? null;
$errorMessage = $_SESSION['admin_customer_error'] ?? null; 
unset($_SESSION['admin_customer_success'], $_SESSION['admin_customer_error']); 

try {
    $sql = "SELECT customerID, name, email, phone, address
            FROM customer
            ORDER BY {$sortColumn} {$sortDir}"; 
    $stmt = $db->query($sql);

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin Manage Customers DB Error: " . $e->getMessage());
    $fetchError = "Error fetching customer data. Please try again.";
}

$currentPagePHP = 'admin_manage_customers'; 
?>

<div class="admin-page-container">
    <!-- Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <div class="admin-content">
        <h1>Manage Customers</h1>

        <!-- Display Messages -->
        <?php if ($successMessage): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div><?php endif; ?>
        <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div><?php endif; ?>
        <?php if ($fetchError): ?><div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div><?php endif; ?>

        <?php if (!empty($customers)): ?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <?php echo table_headers($sortableFields, $sortColumn, $sortDir); ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['customerID']); ?></td>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($customer['address'] ?? 'N/A')); ?></td>
                            <td>
                                <form action="index.php?page=admin_manage_customers" method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this customer and all their related data (e.g., orders)? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="customerID" value="<?php echo htmlspecialchars($customer['customerID']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Customer">
                                       Delete <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!$fetchError): ?>
            <div class="alert alert-info">No customers found.</div>
        <?php endif; ?>

    </div> 
</div> 