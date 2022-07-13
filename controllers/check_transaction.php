<?php
//DELETE THIS FILE
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require '../mysql_auth.php';

require '../classes/validation.php';
require '../classes/WSPay.php';

$WSPay = new WSPay($con);
$Validate = new validation;

$response = array();

$data = json_decode(file_get_contents("php://input"), true);

if(!$Validate->amount((int)$data["amount"])) {
	$response["amountError"] = true;
	echo json_encode($response);
	die();
}

//TODO: validate signiture and wspay id!!!!!

$WSPay->set_amount($data["amount"]);

$transaction_complete = $WSPay->check_transaction($data["WSPayID"], $data["signature"]);
if(!$transaction_complete) {
	$response["transaction"] = false;
	echo json_encode($response);
	die();
}

$response["transaction"] = $transaction_complete;

echo json_encode($response);

die();