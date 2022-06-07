<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user.php';
require '../classes/validation.php';

$Validate = new validation;

$response = array();

$data = json_decode(file_get_contents("php://input"), true);

if(!$Validate->email($data["email"])) {
	$response["userExists"] = false;
	echo json_encode($response);
	die();
}

$User = new User($con);	
$user_id = $User->email_exists($data["email"]);

if (!$user_id) {
	$response["userExists"] = false;
	echo json_encode($response);
	die();		
}

$User->send_password_recovery_email($data["email"], $user_id);
$response["userExists"] = true;

echo json_encode($response);

die();