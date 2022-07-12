<?php
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

$WSPay->set_amount(str_replace(",", "", $data["amount"]));

$auth = $WSPay->authorization_info();
if(!$auth) {
	$response["authTokenError"] = true;
	echo json_encode($response);
	die();
}

$signature = $WSPay->create_form_signature();
if(!$signature) {
	$response["signatureError"] = false;
	echo json_encode($response);
	die();
}

$response["WSPayId"] = $WSPay->WSPayId;
$response["amount"] = $WSPay->amount;
$response["authToken"] = $auth->AuthorizationToken;
$response["signature"] = $signature;

echo json_encode($response);

die();