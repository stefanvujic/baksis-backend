<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/session.php';
require '../classes/user2.php'; // change to 1 but remember to change path in classes
require '../classes/validation2.php'; // change to 1 but remember to change path in classes

$Validate = new validation;

$response = array();

$data = json_decode(file_get_contents("php://input"), true);

if(!$Validate->token($data["token"]) || !$Validate->ID($data["userId"])) {
	$response["session"] = false;
	echo json_encode($response);
	die();
}

$Session = new Session($con, $data["token"]);
if($Session->is_expired()) {
	$response["session"] = false;
	echo json_encode($response);
	die();
}

$response["session"] = true;
echo json_encode($response);
die();