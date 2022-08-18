<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user.php';
require '../classes/validation.php';

$Validate = new validation;

$response = array();

$register_data = json_decode($_POST["registarData"]);

if (!$Validate->username($register_data->username) || 
	!$Validate->email($register_data->email) ||
	!$Validate->name($register_data->name) ||
	!$Validate->name($register_data->surname) ||
	!$Validate->password($register_data->password) ||
	!$Validate->address($register_data->address) ||
	!$Validate->city($register_data->city) ||
	!$Validate->country($register_data->country) ||
	!$Validate->postal_code($register_data->zipCode)) {

	$response["user"] = false;
	echo json_encode($response);
	die();
} 

if($_FILES['thumbnail'] && !$Validate->avatar_img($_FILES['thumbnail']['tmp_name'], "/var/www/html/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']))) {
	$response["user"] = false;
	echo json_encode($response);	
	die();
}

$User = new User($con);	
$user = $User->register($register_data);
$response["user"] = $user;

echo json_encode($response);

die();