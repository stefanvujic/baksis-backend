<?php

class Payspot
{

	private $CON;

	const COMPANY_ID = 98329;
	const SECRET_KEY = "PbDPqkk1lR8";

	const TEST_PAYMENT_ORDER_INSERT_ENDPOINT = "https://test.nsgway.rs:50009/api/paymentorderinsert";
	const LIVE_PAYMENT_ORDER_INSERT_ENDPOINT = "https://www.nsgway.rs:50010/api/paymentorderinsert";

	const TEST_PAYMENT_INFO_ENDPOINT = "https://test.nsgway.rs:50009/api/payment";
	const LIVE_PAYMENT_INFO_ENDPOINT = "https://www.nsgway.rs:50010/api/payment";

	const TEST_CHECK_ORDER_ENDPOINT = "https://test.nsgway.rs:50009/api/paymentorderstatus";
	const LIVE_CHECK_ORDER_ENDPOINT = "https://www.nsgway.rs:50010/api/paymentorderstatus";	

	const TEST_CONFIRM_ORDER_ENDPOINT = "https://test.nsgway.rs:50009/api/paymentorderconfirm";
	const LIVE_CONFIRM_ORDER_ENDPOINT = "https://www.nsgway.rs:50010/api/paymentorderconfirm";		

	function __construct($CON) {
		$this->CON = $CON;
	}

	public function send_payment_info($amount, $transaction_id) {
		$rnd = $this->generate_rnd();

		$headers = array(
			"Content-type: application/json",
		);

		$data = [
			"Data" => [
				"Header" => [
					"Content-type" 		=> "application/json",
					"CompanyID" 		=> self::COMPANY_ID,
					"ExternalRequestID" => (string)$this->create_external_request_id(),
					"RequestDateTime" 	=> date("Y-m-d h:m:s"),
					"MsgType" 			=> "51",
					"Rnd" 				=> $rnd,
					"Hash" 				=> $this->generate_hash(51, $rnd)
				],
				"Body" => [
					"merchantContractID"  	=>	626, 
					"merchantOrderID"     	=>	$transaction_id,
					"merchantOrderAmount"   =>	$amount,
					"merchantCurrencyCode"  =>	941,
					"paymentDate" 			=> 	date("Y-m-d"),
					"transtype"				=>	"PreAuth",
					"paymentType"			=>	1,
					"language"				=>	1,
				]
			]
		];	

		$url = self::TEST_PAYMENT_INFO_ENDPOINT;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($ch);

		$response = json_decode($response);

		curl_close($ch);

		return $response;
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
		return 11; // has to be incremental and never lower than previous ones
	}

