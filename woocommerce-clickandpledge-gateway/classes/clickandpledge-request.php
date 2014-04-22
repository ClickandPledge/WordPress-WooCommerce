<?php
/**
* Click & Pledge API request class - sends given POST data to Click & Pledge server via CURL extension
**/
class clickandpledge_request {
	private $url;
	var $responsecodes = array();
	var $country_code = array();
	/** constructor */
	public function __construct( $url ) {
		$this->url = $url;
		$this->responsecodes = array(2054=>'Total amount is wrong',2055=>'AccountGuid is not valid',2056=>'AccountId is not valid',2057=>'Username is not valid',2058=>'Password is not valid',2059=>'Invalid recurring parameters',2060=>'Account is disabled',2101=>'Cardholder information is null',2102=>'Cardholder information is null',2103=>'Cardholder information is null',2104=>'Invalid billing country',2105=>'Credit Card number is not valid',2106=>'Cvv2 is blank',2107=>'Cvv2 length error',2108=>'Invalid currency code',2109=>'CreditCard object is null',2110=>'Invalid card type ',2111=>'Card type not currently accepted',2112=>'Card type not currently accepted',2210=>'Order item list is empty',2212=>'CurrentTotals is null',2213=>'CurrentTotals is invalid',2214=>'TicketList lenght is not equal to quantity',2215=>'NameBadge lenght is not equal to quantity',2216=>'Invalid textonticketbody',2217=>'Invalid textonticketsidebar',2218=>'Invalid NameBadgeFooter',2304=>'Shipping CountryCode is invalid',2305=>'Shipping address missed',2401=>'IP address is null',2402=>'Invalid operation',2501=>'WID is invalid',2502=>'Production transaction is not allowed. Contact support for activation.',2601=>'Invalid character in a Base-64 string',2701=>'ReferenceTransaction Information Cannot be NULL',2702=>'Invalid Refrence Transaction Information',2703=>'Expired credit card',2805=>'eCheck Account number is invalid',2807=>'Invalid payment method',2809=>'Invalid payment method',2811=>'eCheck payment type is currently not accepted',2812=>'Invalid check number',1001=>'Internal error. Retry transaction',1002=>'Error occurred on external gateway please try again',2001=>'Invalid account information',2002=>'Transaction total is not correct',2003=>'Invalid parameters',2004=>'Document is not a valid xml file',2005=>'OrderList can not be empty',3001=>'Invalid RefrenceTransactionID',3002=>'Invalid operation for this transaction',4001=>'Fraud transaction',4002=>'Duplicate transaction',5001=>'Declined (general)',5002=>'Declined (lost or stolen card)',5003=>'Declined (fraud)',5004=>'Declined (Card expired)',5005=>'Declined (Cvv2 is not valid)',5006=>'Declined (Insufficient fund)',5007=>'Declined (Invalid credit card number)');
		
	$this->country_code = array( 'DE' => '276','AT' => '040','BE' => '056','CA' => '124','CN' => '156','ES' => '724',	'FI' => '246','FR' => '250','GR' => '300', 'IT' => '380','JP' => '392','LU' => '442', 'NL' => '528','PL' => '616','PT' => '620','CZ' => '203','GB' => '826','SE' => '752', 'CH' => '756','DK' => '208','US' => '840','HK' => '344','NO' => '578','AU' => '036',	'SG' => '702','IE' => '372','NZ' => '554','KR' => '410','IL' => '376','ZA' => '710','NG' => '566','CI' => '384','TG' => '768','BO' => '068','MU' => '480','RO' => '642',	'SK' => '703','DZ' => '012','AS' => '016','AD' => '020','AO' => '024','AI' => '660',	'AG' => '028','AR' => '032','AM' => '051','AW' => '533','AZ' => '031','BS' => '044',	'BH' => '048','BD' => '050','BB' => '052','BY' => '112','BZ' => '084','BJ' => '204',	'BT' => '060','56' => '064','BW' => '072','BR' => '076','BN' => '096','BF' => '854',	'MM' => '104','BI' => '108','KH' => '116','CM' => '120','CV' => '132','CF' => '140',	'TD' => '148','CL' => '152','CO' => '170','KM' => '174','CD' => '180','CG' => '178',	'CR' => '188','HR' => '191','CU' => '192','CY' => '196','DJ' => '262','DM' => '212',	'DO' => '214','TL' => '626','EC' => '218','EG' => '818','SV' => '222','GQ' => '226',	'ER' => '232','EE' => '233','ET' => '231','FK' => '238','FO' => '234','FJ' => '242', 'GA' => '266','GM' => '270','GE' => '268','GH' => '288','GD' => '308','GL' => '304', 'GI' => '292','GP' => '312','GU' => '316','GT' => '320','GG' => '831','GN' => '324', 'GW' => '624','GY' => '328','HT' => '332','HM' => '334','VA' => '336','HN' => '340', 'IS' => '352','IN' => '356','ID' => '360','IR' => '364','IQ' => '368','IM' => '833', 'JM' => '388','JE' => '832','JO' => '400','KZ' => '398','KE' => '404','KI' => '296', 'KP' => '408','KW' => '414','KG' => '417','LA' => '418','LV' => '428','LB' => '422','LS' => '426','LR' => '430','LY' => '434','LI' => '438','LT' => '440','MO' => '446','MK' => '807','MG' => '450','MW' => '454','MY' => '458','MV' => '462','ML' => '466','MT' => '470','MH' => '584','MQ' => '474','MR' => '478','HU' => '348','YT' => '175','MX' => '484','FM' => '583','MD' => '498','MC' => '492','MN' => '496','ME' => '499','MS' => '500','MA' => '504','MZ' => '508','NA' => '516','NR' => '520','NP' => '524','BQ' => '535','NC' => '540','NI' => '558','NE' => '562','NU' => '570','NF' => '574','MP' => '580','OM' => '512','PK' => '586','PW' => '585','PS' => '275','PA' => '591','PG' => '598','PY' => '600','PE' => '604','PH' => '608','PN' => '612','PR' => '630','QA' => '634','RE' => '638','RU' => '643','RW' => '646','BL' => '652','KN' => '659', 'LC' => '662','MF' => '663','PM' => '666','VC' => '670','WS' => '882','SM' => '674',	'ST' => '678','SA' => '682','SN' => '686','RS' => '688','SC' => '690','SL' => '694','SI' => '705','SB' => '090','SO' => '706','GS' => '239','LK' => '144','SD' => '729','SR' => '740','SJ' => '744','SZ' => '748','SY' => '760','TW' => '158','TJ' => '762','TZ' => '834','TH' => '764','TK' => '772','TO' => '776','TT' => '780','TN' => '788','TR' => '792','TM' => '795','TC' => '796','TV' => '798','UG' => '800','UA' => '804','AE' => '784','UY' => '858','UZ' => '860','VU' => '548','VE' => '862','VN' => '704','VG' => '092','VI' => '850','WF' => '876','EH' => '732','YE' => '887','ZM' => '894','ZW' => '716','AL' => '008','AF' => '004','AQ' => '010','BA' => '070','BV' => '074','IO' => '086','BG' => '100','KY' => '136','CX' => '162','CC' => '166','CK' => '184','GF' => '254','PF' => '258','TF' => '260','AX' => '248','CW' => '531','SH' => '654','SX' => '534','SS' => '728','UM' => '581'		
          );
	}

