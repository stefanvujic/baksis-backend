<?php

function create_qr_code($code) {
	$url = urldecode("https://89.216.112.122/baksa/check-code?code=" . $code);
	$url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url . "&choe=UTF-8%22%20title=baksisCode";

	return $url;
}