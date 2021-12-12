<?php

function check_session($token, $user_id){
	require 'mysql_auth.php';
	require 'constants.php';

	$response = array();

	$token = htmlentities($token, ENT_QUOTES, 'UTF-8');
	$user_id = htmlentities($user_id, ENT_QUOTES, 'UTF-8');

	$query_string = "SELECT ID, timestamp FROM sessions WHERE token = ? AND user_id = ?";
	$session = mysqli_fetch_assoc(mysqli_query($con, $query_string));

	$session = $con->prepare($query_string);
	$session->bind_param('si', $token, $user_id);
	$session->execute();
	$result = $session->get_result();
	$session = $result->fetch_assoc();	

	if ($session["timestamp"] >= strtotime(SESSION_LENGTH)) {
		$expired = 0;
	}else {
	    $expired = 1;
	}

	if ($session) {
		if ($expired) {
			$response["session"] = false;
		}else {
			$response["session"] = $session;
		}
	}else {
		$response["session"] = false;
	}

	return json_encode($response);
}
