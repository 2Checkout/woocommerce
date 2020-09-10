function buyLinkPay(){
    jQuery('#tco_convert_plus_error').html('');
    jQuery('#place_order').attr('disabled', true);

    jQuery.ajax({
        type: 'POST',
        data: jQuery('.checkout.woocommerce-checkout').serialize(),
        url: wc_checkout_params.checkout_url,
        success: function (response) {
            if(response.result === "success") {
                let payload = JSON.parse(response.payload);
                if( 'url' in response) {
                    let url = response.url;
                    window.location.href = url + jQuery.param(payload);
                }
                else{
                    jQuery('#tco_convert_plus_error').html('An error occurred. Please try again!');
                }
            }

            jQuery('#tcoWait').hide();
            if (response.result === "failure") {
                jQuery('#tco_convert_plus_error').html(response.messages);
                jQuery('#place_order').attr('disabled', false);
            }
            return;
        },
        error: function (response, data) {
            console.error("Error response: " + response + " && data: " + data);
            jQuery('#place_order').attr('disabled', false);
            return;
        },
        complete: function (xhr, status) {
            jQuery('#place_order').attr('disabled', false);
            return;
        }
    });
}

jQuery(document).on("change", "form[name='checkout'] input[name='payment_method']", function(){

    if(jQuery(this).attr('id') == 'payment_method_twocheckout_convert_plus'){
        jQuery("form.woocommerce-checkout").unbind('submit');
        jQuery("form.woocommerce-checkout")
            .on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                buyLinkPay();
            });
    }
});


jQuery(window).on('load', function() {

    if(jQuery("#payment_method_twocheckout_convert_plus").is(':checked')) {
        jQuery("form.woocommerce-checkout").unbind('submit');
        jQuery("form.woocommerce-checkout")
            .on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                buyLinkPay();
            });
    }
});




