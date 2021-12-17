<?php

function delete_card($user_id, $card_id, $token){
	require 'mysql_auth.php';

	$response = array();

	if (!json_decode(check_session($token, $user_id))->session) {die();}

	$query_string = "DELETE FROM cards WHERE ID = ?";

	$delete_card = $con->prepare($query_string);
	$delete_card->bind_param('i', $card_id);	

	$delete_card->execute() ? ($response["deleted"] = 1) : ($response["deleted"] = 0);

	return json_encode($response);
}
