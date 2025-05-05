<?php
$trendingProducts = [];
$featuredBrands = [];
$fetchError = null;

try {
    $stmtTrending = $db->query("
        SELECT productID, pname, price, image_path, average_rating
        FROM product
        WHERE stockquantity > 0 -- Example condition
        ORDER BY date_added DESC -- Example ordering
        LIMIT 4
    ");
    $trendingProducts = $stmtTrending->fetchAll(PDO::FETCH_ASSOC);
    $stmtBrands = $db->query("
        SELECT brand_name, logo_path
        FROM brand
        WHERE is_featured = 1 -- Example condition
        LIMIT 5
    ");
    $featuredBrands = $stmtBrands->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Home Page Fetch Error: " . $e->getMessage());
   
}

function renderStars($rating) {
    $rating = round($rating * 2) / 2;
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $output .= '<i class="fas fa-star"></i>'; 
        } elseif ($rating >= $i - 0.5) {
            $output .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $output .= '<i class="far fa-star"></i>';
        }
    }
    return $output;
}

?>

<?php if ($fetchError): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($fetchError); ?></div>
<?php endif; ?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Step into Style with GradGlow</h1>
        <p>Discover the perfect graduation essentials to celebrate every milestone in style.</p>
        <div class="hero-buttons">
            <a href="index.php?page=product-listing" class="btn btn-primary">Shop Now</a>
            <?php if (!isset($_SESSION['customerID'])): ?>
                <a href="index.php?page=login_register&show=register" class="btn btn-secondary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-images">
        <img src="images/home.png" alt="Comfortable Running Shoe" class="shoe2">
    </div>
</div>

<section class="features-section">
    <div class="feature-card">
        <img src="/images/shipping-icon.png" alt="Shipping Icon" class="feature-icon">
        <h3>Free Shipping</h3>
        <p>On all orders over $50</p>
    </div>
    <div class="feature-card">
        <img src="/images/return-icon.png" alt="Returns Icon" class="feature-icon">
        <h3>Easy Returns</h3>
        <p>30-day return policy</p>
    </div>
    <div class="feature-card">
        <img src="/images/secure-payment-icon.png" alt="Secure Payment Icon" class="feature-icon">
        <h3>Secure Payment</h3>
        <p>Safe & encrypted checkout</p>
    </div>
    <div class="feature-card">
        <img src="/images/support-icon.png" alt="Support Icon" class="feature-icon">
        <h3>24/7 Support</h3>
        <p>Dedicated customer service</p>
    </div>
</section>

<section class="trending-section">
    <h2>Trending Now</h2>
    <?php if (!empty($trendingProducts)): ?>
        <div class="product-grid">
            <?php foreach ($trendingProducts as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php
                            $imageSrc = (!empty($product['image_path']) && file_exists($product['image_path']))
                                        ? htmlspecialchars($product['image_path'])
                                        : 'images/product-placeholder.jpg';
                        ?>
                        <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($product['pname']); ?>">
                        <div class="product-overlay">
                            <a href="index.php?page=product-detail&id=<?php echo htmlspecialchars($product['productID']); ?>" class="btn-view">Quick View</a>
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['productID']); ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" name="add_to_cart" class="btn-add-cart">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['pname']); ?></h3>
                        <div class="product-price">RM<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-rating">
                            <?php echo renderStars($product['average_rating'] ?? 0);?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="view-all">
            <a href="index.php?page=product-listing" class="btn btn-outline">View All Products</a>
        </div>
    <?php else: ?>
        <p>Check back soon for trending products!</p>
    <?php endif; ?>
</section>

<section class="brands-section">
    <h2>Featured Option</h2>
    <?php if (!empty($featuredBrands)): ?>
        <div class="brand-logos">
            <?php foreach ($featuredBrands as $brand): ?>
                <div class="brand-logo">
                     <?php
                        $logoSrc = (!empty($brand['logo_path']) && file_exists($brand['logo_path']))
                                    ? htmlspecialchars($brand['logo_path'])
                                    : 'images/brand-placeholder.png'; // Default placeholder
                    ?>
                    <img src="<?php echo $logoSrc; ?>" alt="<?php echo htmlspecialchars($brand['brand_name']); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Featured products coming soon.</p>
    <?php endif; ?>
</section>

<section class="newsletter-section">
    <div class="newsletter-content">
        <h2>Subscribe to Our Newsletter</h2>
        <p>Stay updated with our latest products and exclusive offers</p>
        <form class="newsletter-form" method="post" action="index.php?page=subscribe">
            <input type="email" name="newsletter_email" placeholder="Enter your email address" required>
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
    </div>
</section>
