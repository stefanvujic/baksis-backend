<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';
require '../classes/user.php';
require '../classes/validation.php';

$Validate = new validation;

$response = array();

$data = json_decode(file_get_contents("php://input"), true);
//check if string in email format

if(filter_var($data["username"], FILTER_VALIDATE_EMAIL)) {
	if(!$Validate->email($data["username"]) || !$Validate->password($data["password"])) {
		$response["user"] = false;
		echo json_encode($response);
		die();
	}
}else {
	if(!$Validate->username($data["username"]) || !$Validate->password($data["password"])) {
		$response["user"] = false;
		echo json_encode($response);
		die();
	}
}

$User = new User($con);	
$user = $User->login($data["username"], $data["password"]);

($user) ? ($response["user"] = $user) : ($response["user"] = false);

echo json_encode($response);

die();