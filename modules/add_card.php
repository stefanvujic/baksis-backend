<?php

function add_card($user_id, $card_data, $token){
	require 'mysql_auth.php';

	$response = array();

	if (!json_decode(check_session($token, $user_id))->session) {die();}

	$validated = 0;

	$card_number = htmlentities($card_data["cardNumber"], ENT_QUOTES, 'UTF-8');
	$card_holder = htmlentities($card_data["cardHolder"], ENT_QUOTES, 'UTF-8');
	$card_expiry = htmlentities($card_data["cardExpiry"], ENT_QUOTES, 'UTF-8');

	if (strlen($card_number) == 16 && is_numeric($card_number)) {
		if (!is_numeric($card_holder) && strlen($card_holder) < 30) {
			if (strlen($card_expiry) == 5) {
				$validated = 1;
			}
		}
	}

	//check how many cards user has
	$query_string = "SELECT COUNT(ID) as card_count FROM cards WHERE user_id = " . $user_id;
	$card_count = mysqli_fetch_assoc(mysqli_query($con, $query_string));

	if ($card_count["card_count"] == 5) {
		$response["cardsExceeded"] = 1;
	}else {
		if ($validated && $card_number !== 4716610961096109 && $card_holder !== "Ime" && $card_expiry !== "01/26") { //placeholder data
			if (strlen($card_number) !== 16 && !is_numeric($card_number)) {
				$response["error"] = 1;
			}else {
				$query_string = "SELECT ID FROM cards WHERE card_number = ".$card_number." AND card_holder = '".$card_holder."' AND expiry_date = '".$card_expiry."'";
				$does_exist = mysqli_fetch_assoc(mysqli_query($con, $query_string));

				if ($does_exist) {
					$response["exists"] = 1;
				}else {
					$query_string = "INSERT INTO cards (ID, user_id, card_number, card_holder, expiry_date) VALUES (DEFAULT, ".$user_id.", ".$card_number.", '".$card_holder."', '".$card_expiry."')";
					mysqli_query($con, $query_string);

					$query_string = "SELECT ID FROM cards WHERE user_id = ".$user_id." AND card_number = ".$card_number." AND card_holder = '".$card_holder."' AND expiry_date = '".$card_expiry. "'";
					$added_card = mysqli_fetch_assoc(mysqli_query($con, $query_string));

					if ($added_card["ID"]) {
						$response["cardAdded"] = 1;
					}		
				}
			}
		}else {
			$response["error"] = 1;
		}
	}

	return json_encode($response);
}
