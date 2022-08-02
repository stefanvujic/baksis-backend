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

	function __construct($CON) {
		$this->CON = $CON;
	}

	private function get_transactions() {
		require 'WSPay.php';
		$con = $this->CON;

		$query_string = "SELECT ID, user_id, waiter_id, amount, payspot_id, timestamp FROM transactions WHERE timestamp >= UNIX_TIMESTAMP(CURDATE()) ORDER BY user_id";		

		$get_transactions = mysqli_query($this->CON, $query_string);

		while ($row = $get_transactions->fetch_assoc()) {
		    $transactions[] = $row;
		}

		foreach ($transactions as $key => $transaction) {

			$transactions[$key]["sequenceNo"] = 1;

			$transactions[$key]["merchantOrderReference"] = $transaction["ID"];

			if ($transaction["user_id"] == 0) {
				$transactions[$key]["debtorName"] = "gost";
				$transactions[$key]["debtorAddress"] = "gost";
				$transactions[$key]["debtorCity"] = "gost";
			}else {
				$query_string = "SELECT first_name, last_name, address, city FROM users WHERE ID = " . $transaction["user_id"];
				$result = $con->query($query_string);
				$user_info = $result->fetch_assoc();

				$transactions[$key]["debtorName"] = $user_info["first_name"] . " " . $user_info["first_name"];
				$transactions[$key]["debtorAddress"] = $user_info["address"];
				$transactions[$key]["debtorCity"] = $user_info["city"];					
			}

			$query_string = "SELECT first_name, last_name, address, city, account_number FROM users WHERE ID = " . $transaction["waiter_id"];
			$result = $con->query($query_string);
			$waiter_info = $result->fetch_assoc();

			$transactions[$key]["beneficiaryAccount"] = $waiter_info["account_number"]; //IMPLE1117MENT THIS			

			$transactions[$key]["beneficiaryName"] = $waiter_info["first_name"] . " " . $waiter_info["first_name"];
			$transactions[$key]["beneficiaryAddress"] = $waiter_info["address"];
			$transactions[$key]["beneficiaryCity"] = $waiter_info["city"];

			$transactions[$key]["amountTrans"] = $transaction["amount"];

			$baksis_fee = (3 / 100) * $transaction["amount"];
			$payspot_fee = 20;
			$senders_fee = $payspot_fee + $baksis_fee;
			$beneficiary_amount = $transaction["amount"] - $senders_fee;

			$transactions[$key]["senderFeeAmount"] = $senders_fee;
			$transactions[$key]["paySpotFeeAmount"] = $payspot_fee;
			$transactions[$key]["beneficiaryAmount"] = $beneficiary_amount;

			$transactions[$key]["beneficiaryCurrency"] = "941";
			$transactions[$key]["purposeCode"] = "189";
			$transactions[$key]["paymentPurpose"] = "Baksis";
			$transactions[$key]["isUrgent"] = "0";
			$transactions[$key]["valueDate"] = date("Y-m-d"); 

			unset($transactions[$key]["user_id"]);
			unset($transactions[$key]["waiter_id"]);
			unset($transactions[$key]["amount"]);
			unset($transactions[$key]["timestamp"]);
		}

		return $transactions;
	}

	private function create_orders() {
		$transactions = $this->get_transactions();

		$orders = array();
		$ctr = 0;
		if($transactions) {
			foreach ($transactions as $key => $transaction) {

				$orders[$ctr]["PaymentOrderGroup"]["merchantContractID"] = 626;		

				$orders[$ctr]["PaymentOrderGroup"]["merchantOrderID"] = $transaction["ID"];
				$orders[$ctr]["PaymentOrderGroup"]["paySpotOrderID"] = $transaction["payspot_id"]; //import from transactions
				unset($transaction["payspot_id"]);
				$orders[$ctr]["PaymentOrderGroup"]["merchantOrderAmount"] = $transaction["amountTrans"];
				$orders[$ctr]["PaymentOrderGroup"]["merchantCurrencyCode"] = 941;
				$orders[$ctr]["PaymentOrderGroup"]["paymentType"] = 1;
				$orders[$ctr]["PaymentOrderGroup"]["requestType"] = "1";
				$orders[$ctr]["PaymentOrderGroup"]["actionType"] = "I";
				$orders[$ctr]["PaymentOrderGroup"]["merchantGroupID"] = $transaction["ID"];
				unset($transaction["ID"]);				
				$orders[$ctr]["PaymentOrderGroup"]["sumOfOrders"] = $transaction["amountTrans"];
				$orders[$ctr]["PaymentOrderGroup"]["numberOfOrders"] = 1;
				$orders[$ctr]["PaymentOrderGroup"]["language"] = "1";
				$orders[$ctr]["PaymentOrderGroup"]["shopID"] = "BAKSISRS";
				$orders[$ctr]["PaymentOrderGroup"]["terminalID"] = "IN001807";
				$orders[$ctr]["PaymentOrderGroup"]["Orders"] = array($transaction);
				$orders[$ctr]["PaymentOrderGroup"]["Customer"] = array("CustomerName" => "gost");

				$ctr++;
			}
		}

		return $orders;
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

	public function insert_payment_orders() {

		$log_output = array();
		$orders = $this->create_orders();
		$url = self::TEST_PAYMENT_ORDER_INSERT_ENDPOINT;
		$con = $this->CON;

		if (!empty($orders)) {
			foreach ($orders as $key => $order) {

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

				echo "<pre>" .json_encode($response, JSON_PRETTY_PRINT). "<pre/>";	

				$merchant_order_id = $response->Data->Body->PaymentOrderGroup->merchantOrderID;
				$payspot_order_id = $response->Data->Body->PaymentOrderGroup->paySpotOrderID;
				$merchant_group_id = $response->Data->Body->PaymentOrderGroup->merchantGroupID;
				$payspot_group_id = $response->Data->Body->PaymentOrderGroup->payspotGroupID;
				$payspot_transaction_id = $response->Data->Body->PaymentOrderGroup->Orders[0]->payspotTransactionID;
				$merchant_order_reference = $response->Data->Body->PaymentOrderGroup->Orders[0]->merchantOrderReference;

				if ($merchant_order_id && $payspot_order_id && $payspot_group_id && $payspot_transaction_id && $merchant_order_reference && $merchant_group_id) {

					$query_string = "INSERT INTO payouts (ID, amount, merchant_order_id, merchant_group_id, payspot_group_id, payspot_transaction_id, merchant_order_reference, timestamp) VALUES (DEFAULT, ".$order["PaymentOrderGroup"]["merchantOrderAmount"].", ".$merchant_order_id.", ".$merchant_group_id.", ".$payspot_group_id.", ".$payspot_transaction_id.", ".$merchant_order_reference.", ".time().")";
					mysqli_query($con, $query_string);
				}

			}

			error_log(json_encode($log_output, JSON_PRETTY_PRINT), 1, "stefan@baksis.rs");
		}
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
		$url = self::TEST_CHECK_ORDER_ENDPOINT;

		$query_string = "SELECT merchant_order_id AS merchantOrderID, merchant_group_id AS merchantGroupID, payspot_group_id AS paymentGroupID, merchant_order_reference AS merchantReference, payspot_transaction_id AS payspotTransactionID FROM payouts";

		$payout_rows = mysqli_query($this->CON, $query_string);

		while ($row = $payout_rows->fetch_assoc()) {
		    $payouts[] = $row;
		}

		foreach($payouts as $key => $payout) {

			// $payout = array('merchantContractID' => 626) + $payout;

			// $rnd = $this->generate_rnd();

			// $data = [
			// 	"Data" => [
			// 		"Header" => [
			// 			"Content-type" 		=> "application/json",
			// 			"CompanyID" 		=> self::COMPANY_ID,
			// 			"ExternalRequestID" => (string)$this->create_external_request_id(), // not even needed
			// 			"RequestDateTime" 	=> date("Y-m-d h:m:s"),
			// 			"MsgType" 			=> "104",
			// 			"Rnd" 				=> $rnd,
			// 			"Hash" 				=> $this->generate_hash(104, $rnd)
			// 		],
			// 		"Body" => $payout
			// 	]
			// ];
			// echo "<pre>" .json_encode($data, JSON_PRETTY_PRINT). "<pre/>";	

			// $ch = curl_init($url);

			// $headers = array(
			// 	"Content-type: application/json",
			// );
			
			// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);		
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

			// $response = curl_exec($ch);

			// curl_close($ch);

			// $response = json_decode($response);	

			// $merchant_order_id = $response->Data->Body->statusTrans;

			// echo "<pre>" .json_encode($response, JSON_PRETTY_PRINT). "<pre/>";			
		}
	}		
}
