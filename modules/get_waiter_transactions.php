<?php

//to do: CHECK SESSION
function get_waiter_transactions($waiter_id, $token){
	require 'mysql_auth.php';
	require 'constants.php';

	$response = array();

	if (!json_decode(check_session($token, $waiter_id))->session) {die();}

	$query_string = "SELECT * FROM transactions WHERE waiter_id = " . $waiter_id . " ORDER BY timestamp DESC";

	$result = mysqli_query($con, $query_string);

	$transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);

	if ($transactions) {
		foreach ($transactions as $key => $transaction) {
			$query_string = "SELECT ID, first_name as firstName, last_name as lastName, thumbnail_path as thumbnailPath FROM users WHERE ID = " . $transaction["user_id"];
			$user = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			$response[$key]["amount"] = $transaction["amount"];
			$response[$key]["date"] = date('d/m/Y', $transaction["timestamp"]);
			$response[$key]["timestamp"] = $transaction["timestamp"];
			$response[$key]["userData"] = $user;

			if ($user["thumbnailPath"]) {
				$response[$key]["userData"]["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $user["thumbnailPath"];	
			}else {
				$response[$key]["userData"]["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/default_avatar.png";	
			}			
		}
	}

	return json_encode($response);
}
