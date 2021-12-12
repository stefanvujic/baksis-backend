<?php

function payment_email($recipient, $waiter_name, $amount) {

	$to      = $recipient;
	$subject = 'Bakšiš je uplaćen!';

	$message .= '<p>Poštovani,</p>';
	$message .= "<p style='font-size: 21px;'>" . $amount . "rsd uplaćeno korisniku - " .$waiter_name . "</p>";
	$message .= '<p>Hvala što koristite baksis.rs</p>';
	$message .= "<img src='http://drive.google.com/uc?export=view&id=1lNVKvttT98R6iYqWKjkszq7OcSgAzU0D' width='400' alt='Logo' title='Logo' />";

	$headers = "From: admin@baksis.rs\r\n";
	$headers .= "Reply-To: admin@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $message, $headers);
}
