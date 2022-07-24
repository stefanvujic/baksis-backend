<?php

/**
 * 
 */
class Payspot
{

	private $CON;

	const COMPANY_ID = 98329;
	const SECRET_KEY = "PbDPqkk1lR8";

	const TEST_PAYMENT_ORDER_INSERT_ENDPOINT = "https://test.nsgway.rs:50009/api/paymentorderinsert";
	const LIVE_PAYMENT_ORDER_INSERT_ENDPOINT = "https://www.nsgway.rs:50010/api/paymentorderinsert";

	const TEST_PAYMENT_INFO_ENDPOINT = "https://test.nsgway.rs:50009/api/payment";
	const LIVE_PAYMENT_INFO_ENDPOINT = "https://www.nsgway.rs:50010/api/payment";	

	// function __construct($CON) {
	// 	$this->CON = $CON;
	// }

	private function get_transactions() {
		require 'WSPay.php';

		$query_string = "SELECT users.ID, 
								users.first_name, 
								users.last_name,
								users.email,
								users.address,
								users.city,
								transactions.ID, 
								transactions.wspay_id, 
								transactions.waiter_id, 
								transactions.user_id, 
								transactions.amount, 
								transactions.timestamp
		FROM transactions
		JOIN users ON users.ID = transactions.waiter_id
		WHERE transactions.timestamp >= UNIX_TIMESTAMP(CURDATE()) ORDER BY transactions.waiter_id";

		$get_transactions = mysqli_query($this->CON, $query_string);

		while ($row = $get_transactions->fetch_assoc()) {
		    $transactions[] = $row;
		}

		$WSPay = new WSPay($this->CON);
		foreach ($transactions as $key => $transaction) {
			if (!$WSPay->check_transaction($transaction["wspay_id"], 1)) {
				unset($transactions[$key]);
			}
		}

		return $transactions;
	}

	private function get_payouts() {
		$transactions = $this->get_transactions();

		foreach ($transactions as $key => $item) {
		   $transactions_by_user[$item['waiter_id']][] = $item;
		}

		ksort($transactions_by_user, SORT_NUMERIC);

		$payouts = array();
		$ctr = 0;
		foreach ($transactions_by_user as $transaction) {
			$payout_amount = array_sum(array_column($transaction,'amount'));

			$payouts[$ctr]["waiter_id"] = $transaction[0]["waiter_id"];
			$payouts[$ctr]["first_name"] = $transaction[0]["first_name"];
			$payouts[$ctr]["last_name"] = $transaction[0]["last_name"];
			$payouts[$ctr]["email"] = $transaction[0]["email"];
			$payouts[$ctr]["address"] = $transaction[0]["address"];
			$payouts[$ctr]["city"] = $transaction[0]["city"];

			$payouts[$ctr]["amount"] = $payout_amount;

			$payout_amount = 0;
			$ctr++;

		}

		return $payouts;
	}

	private function generate_rnd($length = 20) {
	    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    $char_length = strlen($characters);
	    $rnd = '';
	    for ($i = 0; $i < $length; $i++) {
	        $rnd .= $characters[rand(0, $char_length - 1)];
	    }
	    return $rnd;
	}	

	private function generate_hash($msg_type, $rnd) {

		$raw_string = self::COMPANY_ID . "|" . $msg_type . "|" . $rnd . "|" . self::SECRET_KEY;

		return base64_encode(hash("sha512", $raw_string, true));
	}

	private function create_external_request_id() {
		return 4; // has to be incremental and never lower than previous ones
	}

