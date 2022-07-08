<?php

/**
 * User
 */
class User
{	

	private $CON;
	const DEFAULT_AVATAR_NAME = "default_avatar.png";

	function __construct($CON) {
		$this->CON = $CON;
	}	

	public function login($username, $password) {

		require '../classes/login.php';
		$Login = new Login($this->CON, $username, $password);

		require '../classes/session.php';
		$Session = new Session($this->CON, $Login->user["ID"]);

		$token = $Session->start();

		if ($token) { $Login->user["token"] = $token;}			

		return ($Login->user && $token) ? ($Login->user) : (false);
	}

	public function logout($token, $user_id) {

		$con = $this->CON;

		$query_string = "DELETE FROM sessions WHERE token = ? AND user_id = ?";
		$delete_session = $con->prepare($query_string);
		$delete_session->bind_param('si', $token, $user_id);
		$delete_session->execute();

		$query_string = "SELECT ID FROM sessions WHERE token = ? AND user_id = ?";

		$get_session = $con->prepare($query_string);
		$get_session->bind_param('si', $token, $user);
		$get_session->execute();
		$result = $get_session->get_result();
		$session_exists = $result->fetch_assoc();

		return (!$session_exists) ? (true) : (false);	
	}

	public function register($user_details) {
		
		require '../classes/registration.php';
		$registration = new Registration($this->CON, $user_details);
		$user = $registration->create_user();

		require '../classes/session.php';
		$Session = new Session($this->CON, $user["ID"]);
		$token = $Session->start();
		$user["token"] = $token;			

		if ($user["type"] == "waiter") {
			$this->create_wallet($user["ID"]);
		}		

		return ($user && $token) ? ($user) : (false);
	}

	public function upload_thumbnail() {

		$thumbnail = array();

		if ($_FILES["thumbnail"]) {
			if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], "/var/www/html/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']))) {
				$thumbnail["path"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']);
				$thumbnail["name"] = str_replace(" ", "_", $_FILES['thumbnail']['name']);
			} else {
				$thumbnail = false;
			}
		}else {
			$thumbnail["path"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . self::DEFAULT_AVATAR_NAME;
			$thumbnail["name"] = self::DEFAULT_AVATAR_NAME;
		}

		return $thumbnail["name"];
	}	

	public function update_details($details, $thumbnail) {

		$con = $this->CON;

		if ($thumbnail) {
			$query_string = "UPDATE users SET first_name = ?, last_name = ?, address = ?, city = ?, country = ?, postal_code = ?, thumbnail_path = ? WHERE ID = ?";
			$update_user_details = $con->prepare($query_string);
			$update_user_details->bind_param('sssssisi', $details->firstName, $details->lastName, $details->address, $details->city, $details->country, $details->zipCode, $thumbnail, $details->ID);
		}else {
			$query_string = "UPDATE users SET first_name = ?, last_name = ?, address = ?, city = ?, country = ?, postal_code = ? WHERE ID = ?";
			$update_user_details = $con->prepare($query_string);
			$update_user_details->bind_param('ssssssi', $details->firstName, $details->lastName, $details->address, $details->city, $details->country, $details->zipCode, $details->ID);
		}

		return $update_user_details->execute();	
	}		

