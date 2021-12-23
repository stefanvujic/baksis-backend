<?php

function register($user_data){
	require 'mysql_auth.php';
	require 'constants.php';
	require 'modules/upload_thumbnail.php';
	require 'modules/create_token.php';
	require 'modules/create_qr_code.php';

	$response = array();

	//less than 14 and more than 8 or 8 characters
	if (strlen($user_data->password) >= 8 && strlen($user_data->password) < 20) {
		$response["passLength"] = 1;
	}

	//one uppercase
	if (strtoupper($user_data->password) == $user_data->password) {
		$response["passUpper"] = 1;
	}

	$username = htmlentities($user_data->username, ENT_QUOTES, 'UTF-8');
	$password = htmlentities(hash('sha256', $user_data->password), ENT_QUOTES, 'UTF-8');
	$email = htmlentities($user_data->email, ENT_QUOTES, 'UTF-8');
	$name = htmlentities($user_data->name, ENT_QUOTES, 'UTF-8');
	$surname = htmlentities($user_data->surname, ENT_QUOTES, 'UTF-8');
	$address = htmlentities($user_data->address, ENT_QUOTES, 'UTF-8');
	$city = htmlentities($user_data->city, ENT_QUOTES, 'UTF-8');
	$country = htmlentities($user_data->country, ENT_QUOTES, 'UTF-8');
	$zipCode = htmlentities($user_data->zipCode, ENT_QUOTES, 'UTF-8');
	$userType = htmlentities($user_data->userType, ENT_QUOTES, 'UTF-8');

	$user["type"] = $userType;

	if (empty($_FILES["thumbnail"])) {
		$thumbnailName = "default_avatar.png";
	}else {
		$thumbnail = upload_thumbnail();
		if ($thumbnail["imgUploaded"]) {
			$thumbnailName = $thumbnail["imgName"];
		}else {
			$thumbnailName["uploadError"] = 1;
		}
	}

	//check if username exists
	$query_string = "SELECT ID FROM users WHERE username = ?";
	$get_user = $con->prepare($query_string);
	$get_user->bind_param('s', $username);
	$get_user->execute();
	$result = $get_user->get_result();
	$username_exists = $result->fetch_assoc();

	if ($username_exists) {
		$response["usernameExists"] = 1;
	}

	//check if email exists
	$query_string = "SELECT ID FROM users WHERE email = ?";

	$get_user = $con->prepare($query_string);
	$get_user->bind_param('s', $email);
	$get_user->execute();
	$result = $get_user->get_result();
	$email_exists = $result->fetch_assoc();	

	if ($email_exists) {
		$response["emailExists"] = 1;
	}

	if (!$response["usernameExists"] && !$response["emailExists"]) {
		$query_string = "INSERT INTO users (ID, username, email, first_name, last_name, password, address, city, country, postal_code, type, thumbnail_path) VALUES (DEFAULT, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$insert_user = $con->prepare($query_string);
		$insert_user->bind_param('sssssssssss', $username, $email, $name, $surname, $password, $address, $city, $country, $zipCode, $userType, $thumbnailName);
		$insert_user->execute();

		//check if user created
		$query_string = "SELECT ID, username as userName, email, first_name as firstName, last_name as lastName, address, city, country, postal_code as postalCode, type, thumbnail_path as thumbnailPath FROM users WHERE password = ? AND username = ?";

		$get_user = $con->prepare($query_string);
		$get_user->bind_param('ss', $password, $username);
		$get_user->execute();
		$result = $get_user->get_result();
		$user = $result->fetch_assoc();				
		
		if ($user["ID"]) {
			if ($user["type"] == "waiter") {
				//get largest code
				$query_string = "SELECT MAX( code ) AS max FROM codes";
				$result = $con->query($query_string);
				$codes = $result->fetch_assoc();

				$waiter_code = $codes["max"] + 1;
				$query_string = "INSERT INTO codes (ID, waiter_id, code) VALUES (DEFAULT, ".$user["ID"].", ".$waiter_code.")";
				$con->query($query_string);

				//send email
				require 'email/waiter_register_email.php';
				$qr_url = create_qr_code($waiter_code);

				waiter_register_email($user["email"], $qr_url);				

			}

			$user["token"] = create_token($user["ID"]);
			$user["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $thumbnailName;

			//create session
			$query_string = "INSERT INTO sessions (ID, user_id, token, timestamp) VALUES (DEFAULT, " . $user["ID"] . ", '" . $user["token"] . "', '" . time() . "');";
			$session_created = mysqli_query($con, $query_string);

			if ($session_created) {
				$response["created"] = 1;
				$response["user"] = $user;
			}
		}		
	}

	return json_encode($response);
}