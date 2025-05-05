</main>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section about">
                <h3>About GradGlow</h3>
                <p>GradGlow offers thoughtful graduation gifts and flower bouquets to celebrate every milestone with pride and style.</p>
                <div class="social-links">
                    <a href="https://facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <img src="images/facebook.png" alt="Facebook" class="social-icon">
                    </a>
                    <a href="https://twitter.com/" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                         <img src="images/twitter.png" alt="Twitter" class="social-icon">
                    </a>
                    <a href="https://instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                         <img src="images/instagram.png" alt="Instagram" class="social-icon">
                    </a>
                    <a href="https://pinterest.com/" target="_blank" rel="noopener noreferrer" aria-label="Pinterest">
                         <img src="images/pinterest.png" alt="Pinterest" class="social-icon">
                    </a>
                </div>
            </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php?page=product-listing">Shop</a></li>
                    <li><a href="index.php?page=about">About Us</a></li> 
                    <li><a href="index.php?page=faq">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p><img src="images/address.png" alt="Address:" class="contact-icon"> 123 Shoe Street, Fashion District</p>
                <p><img src="images/phone.png" alt="Phone:" class="contact-icon"> <a href="tel:+12345678900">+1 234 567 8900</a></p>
                <p><img src="images/envelope.png" alt="Email:" class="contact-icon"> <a href="mailto:info@shoetopia.com">info@shoetopia.com</a></p>
            </div>
            <div class="footer-section newsletter">
                <h3>Subscribe to Our Newsletter</h3>
                <form action="index.php?page=subscribe" method="post">
                    <input type="email" name="newsletter_email" placeholder="Enter your email" required aria-label="Newsletter Email">
                    <button type="submit">Subscribe</button>
                </form>
                <p class="newsletter-feedback"></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> ShoeTopia. All rights reserved.</p>
            <ul class="footer-links">
                <li><a href="index.php?page=privacy">Privacy Policy</a></li>
                <li><a href="index.php?page=terms">Terms of Service</a></li> 
                <li><a href="index.php?page=shipping">Shipping & Returns</a></li>
            </ul>
        </div>
    </div>
</footer>
</body>
</html>
