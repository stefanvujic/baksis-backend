<?php

/**
 * WSPay
 */
class WSPay
{

	private $CON;
	public $amount;
	public $WSPay_auth_id;
	public $WSPayId;
	public $WSPay_auth_token;

	const SHOP_ID = "BAKSISRS";
	const SECRET_KEY = "a82dc6cb81ed4D";

	const TOKENIZATION_SHOP_ID = "BAKSISRST";
	const TOKENIZATION_SECRET_KEY = "0a914ec5e0934R";

	function __construct($CON) {
		$this->CON = $CON;
	}

	public function set_amount($amount) {
		$this->amount = $amount;
	} 

	private function generate_wspay_id() {

		$con = $this->CON;

		$id = substr(time(), -4) . $this->amount;

		while(!$this->wspay_id_exist($id)) {
			$query_string = "SELECT ID FROM transactions WHERE wspay_id = " .  $id;
			$duplicate_id = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			$id = time() . $this->amount . random_int(1, 500);
		}

		return $id;
	}

	private function wspay_id_exist($id) {

		$con = $this->CON;
		$query_string = "SELECT ID FROM transactions WHERE wspay_id = " .  $id;
		$duplicate_id = mysqli_fetch_assoc(mysqli_query($con, $query_string));
		$duplicate_id = $duplicate_id["ID"];

		return ($duplicate_id) ? (false) : (true);
	}

	public function create_form_signature() {

		$raw_string = self::SHOP_ID . self::SECRET_KEY . $this->WSPayId . self::SECRET_KEY . $this->amount . self::SECRET_KEY;

		return hash("sha512", $raw_string);

	}

	private function generate_authorization_signature() {

		$raw_string = self::SHOP_ID . self::SECRET_KEY . $this->WSPayId . self::SECRET_KEY . self::SHOP_ID . $this->WSPayId;

		return hash("sha512", $raw_string);
	}

	public function authorization_info() {

		$this->WSPayId = $this->generate_wspay_id();
		$this->WSPay_auth_id = $this->generate_authorization_signature();

		$headers = array(
			"Content-type: application/json",
		);

		$data = [		
			'Version' => "2.0", 
			'ShopID' => "BAKSISRS", 
			'ShoppingCartID' => $this->WSPayId, 
			'Amount' => $this->amount, 
			'Duration' => "30",					
			'Signature' => $this->WSPay_auth_id  

		];

		$url = 'https://test.wspay.biz/api/services/authorizationAnnounce';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response);

		$this->WSPay_auth_token = $response->AuthorizationToken;

		return $response;

	}

	public function check_transaction($WSPayId, $signature) {
		$headers = array(
			"Content-type: application/json",
		);

		$data = [		
			'Version' => "2.0", 
			'ShopID' => "BAKSISRS", 
			'ShoppingCartID' => $WSPayId, 
			'Amount' => $this->amount, 			
			'Signature' => hash("sha512", self::SHOP_ID . self::SECRET_KEY . $WSPayId . self::SECRET_KEY . self::SHOP_ID . $WSPayId)

		];

		$url = 'https://test.wspay.biz/api/services/statusCheck';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response);

		return (int)$response->Authorized;
	}

	public function complete_transaction($WSPayId, $approval_code, $stan) {
		$headers = array(
			"Content-type: application/json",
		);

		$data = [		
			'Version' => "2.0", 
			'WsPayOrderId' => $WSPayId, 
			'ShopID' => "BAKSISRS", 
			'ApprovalCode' => $approval_code, 
			'STAN' => $stan,
			'Amount' => $this->amount."00", 			
			'Signature' => hash("sha512", self::SHOP_ID . $WSPayId . self::SECRET_KEY . $stan . self::SECRET_KEY . $approval_code . self::SECRET_KEY . $this->amount."00" . self::SECRET_KEY . $WSPayId)

		];

		$url = 'https://test.wspay.biz/api/services/completion';

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response);

		return $response->ActionSuccess;
	}

}

