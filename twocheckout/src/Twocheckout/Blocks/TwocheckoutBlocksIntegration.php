<?php

namespace Twocheckout\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * 2Checkout API payment method integration for WooCommerce Blocks
 */
final class TwocheckoutBlocksIntegration extends AbstractPaymentMethodType {

	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'twocheckout';

	/**
	 * An instance of the gateway.
	 *
	 * @var \WC_Gateway_Twocheckout
	 */
	private $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_twocheckout_settings', [] );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways['twocheckout'] ?? null;
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
		$script_path       = 'assets/js/twocheckout-blocks.js';
		$script_asset_path = $plugin_root . '/assets/js/twocheckout-blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version'      => '2.4.0',
			];
		$script_url        = plugin_dir_url( $plugin_root . '/wc-twocheckout.php' ) . $script_path;

		wp_register_script(
			'wc-twocheckout-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		return [ 'wc-twocheckout-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'         => $this->get_setting( 'title' ),
			'description'   => $this->get_setting( 'description' ),
			'supports'      => $this->get_supported_features(),
			'seller_id'     => $this->get_setting( 'seller_id' ),
			'test_order'    => $this->get_setting( 'demo' ),
			'default_style' => $this->get_setting( 'default' ),
			'custom_style'  => $this->get_setting( 'style' ),
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
