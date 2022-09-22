<?php

function user_payment_email($transaction_id, $amount, $user_email, $waiter_name, $approval_code, $wspay_order_id, $timestamp, $userEmail) {

    // this is done multiple times throughtout the project, make a function for it
    $baksis_fee = (3 / 100) * $amount;
    $payspot_fee = 20;
    $senders_fee = $payspot_fee + $baksis_fee;
    $beneficiary_amount = $amount - $senders_fee;

	$to      = $user_email;
	$subject = 'Bakšiš je uplaćen korisniku - ' . $waiter_name;

	$date = substr($timestamp, 0, 8);
	$year = substr($date, 0, 4);
	$month = substr($date, 4, 2);
	$day = substr($date, 6, 2);


	$time = substr($timestamp, 8, 6);
	$hour = substr($time, 0, 2);
	$minute = substr($time, 2, 2);
	$second = substr($time, 4, 2);


	$message = "<p style='font-size: 14px;'>Datum: " . $day . "/" . $month . "/" . $year . " " . $hour . ":" . $minute . ":" . $second . "</p>";
	$message .= "<p style='font-size: 14px;'>Email: " . $userEmail . "</p>";
	$message .= "<p style='font-size: 14px;'>Kod Odobrenja: " . $approval_code . "</p>";
	$message .= "<p style='font-size: 14px;'>ID: " . $wspay_order_id . "</p>";
	$message .= "<p style='font-size: 14px;'>Kod Transakcije: " . $transaction_id . "</p>";
    $message .= "<p style='font-size: 14px;'>Ukupan Iznos: " . $amount . " (U cenu je uračunat PDV)</p>";
    $message .= "<p style='font-size: 14px;'>Provizija: 20rsd + 3%</p>";
    $message .= "<p style='font-size: 14px;'>Iznos Provizije: " . $senders_fee . "</p>";
    $message .= "<p style='font-size: 14px;'>Iznos sa provizijom: " . $beneficiary_amount . "</p>";

	$message .= "<p>S poštovanjem,</p>";
	$message .= "<p>Bakšiš</p>";

	$content = $message;

	$headers = "From: no-reply@baksis.rs\r\n";
	$headers .= "Reply-To: no-reply@baksis.rs\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; 
	$headers .= "MIME-Version: 1.0\r\n";

	mail($to, $subject, $content, $headers);
}