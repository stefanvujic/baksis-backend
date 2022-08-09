<?php

function password_recovery_email($recipient, $token) {

	require 'email_template.php';

	$to      = $recipient;
	$subject = 'Baksis; Povrati sifru';

	$message .= "<p>Zatražili ste resetovanje lozinke za pristup Vašem baksis.rs nalogu. Da biste resetovali lozinku, kliknite na link Resetuj lozinku koje se nalazi ispod.</p>";
	$message .= "<a href=https://" . $_SERVER['SERVER_NAME'] . "/povrati-lozinku?token=" . $token . ">Resetuj lozinku</a>";
	$message .= "<p>S poštovanjem,</p>";
	$message .= "<p>Bakšiš</p>";

	$content = $message;

	$headers = "From: no-reply@baksis.rs\r\n";
	$headers .= "Reply-To: no-reply@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $content, $headers);
}
