// Global variable to store the last calculation data
let lastCalculationData = null;

// Initialize basket
function initializeBasket() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const form = document.getElementById('basketForm');
    let updateTimeout = null;
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default behavior
        processCheckout();
    });

    quantityInputs.forEach(input => {
        // Show discount price if quantity is greater than 0
        if (input.value > 0) {
            checkOffer(input);
        }

        // Handle quantity changes
        const handleQuantityChange = function() {
            // Ensure value is not negative
            if (this.value < 0) {
                this.value = 0;
            }
            checkOffer(this);
            
            // Clear any existing timeout
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }
            
            // Set a new timeout
            updateTimeout = setTimeout(() => {
                console.log('Starting update');
                updateCosts();
                updateCheckoutButton();
            }, 300);
        };

        // Listen for input changes
        input.addEventListener('input', handleQuantityChange);
    });

    // Calculate costs initially
    updateCosts();

    // Initial check
    updateCheckoutButton();
}

// Check if quantity meets offer requirements
function checkOffer(input) {
    const code = input.dataset.code;
    const quantity = parseInt(input.value) || 0;
    const offerElement = document.querySelector(`.offer-price-${code}`);
    const discountAmountElement = document.querySelector(`.discount-amount-${code}`);
    
    if (offerElement && discountAmountElement) {
        // Only hide if there is no current discount
        if (parseFloat(discountAmountElement.textContent) === 0) {
            offerElement.style.display = 'none';
        }
    }
}

