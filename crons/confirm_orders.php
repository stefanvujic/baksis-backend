<?php
require '../mysql_auth.php';
require '../classes/payspot.php';

$Payspot = new Payspot($con);
$Payspot->confirm_orders();

//if order confirmed, mark as complete(add a complete column)