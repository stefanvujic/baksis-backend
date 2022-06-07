<?php
// TO DO: CHECK SESSION IF LOGGED IN!!!!!
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/session.php';
require '../classes/user.php';
require '../classes/validation.php';


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