<?php

function delete_card($user_id, $card_id, $token){
	require 'mysql_auth.php';

	$response = array();

	if (!json_decode(check_session($token, $user_id))->session) {die();}

	$query_string = "DELETE FROM cards WHERE ID = " . $card_id;
	$is_deleted = mysqli_fetch_assoc(mysqli_query($con, $query_string));

	$is_deleted ? ($response["deleted"] = 1) : ($response["deleted"] = 0);

	return json_encode($response);
}
