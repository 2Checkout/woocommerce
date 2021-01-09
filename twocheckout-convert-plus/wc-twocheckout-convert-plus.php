<?php
/*
  Plugin Name: 2Checkout Convert Plus Payment Gateway
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
add_action( 'plugins_loaded', 'woocommerce_twocheckout_convert_plus' );

/**
 * Check if WooCommerce is active
 **/
function woocommerce_twocheckout_convert_plus() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	} // if the WC payment gateway class is not available, do nothing
	if ( class_exists( 'WC_Twocheckout_Convert_Plus_Getaway' ) ) {
		return;
	}

	class WC_Twocheckout_Convert_Plus_Getaway extends \WC_Payment_Gateway {

		/**** Plugin properties ****/
		protected $plugin_name;
		protected $version;
		/**** Custom Plugin Properties ****/
		protected $css_filepath;

		// Logging
		public static $log_enabled = false;
		public static $log = false;
		private $seller_id;
		private $secret_key;
		private $test_order;
		private $secret_word;
		private $custom_style;
		private $debug;

		const PAYMENT_METHOD_ID = 'twocheckout_convert_plus';

		/**** Plugin Constructor and Initializer(Run) ****/
		/**
		 * WC_Gateway_Twocheckout_Inline constructor.
		 */
		public function __construct() {
			//Initialize all the basics components of the plugin
			$this->plugin_name        = '2Checkout Convert Plus Payment Method Plugin';
			$this->method_description = __( '2Checkout secured card payments with Buy Links.', 'woocommerce' );
			$this->supports[]         = 'refunds';
			$this->version            = '1.0.0';

			//set the variable for the example endpoint. you can use a option plugin to store it and change it later in the admin page
			$this->css_filepath = 'assets/css/twocheckout.css';

			$this->id         = self::PAYMENT_METHOD_ID;
			$this->icon       = apply_filters( 'woocommerce_twocheckout_icon',
				plugin_dir_url( __FILE__ ) . 'twocheckout.png' );
			$this->has_fields = false;

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


			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

			$this->add_actions();
		}

		/**** Plugin methods ****/

		private function add_actions() {
			//put your actions here
			add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );

			// Save options
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
				[
					$this,
					'process_admin_options',
				] );

			// Payment listener/API hook
			add_action( 'woocommerce_api_2checkout_ipn_convert_plus', [ $this, 'check_ipn_response_convert_plus' ] );
			add_action( 'woocommerce_api_payment_response', [ $this, 'check_payment_response' ] );
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
			$this->form_fields = getTwoCheckoutConvertPlusFormFields();
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

		private function load_helper() {
			require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/Helpers/class-convert-plus-helper.php';
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
			$order->update_meta_data( '_2co_order_type', 'convert_plus' );
			$order->save_meta_data();
			$order->save();
			$this->load_helper();

			try {
				$buy_link_params = [];
				$order_params    = $this->build_checkout_parameters( $order );

				$buy_link_params = array_merge(
					$buy_link_params,
					$order_params['setup_data'],
					$order_params['cart_data'],
					$order_params['products_data'],
					$order_params['shipping_data'],
					$order_params['billing_data']
				);

				$helper = new WC_Twocheckout_Convert_Plus_Ipn_Helper( $buy_link_params, $this->secret_key, $this->debug, $order );
				$buy_link_params['signature'] = $helper->get_signature( $this->seller_id, $this->secret_word );
				$pay_url = 'https://secure.2checkout.com/checkout/buy?' . http_build_query( $buy_link_params );

				return [
					'result'   => 'success',
					'redirect' => $pay_url,
				];

			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), $notice_type = 'error' );

				return [
					'result'   => 'failure',
					'messages' => 'There has been an error processing your order',
				];
			}
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
			if ( $order->get_payment_method() == self::PAYMENT_METHOD_ID ) {
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
		 * @param WC_Order $order
		 *
		 * @return array
		 */
		public function build_checkout_parameters( WC_Order $order ) {
			global $woocommerce;
			$woocommerce_version_formatted = str_replace('.', '_', $woocommerce->version);

			//1. Setup data
			$setup_data             = [];
			$setup_data['merchant'] = $this->seller_id;
			$setup_data['dynamic']  = 1;
			//2. Set the BASE needed fields.
			$cart_data                     = [];
			$cart_data['src']              = 'WOOCOMMERCE_' . $woocommerce_version_formatted;
			$cart_data['return-url']       = add_query_arg( 'wc-api', 'payment_response', home_url( '/' ) ) . "&pm={$order->get_payment_method()}" . "&order-ext-ref={$order->get_id()}";
			$cart_data['return-type']      = 'redirect';
			$cart_data['expiration']       = time() + ( 3600 * 5 );
			$cart_data['order-ext-ref']    = $order->get_id();
			$cart_data['customer-ext-ref'] = $order->get_billing_email();
			$cart_data['currency']         = get_woocommerce_currency();
			$cart_data["test"]             = strtolower( $this->test_order ) === 'yes' ? '1' : '0';

			//3. Language config
			$current_locale_setting = get_option( 'WPLANG' );
			$current_store_lang     = get_locale();
			$lang                   = ! $current_store_lang ? $current_locale_setting : $current_store_lang;
			$langCode               = strstr( $lang, '_', true );
			$cart_data['language']  = $langCode;

			//4. Products
			$products = $this->get_item( $order );
			//dynamic products
			$products_data['prod']     = implode( ';', $products['prod'] );
			$products_data['price']    = implode(
				';',
				$products['price']
			);
			$products_data['qty']      = implode( ';', $products['qty'] );
			$products_data['type']     = implode( ';', $products['type'] );
			$products_data['tangible'] = implode(
				';',
				$products['tangible']
			);

			return [
				'setup_data'    => $setup_data,
				'cart_data'     => $cart_data,
				'products_data' => $products_data,
				'shipping_data' => $this->get_shipping_details( $order ),
				'billing_data'  => $this->get_billing_details( $order ),
			];
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
			$items               = [];
			$items['prod'][]     = get_bloginfo(); //name
			$items['price'][]    = number_format( $order->get_total(), 2, '.', '' );
			$items['qty'][]      = 1;
			$items['type'][]     = 'PRODUCT';
			$items['tangible'][] = 0;

			return $items;
		}

		/**
		 * Validate & process 2Checkout request
		 *
		 * @access public
		 * @return void|string
		 */
		public function check_ipn_response_convert_plus() {
			if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
				return;
			}
			$params = $_POST;
			unset( $params['wc-api'] );
			if ( isset( $params['REFNOEXT'] ) && ! empty( $params['REFNOEXT'] ) ) {
				$order = wc_get_order( $params['REFNOEXT'] );
				if ( $order && $order->get_payment_method() == self::PAYMENT_METHOD_ID ) {
					$this->load_helper();
					try {
						$helper = new WC_Twocheckout_Convert_Plus_Ipn_Helper( $params, $this->secret_key, $this->debug,
							$order );
					}
					catch ( Exception $ex ) {
						$this->log( 'Unable to find order with RefNo: ' . $params['REFNOEXT'] );
						throw new Exception( 'An error occurred!' );
					}
					if ( ! $helper->is_ipn_response_valid() ) {
						self::log( sprintf( 'MD5 hash mismatch for 2Checkout IPN with date: "%s" . ',
							$params['IPN_DATE'] ) );
						echo 'Bad Hash!';

						return;
					}

					return $helper->process_ipn();
				}
			}
		}

		/**
		 * @return void
		 */
		public function check_payment_response() {
			$params = $_GET;
			if ( ! isset( $params['pm'] ) || (string) $params['pm'] !== self::PAYMENT_METHOD_ID ) {
				return;
			}

			if ( ! isset( $params['order-ext-ref'] )
			     || ! isset( $params['refno'] )
			     || empty( $params['order-ext-ref'] )
			     || empty( $params['refno'] )
			) {
				$this->go_to_404_page();
			}

			$order = wc_get_order( (int) $params['order-ext-ref'] );

			if ( ! $order instanceof WC_Order ) {
				$this->log( 'There was a request for an order that doesn\'t exist in current shop! Requested params: '
				            . strip_tags( http_build_query( $params ) ) );
				$this->go_to_404_page();
			}

			$refNo = $params['refno'];
			require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/TwoCheckoutApi.php';
			$api = new Two_Checkout_Api();
			$api->set_seller_id( $this->seller_id );
			$api->set_secret_key( $this->secret_key );
			$api_response = $api->call( 'orders/' . $refNo . '/', [], 'GET' );

			if ( ! isset( $api_response['Status'] )
			     || empty( $api_response['Status'] )
			     || ! in_array( $api_response['Status'], [ 'AUTHRECEIVED', 'COMPLETE' ] )
			) {
				$this->log( 'Api did not respond with expected result' );
				$this->go_to_404_page();
			}

			$redirect_url = $order->get_checkout_order_received_url();
			if ( wp_redirect( $redirect_url ) ) {
				if ( $order->has_status( 'pending' ) ) {
					$order->update_status( 'processing' );
					$order->update_meta_data( '__2co_order_number', $params['refno'] );
					$order->save_meta_data();
					$order->save();
				}
				global $woocommerce;
				$woocommerce->cart->empty_cart();
			}
		}

		/**
		 * Returns a 404 page
		 */
		private function go_to_404_page()
		{
			status_header( 404 );
			nocache_headers();
			include( get_query_template( '404' ) );
			die;
		}
	}

	function add_twocheckout_gateway_convert_plus( $methods ) {
		$methods[] = 'WC_Twocheckout_Convert_Plus_Getaway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways',
		'add_twocheckout_gateway_convert_plus' );

}
