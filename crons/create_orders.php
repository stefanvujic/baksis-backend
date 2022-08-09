<?php
require '../mysql_auth.php';
require '../classes/payspot.php';

$Payspot = new Payspot($con);
$response = $Payspot->send_payment_info(100, "6100");
print_r($response->Data->Status->ErrorCode);