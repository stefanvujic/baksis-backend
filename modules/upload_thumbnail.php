<?php

//validate this!!!!
function upload_thumbnail() {
	$image = array();
	if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], "/var/www/html/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']))) {
		$image["imgUploaded"] = 1;
		$image["imgSrc"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']);
		$image["imgName"] = str_replace(" ", "_", $_FILES['thumbnail']['name']);
	} else {
		$image["imgUploaded"] = 0;
	}

	return $image;
}