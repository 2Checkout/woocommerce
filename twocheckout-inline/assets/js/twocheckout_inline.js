function inlinePay() {
    jQuery('#tco_inline_error').html('');
    jQuery('#place_order').attr('disabled', true);
    var tco_ajax_url;
    var tco_data;
    if (jQuery('.checkout.woocommerce-checkout').length) {
        tco_ajax_url = wc_checkout_params.checkout_url;
        tco_data = jQuery('.checkout.woocommerce-checkout').serialize();
    } else if (jQuery('form#order_review').length) {
        tco_ajax_url = wc_checkout_params.wc_ajax_url
            .toString()
            .replace( 'wc-ajax', 'wc-api' )
            .replace( '%%endpoint%%', 'twocheckout_inline_handle_payment_request' );
        tco_data = jQuery('form#order_review').serialize();
    }
    jQuery.ajax({
        type: 'POST',
        data: tco_data,
        url: tco_ajax_url,
        success: function (response) {
            if (response.result === "success") {
                if (typeof response.payload !== "undefined") {
                    var payload = JSON.parse(response.payload);
                    (function (document, src, libName, config) {
                        var script = document.createElement('script');
                        script.src = src;
                        script.async = true;
                        var firstScriptElement = document.getElementsByTagName('script')[0];
                        script.onload = function () {
                            for (var namespace in config) {
                                if (config.hasOwnProperty(namespace)) {
                                    window[libName].setup.setConfig(namespace, config[namespace]);
                                }
                            }
                            window[libName].register();
                            TwoCoInlineCart.setup.setMerchant(payload['merchant']);
                            TwoCoInlineCart.setup.setMode(payload.mode);
                            TwoCoInlineCart.register();

                            TwoCoInlineCart.cart.setCurrency(payload['currency']);
                            TwoCoInlineCart.cart.setLanguage(payload['language']);
                            TwoCoInlineCart.cart.setReturnMethod(payload['return-method']);
                            TwoCoInlineCart.cart.setTest(payload['test']);
                            TwoCoInlineCart.cart.setOrderExternalRef(payload['order-ext-ref']);
                            TwoCoInlineCart.cart.setExternalCustomerReference(payload['customer-ext-ref']);
                            TwoCoInlineCart.cart.setSource(payload['src']);

                            TwoCoInlineCart.products.removeAll();
                            TwoCoInlineCart.products.addMany(payload['products']);
                            TwoCoInlineCart.billing.setData(payload['billing_address']);
                            TwoCoInlineCart.shipping.setData(payload['shipping_address']);
                            TwoCoInlineCart.cart.setSignature(payload['signature']);
                            TwoCoInlineCart.cart.setAutoAdvance(true);
                            TwoCoInlineCart.cart.checkout();

                        };
                        firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
                    })(document, 'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js', 'TwoCoInlineCart',
                        {"app": {"merchant": payload.merchant}, "cart": {"host": "https:\/\/secure.2checkout.com"}}
                    );
                }else if(typeof response.redirect !== "undefined"){
                    window.location.href = response.redirect;
                }
            }
            jQuery('#tcoWait').hide();
            if (response.result === "failure") {
                jQuery('#tco_inline_error').html(response.messages);
                jQuery('#place_order').attr('disabled', false);
            }
        },
        error: function (response, data) {
            console.error("Error response: " + response + " && data: " + data);
            jQuery('#place_order').attr('disabled', false);
            return false;
        },
        complete: function (xhr, status) {
            jQuery('#place_order').attr('disabled', false);
        }
    });
}

jQuery(document).on("change", "form[name='checkout'] input[name='payment_method']", function () {

    if (jQuery(this).attr('id') == 'payment_method_twocheckout_inline') {
        jQuery("form.woocommerce-checkout").unbind('submit');
        jQuery("form.woocommerce-checkout")
            .on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                inlinePay();
                return false;
            });
    }
});

jQuery(document).on("change", "form[id='order_review'] input[name='payment_method']", function () {

    if (jQuery(this).attr('id') == 'payment_method_twocheckout_inline') {
        jQuery("form#order_review").unbind('submit');
        jQuery("form#order_review")
            .on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                inlinePay();
                return false;
            });
    }
});


jQuery(window).on('load', function () {
    if (jQuery("#payment_method_twocheckout_inline").is(':checked')) {
        if (jQuery('form.woocommerce-checkout').length) {
            jQuery("form.woocommerce-checkout").unbind('submit');
            jQuery("form.woocommerce-checkout")
                .on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    inlinePay();
                    return false;
                });
        } else if (jQuery('form#order_review').length) {
            jQuery("form#order_review").unbind('submit');
            jQuery("form#order_review")
                .on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    inlinePay();
                    return false;
            });
        }
    }
});
