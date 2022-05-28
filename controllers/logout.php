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
	$response["logout"] = false;
	echo json_encode($response);
	die();
}

$Session = new Session($con, $data["userId"]);
if ($Session->is_expired($data["token"])) {
	$response["logout"] = false;
	echo json_encode($response);
	die();
}

$User = new User($con);
$logged_out = $User->logout($data["token"], $data["userId"]);

($logged_out) ? ($response["logout"] = true) : ($response["logout"] = false);

echo json_encode($response);

die();