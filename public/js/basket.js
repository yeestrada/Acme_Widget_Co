document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const form = document.getElementById('basketForm');
    const loader = document.getElementById('loader');
    const successMessage = document.getElementById('successMessage');
    const checkoutButton = document.getElementById('checkoutButton');
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        processCheckout();
    });

    quantityInputs.forEach(input => {
        // Show discount price if quantity is greater than 0
        if (input.value > 0) {
            checkOffer(input);
        }

        //Listen for quantity changes
        input.addEventListener('input', function() {
            checkOffer(this);
            updateCosts();
            updateCheckoutButton();
        });
    });

    // Process checkout
    function processCheckout() {
        // Show loader
        loader.classList.remove('hidden');
        
        // Generate receipt content
        const receiptContent = generateReceipt();
        
        // Create and download receipt file
        const blob = new Blob([receiptContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `receipt_${new Date().toISOString().slice(0,10)}.txt`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Submit form data
        const formData = new FormData(form);
        
        // Simulate processing time (2 seconds)
        setTimeout(() => {
            // Hide loader
            loader.classList.add('hidden');
            
            // Show success message
            successMessage.classList.remove('hidden');
        }, 2000);
    }

    // Generate receipt content
    function generateReceipt() {
        const date = new Date().toLocaleString();
        let content = `Acme Widget Co - Sales Receipt\n`;
        content += `Date: ${date}\n`;
        content += `----------------------------------------\n\n`;
        
        // Add items
        content += `Items:\n`;
        let subtotal = 0;
        let totalDiscount = 0;
        
        quantityInputs.forEach(input => {
            const quantity = parseInt(input.value) || 0;
            if (quantity > 0) {
                const price = parseFloat(input.dataset.price);
                const code = input.dataset.code;
                const itemTotal = quantity * price;
                subtotal += itemTotal;
                
                content += `${code}: ${quantity} x $${price.toFixed(2)} = $${itemTotal.toFixed(2)}\n`;
                
                // Add discount if applicable
                if (code === 'R01' && quantity >= 2) {
                    const discount = Math.floor(quantity / 2) * (price / 2);
                    totalDiscount += discount;
                    content += `   Discount: -$${discount.toFixed(2)}\n`;
                } else if (code === 'B01' && quantity >= 3) {
                    const discount = Math.floor(quantity / 3) * price;
                    totalDiscount += discount;
                    content += `   Discount: -$${discount.toFixed(2)}\n`;
                }
            }
        });
        
        // Add summary
        content += `\n----------------------------------------\n`;
        content += `Subtotal: $${subtotal.toFixed(2)}\n`;
        if (totalDiscount > 0) {
            content += `Offer Savings: -$${totalDiscount.toFixed(2)}\n`;
        }
        
        // Calculate delivery
        let deliveryCost = 0;
        let deliveryRule = '';
        if (subtotal < 50) {
            deliveryCost = 4.95;
            deliveryRule = 'Standard delivery';
        } else if (subtotal < 90) {
            deliveryCost = 2.95;
            deliveryRule = 'Premium delivery';
        } else {
            deliveryCost = 0;
            deliveryRule = 'Free delivery';
        }
        
        content += `Delivery (${deliveryRule}): $${deliveryCost.toFixed(2)}\n`;
        content += `----------------------------------------\n`;
        content += `Total: $${(subtotal - totalDiscount + deliveryCost).toFixed(2)}\n`;
        content += `\nThank you for your purchase!\n`;
        
        return content;
    }

    // Update checkout button
    function updateCheckoutButton() {
        const totalItems = Array.from(quantityInputs).reduce((sum, input) => sum + (parseInt(input.value) || 0), 0);
        checkoutButton.disabled = totalItems === 0;
    }

    // Function to reset the basket
    window.resetBasket = function() {
        quantityInputs.forEach(input => {
            input.value = 0;
            checkOffer(input);
        });
        updateCosts();
        updateCheckoutButton();
        document.getElementById('successMessage').classList.add('hidden');
    }

    // Check offer
    function checkOffer(input) {
        const code = input.dataset.code;
        const quantity = parseInt(input.value);
        const price = parseFloat(input.dataset.price);
        const hasOffer = input.dataset.hasOffer === 'true';
        const offerPriceElement = document.querySelector(`.offer-price-${code}`);
        const discountAmountElement = document.querySelector(`.discount-amount-${code}`);

        if (hasOffer && offerPriceElement) {
            let discount = 0;
            let showOffer = false;

            // Apply offer rules
            if (code === 'R01') {
                if (quantity >= 2) {
                    discount = Math.floor(quantity / 2) * (price / 2);
                    showOffer = true;
                }
            } else if (code === 'B01') {
                if (quantity >= 3) {
                    discount = Math.floor(quantity / 3) * price;
                    showOffer = true;
                }
            }

            // Show or hide the offer element
            offerPriceElement.style.display = showOffer ? 'inline' : 'none';
            if (discountAmountElement) {
                discountAmountElement.textContent = discount.toFixed(2);
            }
        }
    }

    // Update costs
    function updateCosts() {
        let subtotal = 0;
        let totalDiscount = 0;

        quantityInputs.forEach(input => {
            const quantity = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price);
            const code = input.dataset.code;
            
            // Calculate subtotal
            subtotal += quantity * price;

            // Calculate discounts
            if (code === 'R01' && quantity >= 2) {
                totalDiscount += Math.floor(quantity / 2) * (price / 2);
            } else if (code === 'B01' && quantity >= 3) {
                totalDiscount += Math.floor(quantity / 3) * price;
            }
        });

        // Update subtotal
        document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;

        // Update discounts
        const discountsContainer = document.getElementById('discountsContainer');
        const discountsElement = document.getElementById('discounts');
        
        if (totalDiscount > 0) {
            if (!discountsContainer) {
                const newDiscountsContainer = document.createElement('div');
                newDiscountsContainer.id = 'discountsContainer';
                newDiscountsContainer.className = 'flex justify-between text-green-600';
                newDiscountsContainer.innerHTML = `
                    <span>Offer Savings:</span>
                    <span id="discounts">-$${totalDiscount.toFixed(2)}</span>
                `;
                document.getElementById('subtotal').parentElement.after(newDiscountsContainer);
            } else {
                discountsElement.textContent = `-$${totalDiscount.toFixed(2)}`;
            }
        } else if (discountsContainer) {
            discountsContainer.remove();
        }

        // Calculate delivery
        let deliveryCost = 0;
        let deliveryRule = '';
        
        if (subtotal < 50) {
            deliveryCost = 4.95;
            deliveryRule = 'Standard delivery';
        } else if (subtotal < 90) {
            deliveryCost = 2.95;
            deliveryRule = 'Premium delivery';
        } else {
            deliveryCost = 0;
            deliveryRule = 'Free delivery';
        }

        // Update delivery
        document.getElementById('deliveryCost').textContent = `$${deliveryCost.toFixed(2)}`;
        document.getElementById('deliveryRule').textContent = deliveryRule;

        // Calculate total
        const total = subtotal - totalDiscount + deliveryCost;
        document.getElementById('total').textContent = `$${total.toFixed(2)}`;
    }

    //Calculate costs initially
    updateCosts();

    // Initial check
    updateCheckoutButton();
}); 