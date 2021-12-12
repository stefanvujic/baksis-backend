<?php

function payment($user_id=0, $waiter_id, $waiter_rating, $amount, $establishment_id, $register_data) {
	require 'mysql_auth.php';
	require 'update_rating.php';
	require 'email/payment.php';

	$response = array();

	if (!$establishment_id) {$establishment_id = 0;}

	if (!$user_id && !$register_data) {

		$is_transaction_inserted = add_transaction("0", $waiter_id, $establishment_id, $amount);

		($is_transaction_inserted) ? ($response["paymentSuccessful"] = 1) : ($response["paymentSuccessful"] = 0);
	}else {
		if ($register_data) {
			//new user
			//to do: if payment succesfull then register
			$registration = json_decode(register($register_data));

			if ($registration->created) {
				$user = $registration->user;
				$amount_to_save = (5 / 100) * $amount;

				$response["user"] = $user;

				$is_transaction_inserted = add_transaction($user->ID, $waiter_id, 0, $amount);

				($is_transaction_inserted) ? ($response["paymentSuccessful"] = 1) : ($response["paymentSuccessful"] = 0);	

			}else {
				$response["paymentSuccessful"] = 0;
				$response["userCreated"] = 0;
			}		
		}else {
			//existing user
			$is_transaction_inserted = add_transaction($user_id, $waiter_id, $establishment_id, $amount);

			($is_transaction_inserted) ? ($response["paymentSuccessful"] = 1) : ($response["paymentSuccessful"] = 0);
			payment_email("stefanvujic576@gmail.com", "stefan vujic", $amount);
		}
	}

	add_rating($waiter_id, $waiter_rating);

	return json_encode($response);
}



function add_transaction($user_id, $waiter_id, $establishment_id, $amount) {
	require 'mysql_auth.php';

	$query_string = "INSERT INTO transactions (ID, user_id, waiter_id, establishment_id, amount, timestamp) VALUES (DEFAULT, ?, ?, ?, ?, " . time() . ")";

	$insert_transaction = $con->prepare($query_string);
	$insert_transaction->bind_param('ssss', $user_id, $waiter_id, $establishment_id, $amount);

	return $insert_transaction->execute();
}
