<?php

class WC_Twocheckout_Convert_Plus_Ipn_Helper {

	/**
	 * Ipn Constants
	 *
	 * Not all are used, however they should be left here
	 * for future reference
	 */
	const ORDER_CREATED = 'ORDER_CREATED';
	const FRAUD_STATUS_CHANGED = 'FRAUD_STATUS_CHANGED';
	const INVOICE_STATUS_CHANGED = 'INVOICE_STATUS_CHANGED';
	const REFUND_ISSUED = 'REFUND_ISSUED';
	const ORDER_STATUS_PENDING = 'PENDING';
	const ORDER_STATUS_PAYMENT_AUTHORIZED = 'PAYMENT_AUTHORIZED';
	const ORDER_STATUS_SUSPECT = 'SUSPECT';
	const ORDER_STATUS_INVALID = 'INVALID';
	const ORDER_STATUS_COMPLETE = 'COMPLETE';
	const ORDER_STATUS_REFUND = 'REFUND';
	const ORDER_STATUS_REVERSED = 'REVERSED';
	const WC_ORDER_STATUS_PENDING = 'PENDING';
	const WC_ORDER_STATUS_PROCESSING = 'PROCESSING';
	const WC_ORDER_STATUS_COMPLETE = 'COMPLETED';
	const WC_ORDER_STATUS_REFUND = 'REFUNDED';

	const ORDER_STATUS_PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
	const ORDER_STATUS_CANCELED = 'CANCELED';

	const FRAUD_STATUS_REVIEW = 'UNDER REVIEW';
	const FRAUD_STATUS_PENDING = 'PENDING';
	const PAYMENT_METHOD = 'tco_checkout';

	const FRAUD_STATUS_APPROVED = 'APPROVED';
	const FRAUD_STATUS_DENIED = 'DENIED';

	const ORDER_STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
	const ORDER_STATUS_PURCHASE_PENDING = 'PURCHASE_PENDING';

	const CURLOPT_URL = "https://secure.2checkout.com/checkout/api/encrypt/generate/signature";
	const TCO_ORDER_REFERENCE = '__2co_order_number';

	protected $wc_order;
	protected $request_params;
	protected $secret_key;
	/**
	 * @var WC_Logger
	 */
	public static $log_enabled = false;

	/**
	 * Two_Checkout_Ipn_Helper constructor.
	 *
	 * @param array $request_params
	 * @param string $secret_key
	 * @param $debug
	 */
	public function __construct( array $request_params, string $secret_key, bool $debug, WC_Order $order ) {
		$this->request_params = $request_params;
		$this->secret_key     = $secret_key;
		self::$log_enabled    = $debug;
		$this->wc_order       = $order;
	}


	/**
	 * @param $merchant_id
	 * @param $buy_link_secret_word
	 * @param $payload
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_signature( $merchant_id, $buy_link_secret_word ) {
		$jwtToken = $this->generate_JWT_token(
			$merchant_id,
			time(),
			time() + 3600,
			$buy_link_secret_word
		);

		$curl = curl_init();

		curl_setopt_array( $curl, [
			CURLOPT_URL            => self::CURLOPT_URL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => json_encode( $this->request_params ),
			CURLOPT_HTTPHEADER     => [
				'content-type: application/json',
				'cache-control: no-cache',
				'merchant-token: ' . $jwtToken,
			],
		] );
		$response = curl_exec( $curl );
		$err      = curl_error( $curl );
		curl_close( $curl );
		$log = new WC_Logger();

		if ( $err ) {
			wc_add_notice( 'Error when trying to place order', $notice_type = 'error' );
			$log->add( 'twocheckout-convert-plus',
				sprintf( 'Unable to get proper response from signature generation API. In file %s at line %s', __FILE__,
					__LINE__ ) );
		}

		$response = json_decode( $response, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! isset( $response['signature'] ) ) {
			wc_add_notice( 'Error when trying to place order', $notice_type = 'error' );
			$log->add( 'twocheckout-convert-plus',
				sprintf( 'Unable to get proper response from signature generation API. In file %s at line %s', __FILE__,
					__LINE__ ) );
		}

		return $response['signature'];

	}

	/**
	 * @param $sub
	 * @param $iat
	 * @param $exp
	 * @param $buy_link_secret_word
	 *
	 * @return string
	 */
	public function generate_JWT_token( $sub, $iat, $exp, $buy_link_secret_word ) {
		$header    = $this->encode( json_encode( [ 'alg' => 'HS512', 'typ' => 'JWT' ] ) );
		$payload   = $this->encode( json_encode( [ 'sub' => $sub, 'iat' => $iat, 'exp' => $exp ] ) );
		$signature = $this->encode(
			hash_hmac( 'sha512', "$header.$payload", $buy_link_secret_word, true )
		);

		return implode( '.', [
			$header,
			$payload,
			$signature
		] );
	}

