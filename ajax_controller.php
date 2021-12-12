<?php
define('AUTH_CALL', 1);

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

// put these inside the switches for speed
require 'modules/check_code.php';
require 'modules/check_session.php';
require 'modules/register.php';

if ($_POST) {
	switch ($_POST["action"]) {
		case 'register':
			echo register(json_decode($_POST["registarData"])); // do json_decode in register.php file
			break;
		case 'payment':
			require 'modules/payment.php';
			echo payment(0, $_POST["waiterID"], $_POST["waiterRating"], $_POST["amount"], $_POST["establishmentID"], json_decode($_POST["registarData"]));
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
			echo check_code((int)$data["code"], $data["amount"], (bool)$data["QrCode"], (int)$data["establishmentId"]);
			break;
		case 'login':
			require 'modules/login.php'; 
			echo login($data["username"], $data["password"]);
			break;
		case 'logout':
			require 'modules/logout.php';
			echo logout($data["token"], $data["userId"]);
			break;
		case 'addUserCard':
			require 'modules/add_card.php';
			echo add_card($data["userId"], $data["cardData"], $data["token"]);
			break;
		case 'deleteUserCard':
			require 'modules/delete_card.php';
			echo delete_card($data["userId"], $data["cardId"], $data["token"]);
			break;				
		case 'getCards':
			require 'modules/get_cards.php';
			echo get_cards($data["userId"], $data["token"]);
			break;				
		case 'checkSession':
			echo check_session($data["token"], $data["userId"]);
			break;
		case 'payment':
			require 'modules/payment.php';
			echo payment($data["userId"], $data["waiterID"], $data["waiterRating"], $data["amount"], $data["establishmentID"], $data["registarData"]);
			break;
		case 'getTransactions':
			require 'modules/get_transactions.php';
			echo get_transactions($data["userId"], $data["token"]);
			break;
		case 'getWaiterTransactions':
			require 'modules/get_waiter_transactions.php';
			echo get_waiter_transactions($data["waiterId"], $data["token"]);
			break;
	}
}


