<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user2.php'; // change to 1 but remember to change path in classes
require '../classes/validation2.php'; // change to 1 but remember to change path in classes

$Validate = new validation;

$response = array();

$data = json_decode(file_get_contents("php://input"), true);

if(!$Validate->password($data["password"]) || !$Validate->token($data["token"])) {
	$response["passwordChanged"] = false;
	echo json_encode($response);
	die();
}

$User = new User($con);
if (!$User->change_password($data["password"], $data["token"])) {
	$response["passwordChanged"] = false;
	echo json_encode($response);
	die();
}

$response["passwordChanged"] = true;
echo json_encode($response);

die();