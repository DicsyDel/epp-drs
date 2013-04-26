<?

	class SetComPaymentModule extends AbstractPaymentModule implements IPostBackPaymentModule
	{
		/**
		 * Module config
		 *
		 * @var DataForm
		 */
		public $Config;

		private $OrderID;
		
		/**
		 * This methof is called on early module initialization stage. 
		 * @param DataForm $ConfigDataForm DataForm object that you returned in GetConfigurationForm(), filled with values.
		 * @return void
		 */
		public function InitializeModule($Config)
		{
			$this->Config = $Config;
		}
		
		/**
		 * Must return module name.
		 * @return string Module name
		 */
		public function GetModuleName()
		{
			return "SetCom";
		}
		
		/**
		 * This method is called in postback routine, to check either this (and not some other) module must process this postback.
		 * If you return true, OnPaymentComplete() will be called.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool true, if it does seem that payment postback is posted for this module.
		 */
		public function CheckSignature($request)
		{
			return (
    				$request['tnxid'] && 
    				$request['checksum'] && 
    				$request['parity']
				   );
		}
		
		/**
		 * Must return Order ID. You passed it to payment gateway in RedirectToGateway(), remember? Now it should return it back, so you know what this payment is for. And you return it.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return int order ID
		 */
		public function GetOrderID($request)
		{
			return $this->OrderID;
		}
		
		/**
		 * Must return a DataForm object that will be used to draw a configuration form for this module.
		 * @return DataForm object
		 */
		public function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			
			$ConfigurationForm->SetInlineHelp(sprintf("You must configure your Setcom account before you can use this module. <br>Log in to SetCom, go to <b>Profile->Selling Preferences->Website Payment Preferences</b>, enable 'Auto-Redirect' and set 'Redirect URL' to <b>%s</b>", CONFIG::$SITE_URL."/ipn.php"));
			
			$ConfigurationForm->AppendField( new DataFormField("MerchantIdentifier", FORM_FIELD_TYPE::TEXT, "Merchant Identifier", 1));
			$ConfigurationForm->AppendField( new DataFormField("CurrencyAlphaCode", FORM_FIELD_TYPE::TEXT, "Currency", 1, null, null, null, "Available values is: USD, EUR, GBP, ZAR"));
			$ConfigurationForm->AppendField( new DataFormField("Username", FORM_FIELD_TYPE::TEXT , "Username", 1));
			$ConfigurationForm->AppendField( new DataFormField("Password", FORM_FIELD_TYPE::TEXT , "Password", 1));
			
			return $ConfigurationForm;
		}
		
		/**
		 * Must return a DataForm object  that will be used to draw a payment form for this module.
		 * @return DataForm object. 
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
		public function ValidatePaymentFormData($post_values)
		{
			return true;
		}
		
		/**
		 * This method is called when we received a postback from payment proccessor and CheckSignature() returned true. 
		 *
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public function OnPaymentComplete($request)
		{
			$res = $this->PostBack($request);
			
			Log::Log(sprintf("Response from server: %s", $res), E_USER_NOTICE);
			
			$res_xml = simplexml_load_string($res);

			if ($res && $res_xml instanceof SimpleXMLElement && $res_xml->data)
			{
				$response = simplexml_load_string((string)$res_xml->data->string);
				
				$this->OrderID = (int)$response->seller->reference;
				
				if ((string)$response->outcome->status == 'Complete')
					return true;
				else
				{
					$this->FailureReason = (string)$response->outcome->error_desc;
			    	return false;
				}
			}
			else
			{
				$this->FailureReason = _("Payment notify validation falied.");
			    return false;
			}
		}
		
		/**
		 * Redirect user to gateway payment form, using HTTP 'Location:' header or UI::RedirectPOST($host, $values);
		 * 
		 * @param float $amount Purchase amount
		 * @param int $orderid Order ID. Can be used as an unique identifier.
		 * @param string $payment_for Human-readable description of the payment
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return void
		 */
		public final function RedirectToGateway(Order $order, $post_values = array())
		{
			$host = "https://www.monsterpay.com/secure/";
			
			$data = array(
							"ButtonAction" 			=> "buynow",
							"MerchantIdentifier"	=> $this->Config->GetFieldByName("MerchantIdentifier")->Value,
							"CurrencyAlphaCode"		=> $this->Config->GetFieldByName("CurrencyAlphaCode")->Value,
							"MerchCustom"			=> $order->ID,
							"TemplateID"			=> "",
							"BuyerInformation"		=> "",
							"Title"					=> "",
							"FirstName"				=> "",
							"LastName"				=> "",
							"Address1"				=> "",
							"Address2"				=> "",
							"City"					=> "",
							"State"					=> "",
							"PostalCode"			=> "",
							"Country"				=> "",
							"Email"					=> "",
							"LIDSKU"				=> $order->ID,
							"LIDDesc"				=> $order->Description,
							"LIDPrice"				=> number_format($order->GetBillingTotal(), 2, '.', ''),
							"LIDQty"				=> 1,
							"ShippingRequired"		=> 0,
							"Option1name"			=> "ORDERID",
							"Option1value"			=> $order->ID
						);
			
			//if ($post_values["return_url"]) $data["x_receipt_link_url"] = $post_values["return_url"];
			//if ($post_values["product_id"]) $data["product_id"] = $post_values["product_id"];
			
			//if ($this->Config->GetFieldByName("isdemo")->value == 1) $data["demo"] = "Y";
			
			UI::RedirectPOST($host, $data);
			exit();
		}
		
		/**
		 * This method is called if you return false in OnPaymentComplete();
		 * If your payment gateway supports this, you can provide a user with an URL, which can be used to pay this invoice later. This URL will be emailed to user.
		 * This most likely will be the URL that you are redirecting to inside RedirectToGateway().
		 * @param float $amount Purchase amount
		 * @param int $orderid Order ID. Can be used as an unique identifier.
		 * @param string $payment_for Human-readable description of the payment
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return string URL of the payment page, with all parameters.
		 */
		public function GetDeferredPaymentURL(Order $order, $post_values = array())
		{
			return false;
		}
		
		/**
		* Send postback to PayPal server
		* @return string $res
		*/
		private final function PostBack($request)
		{
			$params = array(
								"Method" 		=> "order_synchro",
								"Identifier"	=> $this->Config->GetFieldByName("MerchantIdentifier")->Value,
								"Usrname"		=> $this->Config->GetFieldByName("Username")->Value,
								"Pwd"			=> $this->Config->GetFieldByName("Password")->Value,
								"tnxid"			=> $request['tnxid'],
								"checksum"		=> $request['checksum'],
								"parity"		=> $request['parity']
							);
			$req = http_build_query($params);	

			$postback_url = "https://www.monsterpay.com/secure/components/synchro.cfc?wsdl&{$req}";
			
			Log::Log(sprintf("Sending Postback: %s", $postback_url), E_USER_NOTICE);
			
			return @file_get_contents($postback_url);
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
			return 5;
		}
	}

?>