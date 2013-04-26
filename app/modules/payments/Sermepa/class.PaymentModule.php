<?
	class SermepaPaymentModule extends AbstractPaymentModule implements IPostBackPaymentModule
	{
		/**
		 * Module config
		 *
		 * @var DataForm
		 */
		public $Config;
		
	
		/**
		* Do whetever needed here. 
		* @param $Config DataForm object, filled with values
		* @return void
		*/
		public function InitializeModule($Config)
		{
			$this->Config = $Config;
		}
		
		/**
		* Returns module name.
		* @return string
		*/
		public function GetModuleName()
		{
			return "Sermepa";
		}
		
		public function CheckSignature($response)
		{
			return (
    				$response['Ds_Response'] && 
    				$response['Ds_Signature'] && 
    				$response['Ds_Amount'] &&
    				$response['Ds_MerchantCode'] &&
    				$response['Ds_Currency'] &&
    				$response['Ds_Order']
				   );
		}		
		
		/**
		 * Must return Order ID. You passed it to payment gateway in RedirectToGateway(), remember? Now it should return it back, so you know what this payment is for. And you return it.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return int order ID
		 */
		public function GetOrderID($response)
		{
			return (int)$response['Ds_Order'];
		}
		
		
		/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetConfigurationForm()
		{						
			$Config = new DataForm();
			$Config->AppendField(new DataFormField("MerchantCode", FORM_FIELD_TYPE::TEXT, "Merchant code"));
			$Config->AppendField(new DataFormField("MerchantTerminal", FORM_FIELD_TYPE::TEXT, "Merchant terminal", 
					false, null, "001"));
			$Config->AppendField(new DataFormField("SecretWord", FORM_FIELD_TYPE::TEXT, "Secret word"));					
			$Config->AppendField(new DataFormField("Currency", FORM_FIELD_TYPE::SELECT, "Currency symbol", 
					false, array(978 => "EUR", 840 => "USD", 826 => "GBP", 392 => "JPY"), 978));
			$Config->AppendField(new DataFormField("IsDemo", FORM_FIELD_TYPE::CHECKBOX , "Test mode", 1));
						
			return $Config;
		}
		
		/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
			/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetPaymentForm()
		{
			return false;
		}
		
		/**
		 * This method is called to validate either user filled all fields of your form properly.
		 * If you return true, ProccessPayment will be called. If you return array, user will be presented with values of this array as errors. 
		 *
		 * @param array $post_values
		 * @return true or array of error messages.
		 */
		public function ValidatePaymentFormData($field_values)
		{
			return true;
		}
		
		public function OnPaymentComplete($request)
		{
           	$string2sign = "{$request['Ds_Amount']}{$request['Ds_Order']}{$request['Ds_MerchantCode']}"
           			. "{$request['Ds_Currency']}{$request['Ds_Response']}"
           			. $this->Config->GetFieldByName("SecretWord")->Value;
           	$signature = strtoupper(sha1($string2sign));

           	$result = $signature == $request['Ds_Signature'] && !$request['Ds_ErrorCode'];
           	
           	if ($result) 
           	{
           		return true;
           	} 
           	else
           	{
           		$this->FailureReason = $request['Ds_ErrorCode'] ? 
           			sprintf(_("Payment failed with error code %s"), $request['Ds_ErrorCode']) :
           			_("Response signature validation failed");
           		return false;
           	}
		}		
		
		public final function RedirectToGateway(Order $order, $post_values = array())
		{
			// Amount in cents.
		    $amount = (int)($order->GetBillingTotal()*100);

		    // 12 digit order number
		    $order_id = $order->ID;
		    $order_id = sprintf('%012s', $order_id);
		    
		    /*
		    if (($len = strlen("{$order_id}")) < 4)
		    {
		    	$e = 4-$len;
		    	$order_id .= mt_rand(pow(10, $e-1), pow(10, $e)-1);
		    }
		    $order_id .= strtoupper(substr(md5(uniqid(rand(), true)), 0, 12 - strlen($order_id)));
		    */
		    
		    $merchant_code = $this->Config->GetFieldByName("MerchantCode")->Value;
		    $merchant_terminal = $this->Config->GetFieldByName("MerchantTerminal")->Value;
		    $secret_word = $this->Config->GetFieldByName("SecretWord")->Value;
		    $currency = $this->Config->GetFieldByName("Currency")->Value;
		    $expire_date = "{$post_values["ExpDate_Y"]}{$post_values["ExpDate_m"]}";
		    $trans_type = "A";
		    	    
		    if ($this->Config->GetFieldByName("IsDemo")->Value == 1)
		    {
		    	$url = "https://sis-i.sermepa.es:25443/sis/realizarPago";
		    }
            else
            { 
                $url = "https://sis.sermepa.es/sis/realizarPago";
            }
		    	    
           	$string2sign = "{$amount}{$order_id}{$merchant_code}{$currency}{$secret_word}";
           	$signature = sha1($string2sign);
            	
           	$request = array
           	(
           		"Ds_Merchant_Amount" => $amount,
           		"Ds_Merchant_Currency" => $currency,
           		"Ds_Merchant_Order" => $order_id,
           		"Ds_Merchant_ProductDescription" => "",
           		"Ds_Merchant_ConsumerLanguage" => "002",
           		"Ds_Merchant_MerchantCode" => $merchant_code,
           		"Ds_Merchant_Terminal" => $merchant_terminal,
           		"Ds_Merchant_MerchantURL" => CONFIG::$IPNURL,
           		"Ds_Merchant_UrlOK" => CONFIG::$PDTURL,
           		"Ds_Merchant_MerchantSignature" => $signature 
           	);
           	Log::Log("Sermepa request: " . http_build_query($request), E_USER_NOTICE);
           	CoreUtils::RedirectPOST($url, $request);
		}
		
		public function GetDeferredPaymentURL(Order $order, $post_values = array())
		{
			// 2co DOES support this, so would be cool to implement.
			return false;
		}		
		
		/**
		 * This method is called if payment failes.
		 * Must return a string with explanation of a payment reason.
		 * @return string
		 */
		public function GetFailureReason()
		{
			return $this->FailureReason;
		}
		
		public function GetMinimumAmount()
		{
			return false;
		}
	}
?>
