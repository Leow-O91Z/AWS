<?php
// admin_manage_products.php
if (!isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] !== true) {
    $_SESSION['admin_login_notice'] = 'Admin access required to manage products.';
    header('Location: index.php?page=admin_login&notice=admin_required');
    exit;
}
if (!isset($db) || !$db instanceof PDO) {
    echo "<div class='alert alert-danger'>Database connection error.</div>"; exit;
}

$fetchError = null;
$products = [];
$editProduct = null;
$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;

// --- Search and Filter Logic ---
$searchTerm = trim($_GET['query'] ?? ''); // Get search term

$sortableFields = [
    'productID'    => 'ID',
    'pname'        => 'Name',
    'price'        => 'Price',
    'stockquantity'=> 'Stock',
    'category'     => 'Category',
    'brand'        => 'Brand',
];

$sortColumn = $_GET['sort'] ?? 'pname';
if (!array_key_exists($sortColumn, $sortableFields)) {
    $sortColumn = 'pname';
}

$sortDir = $_GET['dir'] ?? 'asc';
if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
    $sortDir = 'asc';
}
$sortDir = strtolower($sortDir);

// --- Pagination Setup ---
$productsPerPage = 15; // Adjusted for potentially more data
$totalProducts = 0;
$totalPages = 1;
$currentPage = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;

$successMessage = $_SESSION['admin_product_success'] ?? null;
$errorMessage = $_SESSION['admin_product_error'] ?? null;
unset($_SESSION['admin_product_success'], $_SESSION['admin_product_error']);

try {
    // --- Build Base Query ---
    $baseSelect = "SELECT productID, pname, price, stockquantity, category, brand, image_path"; // Added image_path
    $baseFrom = "FROM product";
    $baseWhere = "WHERE 1=1"; // Start with a true condition

    $params = [];
    $countParams = [];

    // --- Add Search Condition ---
    if (!empty($searchTerm)) {
        // *** MODIFIED: Only search pname ***
        // Original line: $baseWhere .= " AND (pname LIKE :searchTerm OR productID LIKE :searchTerm)";
        $baseWhere .= " AND pname LIKE :searchTerm"; // Search name ONLY
        $params[':searchTerm'] = '%' . $searchTerm . '%';
        $countParams[':searchTerm'] = '%' . $searchTerm . '%';
    }

    // --- Handle different actions ---
    if ($action === 'list' || $action === 'bulk_edit_stock') {
        // --- Get Total Count for Pagination (with search filter) ---
        $countSql = "SELECT COUNT(*) " . $baseFrom . " " . $baseWhere;
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($countParams);
        $totalProducts = $stmtCount->fetchColumn();

        $totalPages = ceil($totalProducts / $productsPerPage);
        $currentPage = min($currentPage, $totalPages > 0 ? $totalPages : 1);
        $offset = ($currentPage - 1) * $productsPerPage;

        // --- Fetch Products for the Current Page (with search, sort, limit) ---
        $sql = $baseSelect . " " . $baseFrom . " " . $baseWhere;
        $sql .= " ORDER BY {$sortColumn} {$sortDir}"; // Apply sorting
        $sql .= " LIMIT :limit OFFSET :offset"; // Apply pagination

        $stmt = $db->prepare($sql);

        // Bind common parameters (search term if present)
        if (!empty($searchTerm)) {
            $stmt->bindValue(':searchTerm', $params[':searchTerm'], PDO::PARAM_STR);
        }
        // Bind pagination parameters
        $stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($action === 'edit' && $productId) {
        // Fetch product for editing (no search/pagination needed here)
        $stmt = $db->prepare("SELECT productID, pname, brand, category, price, description, sizeoption, color, stockquantity, image_path FROM product WHERE productID = ?");
        $stmt->execute([$productId]);
        $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editProduct) {
            $_SESSION['admin_product_error'] = "Product with ID " . htmlspecialchars($productId) . " not found for editing.";
            header('Location: index.php?page=admin_manage_products');
            exit;
        }
    }
    // 'add' action doesn't need initial data fetching

} catch (PDOException $e) {
    error_log("Admin Manage Products DB Error: " . $e->getMessage());
    $fetchError = "Error fetching product data. Please try again.";
    // Redirect only if the error occurs during an action that expects data
    if ($action === 'edit' || $action === 'bulk_edit_stock' || $action === 'list') {
        $_SESSION['admin_product_error'] = $fetchError;
        header('Location: index.php?page=admin_manage_products');
        exit;
    }
}

