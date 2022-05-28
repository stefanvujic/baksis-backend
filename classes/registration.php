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

		//TODO if usertype normal user then dont generate numeric code

		if (!$this->email_exists() && !$this->username_exists()) {

			$con = $this->CON;
			$query_string = "INSERT INTO users (ID, username, email, first_name, last_name, password, address, city, country, postal_code, type, thumbnail_path) VALUES (DEFAULT, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$insert_user = $con->prepare($query_string);
			$password = User::hash_password($this->user->password);
			$thumbnail = $this->upload_thumbnail();
			$insert_user->bind_param('sssssssssss', $this->user->username, $this->user->email, $this->user->name, $this->user->surname, $password, $this->user->address, $this->user->city, $this->user->country, $this->user->zipCode, $this->user->userType, $thumbnail["name"]);
			$insert_user->execute();

			$user = $this->get_user($this->user->username, $password);

			if ($user) {
				$user["thumbnailPath"] = $thumbnail["path"];
				if ($user["type"] == "waiter") {
					$numeric_code = $this->generate_numeric_code();
					$this->waiter_email(User::generate_qr_code_path($numeric_code));	
				}				
			}

			return $user;
		}
	}

	private function get_user($username, $password) {

		$query_string = "SELECT ID, username as userName, email, first_name as firstName, last_name as lastName, address, city, country, postal_code as postalCode, type, thumbnail_path as thumbnailPath FROM users WHERE password = ? AND username = ?";

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

	private function waiter_email($qr_url) {

		$to      = $this->user["email"];
		$subject = 'Potvrda Naloga';

		$message = '<body style="padding: 20px;">';
		$message .= '<p>Poštovani,</p>';
		$message .= "<p style='font-size: 21px;'>Ispod se nalazi vaš jedinstveni QR putem koga kod možete primiti bakšiš bilo kad, bilo gde!</p>";
		$message .= "<p style='font-size: 21px;'>Isti se može naći na korisnićkom profilu.</p>";
		$message .= "<img src='".$qr_url."' />";
		$message .= '<p>Hvala što koristite baksis.rs</p>';
		$message .= "<img src='http://drive.google.com/uc?export=view&id=1lNVKvttT98R6iYqWKjkszq7OcSgAzU0D' width='400' alt='Logo' title='Logo' />";
		$message .= '</body>';

		$headers = "From: no-reply@baksis.rs\r\n";
		$headers .= "Reply-To: no-reply@baksis.rs\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
		$headers .= "MIME-Version: 1.0\r\n";

		mail($to, $subject, $message, $headers);
	}	
}