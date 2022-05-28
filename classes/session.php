<?php

/**
 * Session
 */
class Session
{

	private $CON;
	private $user_id;

	const TOKEN_SECRET = "y9jv~%G*X2ZpLJ6T";
	const SESSION_LENGTH = "-24 hours";

	function __construct($CON, $user_id) {
		$this->user_id = $user_id;
		$this->CON = $CON;
	}	

	private function create_token()	{

		$token_string = time() . self::TOKEN_SECRET . $this->user_id;
		$token = hash('sha256', $token_string);

		return $token;
	}

	public function start() {

		$token = $this->create_token($this->user_id);
		$con = $this->CON;
		$query_string = "INSERT INTO sessions (ID, user_id, token, timestamp) VALUES (DEFAULT, " . $this->user_id . ", '" . $token . "', '" . time() . "')";
		$session = mysqli_query($con, $query_string);

		return ($session) ? ($token) : (false);
	}

	public function end($token) {

		$con = $this->CON;
		$query_string = "DELETE FROM sessions WHERE token = ? AND user_id = ?";
		$delete_session = $con->prepare($query_string);
		$delete_session->bind_param('si', $token, $this->user_id);
		$delete_session->execute();

		//fix
		$query_string = "SELECT ID FROM sessions WHERE token = ? AND user_id = ?";

		$get_session = $con->prepare($query_string);
		$get_session->bind_param('si', $token, $this->user_id);
		$get_session->execute();
		$result = $get_session->get_result();
		$session = $result->fetch_assoc();

		return ($session) ? (false) : (true);
	}

	public function is_expired($token) {
		$query_string = "SELECT ID, timestamp FROM sessions WHERE token = ? AND user_id = ?";

		$con = $this->CON;
		$session = $con->prepare($query_string);
		$session->bind_param('si', $token, $this->user_id);
		$session->execute();
		$result = $session->get_result();
		$session = $result->fetch_assoc();	

		return ($session["timestamp"] >= strtotime(self::SESSION_LENGTH)) ? (false) : (true);
	}	
}