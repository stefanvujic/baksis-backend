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

if(!$Validate->amount($data["amount"])) {
	$response["signature"] = false;
	$response["amountError"] = true;
	echo json_encode($response);
	die();
}

$WSPay->set_amount($data["amount"]);

echo json_encode($response);

die();