let cardElementLoaded = false;
let jsPaymentClient, component;

function twoPayJsCall(){
    if (jQuery('form.woocommerce-checkout').length || jQuery('form#order_review').length) {
        jQuery('#tco_error').html('');
        jQuery('#place_order').attr('disabled', true);
        jQuery('#tcoWait').show();
        let customer = jQuery('input[name="billing_first_name"]').val() + ' ' + jQuery('input[name="billing_last_name"]').val();
        if (jQuery('form.woocommerce-checkout').length) {
            if(jQuery("#payment_method_twocheckout").is(':checked')) {
                jsPaymentClient.tokens.generate(component, {name: customer}).then(function (response) {
                    if (response.token) {
                        jQuery('#ess_token').val(response.token);
                        jQuery('#is_guest').val(wc_checkout_params.option_guest_checkout);
                        ajaxJsSubmit();
                    } else {
                        console.log('Error generating token!');
                        jQuery('#tcoWait').hide()
                        jQuery('#place_order').attr('disabled', false)
                        return false;
                    }
                }).catch(function (error) {
                    console.error(error);
                    jQuery('#tcoWait').hide();
                    jQuery('#place_order').attr('disabled', false)
                });
            } else {
                ajaxJsSubmit();
            }
        } else if (jQuery('form#order_review').length) {
            if(jQuery("#payment_method_twocheckout").is(':checked')) {
                jsPaymentClient.tokens.generate(component, {name: customer}).then(function (response) {
                    if (response.token) {
                        jQuery('#ess_token').val(response.token);
                        jQuery('#is_guest').val(wc_checkout_params.option_guest_checkout);
                        jQuery('form#order_review').unbind('submit').submit();
                    } else {
                        console.log('Error generating token!');
                        jQuery('#tcoWait').hide()
                        jQuery('#place_order').attr('disabled', false)
                        return false;
                    }
                }).catch(function (error) {
                    console.error(error);
                    jQuery('#tcoWait').hide();
                    jQuery('#place_order').attr('disabled', false)
                });
            } else {
                jQuery('form#order_review').unbind('submit').submit();
            }
        }
    }
}


function isDomReadyFor2PayJs() {
    if (!cardElementLoaded) {
        if (jQuery('#tco-payment-form').length) {
            cardElementLoaded = true;
        }
        setTimeout(function () {
            isDomReadyFor2PayJs();
        }, 333);
    } else {
        prepareTwoPayJs();
    }
}

jQuery(window).on('load', function() {
    if(jQuery("#payment_method_twocheckout").is(':checked')) {
        if (jQuery('form.woocommerce-checkout').length) {
            jQuery("form.woocommerce-checkout").unbind('submit');
            jQuery("form.woocommerce-checkout")
                .on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    twoPayJsCall();
                    return false;
                });
        } else if (jQuery('form#order_review').length) {
            jQuery("form#order_review").unbind('submit');
            jQuery("form#order_review")
                .on('submit', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    twoPayJsCall();
                    return false;
                });
        }
    }
    isDomReadyFor2PayJs();

});

jQuery(document).on("change", "form[name='checkout'] input[name='payment_method']", function () {
    if(jQuery(this).attr('id')== 'payment_method_twocheckout'){
        jQuery("form.woocommerce-checkout").unbind('submit');
        jQuery("form.woocommerce-checkout")
            .on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                twoPayJsCall();
                return false;
            });
    }
});

jQuery(document).on("change", "form[id='order_review'] input[name='payment_method']", function () {
    if(jQuery(this).attr('id')== 'payment_method_twocheckout'){
        jQuery("form#order_review").unbind('submit');
        jQuery("form#order_review")
            .on('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                twoPayJsCall();
                return false;
            });
    }
});

function prepareTwoPayJs() {
    jsPaymentClient = new TwoPayClient(seller_id);

    if (defaultStyle === 'yes') {
        component = jsPaymentClient.components.create('card');
    } else {
        let style = jQuery('#tco-payment-form').data('json');
        style = style.replace(/'/g, '"');
        component = jsPaymentClient.components.create('card', JSON.parse(style));
    }
    jQuery('#load').fadeOut(150, function () {
        jQuery('#load').remove();
    });
    component.mount('#card-element');
}

function ajaxJsSubmit(){
    jQuery.ajax({
        type: 'POST',
        data: jQuery('.checkout.woocommerce-checkout').serialize(),
        url: wc_checkout_params.checkout_url,
        success: function (response) {
            jQuery('#tcoWait').hide();
            if (response.result === "failure") {
                jQuery('#tco_error').html(response.messages);
            } else {
                window.location.replace(response.redirect);
            }
        },
        error: function (response, data) {
            console.error("Error response: " + response + " && data: " + data);
            jQuery('#tcoWait').hide();
            return false;
        },
        complete: function (xhr, status) {
            jQuery('#tcoWait').hide();
            jQuery('#place_order').attr('disabled', false);
        }
    });
}
