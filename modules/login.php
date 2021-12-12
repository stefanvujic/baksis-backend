<?php

function login($username, $password){
	require 'mysql_auth.php';
	require 'constants.php';
	require 'get_rating.php';

	$response = array();
	$username = htmlentities($username , ENT_QUOTES, 'UTF-8');
	$password = htmlentities(hash('sha256', $password), ENT_QUOTES, 'UTF-8');

	//get user
	$query_string = "SELECT ID, username as userName, email, first_name as firstName, last_name as lastName, address, city, country, postal_code as postalCode, type, thumbnail_path as thumbnailPath FROM users WHERE password = '" . $password . "' AND username = '" . $username . "'";
	$user = mysqli_fetch_assoc(mysqli_query($con, $query_string));

	if ($user) {
		$token_string = time() . TOKEN_SECRET . $user["ID"];
		$token = hash('sha256', $token_string);
		$user["token"] = $token;
		$user["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $user["thumbnailPath"];

		if($user["type"] == "waiter") {
			$user["rating"] = get_rating($user["ID"]);
		}

		//create session
		$query_string = "INSERT INTO sessions (ID, user_id, token, timestamp) VALUES (DEFAULT, " . $user["ID"] . ", '" . $token . "', '" . time() . "');";
		$session_created = mysqli_query($con, $query_string);	

		$response["user"] = $user;
	}else {
		$response["user"] = false;
	}

	return json_encode($response);
}