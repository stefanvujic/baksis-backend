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

if(!$Validate->ID($data["code"])) {
	$response["waiterData"] = false;
	echo json_encode($response);
	die();
}

$User = new User($con);
$waiter_id = $User->id_by_numeric_code($data["code"]);
if (!$waiter_id) {
	$response["waiterData"] = false;
	die();
}

$waiter = $User->basic_details($waiter_id);
if (!$waiter) {
	$response["waiterData"] = false;
	$response["establishmentData"] = false;
	die();
}

$waiter["rating"] = $User->rating($waiter_id);
$response["waiterData"] = $waiter;

echo json_encode($response);

die();