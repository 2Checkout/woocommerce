<?php

/**
 * Class Two_Checkout_Ipn_Helper_Api
 */
final class Two_Checkout_Ipn_Helper_Api {
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
    const ORDER_STATUS_AUTHRECEIVED = 'AUTHRECEIVED';
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

	const TCO_ORDER_REFERENCE = '__2co_order_number';
	protected $wc_order;
	protected $request_params;
	protected $secret_key;
	protected $complete_order_on_payment;

	/**
	 * @var WC_Logger
	 */
	public static $log_enabled = false;
	public static $log = false;

	/**
	 * Two_Checkout_Ipn_Helper_Api constructor.
	 *
	 * @param array $request_params
	 * @param string $secret_key
	 * @param bool $complete_order_on_payment
	 * @param $debug
	 * @param WC_Order $order
	 */
	public function __construct( array $request_params, string $secret_key, bool $complete_order_on_payment, $debug, WC_Order $order ) {
		$this->request_params            = $request_params;
		$this->secret_key                = $secret_key;
		$this->complete_order_on_payment = $complete_order_on_payment;
		self::$log_enabled               = $debug;
		$this->wc_order                  = $order;
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
		return strtoupper( $this->wc_order->get_status() ) == self::WC_ORDER_STATUS_PENDING;
	}

	/**
	 * @return bool
	 */
	protected function _is_order_processing() {
		return strtoupper( $this->wc_order->get_status() ) == self::WC_ORDER_STATUS_PROCESSING;
	}

	/**
	 * @return bool
	 */
	protected function _is_order_completed() {
		return strtoupper( $this->wc_order->get_status() ) == self::WC_ORDER_STATUS_COMPLETE;
	}

	/**
	 * @return bool
	 */
	protected function _is_order_refunded() {
		return strtoupper( $this->wc_order->get_status() ) == self::WC_ORDER_STATUS_REFUND;
	}

	/**
	 * @param $array
	 *
	 * @return string
	 */
	private function array_expand( array $array ) {
		$retval = '';
		foreach ( $array as $key => $value ) {
			$size   = strlen( stripslashes( $value ) );
			$retval .= $size . stripslashes( $value );
		}

		return $retval;
	}

	/**
	 * @return bool
	 */
	private function getCompleteOrderOnPayment() {
		return $this->complete_order_on_payment;
	}

	/**
	 * @throws Exception
	 */
	public function processorder_status(string $order_status,string $refNo) {
		if ( ! empty( $order_status ) ) {
			switch ( trim( $order_status ) ) {
				case self::ORDER_STATUS_PENDING:
				case self::ORDER_STATUS_PURCHASE_PENDING:
				case self::ORDER_STATUS_PENDING_APPROVAL:
					if ( ! $this->_is_order_pending() && ! $this->_is_order_completed() && ! $this->_is_order_refunded() ) {
						$this->wc_order->update_status( 'pending' );
						$this->wc_order->add_order_note( __( "Order status changed to: Pending" ), false, false );
					}
					break;

				case self::ORDER_STATUS_PAYMENT_AUTHORIZED:
					if ( ! $this->_is_order_pending() && ! $this->_is_order_processing() && ! $this->_is_order_completed() && ! $this->_is_order_refunded() ) {
						$this->wc_order->update_status( 'pending' );
						$this->wc_order->add_order_note( __( "Order status changed to: Pending" ), false, false );
					}
					break;

				case self::ORDER_STATUS_COMPLETE:
                case self::ORDER_STATUS_AUTHRECEIVED:
					//woocommerce style :)
					if ( ! $this->_is_order_completed() && ! $this->_is_order_refunded() ) {
						$this->wc_order->update_meta_data( self::TCO_ORDER_REFERENCE, $refNo );
						$this->wc_order->save_meta_data();
						$this->wc_order->payment_complete();
						if ( $this->getCompleteOrderOnPayment() ) {
							$this->wc_order->update_status( 'completed' );
						}
						$this->wc_order->add_order_note( __( '2Checkout transaction ID: ' . $refNo ), false, false );
						$this->wc_order->add_order_note( __( "Order payment is completed." ), false, false );
						$this->wc_order->save();
					}
					break;

				default:
					throw new Exception( 'Cannot handle Ipn message type for this request!' );
			}
		}
	}

