<?php
/*
  Plugin Name: 2Checkout Inline Payment Gateway
  Plugin URI:
  Description: Allows you to use 2Checkout payment gateway with the WooCommerce plugin.
  Version: 1.2.0
  Author: 2Checkout
  Author URI: https://www.2checkout.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/* Add a custom payment class to WC
  ------------------------------------------------------------ */
add_action( 'plugins_loaded', 'woocommerce_twocheckout_inline' );

function woocommerce_twocheckout_inline() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	} // if the WC payment gateway class is not available, do nothing
	if ( class_exists( 'WC_Twocheckout' ) ) {
		return;
	}

	/**
	 * Class WC_Gateway_Twocheckout_Inline
	 */
	class WC_Gateway_Twocheckout_Inline extends WC_Payment_Gateway {

		// Logging
		public static $log_enabled = false;
		public static $log = false;
		private $seller_id;
		private $secret_key;
		private $test_order;
		private $secret_word;
		private $custom_style;
		private $debug;

		const SRC = 'WOOCOMMERCE_3_8';

		/**
		 * WC_Gateway_Twocheckout_Inline constructor.
		 */
		public function __construct() {
			$this->id                 = 'twocheckout_inline';
			$this->icon               = apply_filters( 'woocommerce_twocheckout_icon',
				plugin_dir_url( __FILE__ ) . 'twocheckout.png' );
			$this->plugin_name        = '2Checkout secured Inline payments';
			$this->method_description = __( 'Secured 2Checkout Inline payments without redirects', 'woocommerce' );
			$this->supports[]         = 'refunds';
			$this->has_fields         = true;

			// Load the settings
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->seller_id    = $this->get_option( 'seller_id' );
			$this->secret_key   = $this->get_option( 'secret_key' );
			$this->secret_word  = $this->get_option( 'secret_word' );
			$this->custom_style = $this->get_option( 'style' );
			$this->test_order   = $this->get_option( 'demo' );
			$this->description  = $this->get_option( 'description' );
			$this->debug        = $this->get_option( 'debug' );

			self::$log_enabled = $this->debug;

			$this->add_actions();

			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

		}

		private function add_actions() {
			//put your actions here

			// Actions
			add_action( 'woocommerce_receipt_' . $this->id,
				[ $this, 'receipt_page' ] );

			// Save options
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
				[
					$this,
					'process_admin_options',
				] );

			add_action( 'woo_mp_payment_complete', function ( $order ) {
				$order->set_payment_method( 'inline' );
				$order->save();
			}, 10, 1 );

			// Payment listener/API hook
			add_action( 'woocommerce_api_payment_response', [ $this, 'check_inline_payment_response' ] );
			add_action( 'woocommerce_api_2checkout_ipn_inline',
				[ $this, 'check_ipn_response_inline' ] );

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_style' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );

			add_action( 'woocommerce_api_twocheckout_inline_handle_payment_request', [
				$this,
				'handle_payment_request'
			] );

			// Order Page filter
			add_action( 'woocommerce_pay_order_after_submit', array( $this, 'render_additional_order_page_fields' ) );

		}

		function enqueue_style() {
			wp_enqueue_style( 'twocheckout_inline_style',
				'/wp-content/plugins/twocheckout-inline/assets/css/twocheckout.css' );
		}

		//enqueue a script
		function enqueue_script() {
			wp_enqueue_script( 'twocheckout_inline_script',
				'/wp-content/plugins/twocheckout-inline/assets/js/twocheckout_inline.js' );
		}

		/**
		 * Logging method
		 *
		 * @param string $message
		 */
		public static function log( $message ) {
			if ( self::$log_enabled ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'twocheckout', $message );
			}
		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		public function admin_options() {

			require_once plugin_dir_path( __FILE__ ) . 'templates/admin-options.php';
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			require_once plugin_dir_path( __FILE__ ) . 'templates/init_form_fields.php';
			$this->form_fields = get_two_checkout_inline_form_fields();
		}

		/**
		 * Generate the credit card payment form
		 */
		public function payment_fields() {
			require_once plugin_dir_path( __FILE__ ) . 'templates/payment-inline-fields.php';
		}

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use() {
			$supported_currencies = [
				'AFN',
				'ALL',
				'DZD',
				'ARS',
				'AUD',
				'AZN',
				'BSD',
				'BDT',
				'BBD',
				'BZD',
				'BMD',
				'BOB',
				'BWP',
				'BRL',
				'GBP',
				'BND',
				'BGN',
				'CAD',
				'CLP',
				'CNY',
				'COP',
				'CRC',
				'HRK',
				'CZK',
				'DKK',
				'DOP',
				'XCD',
				'EGP',
				'EUR',
				'FJD',
				'GTQ',
				'HKD',
				'HNL',
				'HUF',
				'INR',
				'IDR',
				'ILS',
				'JMD',
				'JPY',
				'KZT',
				'KES',
				'LAK',
				'MMK',
				'LBP',
				'LRD',
				'MOP',
				'MYR',
				'MVR',
				'MRO',
				'MUR',
				'MXN',
				'MAD',
				'NPR',
				'TWD',
				'NZD',
				'NIO',
				'NOK',
				'PKR',
				'PGK',
				'PEN',
				'PHP',
				'PLN',
				'QAR',
				'RON',
				'RUB',
				'WST',
				'SAR',
				'SCR',
				'SGF',
				'SBD',
				'ZAR',
				'KRW',
				'LKR',
				'SEK',
				'CHF',
				'SYP',
				'THB',
				'TOP',
				'TTD',
				'TRY',
				'UAH',
				'AED',
				'USD',
				'VUV',
				'VND',
				'XOF',
				'YER',
			];

			if ( ! in_array( get_woocommerce_currency(),
				apply_filters( 'woocommerce_twocheckout_supported_currencies',
					$supported_currencies ) ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Include inline helper file
		 */
		private function inline_helper() {
			require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/Helpers/class-two-checkout-inline-helper.php';
		}

		/**
		 * @param int    $order_id
		 * @param null   $amount
		 * @param string $reason
		 *
		 * @return bool|\WP_Error
		 * @throws \Exception
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );
			if ( $order->get_payment_method() == 'twocheckout_inline' ) {
				$transaction_id = $order->get_meta( '__2co_order_number' );

				require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/TwoCheckoutApi.php';
				$api = new Two_Checkout_Api();
				$api->set_seller_id( $this->seller_id );
				$api->set_secret_key( $this->secret_key );
				$tco_order = $api->call( 'orders/' . $transaction_id . '/', [], 'GET' );
				if ( ! $order || ! $tco_order || ! $transaction_id ) {
					$this->log( sprintf( 'Tried to refund order with order ID %s but it has no registered transaction ID, aborting.', $order_id ) );

					return new WP_Error( '2co_refund_error', 'Refund Error: Unable to refund transaction' );
				}

				if ( $amount != $tco_order['GrossPrice'] ) {
					$this->log( 'Only full refund is supported!' );

					return new WP_Error( '2co_refund_error', 'Refund Error: Only full refund is supported.' );
				}

				if ( strtolower( get_woocommerce_currency() ) != strtolower( $tco_order['Currency'] ) ) {
					$this->log( 'Order currency not matching the 2checkout response!' );

					return new WP_Error( '2co_refund_error', 'Refund Error: Order currency not matching the 2checkout response.' );
				}


				$params = [
					"amount"  => $amount,
					"comment" => $reason,
					"reason"  => 'Other'
				];

				$response = $api->call( '/orders/' . $transaction_id . '/refund/', $params, 'POST' );

				if ( isset( $response['error_code'] ) && ! empty( $response['error_code'] ) ) {
					$this->log( 'Refund failed. Please login to your 2Checkout admin to issue the partial refund manually.' );

					return new WP_Error( '2co_refund_error', 'Refund failed. Please login to your 2Checkout admin to issue the partial refund manually.' );
				}

				$order->update_meta_data( '__2co_order_number', $response['id'] );
				$order->add_order_note( __( sprintf( 'Refunded %s out of a total of %s from order', $amount, $order->get_total() ) ) );
				$order->save_meta_data();
				$order->save();

				return true;
			}
		}

		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 *
		 * @param int $order_id
		 *
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );
			$this->inline_helper();
			$helper = new Two_Checkout_Inline_Helper();

			try {
				$order_params = [
					'currency'         => get_woocommerce_currency(),
					'language'         => strtoupper( substr( get_locale(), 0,
						2 ) ),
					'country'          => strtoupper( $order->get_billing_country() ),
					'products'         => $this->get_item( $order ),
					'return-method'    => [
						'type' => 'redirect',
						'url'  => add_query_arg( 'wc-api', 'payment_response', home_url( '/' ) ) . "&pm={$order->get_payment_method()}" . "&order-ext-ref={$order->get_id()}",
					],
					'test'             => strtolower( $this->test_order ) === 'yes' ? '1' : '0',
					'order-ext-ref'    => $order->get_id(),
					'customer-ext-ref' => $order->get_billing_email(),
					'src'              => self::SRC,
					'mode'             => 'DYNAMIC',
					'dynamic'          => '1',
					'merchant'         => $this->seller_id,
				];

				$order_params = array_merge( $order_params,
					$this->get_billing_details( $order ) );
				$order_params = array_merge( $order_params,
					$this->get_shipping_details( $order ) );

				$order_params['billing_address']  = $this->get_billing_details( $order );
				$order_params['shipping_address'] = $this->get_shipping_details( $order );

				$order_params['signature'] = $helper->get_inline_signature(
					$this->seller_id,
					$this->secret_word,
					$order_params );


				return [
					'result'  => 'success',
					'payload' => wp_json_encode( $order_params ),
				];

			}
			catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), $notice_type = 'error' );

				return [
					'result'   => 'failure',
					'messages' => 'There has been an error processing your order',
				];
			}
		}

		/**
		 * @param WC_Order $order
		 *
		 * @return array
		 */
		private function get_billing_details( WC_Order $order ) {
			return [
				'name'         => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'phone'        => $order->get_billing_phone(),
				'country'      => strtoupper( $order->get_billing_country() ),
				'state'        => $order->get_billing_state(),
				'email'        => $order->get_billing_email(),
				'address'      => $order->get_billing_address_1(),
				'address2'     => $order->get_billing_address_2(),
				'city'         => $order->get_billing_city(),
				'zip'          => $order->get_billing_postcode(),
				'company-name' => $order->get_billing_company(),
			];
		}


		/**
		 * @param WC_Order $order
		 *
		 * @return array
		 */
		private function get_shipping_details( WC_Order $order ) {
			return [
				'ship-name'     => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
				'ship-country'  => strtoupper( $order->get_shipping_country() ),
				'ship-state'    => $order->get_shipping_state(),
				'ship-email'    => $order->get_billing_email(),
				//same as billing
				'ship-address'  => $order->get_shipping_address_1(),
				'ship-address2' => $order->get_shipping_address_2(),
				'ship-city'     => $order->get_shipping_city(),
			];
		}

		/**
		 * for safety reasons we only send one Item with the grand total and the Cart_id as ProductName (identifier)
		 * sending products order as ONE we dont have to calculate the total fee of the order (product price, tax, discounts etc)
		 *
		 * @return array
		 */
		private function get_item( WC_Order $order ) {
			$items[] = [
				'type'     => 'PRODUCT',
				'name'     => get_bloginfo(),
				'price'    => number_format( $order->get_total(), 2, '.', '' ),
				'tangible' => 0,
				'quantity' => 1,
			];

			return $items;
		}

		/**
		 * @return void
		 */
		public function check_inline_payment_response() {
			global $woocommerce;
			$params = $_GET;
			if ( isset( $params['pm'] ) && ! empty( $params['pm'] ) ) {
				if ( $params['pm'] == 'twocheckout_inline' ) {
					if ( isset( $params['order-ext-ref'] ) && ! empty( $params['order-ext-ref'] ) ) {
						$order = wc_get_order( (int) $params['order-ext-ref'] );
						if ( ! $order instanceof WC_Order ) {
							$this->log( 'There was a request for an order that doesn\'t exist in current shop! Requested params: ' . strip_tags( http_build_query( $params ) ) );
						} else {
							if ( isset( $params['refno'] ) && ! empty( $params['refno'] ) ) {
								$refNo = $params['refno'];
								require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/TwoCheckoutApi.php';
								$api = new Two_Checkout_Api();
								$api->set_seller_id( $this->seller_id );
								$api->set_secret_key( $this->secret_key );
								$api_response = $api->call( 'orders/' . $refNo . '/', [], 'GET' );

								if ( ! empty( $api_response['Status'] ) && isset( $api_response['Status'] ) ) {
									if ( in_array( $api_response['Status'], [ 'AUTHRECEIVED', 'COMPLETE' ] ) ) {
										$redirect_url = $order->get_checkout_order_received_url();
										if ( wp_redirect( $redirect_url ) ) {
											if ( $order->has_status( 'pending' ) ) {
												$order->update_status( 'processing' );
												$order->update_meta_data( '__2co_order_number', $params['refno'] );
												$order->save_meta_data();
												$order->save();
											}
											$woocommerce->cart->empty_cart();
											exit;
										}
									}
								}
							}
						}
					}
					status_header( 404 );
					nocache_headers();
					include( get_query_template( '404' ) );
					die();
				}
			}
		}

		/**
		 * Validate & process 2Checkout request
		 *
		 * @access public
		 * @return void
		 */
		public function check_ipn_response_inline() {
			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				return;
			}
			$params = $_POST;
			unset( $params['wc-api'] );
			if ( isset( $params['REFNOEXT'] ) && ! empty( $params['REFNOEXT'] ) ) {
				$order = wc_get_order( $params['REFNOEXT'] );
				if ( $order && $order->get_payment_method() == 'twocheckout_inline' ) {
					require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/class-two-checkout-ipn-helper.php';
					try {
						$ipn_helper = new Two_Checkout_Ipn_Helper( $params, $this->secret_key, $this->debug, $order );
					}
					catch ( Exception $ex ) {
						$this->log( 'Unable to find order with RefNo: ' . $params['REFNOEXT'] );
						throw new Exception( 'An error occurred!' );
					}
					if ( ! $ipn_helper->is_ipn_response_valid() ) {
						self::log( sprintf( 'MD5 hash mismatch for 2Checkout IPN with date: "%s" . ',
							$params['IPN_DATE'] ) );

						return;
					}
					$ipn_helper->process_ipn();
				}
			}
		}

		/**
		 * ajax callable wrapper for process_payment
		 *
		 * @access public
		 * @return array
		 */
		public function handle_payment_request() {
			ob_start();
			if ( ! empty( $_POST['terms-field'] ) && empty( $_POST['terms'] ) ) {
				wp_send_json( [
					'result'   => 'failure',
					'messages' => 'Please read and accept the terms and conditions to proceed with your order.',
					'step'     => 'existing_order'
				] );
			}

			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset( $_POST['woocommerce-pay-nonce'] ) || ! isset( $_POST['order_id'] ) ) {
				return;
			}

			$nonce_value = $_POST['woocommerce-pay-nonce'];

			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {
				return;
			}

			$response = $this->process_payment( $_POST['order_id'] );

			wp_send_json( $response );
		}

		/**
		 * Render additional order page fields
		 *
		 * @access public
		 * @return void
		 */
		public function render_additional_order_page_fields( $order = null ) {
			if ( ! isset( $order ) || empty( $order ) ) {
				$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
			}

			if ( isset( $order ) ) {
				echo '<input type="hidden" name="order_id" value="' . esc_attr( $order->get_id() ) . '" />';
			}
		}
	}

	/**
	 * @param $methods
	 *
	 * @return array
	 */
	function add_twocheckout_gateway_inline( $methods ) {
		$methods[] = 'WC_Gateway_Twocheckout_Inline';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways',
		'add_twocheckout_gateway_inline' );
}
