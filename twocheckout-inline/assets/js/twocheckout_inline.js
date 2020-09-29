function inlinePay() {
    jQuery('#tco_inline_error').html('');
    jQuery('#place_order').attr('disabled', true);

    jQuery.ajax({
        type: 'POST',
        data: jQuery('.checkout.woocommerce-checkout').serialize(),
        url: wc_checkout_params.checkout_url,
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
            });
    }
});


jQuery(window).on('load', function () {
    if (jQuery("#payment_method_twocheckout_inline").is(':checked')) {
        jQuery("form.woocommerce-checkout").unbind('submit');
        jQuery("form.woocommerce-checkout")
            .on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                inlinePay();
            });
    }
});
