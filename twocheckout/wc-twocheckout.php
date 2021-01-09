<?php
/*
  Plugin Name: 2Checkout Payment Gateway
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
add_action( 'plugins_loaded', 'woocommerce_twocheckout' );

// Autoload whatever classes we need
// They're required ONLY when "new" is called
// Not prior to that, so there shouldn't be
// Much if any performance issue
//require_once plugin_dir_path(__FILE__) . 'autoload.php';

function woocommerce_twocheckout() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	} // if the WC payment gateway class is not available, do nothing
	if ( class_exists( 'WC_Twocheckout' ) ) {
		return;
	}

	/**
	 * Class WC_Gateway_Twocheckout
	 */
	class WC_Gateway_Twocheckout extends WC_Payment_Gateway {

		// Logging
		public static $log_enabled = false;
		public static $log = false;
		private $seller_id;
		private $secret_key;
		private $test_order;
		private $default_style;
		private $custom_style;
		private $debug;

		/**
		 * WC_Gateway_Twocheckout constructor.
		 */
		public function __construct() {
			$this->id                 = 'twocheckout';
			$this->icon               = apply_filters( 'woocommerce_twocheckout_icon',
				plugin_dir_url( __FILE__ ) . 'twocheckout.png' );
			$this->plugin_name        = '2Checkout 2PayJs over API';
			$this->method_description = __( 'Secured 2Checkout card payments over API.', 'woocommerce' );
			$this->supports[]         = 'refunds';
			$this->has_fields         = true;

			// Load the settings
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title         = $this->get_option( 'title' );
			$this->seller_id     = $this->get_option( 'seller_id' );
			$this->secret_key    = $this->get_option( 'secret_key' );
			$this->default_style = $this->get_option( 'default' );
			$this->custom_style  = $this->get_option( 'style' );
			$this->test_order    = $this->get_option( 'demo' );
			$this->description   = $this->get_option( 'description' );
			$this->debug         = $this->get_option( 'debug' );

			self::$log_enabled = $this->debug;

			// Actions
			add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );

			// Save options
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
				[ $this, 'process_admin_options' ] );


			// Payment listener/API hook
			add_action( 'woocommerce_api_payment_response', [ $this, 'check_api_payment_response' ] );
			add_action( 'woocommerce_api_2checkout_ipn_api', [ $this, 'check_ipn_response_api' ] );

			// Order Page filter
			// add_filter( 'woocommerce_available_payment_gateways', array( $this, 'prepare_order_pay_page' ) );
			add_action( 'woocommerce_pay_order_after_submit', array( $this, 'render_additional_order_page_fields' ) );

			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

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
			$this->form_fields = getTwoCheckoutFormFields();
		}

		/**
		 * Generate the credit card payment form
		 */
		public function payment_fields() {

			wp_enqueue_script( '2payjs', 'https://2pay-js.2checkout.com/v1/2pay.js' );
			wp_enqueue_script( 'twocheckout_script', '/wp-content/plugins/twocheckout/assets/js/twocheckout.js' );
			wp_enqueue_style( 'twocheckout_style', '/wp-content/plugins/twocheckout/assets/css/twocheckout.css' );
			$twocheckout_is_checkout = ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no';
			require_once plugin_dir_path( __FILE__ ) . 'templates/payment-fields.php';
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
				'YER'
			];

			if ( ! in_array( get_woocommerce_currency(),
				apply_filters( 'woocommerce_twocheckout_supported_currencies', $supported_currencies ) ) ) {
				return false;
			}

			return true;
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
			if ( $order->get_payment_method() == 'twocheckout' ) {
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
			global $woocommerce;
			$post_data = $_POST;

			$order       = new WC_Order( $order_id );
			$total       = $order->get_total();
			$customer_ip = $order->get_customer_ip_address();
			require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/TwoCheckoutApi.php';

			$country_code = strtoupper( $order->get_billing_country() );
			try {

				$order_params = [
					'Currency'          => get_woocommerce_currency(),
					'Language'          => strtoupper( substr( get_locale(), 0, 2 ) ),
					'Country'           => $country_code,
					'CustomerIP'        => $customer_ip,
					'Source'            => 'WOOCOMMERCE_3_8',
					'ExternalReference' => $order_id,
					'Items'             => $this->get_item( $total ),
					'BillingDetails'    => $this->get_billing_details( $post_data, $country_code ),
					'PaymentDetails'    => $this->get_payment_details(
						$post_data['ess_token'],
						$customer_ip,
						add_query_arg( 'wc-api', 'payment_response', home_url( '/' ) ) . "&pm={$order->get_payment_method()}" . "&order-ext-ref={$order->get_id()}",
						wc_get_cart_url()
					)
				];

				$api = new Two_Checkout_Api();
				$api->set_seller_id( $this->seller_id );
				$api->set_secret_key( $this->secret_key );
				$api_response = $api->call( 'orders', $order_params );
				if ( ! $api_response || isset( $api_response['error_code'] ) && ! empty( $api_response['error_code'] ) ) { // we dont get any response from 2co or internal account related error
					if ( $api_response && isset( $api_response['message'] ) && ! empty( $api_response['message'] ) ) {
						$error_message = $api_response['message'];
					} else {
						$error_message = __( 'The payment could not be processed for order ' . $order_id . '! Please try again or contact us.' );
					}
					wc_add_notice( __( 'Payment error:', 'woothemes' ) . $error_message, 'error' );
					$json_response = [
						'result'   => 'error',
						'messages' => $error_message,
						'refresh'  => true,
						'reload'   => false,
						'redirect' => null
					];
				} else {
					if ( $api_response['Errors'] ) { // errors that must be shown to the client
						$error_message = '';
						foreach ( $api_response['Errors'] as $key => $value ) {
							$error_message .= $value . PHP_EOL;
						}
						wc_add_notice( __( 'Payment error: ' . $error_message, 'woothemes' ), 'error' );
						$json_response = [
							'result'   => 'error',
							'messages' => $error_message,
							'refresh'  => true,
							'reload'   => false,
							'redirect' => null
						];
					} else {
						$order->add_order_note( __( '2Checkout transaction ID: ' . $api_response['RefNo'] ), false, false );

						$order->update_meta_data( '__2co_order_number', $api_response['RefNo'] );
						$has3ds = false;
						if ( isset( $api_response['PaymentDetails']['PaymentMethod']['Authorize3DS'] ) ) {
							$has3ds = $this->has_authorize_3DS( $api_response['PaymentDetails']['PaymentMethod']['Authorize3DS'] );
						}
						if ( $has3ds ) {
							$redirect_url  = $has3ds;
							$json_response = [
								'result'   => 'success',
								'messages' => '3dSecure Redirect',
								'type'     => '3ds_redirect',
								'refresh'  => true,
								'reload'   => false,
								'redirect' => $redirect_url,
							];
						} else {
							$order->update_status( 'processing' );
							$json_response = [
								'result'   => 'success',
								'messages' => 'Order payment success',
								'refresh'  => true,
								'reload'   => false,
								'redirect' => $this->get_return_url( $order )
							];
						}
					}
				}

				return $json_response;
			}
			catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), $notice_type = 'error' );

				return false;
			}
		}


		/**
		 * @param array  $post_data
		 * @param string $country_code
		 *
		 * @return array
		 */
		private function get_billing_details( array $post_data, string $country_code ) {
			$address = [
				'Address1'    => $post_data['billing_address_1'],
				'City'        => $post_data['billing_city'],
				'State'       => $post_data['billing_state'],
				'CountryCode' => $country_code,
				'Email'       => $post_data['billing_email'],
				'FirstName'   => $post_data['billing_first_name'],
				'LastName'    => $post_data['billing_last_name'],
				'Phone'       => $post_data['billing_phone'],
				'Zip'         => $post_data['billing_postcode'],
				'Company'     => $post_data['billing_company']
			];

			if ( $post_data['billing_address_2'] ) {
				$address['Address2'] = $post_data['billing_address_2'];
			}

			return $address;
		}

		/**
		 * for safety reasons we only send one Item with the grand total and the Cart_id as ProductName (identifier)
		 * sending products order as ONE we dont have to calculate the total fee of the order (product price, tax, discounts etc)
		 *
		 * @param float $total
		 *
		 * @return array
		 */
		private function get_item( float $total ) {
			$items[] = [
				'Code'             => null,
				'Quantity'         => 1,
				'Name'             => get_bloginfo(),
				'Description'      => 'N/A',
				'RecurringOptions' => null,
				'IsDynamic'        => true,
				'Tangible'         => false,
				'PurchaseType'     => 'PRODUCT',
				'Price'            => [
					'Amount' => number_format( $total, 2, '.', '' ),
					'Type'   => 'CUSTOM'
				]
			];

			return $items;
		}

		/**
		 * @param string $token
		 * @param string $customer_ip
		 * @param string $success_url
		 * @param string $cancel_url
		 *
		 * @return array
		 */
		private function get_payment_details(
			string $token,
			string $customer_ip,
			string $success_url,
			string $cancel_url
		) {

			return [
				'Type'          => strtolower( $this->test_order ) === 'yes' ? 'TEST' : 'EES_TOKEN_PAYMENT',
				'Currency'      => get_woocommerce_currency(),
				'CustomerIP'    => $customer_ip,
				'PaymentMethod' => [
					'EesToken'           => $token,
					'Vendor3DSReturnURL' => $success_url,
					'Vendor3DSCancelURL' => $cancel_url
				],
			];
		}

		/**
		 * @param mixed $has3ds
		 *
		 * @return string|null
		 */
		private function has_authorize_3DS( $has3ds ) {
			if ( isset( $has3ds ) && isset( $has3ds['Href'] ) && ! empty( $has3ds['Href'] ) ) {

				return $has3ds['Href'] . '?avng8apitoken=' . $has3ds['Params']['avng8apitoken'];
			}

			return null;
		}

		/**
		 * @return void
		 */
		public function check_api_payment_response() {
			global $woocommerce;
			$params = $_GET;
			if ( isset( $params['pm'] ) && ! empty( $params['pm'] ) ) {
				if ( $params['pm'] == 'twocheckout' ) {
					if ( isset( $params['order-ext-ref'] ) && ! empty( $params['order-ext-ref'] ) ) {
						$order = wc_get_order( (int) $params['order-ext-ref'] );
						if ( ! $order instanceof WC_Order ) {
							$this->log( 'There was a request for an order that doesn\'t exist in current shop! Requested params: ' . strip_tags( http_build_query( $params ) ) );
						} else {
							if ( isset( $params['REFNO'] ) && ! empty( $params['REFNO'] ) ) {
								$refNo = $params['REFNO'];
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
												$order->update_meta_data( '__2co_order_number', $refNo );
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
		public function check_ipn_response_api() {
			if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
				return;
			}
			$params = $_POST;
			unset( $params['wc-api'] );
			if ( isset( $params['REFNOEXT'] ) && ! empty( $params['REFNOEXT'] ) ) {
				$order = wc_get_order( $params['REFNOEXT'] );
				if ( $order && $order->get_payment_method() == 'twocheckout' ) {
					require_once plugin_dir_path( __FILE__ ) . 'src/Twocheckout/TwoCheckoutIpnHelper.php';
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
				echo '<input type="hidden" name="billing_first_name" value="' . esc_attr( $order->get_billing_first_name() ) . '" />';
				echo '<input type="hidden" name="billing_last_name" value="' . esc_attr( $order->get_billing_last_name() ) . '" />';
				echo '<input type="hidden" name="billing_address_1" value="' . esc_attr( $order->get_billing_address_1() ) . '" />';
				echo '<input type="hidden" name="billing_address_2" value="' . esc_attr( $order->get_billing_address_2() ) . '" />';
				echo '<input type="hidden" name="billing_city" value="' . esc_attr( $order->get_billing_city() ) . '" />';
				echo '<input type="hidden" name="billing_state" value="' . esc_attr( $order->get_billing_state() ) . '" />';
				echo '<input type="hidden" name="billing_postcode" value="' . esc_attr( $order->get_billing_postcode() ) . '" />';
				echo '<input type="hidden" name="billing_phone" value="' . esc_attr( $order->get_billing_phone() ) . '" />';
				echo '<input type="hidden" name="billing_email" value="' . esc_attr( $order->get_billing_email() ) . '" />';
				echo '<input type="hidden" name="billing_company" value="' . esc_attr( $order->get_billing_company() ) . '" />';
			}
		}
	}

	/**
	 * @param $methods
	 *
	 * @return array
	 */
	function add_twocheckout_gateway_api( $methods ) {
		$methods[] = 'WC_Gateway_Twocheckout';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_twocheckout_gateway_api' );
}
