<?php

function email_template($content) {
	$message = '<div style="margin: 5%; border-color: #383022; border-style: solid; border-radius: 11px;">';

		$message .= '<div style="height: 10%; background: black; height: 40px; background: #383022;"><div><a><img style="display:block; margin:auto; position: relative; padding-bottom: 0px; top: 0px; padding-left: 5px; border-radius: 45px; background-color: #383022; padding-right: 5px; padding-right: 5px; width: 170px; border-bottom-style: outset; border-color: #ad9349;" src="https://baksis.rs/baksa/static/media/header-logo.2782c27f.svg"></a></div></div>';

		$message .= '<div style="padding: 30px; margin-top: 5%; padding-bottom: 3%;">';
			$message .= '<p>Poštovani,</p>';

			$message .= "<div style='margin-top: 20px; margin-bottom: 20px;'>" . $content . "</div>";

			$message .= '<p>Hvala što koristite baksis.rs</p>';
			$message .= "<img style='width: 200px; padding-top: 3%;' src='http://drive.google.com/uc?export=view&id=1lNVKvttT98R6iYqWKjkszq7OcSgAzU0D' width='400' alt='Logo' title='Logo' />";
		$message .= '</div>';	

	$message .= '</div>';

	return $message;
}

