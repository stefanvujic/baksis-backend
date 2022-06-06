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

if(!$Validate->ID($data["userID"]) || !$Validate->token($data["token"])) {
	$response["amount"] = false;
	echo json_encode($response);
	die();
}

$Session = new Session($con, $data["userID"]);
if ($Session->is_expired($data["token"])) {
	$response["amount"] = false;
	echo json_encode($response);
	die();
}

$User = new User($con);	
$amount = $User->wallet($data["userID"]);

if (!$amount) {
	$response["amount"] = false;
	echo json_encode($response);
	die();		
}

$response["amount"] = $amount;
echo json_encode($response);

die();