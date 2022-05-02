<?php

/**
 * Login
 */
class Login
{
	private $CON;

	private $username;
	private $password;
	public $user;

	function __construct($CON, $username, $password) {
		$this->CON = $CON;
		$this->username = $username;
		$this->password = $password;
		$this->user = $this->get_user();
	}

	private function get_user() {

		$con = $this->CON;
		$query = $con->prepare("SELECT ID, username as userName, email, first_name as firstName, last_name as lastName, address, city, country, postal_code as postalCode, type, thumbnail_path as thumbnailPath FROM users WHERE password = ? AND username = ?");

		$password = User::hash_password($this->password);
		$query->bind_param('ss', $password, $this->username);
		$query->execute();

		$this->user = $query->get_result();
		$this->user = $this->user->fetch_assoc();

		$this->user["thumbnailPath"] = $this->generate_thumbnail_path();

		if ($this->user["type"] == "waiter") {

			$User = new User($con);
			$this->user["rating"] = $User->rating($this->user["ID"]);

			$numeric_code = $User->get_numeric_code($this->user["ID"]);
			$this->user["qrCodeUrl"] = User::generate_qr_code_path($numeric_code);
		}

		return $this->user;
	}

	private function generate_thumbnail_path() {
		return "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $this->user["thumbnailPath"];
	}
}