	public function send_payment_info() {
		$rnd = $this->generate_rnd();

		$headers = array(
			"Content-type: application/json",
			"CompanyID: ".self::COMPANY_ID."",
			"ExternalRequestID: ".(string)$this->create_external_request_id()."",
			"RequestDateTime: ".date("Y-m-d h:m:s")."",
			"MsgType: 51",
			"Rnd: ".$rnd."",
			"Hash: ".$this->generate_hash(51, $rnd).""
		);

		$data = [		
			"merchantContractID"  	=>	626, 
			"merchantOrderID"     	=>	"testOrder",
			"merchantOrderAmount"   =>	1000,
			"merchantCurrencyCode"  =>	941,
			"paymentDate" 			=> 	date("Y-m-d"),
			"transtype"				=>	"PreAuth",
			"paymentType"			=>	1,
			"language"				=>	1,
		];

		echo "<pre>" .json_encode($headers, JSON_PRETTY_PRINT). "<pre/>";
		echo "<pre>" .json_encode($data, JSON_PRETTY_PRINT). "<pre/>";

		$url = self::TEST_PAYMENT_INFO_ENDPOINT;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($ch);

		$response = json_decode($response);

		print_r($response);

		curl_close($ch);

		return $response;
	}

	// public function insert_payment_order() {

	// 	$rnd = $this->generate_rnd();

	// 	$headers = array(
	// 		"Content-type: application/json",
	// 		"CompanyID: ".self::COMPANY_ID."",
	// 		"ExternalRequestID: ".(string)$this->create_external_request_id()."",
	// 		"RequestDateTime: ".date("Y-M-D h:m:s")."",
	// 		"MsgType: ".101."",
	// 		"Hash: ".$this->generate_hash(1, $rnd)."",
	// 		"Rnd: ".$rnd."",
	// 		"Language: 1"
	// 	);

	// 	$data = array(	
	// 		'PaymentOrderGroup' 		=>  array(
	// 			"merchantContractID"  	=>	626,
	// 			"merchantOrderID"     	=>	"1",
	// 			"paySpotOrderID"      	=>	null, //how do we get this???
	// 			"merchantOrderAmount" 	=>	200,
	// 			"merchantCurrencyCode"  =>	626,
	// 			"paymentType"    		=>	3,
	// 			"requestType"      		=> 	"I",
	// 			"merchantGroupID" 		=> 	"1", //create this
	// 			"sumOfOrders"			=> 	200,
	// 			"numberOfOrders"		=> 	1,
	// 			"merchantLocation"		=> 	null,
	// 			"merchantLocationName"  => 	null,
	// 			"language"				=> 	"1",
	// 			"paymentGatewayID" 		=> 	null,
	// 			"shopID"				=> 	"BAKSISRS",  //is this correct???
	// 			"terminalID"			=> 	"IN001807", //how do we get this???
	// 			"authorizationCode"		=>	null,
	// 			"PAN"					=>  null,
	// 			"STAN"					=>  null,
	// 			"IPSReference"  		=>  null,
	// 			"paymentAmount"			=>  0,
	// 			"Orders" 				=>	array(
	// 											"sequenceNo" 			 	=>	1,
	// 											"merchantOrderReference"	=>	"222333", //create this
	// 											"transactionID"				=>	null,
	// 											"debtorAccount"				=>	null,
	// 											"debtorName" 				=>  "Dragan Milanović",
	// 											"debtorAddress" 			=>  "Pavla Jurišića Šturma 717",
	// 											"debtorCity"				=> 	"Palilula",
	// 											"debtorModul"				=> 	null,
	// 											"debtorReference"			=> 	null,
	// 											"beneficiaryAccount"		=> 	"160000000021612549",
	// 											"beneficiaryCode"			=> 	null,
	// 											"beneficiaryName"			=> 	"Vladimir Nikolic",
	// 											"beneficiaryAddress"		=> 	"Vladimira Popovica 6",
	// 											"beneficiaryCity"			=> 	"Beograd-Novi Beograd",
	// 											"beneficiaryModul"			=> 	null,
	// 											"beneficiaryReference"		=> 	"36",
	// 											"amountTrans"				=> 	200,
	// 											"senderFeeAmount"			=> 	26.2,
	// 											"paySpotFeeAmount"			=> 	0,
	// 											"beneficiaryAmount"			=> 	73.8,
	// 											"beneficiaryCurrency"		=> 	"941",
	// 											"purposeCode"				=> 	"189",
	// 											"paymentPurpose"			=> 	"Placanje robe",
	// 											"isUrgent"					=> 	"0",
	// 											"valueDate"					=> 	"2022-12-20",
	// 											"beneficiaryEMail"			=> 	"spajalica@gmail.com",
	// 											"beneficiaryContactNumber"	=> 	"38163000000",		
	// 											"beneficiaryContactPerson"	=>	null						
	// 			),
	// 		), 
	// 		'Customer' 					=> array(
	// 											"CustomerJMBG"			=> 	"31031987473281",
	// 											"CustomerName" 			=> 	"Petar",
	// 											"CustomerLastName" 		=> 	"Petrovic",
	// 											"DocumentType" 			=> 	"Licna karta",
	// 											"DocumentNumber" 		=> 	"34221243",
	// 											"DocIssueCity" 			=> 	"",
	// 											"DocIsueCountryName" 	=> 	"Srbija",
	// 											"DocIssueDate" 			=> 	"2020-03-25",
	// 											"DocValidTo"			=>	"2022-07-25",
	// 											"CustomerPhone"			=>	"063921023",
	// 											"CustomerMail"			=>	"pera87@gmail.com"
	// 		)
	// 	);

