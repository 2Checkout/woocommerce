<?php

function getTwoCheckoutConvertPlusFormFields() {

	return [
		'enabled'     => [
			'title'   => __( 'Enable/Disable', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable 2Checkout Convert Plus', 'woocommerce' ),
			'default' => 'yes'
		],
		'title'       => [
			'title'       => __( 'Title', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
			'default'     => __( '2Checkout Convert Plus Payment Gateway', 'woocommerce' ),
			'desc_tip'    => true,
		],
		'description' => [
			'title'       => __( 'Description', 'woocommerce' ),
			'type'        => 'text',
			'desc_tip'    => true,
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
			'default'     => __( 'Safe payment solutions by <a href="https://www.2checkout.com/" target="_blank">2Checkout </a>', 'woocommerce' )
		],
		'seller_id'   => [
			'title'       => __( 'Seller ID', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Please enter your 2Checkout account number; this is needed in order to take payment.', 'woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
			'placeholder' => ''
		],
		'secret_key'  => [
			'title'       => __( 'Secret Key', 'woocommerce' ),
			'type'        => 'password',
			'description' => __( 'Please enter your 2Checkout Secret Key; this is needed in order to take payment.', 'woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
			'placeholder' => ''
		],
		'secret_word' => [
			'title'       => __( 'Secret Word', 'woocommerce' ),
			'type'        => 'password',
			'description' => __( 'Please enter your 2Checkout Secret Word; this is needed in order to update the payment status.', 'woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
			'placeholder' => ''
		],
		'ipn_webhook' => [
			'title'             => __( 'IPN Callback Url', 'woocommerce' ),
			'type'              => 'text',
			'default'           => str_replace( 'https:', 'http:', add_query_arg( 'wc-api', '2checkout_ipn', home_url( '/' ) ) ),
			'label'             => __( 'IPN Callback Url', 'woocommerce' ),
			'desc_tip'          => true,
			'custom_attributes' => [ 'readonly' => "readonly" ],
			'description'       => sprintf( __( 'The callback endpoint for IPN requests from 2Checkout', 'woocommerce' ), wc_get_log_file_path( 'twocheckout' ) )
		],
		'debug'       => [
			'title'       => __( 'Debug Log', 'woocommerce' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable logging', 'woocommerce' ),
			'default'     => 'no',
			'desc_tip'    => true,
			'description' => sprintf( __( 'Log 2Checkout events', 'woocommerce' ), wc_get_log_file_path( 'twocheckout' ) )
		],
		'demo'        => [
			'title'       => __( 'Demo order', 'woocommerce' ),
			'type'        => 'checkbox',
			'label'       => __( 'Create test orders', 'woocommerce' ),
			'default'     => 'no',
			'desc_tip'    => true,
			'description' => sprintf( __( 'Not available yet for this method!', 'woocommerce' ), wc_get_log_file_path( 'twocheckout' ) )
		],
	];
}
