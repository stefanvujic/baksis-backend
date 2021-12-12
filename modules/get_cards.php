<?php

function get_cards($user_id, $token){
	require 'mysql_auth.php';

	$response = array();

	if (!json_decode(check_session($token, $user_id))->session) {die();}

	$query_string = "SELECT ID, card_number AS cardNumber, card_holder AS cardHolder, expiry_date AS cardExpiry FROM cards WHERE user_id = ".$user_id;
	$result = mysqli_query($con, $query_string);

	$cards = mysqli_fetch_all($result, MYSQLI_ASSOC);

	if ($cards) {
		foreach ($cards as $key => $card) {
			$cards[$key]["cardNumber"] = substr($card["cardNumber"], -4);
		}	
		$response["cards"] = $cards;
	}

	return json_encode($response);
}
