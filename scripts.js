document.addEventListener('DOMContentLoaded', function() {
    // Hero section animation
    const heroImages = document.querySelectorAll('.hero-images img');
    if (heroImages.length > 0) {
        heroImages.forEach((img, index) => {
            setTimeout(() => {
                img.style.transform = 'rotate(0deg)';
                img.style.opacity = '1';
            }, 300 * index);
        });
    }

    // Product card hover effects
    const productCards = document.querySelectorAll('.product-card');
    if (productCards.length > 0) {
        productCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.querySelector('.product-overlay').style.opacity = '1';
            });
            
            card.addEventListener('mouseleave', function() {
                this.querySelector('.product-overlay').style.opacity = '0';
            });
        });
    }

    // Quantity input in product detail and cart pages
    const quantityInputs = document.querySelectorAll('.quantity-input');
    if (quantityInputs.length > 0) {
        quantityInputs.forEach(input => {
            const decrementBtn = input.querySelector('.decrement');
            const incrementBtn = input.querySelector('.increment');
            const inputField = input.querySelector('input');
            
            decrementBtn.addEventListener('click', function() {
                let value = parseInt(inputField.value);
                if (value > 1) {
                    inputField.value = value - 1;
                    // Trigger change event for any listeners
                    const event = new Event('change');
                    inputField.dispatchEvent(event);
                }
            });
            
            incrementBtn.addEventListener('click', function() {
                let value = parseInt(inputField.value);
                inputField.value = value + 1;
                // Trigger change event for any listeners
                const event = new Event('change');
                inputField.dispatchEvent(event);
            });
        });
    }

    // Shopping cart total calculation
    function updateCartTotal() {
        const cartItems = document.querySelectorAll('.cart-item');
        let total = 0;
        
        cartItems.forEach(item => {
            const price = parseFloat(item.querySelector('.item-price').getAttribute('data-price'));
            const quantity = parseInt(item.querySelector('.quantity-input input').value);
            const itemTotal = price * quantity;
            
            item.querySelector('.item-total').textContent = '$' + itemTotal.toFixed(2);
            total += itemTotal;
        });
        
        if (document.getElementById('cart-subtotal')) {
            document.getElementById('cart-subtotal').textContent = '$' + total.toFixed(2);
            
            // Calculate tax (assuming 10%)
            const tax = total * 0.1;
            document.getElementById('cart-tax').textContent = '$' + tax.toFixed(2);
            
            // Calculate shipping (free over $50, otherwise $5)
            const shipping = total > 50 ? 0 : 5;
            document.getElementById('cart-shipping').textContent = shipping === 0 ? 'Free' : '$' + shipping.toFixed(2);
            
            // Calculate grand total
            const grandTotal = total + tax + shipping;
            document.getElementById('cart-total').textContent = '$' + grandTotal.toFixed(2);
        }
    }

    // Update cart when quantity changes
    const cartQuantityInputs = document.querySelectorAll('.cart-item .quantity-input input');
    if (cartQuantityInputs.length > 0) {
        cartQuantityInputs.forEach(input => {
            input.addEventListener('change', updateCartTotal);
        });
        
        // Initial calculation
        updateCartTotal();
    }

    // Remove item from cart
    const removeButtons = document.querySelectorAll('.remove-item');
    if (removeButtons.length > 0) {
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const cartItem = this.closest('.cart-item');
                cartItem.remove();
                updateCartTotal();
                
                // Check if cart is empty
                const remainingItems = document.querySelectorAll('.cart-item');
                if (remainingItems.length === 0) {
                    const cartContent = document.querySelector('.cart-content');
                    const emptyCart = document.createElement('div');
                    emptyCart.className = 'empty-cart';
                    emptyCart.innerHTML = `
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Looks like you haven't added any items to your cart yet.</p>
                        <a href="index.php?page=product-listing" class="btn btn-primary">Continue Shopping</a>
                    `;
                    cartContent.innerHTML = '';
                    cartContent.appendChild(emptyCart);
                }
            });
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form.validate');
    if (forms.length > 0) {
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Check required fields
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        
                        // Add error message if it doesn't exist
                        let errorMsg = field.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            errorMsg.textContent = 'This field is required';
                            field.parentNode.insertBefore(errorMsg, field.nextSibling);
                        }
                    } else {
                        field.classList.remove('is-invalid');
                        
                        // Remove error message if it exists
                        const errorMsg = field.nextElementSibling;
                        if (errorMsg && errorMsg.classList.contains('error-message')) {
                            errorMsg.remove();
                        }
                    }
                });
                
                // Check email format
                const emailFields = form.querySelectorAll('input[type="email"]');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                emailFields.forEach(field => {
                    if (field.value.trim() && !emailRegex.test(field.value.trim())) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        
                        // Add error message if it doesn't exist
                        let errorMsg = field.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            errorMsg.textContent = 'Please enter a valid email address';
                            field.parentNode.insertBefore(errorMsg, field.nextSibling);
                        }
                    }
                });
                
                // Check password match if confirm password exists
                const passwordField = form.querySelector('input[name="password"]');
                const confirmField = form.querySelector('input[name="confirm_password"]');
                
                if (passwordField && confirmField) {
                    if (passwordField.value !== confirmField.value) {
                        isValid = false;
                        confirmField.classList.add('is-invalid');
                        
                        // Add error message if it doesn't exist
                        let errorMsg = confirmField.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'error-message';
                            errorMsg.textContent = 'Passwords do not match';
                            confirmField.parentNode.insertBefore(errorMsg, confirmField.nextSibling);
                        }
                    }
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    }

    // Initialize any tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    if (tooltips.length > 0) {
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', function() {
                const tooltipText = this.getAttribute('data-tooltip');
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip';
                tooltipEl.textContent = tooltipText;
                document.body.appendChild(tooltipEl);
                
                const rect = this.getBoundingClientRect();
                tooltipEl.style.top = rect.top - tooltipEl.offsetHeight - 10 + 'px';
                tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
                tooltipEl.style.opacity = '1';
            });
            
            tooltip.addEventListener('mouseleave', function() {
                const tooltipEl = document.querySelector('.tooltip');
                if (tooltipEl) {
                    tooltipEl.remove();
                }
            });
        });
    }
});