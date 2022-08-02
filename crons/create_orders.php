<?php
require '../mysql_auth.php';
require '../classes/payspot.php';

$Payspot = new Payspot($con);
$Payspot->insert_payment_orders();