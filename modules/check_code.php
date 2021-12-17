<?php

function check_code($code, $amount, $is_qr, $establishment_id){
	require 'mysql_auth.php';
	require 'get_rating.php';

	$response = array();

	if ($is_qr) {
		$query_string = "SELECT waiter_id FROM codes WHERE code = ?";
		$code_info = $con->prepare($query_string);
		$code_info->bind_param('s', $code);
		$code_info->execute();
		$result = $code_info->get_result();
		$code_info = $result->fetch_assoc();

		if (!empty($code_info["waiter_id"])) {
			$query_string = "SELECT ID, username as userName, first_name as firstName, last_name as lastName, thumbnail_path as thumbnailPath FROM users WHERE ID = ".$code_info["waiter_id"];
			$user = mysqli_fetch_assoc(mysqli_query($con, $query_string));

			if ($user) {
				$user["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $user["thumbnailPath"];
				$user["rating"] = get_rating($code_info["waiter_id"]);
				$response["waiterData"] = $user;
					
				$query_string = "SELECT ID, username as userName, name, thumbnail_path as thumbnailPath FROM establishments WHERE ID = ?";
				$establishment = $con->prepare($query_string);
				$establishment->bind_param('i', $establishment_id);
				$establishment->execute();
				$result = $establishment->get_result();
				$establishment = $result->fetch_assoc();

				if ($establishment) {
					$establishment["thumbnailPath"] = "http://" . $_SERVER['SERVER_NAME'] . "/baksa/backend/assets/" . $establishment["thumbnailPath"];
					$response["establishmentData"] = $establishment;
				}else {
					$response["establishmentData"] = false;
					$response["establishmentId"] = $establishment_id;
				}

				$response["amount"] = $amount;		
			}else {
				$response["waiterData"] = false;
				$response["establishmentData"] = false;
			}	
		}else {
			$response["waiterData"] = false;
			$response["establishmentData"] = false;
		}
	}

	return json_encode($response);
}
