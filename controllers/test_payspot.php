<?php
ini_set("show_errors", 1);

require '../mysql_auth.php';
require '../classes/payspot.php';

$Payspot = new Payspot($con);
$Payspot->insert_payment_orders();

// $Payspot->get_payouts();