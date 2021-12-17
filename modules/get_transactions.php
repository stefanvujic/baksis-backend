<?php

function get_transactions($user_id, $token){
	require 'mysql_auth.php';
	require 'constants.php';

	if (!json_decode(check_session($token, $user_id))->session) {die();}

	$response = array();

	$query_string = "SELECT * FROM transactions WHERE user_id = ? ORDER BY timestamp DESC";
	$get_transactions = $con->prepare($query_string);
	$get_transactions->bind_param('i', $user_id);
	$get_transactions->execute();
	$result = $get_transactions->get_result();

	$transactions = mysqli_fetch_all($result, MYSQLI_ASSOC);

	if ($transactions) {
		foreach ($transactions as $key => $transaction) {
			$query_string = "SELECT ID, first_name as firstName, last_name as lastName, thumbnail_path as thumbnailPath FROM users WHERE ID = " . $transaction["waiter_id"];
			$waiter = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			$response[$key]["amount"] = $transaction["amount"];
			$response[$key]["date"] = date('d/m/Y', $transaction["timestamp"]);
			$response[$key]["timestamp"] = $transaction["timestamp"];
			$response[$key]["waiterData"] = $waiter;
			
			if ($waiter["thumbnailPath"]) {
				$response[$key]["waiterData"]["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $waiter["thumbnailPath"];	
			}else {
				$response[$key]["waiterData"]["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/default_avatar.png";	
			}
		}
	}

	return json_encode($response);
}
