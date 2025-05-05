<?php
// product-listing.php

$products = [];
$fetchError = null;
$searchTerm = $_GET['query'] ?? '';
$sortOrder = $_GET['sort'] ?? 'pname_asc';
$filterCategory = $_GET['category'] ?? null;
$filterBrand = $_GET['brand'] ?? null;

try {
    // Base query parts
    // *** MODIFIED: Added image_path to SELECT (already done in previous step) ***
    $baseSelect = "SELECT productID, pname, price, brand, category, stockquantity, image_path";
    $baseFrom = "FROM product";
    $baseWhere = "WHERE stockquantity > 0"; // Filter out-of-stock items

    $params = [];
    $countParams = [];

    // Add search condition
    if (!empty($searchTerm)) {
        // *** MODIFIED: Changed search condition to ONLY search pname ***
        // Original line: $baseWhere .= " AND (pname LIKE :searchTerm OR brand LIKE :searchTerm OR category LIKE :searchTerm)";
        $baseWhere .= " AND pname LIKE :searchTerm"; // Only search product name

        // Use the same parameter name for both arrays
        $params[':searchTerm'] = '%' . $searchTerm . '%';
        $countParams[':searchTerm'] = '%' . $searchTerm . '%';
    }

    // Add filtering conditions (These remain unchanged)
    if (!empty($filterCategory)) {
        $baseWhere .= " AND category = :category";
        $params[':category'] = $filterCategory;
        $countParams[':category'] = $filterCategory;
    }
    if (!empty($filterBrand)) {
        $baseWhere .= " AND brand = :brand";
        $params[':brand'] = $filterBrand;
        $countParams[':brand'] = $filterBrand;
    }

    // --- Pagination Logic ---
    $countSql = "SELECT COUNT(*) " . $baseFrom . " " . $baseWhere;
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($countParams); // Execute count query with its specific params
    $totalProducts = $stmtCount->fetchColumn();

    $productsPerPage = 12;
    $totalPages = ceil($totalProducts / $productsPerPage);
    $currentPage = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
    $currentPage = min($currentPage, $totalPages > 0 ? $totalPages : 1); // Ensure current page is valid
    $offset = ($currentPage - 1) * $productsPerPage;

    // --- Prepare and Execute Main Product Query ---
    $sql = $baseSelect . " " . $baseFrom . " " . $baseWhere;

    // Add Ordering
    switch ($sortOrder) {
        case 'price_asc': $sql .= " ORDER BY price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY price DESC"; break;
        case 'pname_asc': default: $sql .= " ORDER BY pname ASC"; break;
    }

    // Add LIMIT and OFFSET
    $sql .= " LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);

    // Add limit and offset to the main parameters array AFTER defining WHERE params
    $params[':limit'] = $productsPerPage;
    $params[':offset'] = $offset;

    // Bind integer parameters explicitly
    $stmt->bindValue(':limit', $params[':limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $params[':offset'], PDO::PARAM_INT);

    // Bind the rest of the parameters dynamically
    if (!empty($searchTerm)) {
        $stmt->bindValue(':searchTerm', $params[':searchTerm'], PDO::PARAM_STR);
    }
    if (!empty($filterCategory)) {
        $stmt->bindValue(':category', $params[':category'], PDO::PARAM_STR);
    }
    if (!empty($filterBrand)) {
        $stmt->bindValue(':brand', $params[':brand'], PDO::PARAM_STR);
    }

    $stmt->execute(); // Execute after binding all necessary parameters
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch categories and brands for filter dropdowns
    $categories = $db->query("SELECT DISTINCT category FROM product WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    $brands = $db->query("SELECT DISTINCT brand FROM product WHERE brand IS NOT NULL AND brand != '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Product Listing DB Error: " . $e->getMessage());
    $fetchError = "Could not load products. Please try again later.";
}

?>

<!-- HTML Display Section -->
<div class="product-listing-page">
    <h1><?php echo !empty($searchTerm) ? 'Search Results for "' . htmlspecialchars($searchTerm) . '"' : 'Shop All Products'; ?></h1>

    <div class="listing-layout-container">
        <aside class="listing-sidebar">
            <h2>Filters & Sort</h2>
            <form action="index.php" method="GET" class="filter-form">
                <input type="hidden" name="page" value="product-listing">

                <!-- Search Input -->
                <div class="filter-group">
                    <label for="query" class="visually-hidden">Search:</label>
                    <div class="search-container-plp">
                        <input class="search-input-plp" type="text" name="query" id="query" size="31" maxlength="255" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search products..." />
                        <button class="search-button-plp" type="submit">Search</button>
                    </div>
                </div>

                <!-- Sort Dropdown -->
                <div class="filter-group">
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="pname_asc" <?php echo ($sortOrder === 'pname_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="price_asc" <?php echo ($sortOrder === 'price_asc') ? 'selected' : ''; ?>>Price (Low-High)</option>
                        <option value="price_desc" <?php echo ($sortOrder === 'price_desc') ? 'selected' : ''; ?>>Price (High-Low)</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <?php if (!empty($categories)): ?>
                <div class="filter-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="">All</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filterCategory === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                

                 <!-- Reset Button -->
                 <?php if (!empty($filterCategory) || !empty($filterBrand) || !empty($searchTerm)): ?>
                     <div class="filter-group reset-button">
                         <a href="index.php?page=product-listing&sort=<?php echo urlencode($sortOrder); ?>" class="btn btn-sm btn-outline-secondary">Reset All</a>
                     </div>
                 <?php endif; ?>
            </form>
        </aside>

        <!-- Main Content Area -->
        <div class="listing-main-content">

            <!-- Display Fetch Error -->
            <?php if ($fetchError): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
            <?php endif; ?>

            <!-- Product Grid -->
            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product):
                        $detailUrl = "index.php?page=product-detail&id=" . htmlspecialchars($product['productID']);
                        $isOutOfStock = ($product['stockquantity'] <= 0); // Check stock status

                        // Image source logic (already added in previous step)
                        $imageSrc = 'images/product-placeholder.jpg';
                        if (!empty($product['image_path']) && file_exists($product['image_path'])) {
                            $imageSrc = htmlspecialchars($product['image_path']) . '?t=' . time();
                        } elseif (!empty($product['image_path'])) {
                            error_log("Product Listing - Missing image file for product ID {$product['productID']}: {$product['image_path']}");
                        }
                    ?>
                        <div class="product-card">
                            <div class="product-image <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                                <a href="<?php echo $detailUrl; ?>">
                                    <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($product['pname']); ?>">
                                </a>
                                <?php if ($isOutOfStock): ?>
                                    <div class="out-of-stock-overlay">Out of Stock</div>
                                <?php endif; ?>
                                <div class="product-overlay">
                                    <a href="<?php echo $detailUrl; ?>" class="btn-view">Quick View</a>
                                    <form action="index.php?page=shopping-cart" method="post" style="display:inline; margin-top: 5px;">
                                        <input type="hidden" name="add_to_cart_id" value="<?php echo htmlspecialchars($product['productID']); ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn-add-cart" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                                            <?php echo $isOutOfStock ? 'Out of Stock' : 'Add to Cart'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="<?php echo $detailUrl; ?>" style="color: inherit; text-decoration: none;"> <?php echo htmlspecialchars($product['pname']); ?>
                                    </a>
                                </h3>
                                 <?php if (!empty($product['brand'])): ?>
                                     <p class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></p>
                                 <?php endif; ?>
                                <div class="product-price">RM<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-rating">
                                    <?php // Rating display removed ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination">
                        <ul>
                            <?php
                                // Build base URL for pagination links, preserving filters/sort/query
                                $pageParams = ['page' => 'product-listing'];
                                if (!empty($searchTerm)) $pageParams['query'] = $searchTerm;
                                if (!empty($sortOrder)) $pageParams['sort'] = $sortOrder;
                                if (!empty($filterCategory)) $pageParams['category'] = $filterCategory;
                                if (!empty($filterBrand)) $pageParams['brand'] = $filterBrand;
                                $baseURL = 'index.php?' . http_build_query($pageParams);
                            ?>
                            <!-- Previous Page Link -->
                            <?php if ($currentPage > 1): ?>
                                <li><a href="<?php echo $baseURL; ?>&p=<?php echo $currentPage - 1; ?>">Previous</a></li>
                            <?php endif; ?>

                            <!-- Page Number Links -->
                            <?php
                                $maxPagesToShow = 5; // Max number of page links to show
                                $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
                                $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
                                // Adjust start page if end page is capped
                                $startPage = max(1, $endPage - $maxPagesToShow + 1);

                                // Ellipsis and first page link
                                if ($startPage > 1) {
                                    echo '<li><a href="' . $baseURL . '&p=1">1</a></li>';
                                    if ($startPage > 2) echo '<li><span style="padding: 8px 12px;">...</span></li>';
                                }

                                // Numbered page links
                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="<?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                    <a href="<?php echo $baseURL; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor;

                                // Ellipsis and last page link
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) echo '<li><span style="padding: 8px 12px;">...</span></li>';
                                    echo '<li><a href="' . $baseURL . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                                }
                            ?>

                            <!-- Next Page Link -->
                            <?php if ($currentPage < $totalPages): ?>
                                <li><a href="<?php echo $baseURL; ?>&p=<?php echo $currentPage + 1; ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <!-- No Products Found Message -->
            <?php elseif (empty($fetchError)): ?>
                <div class="no-products">
                    <p>No products found<?php echo !empty($searchTerm) ? ' matching your search "' . htmlspecialchars($searchTerm) . '"' : ''; ?>.</p>
                    <a href="index.php?page=product-listing" class="btn btn-secondary">View All Products</a>
                </div>
            <?php endif; ?>

        </div> <!-- /listing-main-content -->
    </div> <!-- /listing-layout-container -->
</div> <!-- /product-listing-page -->