	// 	// echo json_encode($data);

	// 	$url = self::TEST_PAYMENT_ORDER_INSERT_ENDPOINT;

	// 	// echo "<pre>" .json_encode($data, JSON_PRETTY_PRINT). "<pre/>";
	// 	// echo $url;

	// 	$ch = curl_init($url);
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
	// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// 	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	// 	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
	// 	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


	// 	$response = curl_exec($ch);

	// 	curl_close($ch);

	// 	$response = json_decode($response);

	// 	print_r($response);
	// 	// return (int)$response->Authorized;
	// }
}

$Payspot = new Payspot();
$Payspot->send_payment_info();

// REQUEST:
// {
// 	"Data": {
// 	"Header": {
// 	"CompanyID": xxxxxxxxxxx,
// 	"ExternalRequestID": ”nnnnnnnn”,
// 	"RequestDateTime":"date and time of request",
// 	"MsgType": Message Type ID,
// 	"Hash": “Authentication Hash string”,
// 	"Rnd": “Random string”,
// 	"Language": 1,
// },
// "Body": {
// 	Struktura određena tipom poruke
// 		}
// 	}
// }



// $headers = [
// 	"Content-type: application/json",
// 	"CompanyID: ".self::COMPANY_ID."",
// 	"ExternalRequestID: ".(string)$this->create_external_request_id()."",
// 	"RequestDateTime: ".date("Y-m-d h:m:s")."",
// 	"MsgType: 51",
// 	"Rnd: ".$rnd."",
// 	"Hash: ".$this->generate_hash(51, $rnd).""
// ];

// $data = [		
// 	"merchantContractID"  	=>	626, 
// 	"merchantOrderID"     	=>	"testOrder",
// 	"merchantOrderAmount"   =>	1000,
// 	"merchantCurrencyCode"  =>	941,
// 	"email" 				=> 	"stefanvujic576@gmail.com",					
// 	"phoneNumber" 			=> 	"0603188987",
// 	"paymentDate" 			=> 	date("Y-m-d"),
// 	"recurring"				=>  array(
// 									"recurringAmount" => null,
// 									"recurringCurrencyCode" => null,
// 									"recurringPaymentNumber" => null,
// 									"recurringFrequencyUnit" => null,
// 									"recurringFrequency" => null,
// 								),
// 	"transtype"				=>	"PreAuth",
// 	"paymentType"			=>	1,
// 	"language"				=>	1,
// 	"instalment"			=>	null
// ];