// Update costs
function updateCosts() {
    console.log('updateCosts called');
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const products = Array.from(quantityInputs).map(input => ({
        code: input.dataset.code,
        quantity: parseInt(input.value) || 0
    }));

    // Send data to backend
    fetch('/basket/calculate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ products })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            // Update UI with response data
            document.getElementById('subtotal').textContent = `$${data.data.subtotal.toFixed(2)}`;
            
            // Update discounts
            const discountsContainer = document.getElementById('discountsContainer');
            const discountsElement = document.getElementById('discounts');
            lastCalculationData = data.data;
            if (data.data.discounts > 0) {
                if (!discountsContainer) {
                    const newDiscountsContainer = document.createElement('div');
                    newDiscountsContainer.id = 'discountsContainer';
                    newDiscountsContainer.className = 'flex justify-between text-green-600';
                    newDiscountsContainer.innerHTML = `
                        <span>Offer Savings:</span>
                        <span id="discounts">-$${data.data.discounts.toFixed(2)}</span>
                    `;
                    document.getElementById('subtotal').parentElement.after(newDiscountsContainer);
                } else {
                    discountsElement.textContent = `-$${data.data.discounts.toFixed(2)}`;
                }
            } else if (discountsContainer) {
                discountsContainer.remove();
            }

            // Update delivery
            document.getElementById('deliveryCost').textContent = `$${data.data.deliveryCost.toFixed(2)}`;
            document.getElementById('deliveryRule').textContent = data.data.deliveryRule.message;

            // Update total
            document.getElementById('total').textContent = `$${data.data.total.toFixed(2)}`;

            // Update offers visibility and savings
            if (data.data.appliedOffers) {
                console.log('Applied offers:', data.data.appliedOffers);
                Object.entries(data.data.appliedOffers).forEach(([code, offer]) => {
                    console.log(`Processing offer for ${code}:`, offer);
                    const offerElement = document.querySelector(`.offer-price-${code}`);
                    const discountAmountElement = document.querySelector(`.discount-amount-${code}`);
                    
                    if (offerElement && discountAmountElement) {
                        console.log(`Found elements for ${code}`);
                        const currentDiscount = parseFloat(discountAmountElement.textContent);
                        const newDiscount = offer.discount;
                        
                        // Only update if the discount has changed
                        if (currentDiscount !== newDiscount) {
                            console.log(`Updating discount for ${code} from ${currentDiscount} to ${newDiscount}`);
                            if (offer.hasOffer && newDiscount > 0) {
                                discountAmountElement.textContent = newDiscount.toFixed(2);
                                offerElement.style.display = 'inline';
                            } else {
                                discountAmountElement.textContent = '0.00';
                                offerElement.style.display = 'none';
                            }
                        }
                    } else {
                        console.log(`Elements not found for ${code}`);
                    }
                });
            } else {
                console.log('No applied offers in response');
            }
        } else {
            console.error('Error calculating costs:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Update checkout button
function updateCheckoutButton() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const checkoutButton = document.getElementById('checkoutButton');
    const totalItems = Array.from(quantityInputs).reduce((sum, input) => sum + (parseInt(input.value) || 0), 0);
    checkoutButton.disabled = totalItems === 0;
}

// Flag global to control the download
let isDownloading = false;

// Process checkout
function processCheckout() {
    if (isDownloading) return;
    isDownloading = true;
    
    const loader = document.getElementById('loader');
    const successMessage = document.getElementById('successMessage');
    const form = document.getElementById('basketForm');
    
    // Show loader
    loader.classList.remove('hidden');
    
    try {
        // Generate and download receipt
        const receiptContent = generateReceipt();
        const blob = new Blob([receiptContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        
        // Create a single download element
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `receipt_${new Date().toISOString().slice(0,10)}.txt`;
        
        // Add element to DOM
        document.body.appendChild(a);
        
        // Function to clean up and show message
        const cleanup = () => {
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
                        
            // Update checkout button
            updateCheckoutButton();

            initializeBasket();
            
            // Show success message
            successMessage.classList.remove('hidden');
        };

        // Simulate click
        a.click();
        
        // Clean up after a time
        setTimeout(cleanup, 1000);
    } catch (error) {
        console.error('Error:', error);
        alert('Error on checkout! Please try again.');
    } finally {
        // Hide loader
        loader.classList.add('hidden');
        
        // Reset flag after a time
        setTimeout(() => {
            isDownloading = false;
        }, 500);
    }
}

// Generate receipt content
function generateReceipt() {
    if (!lastCalculationData) {
        return 'No basket data available';
    }

    console.log('lastCalculationData:', lastCalculationData);

    const date = new Date().toLocaleString();
    let content = `Acme Widget Co - Sales Receipt\n`;
    content += `Date: ${date}\n`;
    content += `----------------------------------------\n\n`;
    
    // Add items
    content += `Items:\n`;
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        const quantity = parseInt(input.value) || 0;
        if (quantity > 0) {
            const price = parseFloat(input.dataset.price);
            const code = input.dataset.code;
            const itemTotal = quantity * price;
            
            content += `${code}: ${quantity} x $${price.toFixed(2)} = $${itemTotal.toFixed(2)}\n`;
            
            // Add discount if applicable
            if (lastCalculationData.appliedOffers[code]?.hasOffer) {
                content += `   Discount: -$${lastCalculationData.appliedOffers[code].discount.toFixed(2)}\n`;
            }
        }
    });
    
    // Add summary
    content += `\n----------------------------------------\n`;
    content += `Subtotal: $${lastCalculationData.subtotal.toFixed(2)}\n`;
    if (lastCalculationData.discounts > 0) {
        content += `Offer Savings: -$${lastCalculationData.discounts.toFixed(2)}\n`;
    }
    
    // Add delivery
    const deliveryRule = lastCalculationData.deliveryRule;
    const deliveryMessage = deliveryRule.message || 'Standard delivery';
    
    content += `Delivery (${deliveryMessage}): $${lastCalculationData.deliveryCost.toFixed(2)}\n`;
    content += `----------------------------------------\n`;
    content += `Total: $${lastCalculationData.total.toFixed(2)}\n`;
    content += `\nThank you for your purchase!\n`;
    
    return content;
}

// Function to reset the basket
window.resetBasket = function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.value = 0;
        checkOffer(input);
    });
    updateCosts();
    updateCheckoutButton();
    document.getElementById('successMessage').classList.add('hidden');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeBasket();
}); 