$currentPagePHP = 'admin_manage_products';

function table_headers(array $fields, string $currentSort, string $currentDir, string $currentSearch): string {
    $output = '';
    foreach ($fields as $field => $label) {
        $order = 'asc';
        $icon = '';

        if ($field === $currentSort) {
            $order = ($currentDir === 'asc') ? 'desc' : 'asc';
            $icon = ($currentDir === 'asc') ? ' ▴' : ' ▾';
        }

        // Include search query in sorting links
        $urlParams = [
            'page' => 'admin_manage_products',
            'action' => 'list',
            'sort' => $field,
            'dir' => $order
        ];
        if (!empty($currentSearch)) {
            $urlParams['query'] = $currentSearch;
        }

        $output .= "<th><a href=\"index.php?" . http_build_query($urlParams) . "\">" . htmlspecialchars($label) . $icon . "</a></th>";
    }
    return $output;
}
?>

<div class="admin-page-container">
    <?php include 'admin_sidebar.php'; ?>

    <div class="admin-content">
        <h1>Manage Products</h1>
        <?php if ($successMessage): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div><?php endif; ?>
        <?php if ($errorMessage): ?><div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($errorMessage)); // Use nl2br for multi-line errors ?></div><?php endif; ?>
        <?php if ($fetchError && $action === 'list'): // Only show fetch error on list view if it occurred ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <h2><?php echo ($action === 'edit' ? 'Edit Product' : 'Add New Product'); ?></h2>
            <form action="index.php?page=admin_manage_products" method="post" class="admin-form mb-4" enctype="multipart/form-data">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="action" value="update_product">
                    <!-- IMPORTANT: Storing the original ID to find the record -->
                    <input type="hidden" name="original_productID" value="<?php echo htmlspecialchars($editProduct['productID']); ?>">
                    <div class="form-group">
                        <!-- WARNING: Editing Primary Keys (like productID) is generally NOT recommended due to potential data integrity issues. -->
                        <label for="productID">Product ID <span class="required">*</span></label>
                        <input type="text" class="form-control" id="productID" name="productID" required value="<?php echo htmlspecialchars($editProduct['productID']); ?>" maxlength="40" pattern="^[A-Z]+[0-9]{3}$" title="Format: Uppercase letters followed by 3 numbers (e.g., FLO123)">
                     </div>
                <?php else: ?>
                    <input type="hidden" name="action" value="add_product">
                     <div class="form-group">
                        <label for="productID">Product ID <span class="required">*</span></label>
                        <!-- Added pattern and title for format validation -->
                        <input type="text" class="form-control" id="productID" name="productID" required value="<?php echo htmlspecialchars($_SESSION['admin_product_form_data']['productID'] ?? ''); ?>" maxlength="40" pattern="^[A-Z]+[0-9]{3}$" title="Format: Uppercase letters followed by 3 numbers (e.g., FLO123)">
                        <small class="form-text text-muted">Format: Uppercase letters followed by 3 numbers (e.g., FLO123)</small>
                     </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="pname">Product Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="pname" name="pname" required value="<?php echo htmlspecialchars($editProduct['pname'] ?? $_SESSION['admin_product_form_data']['pname'] ?? ''); ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($editProduct['description'] ?? $_SESSION['admin_product_form_data']['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="price">Price <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required value="<?php echo htmlspecialchars($editProduct['price'] ?? $_SESSION['admin_product_form_data']['price'] ?? ''); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="stockquantity">Stock Quantity <span class="required">*</span></label>
                        <input type="number" min="0" class="form-control" id="stockquantity" name="stockquantity" required value="<?php echo htmlspecialchars($editProduct['stockquantity'] ?? $_SESSION['admin_product_form_data']['stockquantity'] ?? '0'); ?>">
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="category">Category</label>
                        <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($editProduct['category'] ?? $_SESSION['admin_product_form_data']['category'] ?? ''); ?>" maxlength="100">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="brand">Choice</label>
                        <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($editProduct['brand'] ?? $_SESSION['admin_product_form_data']['brand'] ?? ''); ?>" maxlength="100">
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="sizeoption">Size Options</label>
                        <input type="text" class="form-control" id="sizeoption" name="sizeoption" placeholder="e.g., US 9" value="<?php echo htmlspecialchars($editProduct['sizeoption'] ?? $_SESSION['admin_product_form_data']['sizeoption'] ?? ''); ?>" maxlength="10">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="color">Option</label>
                        <input type="text" class="form-control" id="color" name="color" placeholder="e.g., See Image" value="<?php echo htmlspecialchars($editProduct['color'] ?? $_SESSION['admin_product_form_data']['color'] ?? ''); ?>" maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <input type="file" class="form-control-file" id="product_image" name="product_image" accept="image/jpeg, image/png, image/gif">
                    <small class="form-text text-muted">Allowed: JPG, PNG, GIF. Max 2MB. <?php echo ($action === 'edit' ? 'Leave blank to keep current image.' : ''); ?></small>
                    <?php if ($action === 'edit' && !empty($editProduct['image_path']) && file_exists($editProduct['image_path'])): ?>
                        <div class="mt-2">
                            <small>Current Image:</small><br>
                            <img src="<?php echo htmlspecialchars($editProduct['image_path']); ?>?t=<?php echo time(); ?>" alt="Current Product Image" style="max-height: 80px; border: 1px solid #ddd; margin-top: 5px;">
                            <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($editProduct['image_path']); ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo ($action === 'edit' ? 'Update Product' : 'Add Product'); ?></button>
                <a href="index.php?page=admin_manage_products" class="btn btn-secondary">Cancel</a>
            </form>
            <?php unset($_SESSION['admin_product_form_data']); // Clear form data after displaying ?>
            <hr>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Search and Action Buttons -->
            <div class="admin-actions-bar mb-3 d-flex justify-content-between align-items-center">
                 <!-- Search Form -->
                <form action="index.php" method="GET" class="form-inline">
                    <input type="hidden" name="page" value="admin_manage_products">
                    <input type="hidden" name="action" value="list">
                    <div class="form-group mr-2">
                        <label for="query" class="sr-only">Search Products</label>
                        <!-- *** MODIFIED: Updated placeholder text *** -->
                        <input type="text" class="form-control form-control-sm" id="query" name="query" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search ...">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i>
                Submit</button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="index.php?page=admin_manage_products&action=list" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </form>
                <br>
                <!-- Action Buttons -->
                <div>
                    <a href="index.php?page=admin_manage_products&action=bulk_edit_stock" class="btn btn-warning mr-2">
                        <i class="fas fa-pencil-alt"></i> Edit Stock Levels
                    </a>
                    <a href="index.php?page=admin_manage_products&action=add" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
            </div>

            <?php if (!empty($products)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <?php echo table_headers($sortableFields, $sortColumn, $sortDir, $searchTerm); ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['productID']); ?></td>
                                    <td><?php echo htmlspecialchars($product['pname']); ?></td>
                                    <td>RM<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($product['stockquantity']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="index.php?page=admin_manage_products&action=edit&id=<?php echo htmlspecialchars($product['productID']); ?>" class="btn btn-sm btn-info mb-1" title="Edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <!-- Delete Form -->
                                        <form action="index.php?page=admin_manage_products" method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete product ID <?php echo htmlspecialchars($product['productID']); ?>?');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="productID" value="<?php echo htmlspecialchars($product['productID']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger mb-1" title="Delete">
                                               <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination mt-4">
                        <ul>
                            <?php
                                // Build base URL for pagination links, preserving search, sort, dir
                                $pageParams = [
                                    'page' => 'admin_manage_products',
                                    'action' => 'list',
                                    'sort' => $sortColumn,
                                    'dir' => $sortDir
                                ];
                                if (!empty($searchTerm)) {
                                    $pageParams['query'] = $searchTerm;
                                }
                                $baseURL = 'index.php?' . http_build_query($pageParams);
                            ?>
                            <?php if ($currentPage > 1): ?>
                                <li><a href="<?php echo $baseURL; ?>&p=<?php echo $currentPage - 1; ?>">Previous</a></li>
                            <?php endif; ?>

                            <?php
                                $maxPagesToShow = 7;
                                $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
                                $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
                                $startPage = max(1, $endPage - $maxPagesToShow + 1);

                                if ($startPage > 1) {
                                    echo '<li><a href="' . $baseURL . '&p=1">1</a></li>';
                                    if ($startPage > 2) echo '<li><span style="padding: 8px 12px;">...</span></li>';
                                }

                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="<?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                    <a href="<?php echo $baseURL; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor;

                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) echo '<li><span style="padding: 8px 12px;">...</span></li>';
                                    echo '<li><a href="' . $baseURL . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                                }
                            ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li><a href="<?php echo $baseURL; ?>&p=<?php echo $currentPage + 1; ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php elseif (!$fetchError): ?>
                 <div class="alert alert-info">
                    No products found<?php echo !empty($searchTerm) ? ' matching your search "' . htmlspecialchars($searchTerm) . '"' : ''; ?>.
                    <?php if (!empty($searchTerm)): ?>
                        <a href="index.php?page=admin_manage_products&action=list" class="alert-link">Clear search</a> or
                    <?php endif; ?>
                    <a href="index.php?page=admin_manage_products&action=add" class="alert-link">add a new product</a>.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($action === 'bulk_edit_stock'): ?>
            <h2>Edit Stock Levels</h2>
            <p>Update the stock quantity for multiple products below and click "Save Changes".</p>
            <?php if (!empty($products)): ?>
                <form action="index.php?page=admin_manage_products" method="post" class="admin-form mb-4">
                    <input type="hidden" name="action" value="update_bulk_stock">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th style="width: 120px;">Stock Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['productID']); ?></td>
                                        <td><?php echo htmlspecialchars($product['pname']); ?></td>
                                        <td>
                                            <input type="number"
                                                   class="form-control form-control-sm"
                                                   name="stock[<?php echo htmlspecialchars($product['productID']); ?>]"
                                                   value="<?php echo htmlspecialchars($product['stockquantity']); ?>"
                                                   min="0"
                                                   required
                                                   aria-label="Stock for <?php echo htmlspecialchars($product['pname']); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination for Bulk Edit -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination mt-3">
                            <ul>
                                <?php
                                    $pageParams = [
                                        'page' => 'admin_manage_products',
                                        'action' => 'bulk_edit_stock'
                                        // Keep search term if needed for bulk edit context?
                                        // if (!empty($searchTerm)) $pageParams['query'] = $searchTerm;
                                    ];
                                    $baseURL = 'index.php?' . http_build_query($pageParams);
                                ?>
                                <?php if ($currentPage > 1): ?>
                                    <li><a href="<?php echo $baseURL; ?>&p=<?php echo $currentPage - 1; ?>">Previous</a></li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="<?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                        <a href="<?php echo $baseURL; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <li><a href="<?php echo $baseURL; ?>&p=<?php echo $currentPage + 1; ?>">Next</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save Stock Changes</button>
                        <a href="index.php?page=admin_manage_products" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php elseif (!$fetchError): ?>
                <div class="alert alert-info">No products found to edit stock.</div>
                 <a href="index.php?page=admin_manage_products" class="btn btn-secondary">Back to Product List</a>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>
