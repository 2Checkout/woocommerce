<?php

namespace Twocheckout\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * 2Checkout Inline payment method integration for WooCommerce Blocks
 */
final class TwocheckoutInlineBlocksIntegration extends AbstractPaymentMethodType {

	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'twocheckout_inline';

	/**
	 * An instance of the gateway.
	 *
	 * @var \WC_Gateway_Twocheckout_Inline
	 */
	private $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_twocheckout_inline_settings', [] );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways['twocheckout_inline'] ?? null;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return ! empty( $this->gateway ) && $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$plugin_root       = dirname( __FILE__, 4 );
		$script_path       = 'assets/js/twocheckout-inline-blocks.js';
		$script_asset_path = $plugin_root . '/assets/js/twocheckout-inline-blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => '2.4.0',
			];
		$script_url        = plugin_dir_url( $plugin_root . '/wc-twocheckout-inline.php' ) . $script_path;

		wp_register_script(
			'wc-twocheckout-inline-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		return [ 'wc-twocheckout-inline-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'                    => $this->get_setting( 'title' ),
			'description'              => $this->get_setting( 'description' ),
			'supports'                 => $this->get_supported_features(),
			'seller_id'                => $this->get_setting( 'seller_id' ),
			'secret_word'              => $this->get_setting( 'secret_word' ),
			'test_order'               => $this->get_setting( 'demo' ),
			'inline_type'              => $this->get_setting( 'inline_type' ),
			'payment_request_endpoint' => WC()->api_request_url( 'twocheckout_inline_handle_payment_request' ),
		];
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return array
	 */
	public function get_supported_features() {
		$gateway_features = $this->gateway ? $this->gateway->supports : [];
		return $gateway_features;
	}
}
