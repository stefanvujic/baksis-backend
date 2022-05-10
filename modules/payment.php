<?php

function payment($user_id=0, $waiter_id, $waiter_rating, $amount, $establishment_id, $register_data) {
	require 'mysql_auth.php';
	require 'update_rating.php';
	require 'email/payment.php';
	require 'classes/user.php';

	$User = new User($con);

	$response = array();

	if (!$establishment_id) {$establishment_id = 0;}

	if (!$user_id && !$register_data) {

		$is_transaction_inserted = add_transaction("0", $waiter_id, $establishment_id, $amount);
		$wallet_updated = $User->add_funds($waiter_id, $amount);

		($is_transaction_inserted && $wallet_updated) ? ($response["paymentSuccessful"] = 1) : ($response["paymentSuccessful"] = 0);
	}else {
		if ($register_data) {
			//new user
			//to do: if payment succesfull then register

			require 'classes/session.php';

			$user = $User->register($register_data);
			
			if ($user) {
				$amount_to_save = (5 / 100) * $amount;

				$session = new Session($con, $user["ID"]);

				$user["token"] = $session->start();				

				$response["user"] = $user;

				$is_transaction_inserted = add_transaction($user["ID"], $waiter_id, 0, $amount);

				$wallet_updated = $User->add_funds($waiter_id, $amount);

				($is_transaction_inserted && $wallet_updated) ? ($response["paymentSuccessful"] = 1) : ($response["paymentSuccessful"] = 0);	

			}else {
				$response["paymentSuccessful"] = 0;
				$response["userCreated"] = 0;
			}		
		}else {
			//existing user
			$is_transaction_inserted = add_transaction($user_id, $waiter_id, $establishment_id, $amount);

			$wallet_updated = $User->add_funds($waiter_id, $amount);

			($is_transaction_inserted && $wallet_updated) ? ($response["paymentSuccessful"] = 1) : ($response["paymentSuccessful"] = 0);
		}
	}

	if ($response["paymentSuccessful"]) {

		$query_string = "SELECT email, first_name, last_name FROM users WHERE ID = ?";
		$get_waiter_info = $con->prepare($query_string);
		$get_waiter_info->bind_param('i', $waiter_id);
		$get_waiter_info->execute();
		$result = $get_waiter_info->get_result();
		$waiter_info = $result->fetch_assoc();
		payment_email($waiter_info["email"], $waiter_info["first_name"] . " " . $waiter_info["last_name"], $amount);#

		add_rating($waiter_id, $waiter_rating);
	}	

	return json_encode($response);
}


function add_transaction($user_id, $waiter_id, $establishment_id, $amount) {
	require 'mysql_auth.php';

	$query_string = "INSERT INTO transactions (ID, user_id, waiter_id, establishment_id, amount, timestamp) VALUES (DEFAULT, ?, ?, ?, ?, " . time() . ")";

	$insert_transaction = $con->prepare($query_string);
	$insert_transaction->bind_param('ssss', $user_id, $waiter_id, $establishment_id, $amount);

	return $insert_transaction->execute();
}
