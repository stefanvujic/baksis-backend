<?php
if ($_GET["pass"] !== "5fe69c95ed70a9869d9f9af7d8400a6673bb9ce9") {
	echo "You should not be doing this";
	die();
}

require '../mysql_auth.php';
require '../classes/payspot.php';

$Payspot = new Payspot($con);
$Payspot->confirm_orders();
die();