	public function basic_details($user_id) {

		$con = $this->CON;

		$query_string = "SELECT ID, type, username as userName, email, first_name as firstName, last_name as lastName, address, country, city, postal_code as postalCode, thumbnail_path as thumbnailPath FROM users WHERE ID = " . $user_id;
		$get_user = $con->prepare($query_string);
		$get_user->bind_param('i', $user_id);
		$get_user->execute();
		$result = $get_user->get_result();
		$user = $result->fetch_assoc();		

		$user["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $user["thumbnailPath"];

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

	public function add_rating($waiter_id, $rating) {

		$con = $this->CON;

		$col_name = $rating . "_star";
		$query_string = "SELECT " . $col_name . " FROM waiter_ratings WHERE waiter_id = ?";

		$get_ratings = $con->prepare($query_string);
		$get_ratings->bind_param('s', $waiter_id);

		$get_ratings->execute();
		$result = $get_ratings->get_result();
		$ratings = $result->fetch_assoc();

		if ($ratings) { // if already has ratings
			$new_rating = $ratings[$col_name] + 1;
			$query_string = "UPDATE waiter_ratings SET " . $col_name . " = ? WHERE waiter_id = ?";

			$insert_rating = $con->prepare($query_string);
			$insert_rating->bind_param('ss', $new_rating, $waiter_id);
			$result = $insert_rating->execute();
		}else {
			$query_string = "INSERT INTO waiter_ratings (ID, waiter_id, 1_star, 2_star, 3_star, 4_star, 5_star) VALUES (DEFAULT, ?, '0', '0', '0', '0', '0')";

			$insert_ratings = $con->prepare($query_string);
			$insert_ratings->bind_param('i', $waiter_id);
			$insert_ratings->execute();		

			$query_string = "UPDATE waiter_ratings SET " . $col_name . " = 1 WHERE waiter_id = ?";

			$update_ratings = $con->prepare($query_string);
			$update_ratings->bind_param('i', $waiter_id);
			$update_ratings->execute();				
		}

		return $result;
	}	

	public function stats($user_id, $user_type) {

		//CHECK SESSION, IMPORTANT
		$transactions = array();
		$con = $this->CON;

		if ($user_type == "waiter") {
			$id_type = "waiter_id";
		}elseif ($user_type == "user") {
			$id_type = "user_id";
		}

		$query_string = "SELECT amount, timestamp FROM transactions WHERE " . $id_type . " = ? ORDER BY timestamp ASC";
		$get_transactions = $con->prepare($query_string);
		$get_transactions->bind_param('i', $user_id);
		$get_transactions->execute();
		$result = $get_transactions->get_result();

		$transactions["basicStatsData"] = mysqli_fetch_all($result, MYSQLI_ASSOC);

		$months_passed = array();

		for($i=0;$i<=date('n');$i++) { // get all months that have passed and the current month
    		$months_passed[] = $i;
		}

		unset($months_passed[0]);

		$ctr = 0;
		foreach ($months_passed as $month) {
			$query_string = "SELECT SUM(amount) as total FROM transactions WHERE MONTH(FROM_UNIXTIME(timestamp)) = " . $month . " AND YEAR(FROM_UNIXTIME(timestamp)) = " . date("Y") . " AND user_id = " . $user_id;
			$month_amount = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			switch ($month) {
				case 01:
					$month = "Jan";
					break;
				case 02:
					$month = "Feb";
					break;
				case 03:
					$month = "Mar";
					break;
				case 04:
					$month = "Apr";
					break;
				case 05:
					$month = "Maj";
					break;
				case 06:
					$month = "Jun";
					break;																														
				case 07:
					$month = "Jul";
					break;
				case '08':
					$month = "Avg";
					break;
				case '09':
					$month = "Sep";
					break;
				case '10':
					$month = "Okt";
					break;
				case '11':
					$month = "Nov";
					break;
				case '12':
					$month = "Dec";
					break;							
			}

			$transactions["chartData"][$ctr]["name"] = $month;
			$transactions["chartData"][$ctr]["uv"] = (int)$month_amount["total"];

			$ctr++;
		}

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

	public function add_transaction($user_id, $waiter_id, $amount) {

		$con = $this->CON;

		$query_string = "INSERT INTO transactions (ID, user_id, waiter_id, establishment_id, wspay_id, amount, timestamp) VALUES (DEFAULT, ?, ?, 0, 0, ?, " . time() . ")";

		$insert_transaction = $con->prepare($query_string);
		$insert_transaction->bind_param('sss', $user_id, $waiter_id, $amount);

		return $insert_transaction->execute();
	}

	public function get_transactions($user_id, $user_type) {

		$con = $this->CON;

		$query_string = ($user_type == "waiter") ? ("SELECT * FROM transactions WHERE waiter_id = ? ORDER BY timestamp DESC") : ("SELECT * FROM transactions WHERE user_id = ? ORDER BY timestamp DESC");
		$get_transactions = $con->prepare($query_string);
		$get_transactions->bind_param('i', $user_id);
		$get_transactions->execute();
		$result = $get_transactions->get_result();

		$raw_transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);

		if (!$raw_transactions) {
			return false;
		}

		$transactions = array();
		foreach ($raw_transactions as $key => $transaction) {
			$query_string = ($user_type == "waiter") ? ("SELECT ID, first_name as firstName, last_name as lastName, thumbnail_path as thumbnailPath FROM users WHERE ID = " . $transaction["user_id"]) : ("SELECT ID, first_name as firstName, last_name as lastName, thumbnail_path as thumbnailPath FROM users WHERE ID = " . $transaction["waiter_id"]);
			$user = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			$transactions[$key]["amount"] = $transaction["amount"];
			$transactions[$key]["date"] = date('d/m/Y', $transaction["timestamp"]);
			$transactions[$key]["timestamp"] = $transaction["timestamp"];
			$transactions[$key]["data"] = $user;
			
			if ($user["thumbnailPath"]) {
				$transactions[$key]["data"]["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $user["thumbnailPath"];	
			}else {
				$transactions[$key]["data"]["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/default_avatar.png";	
			}
		}

		return $transactions;		

	}	

	public function id_by_numeric_code($code) {

		$con = $this->CON;

		$query_string = "SELECT waiter_id as ID FROM codes WHERE code = " . $code;
		$waiter = mysqli_fetch_assoc(mysqli_query($con, $query_string));

		return $waiter["ID"];
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

	public function email_exists($email) {

		$con = $this->CON;	

		$query_string = "SELECT ID FROM users WHERE email = ?";
		$get_user = $con->prepare($query_string);
		$get_user->bind_param('s', $email);
		$get_user->execute();
		$result = $get_user->get_result();

		$user = $result->fetch_assoc();

		return ($user["ID"]) ? ($user["ID"]) : (false);
	}

	private function generate_recovery_token($user_id) {

		$length = 78;
		$token = bin2hex(random_bytes($length));

		$con = $this->CON;
		$query_string = "INSERT INTO password_recovery_codes (ID, user_id, code) VALUES (DEFAULT, " . $user_id . ", '" . $token . "')";
		$con->query($query_string);		

		return $token;
	}

	public function send_password_recovery_email($email, $user_id) {

		$token = $this->generate_recovery_token($user_id);

		if ($token) {
			require '../emails/password_recovery.php';
			password_recovery_email($email, $token);
			//TODO: DELETE RECOVERY CODE
		}
	}

	public function change_password($password, $token) {
		$con = $this->CON;

		$user_id = $this->check_recovery_code($password, $token);

		if ($user_id) {
			$password = $this::hash_password($password);

			$query_string = "UPDATE users SET password = ? WHERE ID = " . $user_id;

			$change_password = $con->prepare($query_string);
			$change_password->bind_param('s', $password);

			return ($change_password->execute()) ? (true) : (false);
		}else{
			return false;
		}
	}

	private function check_recovery_code($password, $token) {
		$con = $this->CON;	

		$query_string = "SELECT ID, code, user_id FROM password_recovery_codes WHERE code = ?";
		$get_token = $con->prepare($query_string);
		$get_token->bind_param('s', $token);
		$get_token->execute();
		$result = $get_token->get_result();

		$token_info = $result->fetch_assoc();

		if ($token_info) {
			$query_string = "DELETE FROM password_recovery_codes WHERE ID = " . $token_info["ID"];
			$con->query($query_string);	
		}

		return ($token_info) ? ($token_info["user_id"]) : (false);
	}		
}