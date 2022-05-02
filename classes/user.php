<?php

/**
 * User
 */
class User
{	

	private $CON;

	function __construct($CON) {
		$this->CON = $CON;
	}	

	public function login($username, $password) {

		$Login = new Login($this->CON, $username, $password);

		$Session = new Session($this->CON, $Login->user["ID"]);

		$token = $Session->start();

		if ($token) { $Login->user["token"] = $token;}			

		return ($Login->user && $token) ? ($Login->user) : (false);
	}

	public function register($user_details) {
		
		require 'classes/registration.php';
		$registration = new Registration($this->CON, $user_details);
		$user = $registration->create_user();

		if ($user["type"] == "waiter") {
			$this->create_wallet($user["ID"]);
		}

		return $user;
	}

	public function rating($user_id) {

		$query_string = "SELECT * FROM waiter_ratings WHERE waiter_id = ?";

		$con = $this->CON;
		$get_ratings = $con->prepare($query_string);
		$get_ratings->bind_param('s', $user_id);

		$get_ratings->execute();

		$result = $get_ratings->get_result();
		$ratings = $result->fetch_assoc();

		if ($ratings) {
			$total_reviews = $ratings["1_star"] + $ratings["2_star"] + $ratings["3_star"] + $ratings["4_star"] + $ratings["5_star"];
			if ($total_reviews !== 0) {
				$rating = (1*$ratings["1_star"] + 2*$ratings["2_star"] + 3*$ratings["3_star"] + 4*$ratings["4_star"] + 5*$ratings["5_star"]) / ($total_reviews);
				$rating = round($rating);
			}else {
				$rating = 0;
			}
		}else {
			$rating = 0;
		}

		return $rating;
	}

	public function stats($user_id, $user_type) {

		//CHECK SESSION, IMPORTANT

		$con = $this->CON;

		if ($user_type == "waiter") {
			$id_type = "waiter_id";
		}elseif ($user_type == "user") {
			$id_type = "user_id";
		}

		$query_string = "SELECT amount, timestamp FROM transactions WHERE " . $id_type . " = ? ORDER BY timestamp DESC";
		$get_transactions = $con->prepare($query_string);
		$get_transactions->bind_param('i', $user_id);
		$get_transactions->execute();
		$result = $get_transactions->get_result();

		$transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);		

		return $transactions;
	}	

	private function create_wallet($user_id) {

		$con = $this->CON;	

		$query_string = "INSERT INTO wallets (ID, user_id, amount) VALUES (DEFAULT, " . $user_id . ", 0)";
		$is_created = $con->query($query_string);		

		return $is_created;
	}

	public function wallet($user_id) {

		$con = $this->CON;

		$query_string = "SELECT amount FROM wallets WHERE user_id = " . $user_id;
		$wallet = mysqli_fetch_assoc(mysqli_query($con, $query_string));

		return $wallet["amount"];
	}	

	public function add_funds($user_id, $amount_to_add) {

		$con = $this->CON;

		$wallet_amount = $this->wallet($user_id);
		$wallet = $wallet_amount + $amount_to_add;

		$query_string = "UPDATE wallets SET amount = " . $wallet . " WHERE user_id = " . $user_id . "";
		$is_inserted = $con->query($query_string);	

		return $is_inserted;
	}		

	public function get_numeric_code($user_id) {
		$con = $this->CON;
		$query_string = "SELECT code FROM codes WHERE waiter_id = " . $user_id;
		$waiter_code = mysqli_fetch_assoc(mysqli_query($con, $query_string));

		return $waiter_code["code"];
	}

	public static function generate_qr_code_path($numeric_code) {
		$url = urldecode("https://baksis.rs/check-code?code=" . $numeric_code);
		$url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url . "&choe=UTF-8%22%20title=baksisCode";

		return $url;
	}

	public static function hash_password($password) {
		return htmlentities(hash('sha256', $password), ENT_QUOTES, 'UTF-8');
	}	
}