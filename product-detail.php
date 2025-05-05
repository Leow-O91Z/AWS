<?php
// product-detail.php

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$product = null;
$fetchError = null;
$relatedProducts = [];

if ($id) {
    try {
        // Fetch main product details
        // *** MODIFIED: Added image_path to SELECT ***
        $stmt = $db->prepare("
            SELECT productID, pname, price, description, brand, category, stockquantity, sizeoption, color, image_path
            FROM product
            WHERE productID = ?
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $fetchError = "Product not found.";
            http_response_code(404);
        } else {
            // Fetch related products
            if (!empty($product['category'])) {
                // *** MODIFIED: Added image_path to SELECT ***
                $stmtRelated = $db->prepare("
                    SELECT productID, pname, price, stockquantity, image_path
                    FROM product
                    WHERE category = ? AND productID != ? AND stockquantity > 0
                    ORDER BY RAND()
                    LIMIT 4
                ");
                $stmtRelated->execute([$product['category'], $product['productID']]);
                $relatedProducts = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
            }
        }

    } catch (PDOException $e) {
        error_log("Product Detail DB Error: " . $e->getMessage());
        $fetchError = "Could not load product details. Please try again later.";
    }
} else {
    $fetchError = "No product specified.";
}

// Determine stock status after fetching
$isOutOfStock = (!$product || $product['stockquantity'] <= 0);

?>

<div class="product-detail-page">
    <?php if ($fetchError): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
        <a href="index.php?page=product-listing" class="btn btn-secondary">Back to Shop</a>
    <?php elseif ($product): ?>
        <div class="product-detail-container">
            <div class="product-image-gallery <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">
                <?php
                    // *** ADDED: Main image source logic ***
                    $imageSrc = 'images/product-placeholder.jpg'; // Default placeholder
                    if (!empty($product['image_path']) && file_exists($product['image_path'])) {
                        // Add timestamp to URL to help bypass browser cache if image is updated
                        $imageSrc = htmlspecialchars($product['image_path']) . '?t=' . time();
                    } elseif (!empty($product['image_path'])) {
                        // Optional: Log if path exists in DB but file is missing
                        error_log("Product Detail - Missing image file for product ID {$product['productID']}: {$product['image_path']}");
                    }
                ?>
                <!-- *** MODIFIED: Use $imageSrc *** -->
                <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($product['pname']); ?>" class="main-product-image">
                <?php if ($isOutOfStock): ?>
                    <div class="out-of-stock-overlay">Out of Stock</div>
                <?php endif; ?>
            </div>

            <div class="product-info-details">
                <h1 class="product-title"><?php echo htmlspecialchars($product['pname']); ?></h1>
                <?php if (!empty($product['brand'])): ?>
                    <p class="product-brand">Choice:<?php echo htmlspecialchars($product['brand']); ?></p>
                <?php endif; ?>
                 <?php if (!empty($product['category'])): ?>
                    <p class="product-category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                <?php endif; ?>

                <div class="product-rating-detail">
                    <?php // Rating display removed ?>
                </div>

                <div class="product-price-detail">RM<?php echo number_format($product['price'], 2); ?></div>

                <!-- Stock Status Display -->
                <?php if ($product['stockquantity'] > 0 && $product['stockquantity'] <= 10): ?>
                    <p class="stock-status low-stock">Low stock! Only <?php echo $product['stockquantity']; ?> left.</p>
                <?php elseif ($product['stockquantity'] > 0): ?>
                     <p class="stock-status in-stock">In Stock</p>
                <?php else: ?>
                     <p class="stock-status out-of-stock">Out of Stock</p>
                <?php endif; ?>

                <div class="product-description">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                </div>

                <!-- Add to Cart Form -->
                <form action="index.php?page=shopping-cart" method="post" class="add-to-cart-form">
                    <input type="hidden" name="add_to_cart_id" value="<?php echo htmlspecialchars($product['productID']); ?>">

                    <!-- Size/Color Selection -->
                    <?php if (!empty($product['sizeoption'])): ?>
                        <div class="form-group options">
                            <label for="size">Size:</label>
                            <select name="size" id="size" required <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                                <?php foreach (explode(',', $product['sizeoption']) as $size): $trimmedSize = trim($size); ?>
                                    <option value="<?php echo htmlspecialchars($trimmedSize); ?>"><?php echo htmlspecialchars($trimmedSize); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                     <?php if (!empty($product['color'])): ?>
                        <div class="form-group options">
                            <label for="color">Option:</label>
                            <select name="color" id="color" required <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                                 <?php foreach (explode(',', $product['color']) as $color): $trimmedColor = trim($color); ?>
                                    <option value="<?php echo htmlspecialchars($trimmedColor); ?>"><?php echo htmlspecialchars($trimmedColor); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group quantity">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stockquantity'] > 0 ? $product['stockquantity'] : '1'; ?>" class="quantity-input-detail" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                    </div>

                    <button type="submit" class="btn btn-primary btn-add-to-cart-detail" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                        <?php echo $isOutOfStock ? 'Out of Stock' : 'Add to Cart'; ?>
                    </button>
                </form>

            </div>
        </div>

        <!-- Related Products Section -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-products">
            <h2>You Might Also Like</h2>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $related):
                     // *** ADDED: Related image source logic ***
                     $relatedImageSrc = 'images/product-placeholder.jpg'; // Default placeholder
                     if (!empty($related['image_path']) && file_exists($related['image_path'])) {
                         // Add timestamp to URL to help bypass browser cache if image is updated
                         $relatedImageSrc = htmlspecialchars($related['image_path']) . '?t=' . time();
                     } elseif (!empty($related['image_path'])) {
                         // Optional: Log if path exists in DB but file is missing
                         error_log("Related Products - Missing image file for product ID {$related['productID']}: {$related['image_path']}");
                     }
                     $relatedIsOutOfStock = ($related['stockquantity'] <= 0);
                ?>
                    <div class="product-card">
                         <div class="product-image <?php echo $relatedIsOutOfStock ? 'out-of-stock' : '';?>">
                            <a href="index.php?page=product-detail&id=<?php echo htmlspecialchars($related['productID']); ?>">
                                <!-- *** MODIFIED: Use $relatedImageSrc *** -->
                                <img src="<?php echo $relatedImageSrc; ?>" alt="<?php echo htmlspecialchars($related['pname']); ?>">
                            </a>
                             <?php if ($relatedIsOutOfStock): ?>
                                <div class="out-of-stock-overlay">Out of Stock</div>
                             <?php endif; ?>
                            <div class="product-overlay">
                                <a href="index.php?page=product-detail&id=<?php echo htmlspecialchars($related['productID']); ?>" class="btn-view">Quick View</a>
                                <form action="index.php?page=shopping-cart" method="post" style="display:inline; margin-top: 5px;">
                                    <input type="hidden" name="add_to_cart_id" value="<?php echo htmlspecialchars($related['productID']); ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn-add-cart" <?php echo $relatedIsOutOfStock ? 'disabled' : ''; ?>>
                                         <?php echo $relatedIsOutOfStock ? 'Out of Stock' : 'Add to Cart'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="product-info">
                             <h3>
                                <a href="index.php?page=product-detail&id=<?php echo htmlspecialchars($related['productID']); ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($related['pname']); ?>
                                </a>
                            </h3>
                            <div class="product-price">RM<?php echo number_format($related['price'], 2); ?></div>
                             <div class="product-rating">
                                <?php // Rating display removed ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>
