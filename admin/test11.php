<?php


function credit_processusa($xType,$address,$zipcode,$card_num,$cvv2,$month,$year,$last_total,$first_name,$last_name,$invoice_num)
	{
		$DEBUGGING					= 1;				# Display additional information to track down problems
		$TESTING					= 1;				# Set the testing flag so that transactions are not live
		$ERROR_RETRIES				= 2;				# Number of transactions to post if soft errors occur

		$auth_net_login_id			= "2Kqx5YH8h4G";
		$auth_net_tran_key			= "68MKuw76m68w5rHh";
		#$auth_net_url				= "https://certification.authorize.net/gateway/transact.dll";
		#  Uncomment the line ABOVE for shopping cart test accounts or BELOW for live merchant accounts
		$auth_net_url				= "https://secure.authorize.net/gateway/transact.dll";

		$expire_date = $month.$year;

		$ship_address = explode("NaN",$address);

		$authnet_values				= array
		(
			"x_login"				=> $auth_net_login_id,
			"x_version"				=> "3.1",
			"x_delim_char"			=> "|",
			"x_delim_data"			=> "TRUE",
			"x_url"					=> "FALSE",
			"x_type"				=> $xType,
			"x_method"				=> "CC",
			"x_tran_key"			=> $auth_net_tran_key,
			"x_relay_response"		=> "FALSE",
			"x_card_num"			=> $card_num,
			"x_exp_date"			=> $expire_date,
			"x_description"			=> "DONGBUTOUR",
			"x_amount"				=> $last_total,
			"x_first_name"			=> $first_name,
			"x_last_name"			=> $last_name,
			"x_address"				=> $ship_address[1],
			"x_city"				=> $ship_address[2],
			"x_state"				=> $ship_address[3],
			"x_zip"					=> $zipcode,
			"x_invoice_num"					=> $invoice_num,
			"CustomerBirthMonth"	=> "&nbsp;",
			"CustomerBirthDay"		=> "&nbsp;",
			"CustomerBirthYear"		=> "&nbsp;",
			"SpecialCode"			=> "&nbsp;",
		);

		$fields = "";
		foreach( $authnet_values as $key => $value ) $fields .= "$key=" . urlencode( $value ) . "&";

		$ch = curl_init("https://secure.authorize.net/gateway/transact.dll"); // URL of gateway for cURL to post to

		//$ch = curl_init("https://certification.authorize.net/gateway/transact.dll");

		/**
		* Go daddy 땜시 특별히 넣음

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, true);
		curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt ($ch, CURLOPT_PROXY, '64.202.165.130:3128');
		*/

		#$ch = curl_init("https://secure.authorize.net/gateway/transact.dll"); // URL of gateway for cURL to post to
		curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $fields, "& " )); // use HTTP POST to send form data

		### curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response. ###
		curl_error($ch);
		$resp = curl_exec($ch); //execute post and get results
		curl_close ($ch);

		echo $resp;
		exit;
		return $result_value = explode("|",$resp);
		
	}