	/**
	 * @param $data
	 *
	 * @return string|string[]
	 */
	private function encode( $data ) {
		return str_replace( '=', '', strtr( base64_encode( $data ), '+/', '-_' ) );
	}

	/**
	 * Validate Ipn request
	 * @return bool
	 */
	public function is_ipn_response_valid() {
		$result       = '';
		$receivedHash = $this->request_params['HASH'];
		foreach ( $this->request_params as $key => $val ) {

			if ( $key != "HASH" ) {
				if ( is_array( $val ) ) {
					$result .= $this->array_expand( $val );
				} else {
					$size   = strlen( stripslashes( $val ) );
					$result .= $size . stripslashes( $val );
				}
			}
		}

		if ( isset( $this->request_params['REFNO'] ) && ! empty( $this->request_params['REFNO'] ) ) {
			$calcHash = $this->generate_hash( $this->secret_key, $result );
			if ( $receivedHash === $calcHash ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * generates hmac
	 *
	 * @param string $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function generate_hash( $key, $data ) {
		$b = 64; // byte length for md5
		if ( strlen( $key ) > $b ) {
			$key = pack( "H*", md5( $key ) );
		}

		$key    = str_pad( $key, $b, chr( 0x00 ) );
		$ipad   = str_pad( '', $b, chr( 0x36 ) );
		$opad   = str_pad( '', $b, chr( 0x5c ) );
		$k_ipad = $key ^ $ipad;
		$k_opad = $key ^ $opad;

		return md5( $k_opad . pack( "H*", md5( $k_ipad . $data ) ) );
	}

	/**
	 * @param $array
	 *
	 * @return string
	 */
	private function array_expand( $array ) {
		$retval = '';
		foreach ( $array as $key => $value ) {
			$size   = strlen( stripslashes( $value ) );
			$retval .= $size . stripslashes( $value );
		}

		return $retval;
	}

	/**
	 * @return string
	 */
	public function process_ipn() {
		try {
			if ( ! isset( $this->request_params['REFNO'] ) && empty( $this->request_params['REFNO'] ) ) {
				self::log( 'Cannot identify order: "%s".', $this->request_params['REFNOEXT'] );

				return;
			}
			$orderId              = intval( $this->request_params['REFNOEXT'] );
			$params['REFNOEXT_D'] = ! empty( $orderId ) ? $orderId : 0;

			if ( $this->wc_order->get_id() ) {
				$this->_process_fraud();
				if ( ! $this->_is_fraud() ) {
					$this->_processorder_status();
				}
			}
		} catch ( Exception $ex ) {
			self::log( 'Exception processing IPN: ' . $ex->getMessage() );
		}
		echo $this->_calculate_ipn_response();
		exit();
	}

	/**
	 * @return void
	 */
	protected function _process_fraud() {

		if ( isset( $this->request_params['FRAUD_STATUS'] ) ) {
			switch ( trim( $this->request_params['FRAUD_STATUS'] ) ) {
				case self::FRAUD_STATUS_DENIED:
				case self::ORDER_STATUS_INVALID:
					$this->wc_order->update_status( 'failed' );
					$this->wc_order->add_order_note( __( "Order status changed to failed" ), false, false );

					break;

				case self::FRAUD_STATUS_APPROVED:
					if ( ! $this->_is_order_processing() && ! $this->_is_order_completed() && ! $this->_is_order_refunded()) {
						$this->wc_order->update_status( 'processing' );
						$this->wc_order->add_order_note( __( "Order status changed to processing" ), false, false );
					}
					break;
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function _is_fraud() {
		return ( isset( $this->request_params['FRAUD_STATUS'] )
		         && $this->request_params['FRAUD_STATUS'] === self::FRAUD_STATUS_DENIED );
	}

	/**
	 * @return bool
	 */
	protected function _is_order_pending() {
		return strtoupper($this->wc_order->get_status()) == self::WC_ORDER_STATUS_PENDING;
	}

	/**
	 * @return bool
	 */
	protected function _is_order_processing() {
		return strtoupper($this->wc_order->get_status()) == self::WC_ORDER_STATUS_PROCESSING;
	}

	/**
	 * @return bool
	 */
	protected function _is_order_completed() {
		return strtoupper($this->wc_order->get_status()) == self::WC_ORDER_STATUS_COMPLETE;
	}

	/**
	 * @return bool
	 */
	protected function _is_order_refunded() {
		return strtoupper($this->wc_order->get_status()) == self::WC_ORDER_STATUS_REFUND;
	}

	/**
	 * @return string
	 */
	private function _calculate_ipn_response() {
		$resultResponse      = '';
		$ipn_params_response = [];
		// we're assuming that these always exist, if they don't then the problem is on avangate side
		$ipn_params_response['IPN_PID'][0]   = $this->request_params['IPN_PID'][0];
		$ipn_params_response['IPN_PNAME'][0] = $this->request_params['IPN_PNAME'][0];
		$ipn_params_response['IPN_DATE']     = $this->request_params['IPN_DATE'];
		$ipn_params_response['DATE']         = date( 'YmdHis' );

		foreach ( $ipn_params_response as $key => $val ) {
			$resultResponse .= $this->array_expand( (array) $val );
		}

		return sprintf(
			'<EPAYMENT>%s|%s</EPAYMENT>',
			$ipn_params_response['DATE'],
			$this->generate_hash( $this->secret_key, $resultResponse )
		);
	}

	/**
	 * @throws Exception
	 */
	protected function _processorder_status() {
		$order_status = $this->request_params['ORDERSTATUS'];
		if ( ! empty( $order_status ) ) {
			switch ( trim( $order_status ) ) {
				case self::ORDER_STATUS_PENDING:
				case self::ORDER_STATUS_PURCHASE_PENDING:
				case self::ORDER_STATUS_PENDING_APPROVAL:
				if ( ! $this->_is_order_pending() && ! $this->_is_order_completed()  && ! $this->_is_order_refunded()) {
						$this->wc_order->update_status( 'pending' );
						$this->wc_order->add_order_note( __( "Order status changed to: Pending" ), false, false );
					}
					break;


				case self::ORDER_STATUS_PAYMENT_AUTHORIZED:
					if ( ! $this->_is_order_processing() && ! $this->_is_order_completed()  && ! $this->_is_order_refunded()) {
						$this->wc_order->update_status( 'processing' );
						$this->wc_order->add_order_note( __( "Order status changed to: Processing" ), false, false );
					}
					break;

				case self::ORDER_STATUS_COMPLETE:
					if ( ! $this->_is_order_completed()  && ! $this->_is_order_refunded()) {
						$this->wc_order->update_status( 'completed' );
						$this->wc_order->payment_complete();
						$this->wc_order->add_order_note( __( '2Checkout transaction ID: ' . $this->request_params['REFNO'] ),false, false );
						$this->wc_order->update_meta_data( self::TCO_ORDER_REFERENCE, $this->request_params['REFNO'] );
						$this->wc_order->save_meta_data();
						$this->wc_order->add_order_note( __( "Order payment is completed." ), false, false );
						$this->wc_order->save();
					}
					break;

				default:
					throw new Exception( 'Cannot handle Ipn message type for this request!' );
			}
		}
	}

}
