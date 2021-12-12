<?php

function logout($token, $user_id){
	require 'mysql_auth.php';

	$response = array();

	$token = htmlentities($token, ENT_QUOTES, 'UTF-8');
	$user_id = htmlentities($user_id, ENT_QUOTES, 'UTF-8');

	$query_string = "DELETE FROM sessions WHERE token = '" . $token . "' AND user_id = " . $user_id . "";
	mysqli_query($con, $query_string);

	$query_string = "SELECT ID FROM sessions WHERE token = '" . $token . "' AND user_id = " . $user_id . "";
	$session_exists = mysqli_fetch_assoc(mysqli_query($con, $query_string));	

	if (!$session_exists) {
		$response["logout"] = 1;
	}else{
		$response["logout"] = 0;
	}

	return json_encode($response);
}