    /**
     * @return array    [hash, algo]
     */
    protected function extractHashFromRequest():array {
        $receivedAlgo = 'sha3-256';
        $receivedHash = $this->request_params['SIGNATURE_SHA3_256'];

        if (!$receivedHash) {
            $receivedAlgo = 'sha256';
            $receivedHash = $this->request_params['SIGNATURE_SHA2_256'];
        }

        if (!$receivedHash) {
            $receivedAlgo = 'md5';
            $receivedHash = $this->request_params['HASH'];
        }

        return ['hash' => $receivedHash, 'algo' => $receivedAlgo];
    }

	/**
	 * Validate Ipn request
	 * @return bool
	 */
	public function is_ipn_response_valid() {
		$result        = '';

        $hash = $this->extractHashFromRequest();

		foreach ( $this->request_params as $key => $val ) {
			if ( !in_array($key ,["HASH", "SIGNATURE_SHA2_256", "SIGNATURE_SHA3_256"]) ) {
				if ( is_array( $val ) ) {
					$result .= $this->array_expand( $val );
				} else {
					$size   = strlen( StripSlashes( $val ) );
					$result .= $size . StripSlashes( $val );
				}
			}
		}

		if ( isset( $this->request_params['REFNO'] ) && ! empty( $this->request_params['REFNO'] ) ) {
			$calc_hash = $this->generate_hash( $this->secret_key, $result, $hash['algo'] );
			if ( $hash['hash'] === $calc_hash ) {
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
    public function generate_hash($key, $data, $receivedAlgo = 'sha3-256') {
        if ('sha3-256' === $receivedAlgo) {
            return hash_hmac($receivedAlgo, $data, $key);
        }

        $b = 64; // byte length for hash
        if (strlen($key) > $b) {
            $key = pack("H*", hash($receivedAlgo, $key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return hash($receivedAlgo, $k_opad . pack("H*", hash($receivedAlgo, $k_ipad . $data)));
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
	 * @return string
	 */
	public function process_ipn() {
        $hash = $this->extractHashFromRequest();

		try {
			if ( ! isset( $this->request_params['REFNO'] ) && empty( $this->request_params['REFNO'] ) ) {
				self::log( 'Cannot identify order: "%s".', $this->request_params['REFNOEXT'] );

				return;
			}
			$order_id                           = intval( $this->request_params['REFNOEXT'] );
			$this->request_params['REFNOEXT_D'] = ! empty( $order_id ) ? $order_id : 0;

			if ( $this->wc_order->get_id() ) {
				$this->_process_fraud();
				if ( ! $this->_is_fraud() ) {
					$this->processorder_status($this->request_params['ORDERSTATUS'],$this->request_params['REFNO']);
				}
			}
		} catch ( Exception $ex ) {
			self::log( 'Exception processing IPN: ' . $ex->getMessage() );
		}
		echo $this->_calculate_ipn_response($hash['algo']);
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
					if ( ! $this->_is_order_pending() &&  ! $this->_is_order_processing() && ! $this->_is_order_completed() && ! $this->_is_order_refunded() ) {
						$this->wc_order->update_status( 'pending' );
						$this->wc_order->add_order_note( __( "Order status changed to: Pending" ), false, false );
					}
					break;

			}
		}
	}

    /**
     * @return string
     */
    private function _calculate_ipn_response($algo='sha3-256') {
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

        if ('md5' === $algo)
            return sprintf(
                '<EPAYMENT>%s|%s</EPAYMENT>',
                $ipn_params_response['DATE'],
                $this->generate_hash($this->secret_key, $resultResponse, $algo)
            );
        else
            return sprintf(
                '<sig algo="%s" date="%s">%s</sig>',
                $algo,
                $ipn_params_response['DATE'],
                $this->generate_hash($this->secret_key, $resultResponse, $algo)
            );
    }
}
