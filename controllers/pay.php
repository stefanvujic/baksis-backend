<?php
// TO DO: CHECK SESSION IF LOGGED IN!!!!!
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user.php';
require '../classes/validation.php';
require '../emails/payment.php';
require '../classes/WSPay.php';


$Validate = new validation;
$WSPay = new WSPay($con);
$User = new User($con);

$response = array();

$data = json_decode(file_get_contents("php://input"), true);

if (!$Validate->ID($data["waiterID"]) ||
	!$Validate->rating($data["waiterRating"]) ||
	!$Validate->amount($data["amount"])) {

	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();
} 

if ($data["userId"]) {
	if (!$Validate->ID($data["userId"])) {
		$response["paymentSuccessful"] = false;
		echo json_encode($response);		
		die();
	}
}

//TODO: validate signiture and wspay id!!!!!

$WSPay->set_amount($data["amount"]);

$transaction_complete = $WSPay->check_transaction($data["WSPayID"], $data["signature"]);
if(!$transaction_complete) {
	$response["transaction"] = false;
	echo json_encode($response);
	die();
}

$amount_to_save = (5 / 100) * $data["amount"];

if (!$User->add_rating($data["waiterID"], $data["waiterRating"])) {
	$response["ratingUpdateError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();		
}

if (!$User->add_funds($data["waiterID"], $data["amount"])) {
	$response["addFundsError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();		
}


(!empty($data["userId"])) ? ($user_id = $data["userId"]) : ($user_id = 0);

if (!$User->add_transaction($user_id, (int)$data["waiterID"], (int)$data["amount"], (int)$data["WSPayID"])) {
	$response["addTransactionError"] = true;
	$response["paymentSuccessful"] = false;
	$response["amount"] = (int)$data["amount"];
	$response["transactionStatus"] = $transaction_complete;
	echo json_encode($response);
	die();
}	

$waiter = $User->basic_details($data["waiterID"]);
payment_email($waiter["email"], $waiter["firstName"] . " " . $waiter["lastName"], $data["amount"]);

$response["transactionStatus"] = $transaction_complete;
echo json_encode($response);

die();