	/**
     * Create and send the request
     * @param array $options array of options to be send in POST request
	 * @return clickandpledge_response response object
     */
	public function send($settings, $post, $order) {
		
		$strParam =  $this->buildXML( $settings, $post, $order );
		//echo $strParam;
		//die();
		$connect = array('soap_version' => SOAP_1_1, 'trace' => 1, 'exceptions' => 0);
		 $client = new SoapClient('https://paas.cloud.clickandpledge.com/paymentservice.svc?wsdl', $connect);
		 $soapParams = array('instruction'=>$strParam);
		 
		 $response = $client->Operation($soapParams);
//print_r($response);
//die();
		 if (($response === FALSE)) {
		  return array('status' => 'fail', 'error' => 'Connection to payment gateway failed - no data returned.');
		}
	
		$ResultCode=$response->OperationResult->ResultCode;
		$transation_number=$response->OperationResult->TransactionNumber;
		$VaultGUID=$response->OperationResult->VaultGUID; 
		
		if($ResultCode=='0')
		{
			$response_message = $response->OperationResult->ResultData;
			//Success
			$params['TransactionNumber'] = $VaultGUID;
			$params['trxn_result_code'] = $response_message;
			$params['status'] = 'Success';
			$params['ResultCode'] = $ResultCode;
			if(isset($post['clickandpledge_isRecurring']) && $post['clickandpledge_isRecurring'] != 'yes') {
				$recurringNote = __($response_message.'. <b>This was recurring transaction</b>', 'woothemes');
				$order->add_order_note( $recurringNote );
			}
		}
		else
		{
			if( in_array( $ResultCode, array( 2051,2052,2053 ) ) )
			{
				$AdditionalInfo = $response->OperationResult->AdditionalInfo;
			}
			else
			{
				if( isset( $this->responsecodes[$ResultCode] ) )
				{
					$AdditionalInfo = $this->responsecodes[$ResultCode];
				}
				else
				{
					$AdditionalInfo = 'Unknown error';
				}
			}
			$params['error'] = $AdditionalInfo;
			$params['ResultCode'] = $ResultCode;
			$params['status'] = 'Fail';			
		}
		//$items = $order->get_items();
		//print_r($params);
		//die();
		return $params;
	}
	
