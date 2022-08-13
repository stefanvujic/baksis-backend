<?php

/**
 * Register
 */
class Registration
{
	private $user;
	const DEFAULT_AVATAR_NAME = "default_avatar.png";

	function __construct($CON, $user) {
		$this->user = $user;
		$this->CON = $CON;
	}

	private function email_exists() {

		$con = $this->CON;
		$query_string = "SELECT ID FROM users WHERE email = ?";
		$get_user = $con->prepare($query_string);
		$get_user->bind_param('s', $this->user->email);
		$get_user->execute();
		$result = $get_user->get_result();
		$email_exists = $result->fetch_assoc();

		return ($email_exists) ? (true) : (false);		
	}

	private function username_exists() {

		$con = $this->CON;
		$query_string = "SELECT ID FROM users WHERE username = ?";
		$get_user = $con->prepare($query_string);
		$get_user->bind_param('s', $this->user->userName);
		$get_user->execute();
		$result = $get_user->get_result();
		$username_exists = $result->fetch_assoc();

		return ($username_exists) ? (true) : (false);			
		
	}

	public function create_user() {

		if (!$this->email_exists() && !$this->username_exists()) {

			($this->user->userType == "waiter") ? ($acc_numb = $this->user->accNumb) : ($acc_numb = "0");

			$con = $this->CON;
			$query_string = "INSERT INTO users (ID, username, email, first_name, last_name, password, address, city, country, postal_code, type, thumbnail_path, account_number) VALUES (DEFAULT, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$insert_user = $con->prepare($query_string);
			$password = User::hash_password($this->user->password);
			$thumbnail = $this->upload_thumbnail();
			$insert_user->bind_param('ssssssssssss', $this->user->username, $this->user->email, $this->user->name, $this->user->surname, $password, $this->user->address, $this->user->city, $this->user->country, $this->user->zipCode, $this->user->userType, $thumbnail["name"], $acc_numb);
			$insert_user->execute();

			$user = $this->get_user($this->user->username, $password);

			if ($user) {
				$user["thumbnailPath"] = $thumbnail["path"];
				if ($user["type"] == "waiter") {
					$numeric_code = $this->generate_numeric_code();
					$qr_code_url = User::generate_qr_code_path($numeric_code);
					$user["qrCodeUrl"] = $qr_code_url;

					require '../emails/waiter_register.php';
					waiter_register_email($this->user["email"], $qr_code_url);
				}				
			}

			return $user;
		}
	}

	private function get_user($username, $password) {

		$query_string = "SELECT ID, username as userName, email, first_name as firstName, last_name as lastName, address, city, country, postal_code as postalCode, type, thumbnail_path as thumbnailPath, account_number as accountNumber FROM users WHERE password = ? AND username = ?";

		$con = $this->CON;
		$get_user = $con->prepare($query_string);
		$get_user->bind_param('ss', $password, $username);
		$get_user->execute();
		$result = $get_user->get_result();
		$user = $result->fetch_assoc();

		$this->user = $user;

		return $this->user;	
	}	

	private function upload_thumbnail() {

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

		return $thumbnail;
	}

	private function generate_numeric_code() {

		$con = $this->CON;
		$query_string = "SELECT MAX( code ) AS max FROM codes";
		$result = $con->query($query_string);
		$codes = $result->fetch_assoc();

		$waiter_code = $codes["max"] + 1;
		$query_string = "INSERT INTO codes (ID, waiter_id, code) VALUES (DEFAULT, ".$this->user["ID"].", ".$waiter_code.")";
		$con->query($query_string);

		return $waiter_code;			
	}
}