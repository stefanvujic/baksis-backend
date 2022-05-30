<?php
define('AUTH_CALL', 1);

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

require 'modules/check_session.php';
require 'classes/validation.php';
$Validate = new validation;

if ($_POST) {
	switch ($_POST["action"]) {

		case 'register':
			require 'mysql_auth.php';
			require 'classes/user.php';

			$response = array();
			$register_data = json_decode($_POST["registarData"]);

			if ($Validate->username($register_data->username) && 
				$Validate->email($register_data->email) &&
				$Validate->name($register_data->name) &&
				$Validate->name($register_data->surname) &&
				$Validate->password($register_data->password) &&
				$Validate->address($register_data->address) &&
				$Validate->city($register_data->city) &&
				$Validate->country($register_data->country) &&
				$Validate->postal_code($register_data->zipCode)) {

				if ($_FILES['thumbnail']) {
					if($Validate->avatar_img($_FILES['thumbnail']['tmp_name'], "/var/www/html/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']))){
						$User = new User($con);	
						$user = $User->register($register_data);
					}
				}else {
						$User = new User($con);	
						$user = $User->register($register_data);
				}
			} 

			($user) ? ($response["user"] = $user) : ($response["user"] = false);

			echo json_encode($response);
			break;

		case 'payment':
			require 'modules/payment.php';
			if ($Validate->ID($_POST["waiterID"]) && 
				$Validate->rating($_POST["waiterRating"]) && 
				$Validate->amount($_POST["amount"])) {

				if ($_POST["registarData"]) {
					$register_data = json_decode($_POST["registarData"]);

					if ($Validate->username($register_data->username) && 
						$Validate->email($register_data->email) &&
						$Validate->name($register_data->name) &&
						$Validate->name($register_data->surname) &&
						$Validate->password($register_data->password) &&
						$Validate->address($register_data->address) &&
						$Validate->city($register_data->city) &&
						$Validate->country($register_data->country) &&
						$Validate->postal_code($register_data->zipCode)) {

						if ($_FILES['thumbnail']) {
							if($Validate->avatar_img($_FILES['thumbnail']['tmp_name'], "/var/www/html/baksa/backend/assets/" . str_replace(" ", "_", $_FILES['thumbnail']['name']))){

								echo payment(0, $_POST["waiterID"], $_POST["waiterRating"], $_POST["amount"], 0, $register_data);
							}else {
								$response["paymentSuccessful"] = 0;
								echo json_encode($response);
							}
						}else {
							echo payment(0, $_POST["waiterID"], $_POST["waiterRating"], $_POST["amount"], 0, $register_data);
						}

					}else {
						$response["paymentSuccessful"] = 0;
						echo json_encode($response);
					}
				}else {
					echo payment(0, $_POST["waiterID"], $_POST["waiterRating"], $_POST["amount"], $_POST["establishmentID"], json_decode($_POST["registarData"]));
				}
			}else {
				$response["paymentSuccessful"] = 0;
				echo json_encode($response);
			}
			break;	

		case 'updateUserInfo':
			require 'modules/update_user_details.php';
			echo update_user_info($_POST["userData"]);
			break;					
	}
}else { // if not post take data from php input, should be a bit faster than posting everything
	$response_obj = array();	
	$data = json_decode(file_get_contents("php://input"), true);

	switch ($data["action"]) {

		case 'checkCode':
			require 'modules/check_code.php';
			echo check_code((int)$data["code"], $data["amount"], (bool)$data["QrCode"], (int)$data["establishmentId"]);
			break;

		case 'login':
			require 'mysql_auth.php';
			require 'classes/user.php';

			$response = array();

			if ($Validate->username($data["username"]) && $Validate->password($data["password"])) {
				$User = new User($con);	
				$user = $User->login($data["username"], $data["password"]);
			}

			($user) ? ($response["user"] = $user) : ($response["user"] = false);

			echo json_encode($response);
			break;

		case 'logout':
			require 'modules/logout.php';
			echo logout($data["token"], $data["userId"]);
			break;			
		case 'checkSession':
			echo check_session($data["token"], $data["userId"]);
			break;
		case 'payment':
			require 'modules/payment.php';				
			echo payment($data["userId"], $data["waiterID"], $data["waiterRating"], $data["amount"], $data["establishmentID"], $data["registarData"]);
			break;

		case 'get_stats':
			require 'mysql_auth.php';
			require 'classes/user.php';

			$response = array();

			//check session TODO

			$User = new User($con);

			$stats = $User->stats($data["userID"], $data["type"]);

			($stats) ? ($response["stats"] = $stats) : ($response["stats"] = false);

			echo json_encode($response);
			break;	

		case 'get_wallet':
			require 'mysql_auth.php';
			require 'classes/user.php';

			$response = array();

			//check session TODO

			$User = new User($con);
			$amount = $User->wallet($data["userID"]);

			($amount) ? ($response["amount"] = $amount) : ($response["amount"] = false);

			echo json_encode($response);
			break;					

		case 'getTransactions':
			require 'modules/get_transactions.php';
			echo get_transactions($data["userId"], $data["token"]);
			break;
		case 'getWaiterTransactions':
			require 'modules/get_waiter_transactions.php';
			echo get_waiter_transactions($data["waiterId"], $data["token"]);
			break;

		case 'sendPasswordRecoverEmail':
			require 'mysql_auth.php';
			require 'classes/user.php';

			$response = array();
			$User = new User($con);
			$user_id = $User->email_exists($data["email"]);

			if ($user_id) {
				$User->send_password_recovery_email($data["email"], $user_id);
				$response["userExists"] = true;
			}else {
				$response["userExists"] = false;
			}

			echo json_encode($response);

			break;

		case 'changePassword':
			require 'mysql_auth.php';
			require 'classes/user.php';

			$response = array();
			$User = new User($con);

			$response["passwordChanged"] = $User->change_password($data["password"], $data["token"]);

			echo json_encode($response);

			break;			
	}
}

die();
