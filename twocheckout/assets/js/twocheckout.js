let twoPayJsCardElementLoaded = false;
let twoPayJsPaymentClient, twoPayJsComponent;

function twoPayJsCall(){
    if (jQuery('form.woocommerce-checkout').length || jQuery('form#order_review').length) {
        jQuery('#tco_error').html('');
        jQuery('#place_order').attr('disabled', true);
        jQuery('#tcoWait').show();
        let customer = jQuery('input[name="billing_first_name"]').val() + ' ' + jQuery('input[name="billing_last_name"]').val();
        if (jQuery('form.woocommerce-checkout').length) {
            if(jQuery("#payment_method_twocheckout").is(':checked')) {
                twoPayJsPaymentClient.tokens.generate(twoPayJsComponent, {name: customer}).then(function (response) {
                    if (response.token) {
                        jQuery('#ess_token').val(response.token);
                        jQuery('#is_guest').val(wc_checkout_params.option_guest_checkout);
                        ajaxTwoPayJsSubmit();
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
                ajaxTwoPayJsSubmit();
            }
        } else if (jQuery('form#order_review').length) {
            if(jQuery("#payment_method_twocheckout").is(':checked')) {
                twoPayJsPaymentClient.tokens.generate(twoPayJsComponent, {name: customer}).then(function (response) {
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
    if(jQuery("#payment_method_twocheckout").is(':checked')) {
        if (jQuery('form.woocommerce-checkout').length) {
            jQuery("form.woocommerce-checkout").unbind('submit');
            jQuery("form.woocommerce-checkout").on('submit', bindTwoPayJs);
        } else if (jQuery('form#order_review').length) {
            jQuery("form#order_review").unbind('submit');
            jQuery("form#order_review").on('submit', bindTwoPayJs);
        }
        if (!twoPayJsCardElementLoaded) {
            if (jQuery('#tco-payment-form').length && jQuery('#tco-payment-form').data('json').length) {
                twoPayJsCardElementLoaded = true;
            }
            setTimeout(function () {
                isDomReadyFor2PayJs();
            }, 100);
        } else {
            prepareTwoPayJs();
        }
    } else {
        jQuery("form.woocommerce-checkout").off('submit', bindTwoPayJs);
        jQuery("form#order_review").off('submit', bindTwoPayJs);
    }
}

jQuery(window).on('load', function() {
    if (twocheckoutIsCheckout === 'yes') {
        jQuery( document.body ).on( 'updated_checkout', function() {
            isDomReadyFor2PayJs();
        });
    } else if (jQuery( 'form#add_payment_method' ).length || jQuery( 'form#order_review' ).length ) {
        isDomReadyFor2PayJs();
    }
});

jQuery(document).on("change", "form[name='checkout'] input[name='payment_method']", function () {
    if (jQuery(this).attr('id')== 'payment_method_twocheckout') {
        if (jQuery('#card-element iframe').length) {
            jQuery('#card-element iframe').remove();
        }
    }
    isDomReadyFor2PayJs();
});

jQuery(document).on("change", "form[id='order_review'] input[name='payment_method']", function () {
    if (jQuery(this).attr('id')== 'payment_method_twocheckout') {
        if (jQuery('#card-element iframe').length) {
            jQuery('#card-element iframe').remove();
        }
    }
    isDomReadyFor2PayJs();
});

function bindTwoPayJs(e) {
    e.preventDefault();
    e.stopPropagation();
    twoPayJsCall();
    return false;
}

function prepareTwoPayJs() {
    if (!jQuery('#card-element iframe:visible').length) {
        jQuery('#card-element iframe').remove();
        jQuery('#load').show();

        twoPayJsPaymentClient = new TwoPayClient(twocheckoutSellerId);

        if (twocheckoutDefaultStyle === 'yes') {
            twoPayJsComponent = twoPayJsPaymentClient.components.create('card');
        } else {
            let style = jQuery('#tco-payment-form').data('json');
            style = style.replace(/'/g, '"');
            twoPayJsComponent = twoPayJsPaymentClient.components.create('card', JSON.parse(style));
        }
        jQuery('#load').fadeOut(150, function () {
            jQuery('#load').hide();
        });
        twoPayJsComponent.mount('#card-element');
    }
}

function ajaxTwoPayJsSubmit(){
    jQuery.ajax({
        type: 'POST',
        data: jQuery('.checkout.woocommerce-checkout').serialize(),
        url: wc_checkout_params.checkout_url,
        success: function (response) {
            jQuery('#tcoWait').hide();
            if (response.result === "failure") {
                jQuery('#tco_error').html(response.messages);
                jQuery('#tcoWait').hide();
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