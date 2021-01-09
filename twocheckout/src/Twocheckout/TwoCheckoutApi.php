<?php

/**
 * makes API calls with 2CO platform
 * Class TwoCheckoutApi
 * @package TwoCheckout
 */
class Two_Checkout_Api {

	const   API_URL = 'https://api.2checkout.com/rest/';
	const   API_VERSION = '6.0';

	/**
	 * used for auth with 2co api
	 * @var string
	 */
	private $seller_id;

	/**
	 * used for auth with 2co api
	 * @var string
	 */
	private $secret_key;

	/**
	 * place test order
	 * @var int
	 */
	private $test_order;


	/**
	 * @return int
	 */
	public function get_test_order() {
		return $this->test_order;
	}

	/**
	 * @param bool $test_order
	 */
	public function set_test_order( bool $test_order ) {
		$this->test_order = $test_order;
	}

	/**
	 * @return null
	 */
	public function get_seller_id() {
		return $this->seller_id;
	}

	/**
	 * @param null seller_id
	 *
	 * @return TwoCheckoutApi
	 */
	public function set_seller_id( $seller_id ) {
		$this->seller_id = $seller_id;

		return $this;
	}

	/**
	 * @return null
	 */
	public function get_secret_key() {
		return $this->secret_key;
	}

	/**
	 * @param null $secret_key
	 *
	 * @return TwoCheckoutApi
	 */
	public function set_secret_key( $secret_key ) {
		$this->secret_key = $secret_key;

		return $this;
	}

	/**
	 *  sets the header with the auth has and params
	 * @return array
	 * @throws Exception
	 */
	public function get_headers() {
		if ( ! $this->seller_id || ! $this->secret_key ) {
			throw new Exception( 'Merchandiser needs a valid 2Checkout SellerId and SecretKey to authenticate!' );
		}
		$gmt_date = gmdate( 'Y-m-d H:i:s' );
		$string   = strlen( $this->seller_id ) . $this->seller_id . strlen( $gmt_date ) . $gmt_date;
		$hash     = hash_hmac( 'md5', $string, $this->secret_key );

		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Accept: application/json';
		$headers[] = 'X-Avangate-Authentication: code="' . $this->seller_id . '" date="' . $gmt_date . '" hash="' . $hash . '"';;

		return $headers;
	}

	/**
	 * @param string $endpoint
	 * @param array $params
	 * @param string $method
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function call( string $endpoint, array $params, $method = 'POST' ) {
		// if endpoint does not starts or end with a '/' we add it, as the API needs it
		if ( $endpoint[0] !== '/' ) {
			$endpoint = '/' . $endpoint;
		}
		if ( $endpoint[ - 1 ] !== '/' ) {
			$endpoint = $endpoint . '/';
		}

		try {
			$url = self::API_URL . self::API_VERSION . $endpoint;
			$ch  = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->get_headers() );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			if ( $method === 'POST' ) {
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $params, JSON_UNESCAPED_UNICODE ) );
			}
			$response = curl_exec( $ch );

			if ( $response === false ) {
				exit( curl_error( $ch ) );
			}
			curl_close( $ch );

			return json_decode( $response, true );
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}
}
