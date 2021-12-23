<?php
require 'modules/create_qr_code.php';
require 'modules/email/waiter_register_email.php';

echo "strindwefwg";
$qr_url = create_qr_code(161618);
$qr_img = "<img src='".$qr_url."' />";

waiter_register_email("stefanvujic576@gmail.com", "stefan vujic", $qr_url);

die();