	function search_country( $country )
	{
		foreach ($this->country_code as $cname => $code)
		{
			if ($cname == $country)
				return $code;
		}
	}
	/**
	     * Get user's IP address
	     */
	function get_user_ip() {
		$ipaddress = '';
		 if ($_SERVER['HTTP_CLIENT_IP'])
			 $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		 else if($_SERVER['HTTP_X_FORWARDED_FOR'])
			 $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		 else if($_SERVER['HTTP_X_FORWARDED'])
			 $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		 else if($_SERVER['HTTP_FORWARDED_FOR'])
			 $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		 else if($_SERVER['HTTP_FORWARDED'])
			 $ipaddress = $_SERVER['HTTP_FORWARDED'];
		 else
			 $ipaddress = $_SERVER['REMOTE_ADDR'];

		 return $ipaddress; 
	}
	
	function safeString( $str,  $length=1, $start=0 )
	{
		return substr( htmlentities( ( $str ) ), $start, $length );
	}
	
	function buildXML( $settings, $post, $orderplaced )
	{
		$configValues = $settings;
		$params = $post;
		
		$dom = new DOMDocument('1.0', 'UTF-8');
		$root = $dom->createElement('CnPAPI', '');
		$root->setAttribute("xmlns","urn:APISchema.xsd");
		$root = $dom->appendChild($root);

		$version=$dom->createElement("Version","1.5");
		$version=$root->appendChild($version);

		$engine = $dom->createElement('Engine', '');
		$engine = $root->appendChild($engine);

		$application = $dom->createElement('Application','');
		$application = $engine->appendChild($application);

		$applicationid=$dom->createElement('ID','CnP_WooCommerce_WordPress'); //
		$applicationid=$application->appendChild($applicationid);

		$applicationname=$dom->createElement('Name','CnP_WooCommerce_WordPress'); //CnP_CiviCRM_WordPress#CnP_CiviCRM_Joomla#CnP_CiviCRM_Drupal
		$applicationid=$application->appendChild($applicationname);

		$applicationversion=$dom->createElement('Version','1.100.000.000.20140422');  //2.000.000.000.20130103 Version-Minor change-Bug Fix-Internal Release Number -Release Date
		$applicationversion=$application->appendChild($applicationversion);

		$request = $dom->createElement('Request', '');
		$request = $engine->appendChild($request);

		$operation=$dom->createElement('Operation','');
		$operation=$request->appendChild( $operation );

		$operationtype=$dom->createElement('OperationType','Transaction');
		$operationtype=$operation->appendChild($operationtype);
		
		$ipaddress=$dom->createElement('IPAddress',$this->get_user_ip());
		$ipaddress=$operation->appendChild($ipaddress);
		
		$httpreferrer=$dom->createElement('UrlReferrer',$_SERVER['HTTP_REFERER']);
		$httpreferrer=$operation->appendChild($httpreferrer);
		
		$authentication=$dom->createElement('Authentication','');
		$authentication=$request->appendChild($authentication);

		$accounttype=$dom->createElement('AccountGuid',$configValues['AccountGuid'] ); 
		$accounttype=$authentication->appendChild($accounttype);
		
		$accountid=$dom->createElement('AccountID',$configValues['AccountID'] );
		$accountid=$authentication->appendChild($accountid);
				 
		$order=$dom->createElement('Order','');
		$order=$request->appendChild($order);

		if( $configValues['OrderMode'] == 'yes' ){
		$orderMode = 'Test';
		}else{		
		$orderMode = 'Production';
		}
		$ordermode=$dom->createElement('OrderMode',$orderMode);
		$ordermode=$order->appendChild($ordermode);
				
										
		$cardholder=$dom->createElement('CardHolder','');
		$cardholder=$order->appendChild($cardholder);

		$billinginfo=$dom->createElement('BillingInformation','');
		$billinginfo=$cardholder->appendChild($billinginfo);

		$billfirst_name=$dom->createElement('BillingFirstName',$this->safeString($orderplaced->billing_first_name,50));
		$billfirst_name=$billinginfo->appendChild($billfirst_name);
					
		$billlast_name=$dom->createElement('BillingLastName',$this->safeString($orderplaced->billing_last_name,50));
		$billlast_name=$billinginfo->appendChild($billlast_name);

		if (isset($orderplaced->billing_email) && $orderplaced->billing_email != '') {
			$email = $orderplaced->billing_email;
		}

		if( $email != '' )
		{
			$bill_email=$dom->createElement('BillingEmail',$email);
			$bill_email=$billinginfo->appendChild($bill_email);
		}
				
		if( $orderplaced->billing_phone != '' )
		{
			$bill_phone=$dom->createElement('BillingPhone',$this->safeString($orderplaced->billing_phone, 50));
			$bill_phone=$billinginfo->appendChild($bill_phone);
		}
				
				
		$billingaddress=$dom->createElement('BillingAddress','');
		$billingaddress=$cardholder->appendChild($billingaddress);

		$billingaddress1=$dom->createElement('BillingAddress1',$this->safeString($orderplaced->billing_address_1,100));
		$billingaddress1=$billingaddress->appendChild($billingaddress1);
		
		if(!empty($orderplaced->billing_address_2)) {
			$billingaddress2=$dom->createElement('BillingAddress2',$this->safeString($orderplaced->billing_address_2,100));
			$billingaddress2=$billingaddress->appendChild($billingaddress2);
		}
		
		$billing_city=$dom->createElement('BillingCity',$this->safeString($orderplaced->billing_city,50));
		$billing_city=$billingaddress->appendChild($billing_city);

		$billing_state=$dom->createElement('BillingStateProvince',$this->safeString($orderplaced->billing_state,50));
		$billing_state=$billingaddress->appendChild($billing_state);
				
		$billing_zip=$dom->createElement('BillingPostalCode',$this->safeString( $orderplaced->billing_postcode,20 ));
		$billing_zip=$billingaddress->appendChild($billing_zip);
		
		$billing_country_id = '';
		if(ini_get('allow_url_fopen')) //To check if fopen is "ON"
		{
			$countries = simplexml_load_file( WP_PLUGIN_URL.DIRECTORY_SEPARATOR.plugin_basename( dirname(__FILE__)).DIRECTORY_SEPARATOR.'Countries.xml' );
			foreach( $countries as $country ){
				if( $country->attributes()->Abbrev == $orderplaced->billing_country ){
					$billing_country_id = $country->attributes()->Code;
				} 
			}
		}
		if($billing_country_id) {
		$billing_country=$dom->createElement('BillingCountryCode',str_pad($billing_country_id, 3, "0", STR_PAD_LEFT));
		$billing_country=$billingaddress->appendChild($billing_country);
		} else {
			$billing_country_id = $this->search_country($orderplaced->billing_country);
			if($billing_country_id) {
			$billing_country=$dom->createElement('BillingCountryCode',str_pad($billing_country_id, 3, "0", STR_PAD_LEFT));
			$billing_country=$billingaddress->appendChild($billing_country);
			}
		}
		
		//Shipping Address
		if( $orderplaced->shipping_address_1 != '' &&  $orderplaced->shipping_city != '' && $orderplaced->shipping_country != '' )
		{
			$shippinginfo=$dom->createElement('ShippingInformation','');
			$shippinginfo=$cardholder->appendChild($shippinginfo);
			
			$shippingaddress=$dom->createElement('ShippingAddress','');
			$shippingaddress=$shippinginfo->appendChild($shippingaddress);
			
			if( $orderplaced->shipping_address_1 != '' )
			{
				$ship_address1=$dom->createElement('ShippingAddress1',$this->safeString($orderplaced->shipping_address_1,100));
				$ship_address1=$shippingaddress->appendChild($ship_address1);
			}

			if( $orderplaced->shipping_address_2 != '' )
			{
				$ship_address2=$dom->createElement('ShippingAddress2',$this->safeString($orderplaced->shipping_address_2,100));
				$ship_address2=$shippingaddress->appendChild($ship_address2);
			}

			if( $orderplaced->shipping_city != '' )
			{
				$ship_city=$dom->createElement('ShippingCity',$this->safeString($orderplaced->shipping_city, 50));
				$ship_city=$shippingaddress->appendChild($ship_city);
			}

			if( $orderplaced->shipping_state != '' )
			{
				$ship_state=$dom->createElement('ShippingStateProvince',$this->safeString($orderplaced->shipping_state, 50));
				$ship_state=$shippingaddress->appendChild($ship_state);
			}
			
			if( $orderplaced->shipping_postcode != '' )
			{
				$ship_zip=$dom->createElement('ShippingPostalCode',$this->safeString($orderplaced->shipping_postcode, 20));
				$ship_zip=$shippingaddress->appendChild($ship_zip);
			}

			if( $orderplaced->shipping_country != '' )
			{
				$shipping_country = '';
				if(ini_get('allow_url_fopen')) //To check if fopen is "ON"
				{
					foreach( $countries as $country ){
						if( $country->attributes()->Abbrev == $orderplaced->shipping_country ){
							$shipping_country = $country->attributes()->Code;
						} 
					}
				}
				
				if($shipping_country)
				{
				$ship_country=$dom->createElement('ShippingCountryCode',str_pad($shipping_country, 3, "0", STR_PAD_LEFT));
				$ship_country=$shippingaddress->appendChild($ship_country);
				}
				else
				{
					$shipping_country = $this->search_country($orderplaced->shipping_country);
					if($shipping_country) {
						$ship_country=$dom->createElement('ShippingCountryCode',str_pad($shipping_country, 3, "0", STR_PAD_LEFT));
						$ship_country=$shippingaddress->appendChild($ship_country);
					}
				}
			}
		}//End of Shipping Address node
		
		$customfieldlist = $dom->createElement('CustomFieldList','');
		$customfieldlist = $cardholder->appendChild($customfieldlist);
			
		if( isset($orderplaced->billing_company) && $orderplaced->billing_company != '' )
		{
			$customfield = $dom->createElement('CustomField','');
			$customfield = $customfieldlist->appendChild($customfield);
				
			$fieldname = $dom->createElement('FieldName','Company Name');
			$fieldname = $customfield->appendChild($fieldname);
				
			$fieldvalue = $dom->createElement('FieldValue',$this->safeString($orderplaced->billing_company, 500));
			$fieldvalue = $customfield->appendChild($fieldvalue);
		}
		
		if( isset($orderplaced->customer_note) && $orderplaced->customer_note != '' )
		{
			$customfield = $dom->createElement('CustomField','');
			$customfield = $customfieldlist->appendChild($customfield);
				
			$fieldname = $dom->createElement('FieldName','Order Notes');
			$fieldname = $customfield->appendChild($fieldname);
				
			$fieldvalue = $dom->createElement('FieldValue',$this->safeString($orderplaced->customer_note, 500));
			$fieldvalue = $customfield->appendChild($fieldvalue);
		}
		
		$paymentmethod=$dom->createElement('PaymentMethod','');
		$paymentmethod=$cardholder->appendChild($paymentmethod);

		$payment_type=$dom->createElement('PaymentType','CreditCard');
		$payment_type=$paymentmethod->appendChild($payment_type);

		$creditcard=$dom->createElement('CreditCard','');
		$creditcard=$paymentmethod->appendChild($creditcard);
			
		if (isset($params['clickandpledge_name_on_card'])) {
			$credit_card_name = $params['clickandpledge_name_on_card'];
		}
		else {
			$credit_card_name = $params['billing_first_name'] . " ";
		if (isset($params['billing_middle_name']) && !empty($params['billing_middle_name'])) {
			$credit_card_name .= $params['billing_middle_name'] . " ";
		}
			$credit_card_name .= $params['billing_last_name'];
		}
		
		$credit_name=$dom->createElement('NameOnCard',$this->safeString( $credit_card_name, 50));
		$credit_name=$creditcard->appendChild($credit_name);
				
		$credit_number=$dom->createElement('CardNumber',$this->safeString( str_replace(' ', '', $params['clickandpledge_card_number']), 17));
		$credit_number=$creditcard->appendChild($credit_number);

		$credit_cvv=$dom->createElement('Cvv2',$params['clickandpledge_card_csc']);
		$credit_cvv=$creditcard->appendChild($credit_cvv);

		$credit_expdate=$dom->createElement('ExpirationDate',str_pad($params['clickandpledge_card_expiration_month'],2,'0',STR_PAD_LEFT) ."/" .substr($params['clickandpledge_card_expiration_year'],2,2));
		$credit_expdate=$creditcard->appendChild($credit_expdate);

		$orderitemlist=$dom->createElement('OrderItemList','');
		$orderitemlist=$order->appendChild($orderitemlist);
		
		
		$items = 0;
		$custom_fields = array();
		foreach($orderplaced->get_items() as $i => $Item) 
		{

			$metadata = get_post_meta($Item['product_id']);
			
			foreach($metadata as $key => $val) {
				if(substr($key, 0, 1) != '_')
				$custom_fields[$Item['name']][] = array($key => $val[0]);
			}
			
			if(isset($Item['variation_id']) && $Item['variation_id'] != '') {
				 $product_attributes = unserialize($metadata['_product_attributes'][0]);
				 foreach($product_attributes as $attr => $attr_val) {
					$name = $attr_val['name'];
					$custom_fields[$Item['name']][] = array($name => $Item[$name]); 
				 }				
			}
			
			$orderitem=$dom->createElement('OrderItem','');
			$orderitem=$orderitemlist->appendChild($orderitem);

			$itemid=$dom->createElement('ItemID',++$items);
			$itemid=$orderitem->appendChild($itemid);

			$itemname=$dom->createElement('ItemName',$this->safeString(trim($Item['name']), 50));
			$itemname=$orderitem->appendChild($itemname);

			$quntity=$dom->createElement('Quantity',$Item['qty']);
			$quntity=$orderitem->appendChild($quntity);

			if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
				if($params['clickandpledge_RecurringMethod'] == 'Installment') {
				$UnitPrice = (number_format(($orderplaced->get_item_total($Item)/$params['clickandpledge_Installment']),2,'.','')*100);
				$unitprice=$dom->createElement('UnitPrice', $UnitPrice);
				$unitprice=$orderitem->appendChild($unitprice);
				} else {
				$unitprice=$dom->createElement('UnitPrice',($orderplaced->get_item_total($Item)*100));
				$unitprice=$orderitem->appendChild($unitprice);
				}
			} else {
			$unitprice=$dom->createElement('UnitPrice',($orderplaced->get_item_total($Item)*100));
			$unitprice=$orderitem->appendChild($unitprice);
			}
			
			if( isset( $Item['line_tax'] ) && $Item['line_tax'] != 0 )
			{
				if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
					if($params['clickandpledge_RecurringMethod'] == 'Installment') {
					$UnitTax = number_format(($orderplaced->get_item_tax($Item)/$params['clickandpledge_Installment']),2,'.','')*100;
					$unit_tax=$dom->createElement('UnitTax', $UnitTax);
					$unit_tax=$orderitem->appendChild($unit_tax);
					} else {
					$unit_tax=$dom->createElement('UnitTax',number_format($orderplaced->get_item_tax($Item),2,'.','')*100);
					$unit_tax=$orderitem->appendChild($unit_tax);
					}
				}
				else {
				$unit_tax=$dom->createElement('UnitTax',number_format($orderplaced->get_item_tax($Item),2,'.','')*100);
				$unit_tax=$orderitem->appendChild($unit_tax);
				}
			}
			
