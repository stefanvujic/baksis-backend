<?php

function payment_email($recipient, $waiter_name, $amount) {

	require 'email_template.php';

	$to      = $recipient;
	$subject = 'Bakšiš je uplaćen!';

	$message = "<p style='font-size: 21px;'>" . $amount . "rsd uplaćeno korisniku - " .$waiter_name . "</p>";
	$message .= "<p>S poštovanjem,</p>";
	$message .= "<p>Bakšiš</p>";

	$content = $message;

	$headers = "From: no-reply@baksis.rs\r\n";
	$headers .= "Reply-To: no-reply@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $content, $headers);
}
