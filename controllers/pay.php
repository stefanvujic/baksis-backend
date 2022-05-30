<?php

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user2.php'; // change to 1 but remember to change path in classes
require '../classes/validation2.php'; // change to 1 but remember to change path in classes
require '../modules/email/payment.php';


$Validate = new validation;
$User = new User($con);

$response = array();

$register_data = json_decode($_POST["registarData"]);

if (!$Validate->ID($_POST["waiterID"]) ||
	!$Validate->rating($_POST["waiterRating"]) ||
	!$Validate->amount($_POST["amount"])) {

	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();
} 

if ($_POST["userId"]) {
	if (!$Validate->ID($_POST["userId"])) {
		$response["paymentSuccessful"] = false;
		echo json_encode($response);		
		die();
	}
}

if ($register_data) {
	if (!$Validate->username($register_data->username) || 
		!$Validate->email($register_data->email) ||
		!$Validate->name($register_data->name) ||
		!$Validate->name($register_data->surname) ||
		!$Validate->password($register_data->password) ||
		!$Validate->address($register_data->address) ||
		!$Validate->city($register_data->city) ||
		!$Validate->country($register_data->country) ||
		!$Validate->postal_code($register_data->zipCode)) {

		$response["paymentSuccessful"] = false;
		echo json_encode($response);
		die();
	}

	$user = $User->register($register_data);

	if (!$user) {
		$response["paymentSuccessful"] = false;
		$response["userCreated"] = 0;
		echo json_encode($response);
		die();		
	}

	$response["user"] = $user;	
}

$amount_to_save = (5 / 100) * $_POST["amount"];

if (!$User->add_rating($_POST["waiterID"], $_POST["waiterRating"])) {
	$response["ratingUpdateError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();		
}

if (!$User->add_funds($_POST["waiterID"], $_POST["amount"])) {
	$response["addFundsError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();		
}

if (!$Validate->ID($_POST["waiterID"]) ||
	!$Validate->rating($_POST["waiterRating"]) ||
	!$Validate->amount($_POST["amount"])) {

	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();
} 

if ($_POST["userId"]) {$user_id = $_POST["userId"];}
if ($user["ID"]) {$user_id = $user["ID"];}
if (!$user["ID"] && !$_POST["userId"]) {$user_id=0;}

if (!$User->add_transaction($user_id, (int)$_POST["waiterID"], $_POST["amount"])) {
	$response["addTransactionError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();
}	

$waiter = $User->basic_details($_POST["waiterID"]);
payment_email($waiter["email"], $waiter["firstName"] . " " . $waiter["lastName"], $_POST["amount"]);

$response["paymentSuccessful"] = 1;
echo json_encode($response);

die();
