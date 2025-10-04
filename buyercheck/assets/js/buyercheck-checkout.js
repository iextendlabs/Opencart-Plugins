jQuery(document).ready(function($) {
    // Field selectors for robust matching
    var emailSelectors = ['#billing_email', '#billing-email', '#email'];
    var phoneSelectors = ['#billing_phone', '#billing-phone', '#shipping-phone', '#shipping_phone', '#phone'];
    var orderTotalSelectors = ['.wc-block-components-totals-footer-item-tax-value', '.woocommerce-Price-amount .amount', '.order-total .woocommerce-Price-amount.amount'];
    var codSelector = 'input[value="cod"]';
    
    // Function to find first matching element
    function findElement(selectors) {
        for (var i = 0; i < selectors.length; i++) {
            var element = $(selectors[i]);
            if (element.length > 0) {
                return element;
            }
        }
        return $();
    }
    
    // Function to get order total from various selectors
    function getOrderTotal() {
        for (var i = 0; i < orderTotalSelectors.length; i++) {
            var elements = $(orderTotalSelectors[i]);
            if (elements.length > 0) {
                var text = elements.first().text();
                // Handle European decimal format (comma as decimal separator)
                // Remove all non-digit characters except comma and dot, then convert comma to dot
                var cleanText = text.replace(/[^\d,.]/g, '').replace(',', '.');
                var total = parseFloat(cleanText);
                if (!isNaN(total) && total > 0) {
                    return total;
                }
            }
        }
        return 0;
    }
    
    // Function to get field values
    function getFieldValues() {
        var emailField = findElement(emailSelectors);
        var phoneField = findElement(phoneSelectors);
        
        var email = emailField.length > 0 ? emailField.val() : '';
        var phone = phoneField.length > 0 ? phoneField.val() : '';
        
        return {
            email: email,
            phone: phone,
            emailField: emailField,
            phoneField: phoneField
        };
    }
    
    // Function to perform risk check
    function performRiskCheck() {
        try {
            var fieldValues = getFieldValues();
            var email = fieldValues.email;
            var phone = fieldValues.phone;
            var orderTotal = getOrderTotal();
            
            // Check if we have at least email or phone and a valid order total
            if ((email || phone) && orderTotal > 0) {
                // Prepare data
                var data = {
                    amount: orderTotal
                };
                
                if (email) {
                    data.email = email;
                }
                if (phone) {
                    data.phone = phone;
                }
                
                // Send data to REST endpoint
                $.ajax({
                    url: buyercheck_vars.rest_url,
                    method: 'POST',
                    contentType: 'application/json',
                    headers: {
                        'X-WP-Nonce': buyercheck_vars.nonce
                    },
                    data: JSON.stringify(data),
                    success: function(response) {
                        if (response.hide_cod && response.hide_cod === true) {
                            // Hide COD option if risk level is high
                            try {
                                // If more than one payment method, otherwise skip
                                if ($('input[name="radio-control-wc-payment-method-options"]').length > 1) {
                                    var codElement = $(codSelector);
                                    if (codElement.length > 0) {
                                        // Remove checked property and disable the input
                                        codElement.prop('disabled', true);
                                        
                                        // Try to find the closest li first, then fall back to the WooCommerce block class
                                        var parentElement = codElement.closest('li');
                                        if (parentElement.length === 0) {
                                            parentElement = codElement.closest('.wc-block-components-radio-control-accordion-option');
                                        }
                                        if (parentElement.length > 0) {
                                            parentElement.hide();
                                        }

                                        // Select the first enabled payment method (not the disabled COD)
                                        $('input[name="radio-control-wc-payment-method-options"]:not(:disabled)').first().click();
                                    }
                                }
                            } catch (e) {
                                console.error('Failed to hide COD option');
                            }
                        }
                    },
                    error: function() {
                        console.error('Failed to check risk level');
                    }
                });
            }
        } catch (e) {
            console.error('Failed to perform risk check:', e);
        }
    }
    
    // Function to handle field changes
    function handleFieldChange() {
        // Debounce the risk check to avoid too many requests
        clearTimeout(window.buyercheckTimeout);
        window.buyercheckTimeout = setTimeout(performRiskCheck, 500);
    }
    
    // Attach event handlers to all possible field selectors
    var allSelectors = emailSelectors.concat(phoneSelectors);
    $(allSelectors.join(', ')).on('blur', handleFieldChange);
    
    // Also check on page load for prefilled values
    $(window).on('load', function() {
        setTimeout(performRiskCheck, 3000); // Small delay to ensure all fields are loaded
    });
    
    // Check when payment methods are updated
    $('body').on('updated_checkout', function() {
        setTimeout(performRiskCheck, 500);
    });
}); 