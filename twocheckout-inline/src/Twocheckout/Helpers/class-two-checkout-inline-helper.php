<?php

/**
 * Class class-two-checkout-inline-helper
 */
class Two_Checkout_Inline_Helper {

	const CURLOPT_URL = "https://secure.2checkout.com/checkout/api/encrypt/generate/signature";
	/**
	 * @param $merchant_id
	 * @param $buy_link_secret_word
	 * @param $payload
	 * @return mixed
	 * @throws Exception
	 */
	public function get_inline_signature($merchant_id, $buy_link_secret_word, $payload)
	{
		$jwtToken = $this->generate_JWT_token(
			$merchant_id,
			time(),
			time() + 3600,
			$buy_link_secret_word
		);

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL            => self::CURLOPT_URL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => json_encode($payload),
			CURLOPT_HTTPHEADER     => [
				'content-type: application/json',
				'cache-control: no-cache',
				'merchant-token: ' . $jwtToken,
			],
		]);
		$response = curl_exec($curl);
		$err      = curl_error($curl);
		curl_close($curl);
		$log = new WC_Logger();

		if ($err) {
			wc_add_notice('Error when trying to place order', $notice_type = 'error' );
			$log->add('twocheckout-inline',sprintf('Unable to get proper response from signature generation API. In file %s at line %s', __FILE__, __LINE__));
		}

		$response = json_decode($response, true);
		if (JSON_ERROR_NONE !== json_last_error() || !isset($response['signature'])) {
			wc_add_notice('Error when trying to place order', $notice_type = 'error' );
			$log->add('twocheckout-inline',sprintf('Unable to get proper response from signature generation API. In file %s at line %s', __FILE__, __LINE__));
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
	public function generate_JWT_token($sub, $iat, $exp, $buy_link_secret_word)
	{
		$header = $this->encode(json_encode(['alg' => 'HS512', 'typ' => 'JWT']));
		$payload = $this->encode(json_encode(['sub' => $sub, 'iat' => $iat, 'exp' => $exp]));
		$signature = $this->encode(
			hash_hmac('sha512', "$header.$payload", $buy_link_secret_word, true)
		);

		return implode('.', [
			$header,
			$payload,
			$signature
		]);
	}

	/**
	 * @param $data
	 *
	 * @return string|string[]
	 */
	private function encode($data)
	{
		return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
	}

}