			if( $metadata['_sku'][0] != '' ) {			
			$sku_code=$dom->createElement('SKU',substr($metadata['_sku'][0], 0, 25));
			$sku_code=$orderitem->appendChild($sku_code);
			}

		}					
				
		if(count($custom_fields)) 
		{
			foreach($custom_fields as $key => $val) 
			{
				foreach($val as $v) 
				{
					$field_name_array = array_keys($v);
					$field_value_array = array_values($v);
					
					$customfield = $dom->createElement('CustomField','');
					$customfield = $customfieldlist->appendChild($customfield);
						
					$fieldname = $dom->createElement('FieldName',$this->safeString($key . ' ('.$field_name_array[0].')', 200));
					$fieldname = $customfield->appendChild($fieldname);
						
					$fieldvalue = $dom->createElement('FieldValue',$this->safeString($field_value_array[0], 500));
					$fieldvalue = $customfield->appendChild($fieldvalue);
				}
			}
		}
				
		if(isset($orderplaced->order_shipping) && $orderplaced->order_shipping != 0){
			$shipping=$dom->createElement('Shipping','');
			$shipping=$order->appendChild($shipping);
			$ship = new WC_Shipping();
			$methods = $ship->load_shipping_methods();
			foreach($methods as $m => $v)
			{
				if($m == $post['shipping_method'][0]) {
					$shiptitle = $v;
				}
			}
			$shiptitle = $shiptitle->title;	
			$shipping_method=$dom->createElement('ShippingMethod',$shiptitle);
			$shipping_method=$shipping->appendChild($shipping_method);
			//print_r($shipp);
			if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
				if($params['clickandpledge_RecurringMethod'] == 'Installment') {
				$ShippingValue = number_format(($orderplaced->order_shipping/$params['clickandpledge_Installment']), 2, '.', '')*100;
				$shipping_value = $dom->createElement('ShippingValue', $ShippingValue);
				$shipping_value=$shipping->appendChild($shipping_value);
				} else {
				$shipping_value = $dom->createElement('ShippingValue',number_format($orderplaced->order_shipping, 2, '.', '')*100);
				$shipping_value=$shipping->appendChild($shipping_value);
				}
			} else {
			$shipping_value = $dom->createElement('ShippingValue',number_format($orderplaced->order_shipping, 2, '.', '')*100);
			$shipping_value=$shipping->appendChild($shipping_value);
			}

			if( $orderplaced->order_shipping_tax )
			{
				if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
					if($params['clickandpledge_RecurringMethod'] == 'Installment') {
					$ShippingTax = number_format( (($orderplaced->order_shipping_tax/$params['clickandpledge_Installment'])), 2, '.', '' )*100;	
					$shipping_tax=$dom->createElement('ShippingTax',$ShippingTax);
					$shipping_tax=$shipping->appendChild($shipping_tax);
					} else {
					$ShippingTax = number_format( ($orderplaced->order_shipping_tax), 2, '.', '' )*100;	
					$shipping_tax=$dom->createElement('ShippingTax',$ShippingTax);
					$shipping_tax=$shipping->appendChild($shipping_tax);
					}
				} else {
				$ShippingTax = number_format( ($orderplaced->order_shipping_tax), 2, '.', '' )*100;	
				$shipping_tax=$dom->createElement('ShippingTax',$ShippingTax);
				$shipping_tax=$shipping->appendChild($shipping_tax);
				}
			}			
		}
		
		
		$receipt=$dom->createElement('Receipt','');
		$receipt=$order->appendChild($receipt);

		$recipt_lang=$dom->createElement('Language','ENG');
		$recipt_lang=$receipt->appendChild($recipt_lang);
		
		if( $settings['OrganizationInformation'] != '')
		{
			$recipt_org=$dom->createElement('OrganizationInformation',$this->safeString($settings['OrganizationInformation'], 1500));
			$recipt_org=$receipt->appendChild($recipt_org);
		}
		
		if( $settings['ThankYouMessage'] != '')
		{
			$recipt_thanks=$dom->createElement('ThankYouMessage',$this->safeString($settings['ThankYouMessage'], 500));
			$recipt_thanks=$receipt->appendChild($recipt_thanks);
		}
		
		if( $settings['TermsCondition'] != '')
		{
			$recipt_terms=$dom->createElement('TermsCondition',$this->safeString($settings['TermsCondition'], 1500));
			$recipt_terms=$receipt->appendChild($recipt_terms);
		}

		$recipt_email=$dom->createElement('EmailNotificationList','');
		$recipt_email=$receipt->appendChild($recipt_email);			
		
		$email_notification = '';		
		if (isset($params['billing_email']) && $params['billing_email'] != '') {
			$email_notification = $params['billing_email'];
		}
							
		$email_note=$dom->createElement('NotificationEmail',$email_notification);
		$email_note=$recipt_email->appendChild($email_note);

		$transation=$dom->createElement('Transaction','');
		$transation=$order->appendChild($transation);

		$trans_type=$dom->createElement('TransactionType','Payment');
		$trans_type=$transation->appendChild($trans_type);

		$trans_desc=$dom->createElement('DynamicDescriptor','DynamicDescriptor');
		$trans_desc=$transation->appendChild($trans_desc); 
		
		
		if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' )
		{
			$trans_recurr=$dom->createElement('Recurring','');
			$trans_recurr=$transation->appendChild($trans_recurr);
			if  (isset($params['clickandpledge_indefinite']) &&  $params['clickandpledge_indefinite'] == 'on' )
			{
				$total_installment=$dom->createElement('Installment',999);
				$total_installment=$trans_recurr->appendChild($total_installment);
			}
			else
			{
				if($params['clickandpledge_Installment'] != '') {
					$total_installment=$dom->createElement('Installment',$params['clickandpledge_Installment']);
					$total_installment=$trans_recurr->appendChild($total_installment);
				} else {
					$total_installment=$dom->createElement('Installment',1);
					$total_installment=$trans_recurr->appendChild($total_installment);
				}
			}			
			$total_periodicity=$dom->createElement('Periodicity',$params['clickandpledge_Periodicity']);
			$total_periodicity=$trans_recurr->appendChild($total_periodicity);
			
			if( isset($params['clickandpledge_RecurringMethod']) ) {
				$RecurringMethod=$dom->createElement('RecurringMethod',$params['clickandpledge_RecurringMethod']);
				$RecurringMethod=$trans_recurr->appendChild($RecurringMethod);
			} else {
				$RecurringMethod=$dom->createElement('RecurringMethod','Subscription');
				$RecurringMethod=$trans_recurr->appendChild($RecurringMethod);
			}	
		}
		
		$trans_totals=$dom->createElement('CurrentTotals','');
		$trans_totals=$transation->appendChild($trans_totals);
		
		//Discount Calculation
		$order_discount = 0;
		$cart_discount = 0;		
		if( isset($orderplaced->order_discount) && $orderplaced->order_discount != 0 )
		{
			$order_discount = $orderplaced->order_discount;			
        }
		if( isset($orderplaced->cart_discount) && $orderplaced->cart_discount != 0 )
		{
			$cart_discount = $orderplaced->cart_discount;
		}
		if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
			if($params['clickandpledge_RecurringMethod'] == 'Installment') {
				$TotalDiscount = ($order_discount + $cart_discount)/$params['clickandpledge_Installment'];
				$TotalDiscount = number_format($TotalDiscount, 2, '.', '')*100;
			} else {
				$TotalDiscount = $order_discount + $cart_discount;		
			}
		} else {
		$TotalDiscount = $order_discount + $cart_discount;		
		}
		if($TotalDiscount) {		
		$total_discount=$dom->createElement('TotalDiscount', $TotalDiscount);
		$total_discount=$trans_totals->appendChild($total_discount);
		}
		//echo '<pre>';
		//print_r($orderplaced);
		//die();
		//Tax Calculation
		$order_tax = 0;
		$order_shipping_tax = 0;
		if( isset($orderplaced->order_tax) && $orderplaced->order_tax != 0 )
		{
			$order_tax = $orderplaced->order_tax;
		}
		
		if( isset($orderplaced->order_shipping_tax) && $orderplaced->order_shipping_tax != 0 )
		{
			$order_shipping_tax = $orderplaced->order_shipping_tax;
		}
		$TotalTax = $order_tax+$order_shipping_tax;		
		if($TotalTax) {
			if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
				if($params['clickandpledge_RecurringMethod'] == 'Installment') {
				$TotalTax = number_format(($TotalTax/$params['clickandpledge_Installment']), 2, '.', '')*100;
				$total_tax=$dom->createElement('TotalTax', $TotalTax);
				$total_tax=$trans_totals->appendChild($total_tax);
				} else {
				$total_tax=$dom->createElement('TotalTax',number_format($TotalTax, 2, '.', '')*100);
				$total_tax=$trans_totals->appendChild($total_tax);
				}
			} else {
			$total_tax=$dom->createElement('TotalTax',number_format($TotalTax, 2, '.', '')*100);
			$total_tax=$trans_totals->appendChild($total_tax);
			}
		}
			
		if( isset($orderplaced->order_shipping) && $orderplaced->order_shipping != 0 )
		{
			if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
				if($params['clickandpledge_RecurringMethod'] == 'Installment') {
				$TotalShipping = number_format(($orderplaced->order_shipping/$params['clickandpledge_Installment']), 2, '.', '')*100;
				$total_ship=$dom->createElement('TotalShipping', $TotalShipping);
				$total_ship=$trans_totals->appendChild($total_ship);
				} else {
				$total_ship=$dom->createElement('TotalShipping',number_format($orderplaced->order_shipping, 2, '.', '')*100);
				$total_ship=$trans_totals->appendChild($total_ship);
				}
			} else {
			$total_ship=$dom->createElement('TotalShipping',number_format($orderplaced->order_shipping, 2, '.', '')*100);
			$total_ship=$trans_totals->appendChild($total_ship);
			}
		}
		
		if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
			if($params['clickandpledge_RecurringMethod'] == 'Installment') {
			$Total = ( $UnitPrice + $UnitTax + $ShippingValue + $ShippingTax ) - ($TotalDiscount);
			//$Total = $UnitPrice + $UnitTax + $ShippingValue + $ShippingTax;	
			$total_amount=$dom->createElement('Total', $Total);
			$total_amount=$trans_totals->appendChild($total_amount);
			} else {
			$total_amount=$dom->createElement('Total',($orderplaced->order_total*100));
			$total_amount=$trans_totals->appendChild($total_amount);
			}
		} else {
		$total_amount=$dom->createElement('Total',($orderplaced->order_total*100));
		$total_amount=$trans_totals->appendChild($total_amount);
		}
		
		if(count($orderplaced->get_used_coupons())) {
			$usercoupons = $orderplaced->get_used_coupons();
			$couponcode="";
			for($c = 0; $c < count($usercoupons); $c++) {
				$couponcode.= $usercoupons[$c];
				$couponcode.= ";";
			}
			if( $couponcode != '' ) {
			$trans_coupon=$dom->createElement('CouponCode',substr($couponcode,0,-1));
			$trans_coupon=$transation->appendChild($trans_coupon);
			}
		}
		
		if( $TotalDiscount )
		{
			if( isset($params['clickandpledge_isRecurring']) &&  $params['clickandpledge_isRecurring'] == 'on' ) {
				if($params['clickandpledge_RecurringMethod'] == 'Installment') {
				$TransactionDiscount =$TotalDiscount;
				$trans_coupon_discount=$dom->createElement('TransactionDiscount', $TransactionDiscount);
				$trans_coupon_discount=$transation->appendChild($trans_coupon_discount);
				} else {
				$trans_coupon_discount=$dom->createElement('TransactionDiscount',number_format($TotalDiscount, 2, '.', '')*100);
				$trans_coupon_discount=$transation->appendChild($trans_coupon_discount);
				}
			} else {
			$trans_coupon_discount=$dom->createElement('TransactionDiscount',number_format($TotalDiscount, 2, '.', '')*100);
			$trans_coupon_discount=$transation->appendChild($trans_coupon_discount);
			}
		}
		
		$strParam =$dom->saveXML();

		//echo $strParam;
		//die();
		return $strParam;
	  }
}
?>