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

if(!$Validate->ID($data["userID"]) || !$Validate->token($data["token"]) || !$Validate->user_type($data["type"])) {
	$response["stats"] = false;
	echo json_encode($response);
	die();
}

$Session = new Session($con, $data["userID"]);
if ($Session->is_expired($data["token"])) {
	$response["stats"] = false;
	echo json_encode($response);
	die();
}

$User = new User($con);	
$stats = $User->stats($data["userID"], $data["type"]);

if (!$stats) {
	$response["stats"] = false;
	echo json_encode($response);
	die();		
}

$response["stats"] = $stats;
echo json_encode($response);

die();