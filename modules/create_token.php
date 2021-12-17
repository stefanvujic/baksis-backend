<?php

function create_token($user_id){
	require 'constants.php';

	$token_string = time() . TOKEN_SECRET . $user_id;
	$token = hash('sha256', $token_string);

	return $token;
}