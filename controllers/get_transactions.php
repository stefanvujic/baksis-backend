<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user.php';
require '../classes/session.php'; 
require '../classes/validation.php';

$response = array();

$Validate = new validation;

$User = new User($con);
$data = json_decode(file_get_contents("php://input"), true);

if(!$Validate->ID($data["userId"]) || !$Validate->token($data["token"])) {
	$response["transactions"] = false;
	echo json_encode($response);
	die();
}

$Session = new Session($con, $data["userId"]);
if ($Session->is_expired($data["token"])) {
	$response["transactions"] = false;
	echo json_encode($response);
	die();
}

$transactions = $User->get_transactions($data["userId"], $data["userType"]);

echo json_encode($transactions);

die();