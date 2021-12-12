<?php

//PREPARE QUERIES!!!!!
function update_user_info($user_data){
	require 'mysql_auth.php';
	require 'constants.php';
	require 'upload_thumbnail.php';

	$response = array();
	$user_data = json_decode($user_data);

	if (!json_decode(check_session($user_data->token, $user_data->ID))->session) {die();}

	$user_id = htmlentities($user_data->ID, ENT_QUOTES, 'UTF-8');
	$name = htmlentities($user_data->firstName, ENT_QUOTES, 'UTF-8');
	$surname = htmlentities($user_data->lastName, ENT_QUOTES, 'UTF-8');
	$address = htmlentities($user_data->address, ENT_QUOTES, 'UTF-8');
	$city = htmlentities($user_data->city, ENT_QUOTES, 'UTF-8');
	$country = htmlentities($user_data->country, ENT_QUOTES, 'UTF-8');
	$zipCode = htmlentities($user_data->zipCode, ENT_QUOTES, 'UTF-8');
	$token = htmlentities($user_data->token, ENT_QUOTES, 'UTF-8');	

	if (!empty($_FILES["thumbnail"])) {
		$thumbnail = upload_thumbnail();

		if ($thumbnail["imgUploaded"]) {
			$thumbnail_name = $thumbnail["imgName"];
		}else {
			$thumbnail_name = "default_avatar.png";
		}

		$query_string = "UPDATE users SET first_name = '".$name."', last_name = '".$surname."', address = '".$address."', city = '".$city."', country = '".$country."', postal_code = '".$zipCode."', thumbnail_path = '".$thumbnail_name."' WHERE ID = " . $user_id;
		mysqli_query($con, $query_string);
	}else {
		$query_string = "UPDATE users SET first_name = '".$name."', last_name = '".$surname."', address = '".$address."', city = '".$city."', country = '".$country."', postal_code = '".$zipCode."'WHERE ID = " . $user_id;
		mysqli_query($con, $query_string);
	}

	$query_string = "SELECT ID, username as userName, email, first_name as firstName, last_name as lastName, address, city, country, postal_code as postalCode, type, thumbnail_path as thumbnailPath FROM users WHERE ID =" . $user_id;
	$user = mysqli_fetch_assoc(mysqli_query($con, $query_string));
	
	if ($user["ID"]) {
		$user["token"] = $token;
		$user["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $user["thumbnailPath"];
		$response["userData"] = $user;
	}

	return json_encode($response);
}
