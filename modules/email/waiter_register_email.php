<?php

function waiter_register_email($recipient, $qr_url) {

	$to      = $recipient;
	$subject = 'Potvrda Naloga';

	$message = '<body style="padding: 20px;">';
	$message .= '<p>Poštovani,</p>';
	$message .= "<p style='font-size: 21px;'>Ispod se nalazi vaš jedinstveni QR putem koga kod možete primiti bakšiš bilo kad, bilo gde!</p>";
	$message .= "<p style='font-size: 21px;'>Isti se može naći na korisnićkom profilu.</p>";
	$message .= "<img src='".$qr_url."' />";
	$message .= '<p>Hvala što koristite baksis.rs</p>';
	$message .= "<img src='http://drive.google.com/uc?export=view&id=1lNVKvttT98R6iYqWKjkszq7OcSgAzU0D' width='400' alt='Logo' title='Logo' />";
	$message .= '</body>';

	$headers = "From: no-reply@baksis.rs\r\n";
	$headers .= "Reply-To: no-reply@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $message, $headers);
}