	public function insert_payment_order($amount, $trans_id, $user_id, $waiter_id, $payspot_id) {

		$con = $this->CON;

		$transaction = array();
		$transaction["sequenceNo"] = 1;
		$transaction["merchantOrderReference"] = $trans_id;

		if ($user_id == 0) {
			$transaction["debtorName"] = "gost";
			$transaction["debtorAddress"] = "gost";
			$transaction["debtorCity"] = "gost";
		}else {

			$query_string = "SELECT first_name, last_name, address, city FROM users WHERE ID = ?";
			$user_info = $con->prepare($query_string);
			$user_info->bind_param('i', $user_id);
			$user_info->execute();
			$result = $user_info->get_result();
			$user_info = $result->fetch_assoc();	

			$transaction["debtorName"] = $user_info["first_name"] . " " . $user_info["first_name"];
			$transaction["debtorAddress"] = $user_info["address"];
			$transaction["debtorCity"] = $user_info["city"];					
		}

		$query_string = "SELECT first_name, last_name, address, city, account_number FROM users WHERE ID = ?";
		$waiter_info = $con->prepare($query_string);
		$waiter_info->bind_param('i', $waiter_id);
		$waiter_info->execute();
		$result = $waiter_info->get_result();
		$waiter_info = $result->fetch_assoc();				

		$transaction["beneficiaryAccount"] = $waiter_info["account_number"];

		$transaction["beneficiaryName"] = $waiter_info["first_name"] . " " . $waiter_info["first_name"];
		$transaction["beneficiaryAddress"] = $waiter_info["address"];
		$transaction["beneficiaryCity"] = $waiter_info["city"];

		$transaction["amountTrans"] = $amount;

		$baksis_fee = (3 / 100) * $amount;
		$payspot_fee = 20;
		$senders_fee = $payspot_fee + $baksis_fee;
		$beneficiary_amount = $amount - $senders_fee;

		$transaction["senderFeeAmount"] = $senders_fee;
		$transaction["paySpotFeeAmount"] = $payspot_fee;
		$transaction["beneficiaryAmount"] = $beneficiary_amount;

		$transaction["beneficiaryCurrency"] = "941";
		$transaction["purposeCode"] = "189";
		$transaction["paymentPurpose"] = "Baksis";
		$transaction["isUrgent"] = "0";
		$transaction["valueDate"] = date("Y-m-d"); 					

		$order = array();
		$order["PaymentOrderGroup"]["merchantContractID"] = 626;		
		$order["PaymentOrderGroup"]["merchantOrderID"] = $trans_id;
		$order["PaymentOrderGroup"]["paySpotOrderID"] = $payspot_id;

		$order["PaymentOrderGroup"]["merchantOrderAmount"] = $amount;
		$order["PaymentOrderGroup"]["merchantCurrencyCode"] = 941;
		$order["PaymentOrderGroup"]["paymentType"] = 1;
		$order["PaymentOrderGroup"]["requestType"] = "1";
		$order["PaymentOrderGroup"]["actionType"] = "I";
		$order["PaymentOrderGroup"]["merchantGroupID"] = $trans_id;
			
		$order["PaymentOrderGroup"]["sumOfOrders"] = $amount;
		$order["PaymentOrderGroup"]["numberOfOrders"] = 1;
		$order["PaymentOrderGroup"]["language"] = "1";
		$order["PaymentOrderGroup"]["shopID"] = "BAKSISRS";
		$order["PaymentOrderGroup"]["terminalID"] = "IN001807";
		$order["PaymentOrderGroup"]["Orders"] = array($transaction);
		$order["PaymentOrderGroup"]["Customer"] = array("CustomerName" => "gost");		

		$log_output = array();
		$url = self::TEST_PAYMENT_ORDER_INSERT_ENDPOINT;

		$rnd = $this->generate_rnd();

		$data = [
			"Data" => [
				"Header" => [
					"Content-type" 		=> "application/json",
					"CompanyID" 		=> self::COMPANY_ID,
					"ExternalRequestID" => (string)$this->create_external_request_id(), // not even needed
					"RequestDateTime" 	=> date("Y-m-d h:m:s"),
					"MsgType" 			=> "101",
					"Rnd" 				=> $rnd,
					"Hash" 				=> $this->generate_hash(101, $rnd)
				],
				"Body" => $order
			]
		];	

		//echo "<pre>" .json_encode($data, JSON_PRETTY_PRINT). "<pre/>";	

		$ch = curl_init($url);

		$headers = array(
			"Content-type: application/json",
		);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));


		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response);

		$log_output[] = $response;

		$merchant_order_id = $response->Data->Body->PaymentOrderGroup->merchantOrderID;
		$payspot_order_id = $response->Data->Body->PaymentOrderGroup->paySpotOrderID;
		$merchant_group_id = $response->Data->Body->PaymentOrderGroup->merchantGroupID;
		$payspot_group_id = $response->Data->Body->PaymentOrderGroup->payspotGroupID;
		$payspot_transaction_id = $response->Data->Body->PaymentOrderGroup->Orders[0]->payspotTransactionID;
		$merchant_order_reference = $response->Data->Body->PaymentOrderGroup->Orders[0]->merchantOrderReference;

		if ($merchant_order_id && $payspot_order_id && $payspot_group_id && $payspot_transaction_id && $merchant_order_reference && $merchant_group_id) {

			$query_string = "INSERT INTO payouts (ID, amount, merchant_order_id, merchant_group_id, payspot_group_id, payspot_transaction_id, merchant_order_reference, complete, timestamp) VALUES (DEFAULT, ?, ".$merchant_order_id.", ".$merchant_group_id.", ".$payspot_group_id.", ".$payspot_transaction_id.", ".$merchant_order_reference.", 0, ".time().")";
			$insert_order = $con->prepare($query_string);

			$insert_order->bind_param('i', $amount);
			$is_inserted = $insert_order->execute();
		}

		return $is_inserted;	
	}

	public function check_orders() {
		$con = $this->CON;
		$url = self::TEST_CHECK_ORDER_ENDPOINT;

		$query_string = "SELECT merchant_order_id AS merchantOrderID, merchant_group_id AS merchantGroupID, payspot_group_id AS paymentGroupID, merchant_order_reference AS merchantReference, payspot_transaction_id AS payspotTransactionID FROM payouts";

		$payout_rows = mysqli_query($this->CON, $query_string);

		while ($row = $payout_rows->fetch_assoc()) {
		    $payouts[] = $row;
		}

		foreach($payouts as $key => $payout) {

			$payout = array('merchantContractID' => 626) + $payout;

			$rnd = $this->generate_rnd();

			$data = [
				"Data" => [
					"Header" => [
						"Content-type" 		=> "application/json",
						"CompanyID" 		=> self::COMPANY_ID,
						"ExternalRequestID" => (string)$this->create_external_request_id(), // not even needed
						"RequestDateTime" 	=> date("Y-m-d h:m:s"),
						"MsgType" 			=> "104",
						"Rnd" 				=> $rnd,
						"Hash" 				=> $this->generate_hash(104, $rnd)
					],
					"Body" => $payout
				]
			];
			echo "<pre>" .json_encode($data, JSON_PRETTY_PRINT). "<pre/>";	

			$ch = curl_init($url);

			$headers = array(
				"Content-type: application/json",
			);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

			$response = curl_exec($ch);

			curl_close($ch);

			$response = json_decode($response);	

			$merchant_order_id = $response->Data->Body->statusTrans;

			echo "<pre>" .json_encode($response, JSON_PRETTY_PRINT). "<pre/>";			
		}
	}	


	public function confirm_orders() {
		$con = $this->CON;
		$url = self::TEST_CONFIRM_ORDER_ENDPOINT;
		$rnd = $this->generate_rnd();

		$query_string = "SELECT ID, timestamp, merchant_order_id AS merchantOrderID, merchant_group_id AS merchantGroupID, payspot_group_id AS paymentGroupID, merchant_order_reference AS merchantReference, payspot_transaction_id AS payspotTransactionID FROM payouts WHERE complete = 0 ORDER BY timestamp";

		$payout_rows = mysqli_query($this->CON, $query_string);

		while ($row = $payout_rows->fetch_assoc()) {
		    $payouts[] = $row;
		}

		foreach($payouts as $key => $payout) {
			$dt = date( "Y-m-d", $payout["timestamp"]);
			$date = new DateTime($dt);
			$now = new DateTime();
			$diff = $now->diff($date);

			if($diff->days < 4 && $diff->days !== 0 ) {

				$formatted_payouts = array();
				$payout = array('merchantContractID' => 626) + $payout;

				$query_string = "SELECT waiter_id, amount, timestamp FROM transactions WHERE ID = " . $payout["merchantOrderID"];
				$result = $con->query($query_string);
				$transaction_info = $result->fetch_assoc();

				$query_string = "SELECT account_number FROM users WHERE ID = " . $transaction_info["waiter_id"];
				$result = $con->query($query_string);
				$waiter_info = $result->fetch_assoc();

				$payout["beneficiaryAccount"] = $waiter_info["account_number"];

				$baksis_fee = (3 / 100) * $transaction_info["amount"];
				$payspot_fee = 20;
				$senders_fee = $payspot_fee + $baksis_fee;
				$beneficiary_amount = $transaction_info["amount"] - $senders_fee;	
					
				$payout["beneficiaryAmount"] = $beneficiary_amount;
				$payout["valueDate"] = date("Y-m-d", $transaction_info["timestamp"]);
				$formatted_payouts["OrderConfirm"][] = $payout;


				$data = [
					"Data" => [
						"Header" => [
							"Content-type" 		=> "application/json",
							"CompanyID" 		=> self::COMPANY_ID,
							"RequestDateTime" 	=> date("Y-m-d h:m:s"),
							"MsgType" 			=> "110",
							"Rnd" 				=> $rnd,
							"Hash" 				=> $this->generate_hash(110, $rnd),
							"Language"			=> 1
						],
						"Body" => $formatted_payouts
					]
				];

				$ch = curl_init($url);

				$headers = array(
					"Content-type: application/json",
				);
				
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

				$response = curl_exec($ch);

				curl_close($ch);

				$response = json_decode($response);

				echo "<pre>" .json_encode($response, JSON_PRETTY_PRINT). "<pre/>";	

				$status_code = $response->Data->Body->OrderConfirm[0]->statusProcessing;		

				if ($status_code == "-1" || $status_code == "1") {
					$query_string = "UPDATE payouts SET complete = 1 WHERE ID = " . $payout["ID"];
					$is_inserted = $con->query($query_string);

					$query_string = "SELECT amount FROM wallets WHERE user_id = " . $transaction_info["waiter_id"];
					$result = $con->query($query_string);
					$wallet = $result->fetch_assoc();

					$amount = $wallet["amount"] - $beneficiary_amount;

					$query_string = "UPDATE wallets SET amount = '".$amount."' WHERE user_id = " . $transaction_info["waiter_id"];
					$amended_wallet = $con->query($query_string);		

				}
			}else {
				echo "<br>void-".$payout["merchantOrderID"]."<br>";
			}
		}
	}		
}
