<h3><?php _e( '2Checkout', 'woocommerce' ); ?></h3>
<p><?php _e( '2Checkout - Credit Card by Inline Checkout', 'woocommerce' ); ?></p>
<table class="form-table">
    <?php
    // Generate the HTML For the settings form.
    $this->generate_settings_html();
    ?>

    <tr valign="top">
        <th scope="row" class="titledesc">
            <label><?php esc_html_e( 'IPN Callback URL', 'woocommerce' ); ?></label>
        </th>
        <td class="forminp">
            <fieldset>
                <legend class="screen-reader-text"><span><?php esc_html_e( 'IPN Callback URL', 'woocommerce' ); ?></span></legend>
                <input class="input-text regular-input" type="text" value="<?php echo add_query_arg( 'wc-api', '2checkout_ipn_inline', home_url( '/' ) ); ?>" readonly>
                <p class="description"><?php esc_html_e( 'The callback endpoint for IPN requests from 2Checkout', 'woocommerce' ); ?></p>
            </fieldset>
        </td>
    </tr>
</table><!--/.form-table-->
