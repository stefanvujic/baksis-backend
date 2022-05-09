<?php

function password_recovery_email($recipient, $token) {

	$to      = $recipient;
	$subject = 'Bakšiš; Povrati šifru';

	$message .= '<p>Poštovani,</p>';
	$message .= "<p>Zatražili ste resetovanje lozinke za pristup Vašem baksis.rs nalogu. Da biste resetovali lozinku, kliknite na link „Resetuj lozinku“ koje se nalazi ispod.</p>";
	$message .= "<a href=https://" . $_SERVER['SERVER_NAME'] . "/povrati-lozinku?token=" . $token . ">Resetuj lozinku</a>";
	$message .= '<p>Hvala što koristite baksis.rs</p>';
	$message .= "<img src='http://drive.google.com/uc?export=view&id=1lNVKvttT98R6iYqWKjkszq7OcSgAzU0D' width='400' alt='Logo' title='Logo' />";

	$headers = "From: no-reply@baksis.rs\r\n";
	$headers .= "Reply-To: no-reply@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $message, $headers);
}
