<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/session.php';
require '../classes/user.php';
require '../classes/validation.php';

$Validate = new validation;

$response = array();

$data = json_decode(file_get_contents("php://input"), true);

if(!$Validate->token($data["token"]) || !$Validate->ID($data["userId"])) {
	$response["session"] = false;
	echo json_encode($response);
	die();
}

$Session = new Session($con, $data["userId"]);
if($Session->is_expired($data["token"])) {
	$response["session"] = false;
	echo json_encode($response);
	die();
}

$response["session"] = true;
echo json_encode($response);
die();