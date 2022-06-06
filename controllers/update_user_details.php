<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user2.php'; // change to 1 but remember to change path in classes
require '../classes/session.php'; 
require '../classes/validation2.php'; // change to 1 but remember to change path in classes

$response = array();

$Validate = new validation;

if($_FILES['thumbnail'] && !$Validate->avatar_img($_FILES['thumbnail']['tmp_name'], "/var/www/html/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']))) {
	$response["userData"] = false;
	$response["uploadError"] = true;
	echo json_encode($response);	
	die();
}

$user_details = json_decode($_POST["userData"]);

if (!$Validate->name($user_details->firstName) ||
	!$Validate->name($user_details->lastName) ||
	!$Validate->address($user_details->address) ||
	!$Validate->city($user_details->city) ||
	!$Validate->country($user_details->country) ||
	!$Validate->postal_code($user_details->zipCode) ||
	!$Validate->ID($user_details->ID)) {

	$response["test"] = $user_details->zipCode;
	$response["userData"] = false;
	$response["valError"] = true;
	echo json_encode($response);
	die();
} 

$Session = new Session($con, $user_details->ID);
if ($Session->is_expired($user_details->token)) {
	$response["userData"] = false;
	$response["sessionError"] = true;
	echo json_encode($response);
	die();
}

$User = new User($con);

$thumbnail = 0;
if (!empty($_FILES["thumbnail"])) {
	$thumbnail = $User->upload_thumbnail();
	if (!$thumbnail) {
		$response["userData"] = false;
		$response["uploadError"] = true;
		echo json_encode($response);
		die();
	}
}

$is_updated = $User->update_details($user_details, $thumbnail);
if (!$is_updated) {
	$response["userData"] = false;
	$response["updateError"] = true;
	echo json_encode($response);
	die();
}

$user = $User->basic_details($user_details->ID);

if ($user["type"] == "waiter") {
	$user["rating"] = $User->rating($user["ID"]);

	$numeric_code = $User->get_numeric_code($user["ID"]);
	$user["qrCodeUrl"] = User::generate_qr_code_path($numeric_code);
}	

$user["token"] = $user_details->token;

$response["userData"] = $user;

echo json_encode($response);

die();