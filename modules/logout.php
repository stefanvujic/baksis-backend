<?php

function logout($token, $user_id){
	require 'mysql_auth.php';

	$response = array();

	$token = htmlentities($token, ENT_QUOTES, 'UTF-8');
	$user_id = htmlentities($user_id, ENT_QUOTES, 'UTF-8');

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

	if (!$session_exists) {
		$response["logout"] = 1;
	}else{
		$response["logout"] = 0;
	}

	return json_encode($response);
}