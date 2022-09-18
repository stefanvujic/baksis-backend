<?php
// TO DO: CHECK SESSION IF LOGGED IN!!!!!
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/user.php';
require '../classes/validation.php';
require '../emails/payment_waiter_email.php';
require '../emails/payment_sender_email.php';
require '../classes/WSPay.php';
require '../classes/payspot.php';


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

$transaction_authorized = $WSPay->check_transaction($data["WSPayID"], $data["signature"]);
if(!$transaction_authorized) {
	$response["transaction"] = false;
	$response["check_transaction"] = false;
	echo json_encode($response);
	die();
}

if (!$User->add_funds($data["waiterID"], $data["amount"])) { //put underneath payspot
	$response["addFundsError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();		
}


(!empty($data["userId"])) ? ($user_id = $data["userId"]) : ($user_id = 0);

$trans_id = $User->add_transaction((int)$user_id, (int)$data["waiterID"], (int)$data["amount"], (int)$data["WSPayID"]);
if (!$trans_id) {
	$response["addTransactionError"] = true;
	$response["paymentSuccessful"] = false;
	$response["amount"] = (int)$data["amount"];
	$response["transactionStatus"] = $transaction_authorized;
	echo json_encode($response);
	die();
}

if (!$User->add_rating($data["waiterID"], $data["waiterRating"])) { //put underneath payspot
	$response["ratingUpdateError"] = true;
	$response["paymentSuccessful"] = false;
	echo json_encode($response);
	die();		
}

$transaction_completed = $WSPay->complete_transaction($data["wspayOrderId"], $data["approvalCode"], $data["stan"]);
if(!$transaction_completed) {
	$response["transaction"] = false;
	$response["complete_transaction"] = false;
	echo json_encode($response);
	die();
}

$Payspot = new Payspot($con);
$payment_info = $Payspot->send_payment_info($data["amount"], (string)$trans_id);
$payspot_order_id = $payment_info->data->body->paySpotOrderID;
if(!$payspot_order_id) {
	$response["payspot"] = false;
	echo json_encode($response);
	die();
}

$insert_order = $Payspot->insert_payment_order((int)$data["amount"], (string)$trans_id, (int)$data["userId"], (int)$data["waiterID"], (string)$payspot_order_id); //get transaction amount from wspay response, 
if(!$insert_order) {
	$response["payspot"] = false;
	echo json_encode($response);
	die();
}

$query_string = "UPDATE transactions SET payspot_id = '" . $payspot_order_id . "' WHERE ID = " . $trans_id;
mysqli_query($con, $query_string);

$waiter = $User->basic_details($data["waiterID"]);
waiter_payment_email($waiter["email"], $waiter["firstName"] . " " . $waiter["lastName"], $data["amount"]);
user_payment_email($trans_id, $data["amount"], $data["userEmail"], $waiter["firstName"] . " " . $waiter["lastName"]);

$response["transactionStatus"] = $transaction_authorized;
$response["transID"] = $trans_id;

echo json_encode($response);

die();
