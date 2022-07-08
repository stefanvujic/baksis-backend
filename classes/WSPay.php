<?php

/**
 * WSPay
 */
class WSPay
{

	private $CON;
	public $amount;

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

		$id = time() . $this->amount . random_int(1, 5000);

		while(!$this->wspay_id_exist($id)) {
			$query_string = "SELECT ID FROM transactions WHERE wspay_id = " .  $id;
			$duplicate_id = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			$id = time() . $this->amount . random_int(1, 5000);
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

	public function create_signiture() {

		$raw_string = self::SHOP_ID . self::SECRET_KEY . $this->generate_wspay_id() . self::SECRET_KEY . $this->amount . self::SECRET_KEY;
		
		return hash("sha512", $raw_string);

	} 
}