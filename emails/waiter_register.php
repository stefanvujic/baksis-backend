<?php

function waiter_register_email($recipient, $qr_url) {

	require 'email_template.php';

	$to      = $recipient;
	$subject = 'Potvrda Naloga';

	$message = "<p style='font-size: 21px;'>Ispod se nalazi vaš jedinstveni QR putem koga kod možete primiti bakšiš bilo kad, bilo gde!</p>";
	$message .= "<p style='font-size: 21px;'>Isti se može naći na korisnićkom profilu.</p>";
	$message .= "<img src='".$qr_url."' />";

	$content = $message;

	$message .= "<p>S poštovanjem,</p>";
	$message .= "<p>Bakšiš</p>";

	$headers = "From: no-reply@baksis.rs\r\n";
	$headers .= "Reply-To: no-reply@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $content, $headers);
}
