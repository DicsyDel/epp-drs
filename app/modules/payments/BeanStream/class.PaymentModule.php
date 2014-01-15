<?php
	class BeanStreamPaymentModule extends AbstractPaymentModule implements IDirectPaymentModule
	{
		/**
		 * Module config
		 *
		 * @var DataForm
		 */
		public $Config;
		
		/**
		 * @var int Order ID
		 */
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
			return "BeanStream";
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
			$ConfigurationForm->AppendField( new DataFormField("merchantID", FORM_FIELD_TYPE::TEXT, "Merchant ID"));
			$ConfigurationForm->AppendField( new DataFormField("gateURL", FORM_FIELD_TYPE::TEXT , "Processing URL", 1));
						
			return $ConfigurationForm;
		}
		
		/**
		 * Must return a DataForm object  that will be used to draw a payment form for this module.
		 * @return DataForm object. 
		 */
		public function GetPaymentForm()
		{			
			$ConfigurationForm = new DataForm();
			
			$ConfigurationForm->SetInlineHelp(_("This module requires additional actions from you outside EPP-DRS. <br/>&bull; Log into your Beanstream merchant account control panel. <br>&bull; Go to administration->account settings->order settings and ensure that 'Require CVD number for credit card transactions' checkbox is ticked."));
			
			$db = self::GetDBInstance();
			
			/**
			 * Credit card information
			 */
			$ConfigurationForm->AppendField( new DataFormField("cc_owner", FORM_FIELD_TYPE::TEXT, "Credit Card Owner", 1));
			$ConfigurationForm->AppendField( new DataFormField("ccn", FORM_FIELD_TYPE::TEXT, "Credit Card Number", 1));
			$ConfigurationForm->AppendField( new DataFormField("Expdate", FORM_FIELD_TYPE::DATE , "Credit Card Expiration", 1));
			$ConfigurationForm->AppendField( new DataFormField("cvv", FORM_FIELD_TYPE::TEXT , "Credit Card CVV Number", 1));			
			
			/**
			 * Billing information
			 */
			
			// Get Countries list
			foreach ($db->GetAll("SELECT * FROM countries") as $c)
				$countries[$c["code"]] = $c["name"];
				
			// Get states list
			$states["--"] = "Outside U.S./Canada";
			foreach ($db->GetAll("SELECT * FROM states") as $s)
				$states[$s["code"]] = $s["name"];
				
			
			$ConfigurationForm->AppendField( new DataFormField("email", FORM_FIELD_TYPE::TEXT , "Email", 1));			
			$ConfigurationForm->AppendField( new DataFormField("name", FORM_FIELD_TYPE::TEXT , "Full name", 1));			
			$ConfigurationForm->AppendField( new DataFormField("phone", FORM_FIELD_TYPE::TEXT , "Phone", 1));			
			$ConfigurationForm->AppendField( new DataFormField("address", FORM_FIELD_TYPE::TEXT , "Street 1", 1));			
			$ConfigurationForm->AppendField( new DataFormField("address2", FORM_FIELD_TYPE::TEXT , "Street 2", 0));			
			$ConfigurationForm->AppendField( new DataFormField("city", FORM_FIELD_TYPE::TEXT , "City", 1));			
			$ConfigurationForm->AppendField( new DataFormField("state", FORM_FIELD_TYPE::SELECT , "Province", 1, $states));			
			$ConfigurationForm->AppendField( new DataFormField("zipcode", FORM_FIELD_TYPE::TEXT , "Postal code", 1));			
			$ConfigurationForm->AppendField( new DataFormField("country", FORM_FIELD_TYPE::SELECT , "Country", 1, $countries));			
			
			return $ConfigurationForm;
		}
		
		/**
		 * Called to validate either user filled all fields of your form properly.
		 * If you return true, ProccessPayment will be called. If you return array, user will be presented with values of this array as errors. 
		 *
		 * @param array $post_values
		 * @return true or array of error messages.
		 */
		public function ValidatePaymentFormData($post_values)
		{
			$retval = array();
		    
		    if (!preg_match("/^[0-9]{12,20}$/", $post_values["ccn"]))
                $retval[] = _("Incorrect credit card number");
                
		    if ((int)$post_values["Expdate_m"] < 0 || (int)$post_values["Expdate_m"] > 12)
		        $retval[] = _("Incorrect Expiration date");
		        
		    if (!preg_match("/^[0-9]{2}$/", $post_values["Expdate_Y"]))
		        $retval[] = _("Incorrect Expiration date");
		        
		    if (!preg_match("/^[0-9]{3,4}$/", $post_values["cvv"]))
		        $retval[] = _("Incorrect CVC code");
		        
		    if (!$post_values["email"])
		    	$retval[] = _("E-mail required");
		    	
		    if (!$post_values["name"])
		    	$retval[] = _("Full name required");

		    if (!$post_values["address"])
		    	$retval[] = _("Address required");

		    if (!$post_values["phone"])
		    	$retval[] = _("Phone required");
		    	
		   	if (!$post_values["city"])
		    	$retval[] = _("City required");
		    	
		    if (!$post_values["zipcode"])
		    	$retval[] = _("Postal required");
		    	     
		    return (count($retval) > 0) ? $retval : true;
		}
		
		/**
		 * This method is called when user submits a payment form. 
		 * @param float $amount Purchase amount
		 * @param int $orderid Order ID. Can be used as an unique identifier.
		 * @param string $payment_for Human-readable description of the payment
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public final function ProcessPayment(Order $order, $post_values = array())
		{
		    $this->OrderID = $order->ID;
			
			$data = array(
		    				"requestType"		=> "BACKEND",
		    				"merchant_id"		=> $this->Config->GetFieldByName("merchantID")->Value,
		    				"trnCardOwner"		=> $post_values["cc_owner"],
		    				"trnCardNumber"		=> $post_values["ccn"],
		    				"trnExpMonth"		=> $post_values["Expdate_m"],
		    				"trnExpYear"		=> $post_values["Expdate_Y"],
		    				"trnCardCvd"		=> $post_values["cvv"],
		    				"trnOrderNumber"	=> microtime(true),
		    				"trnAmount"			=> number_format($order->GetBillingTotal(), 2, '.', ''),
		    				"ordEmailAddress"	=> $post_values["email"],
		    				"ordName"			=> $post_values["name"],
		    				"ordPhoneNumber"	=> $post_values["phone"],
		    				"ordAddress1"		=> $post_values["address"],
		    				"ordAddress2"		=> $post_values["address2"],
		    				"ordCity"			=> $post_values["city"],
		    				"ordProvince"		=> $post_values["state"],
		    				"ordPostalCode"		=> $post_values["zipcode"],
		    				"ordCountry"		=> $post_values["country"],
		    				"ref1"				=> $order->ID,
		    				"ref2"				=> $order->Description
		    			);		        
            try
            {   
			    $ch = curl_init();
			    
			    // Enable SSL
			    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,1);
			    
				// set URL and other appropriate options
				curl_setopt($ch, CURLOPT_URL, $this->Config->GetFieldByName("gateURL")->Value);
				curl_setopt($ch, CURLOPT_HEADER, 0);
	            
				curl_setopt($ch, CURLOPT_POST,1);
	            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
	            
	            $response = curl_exec($ch);
	            $error = curl_error($ch);
	            curl_close($ch);
	            
	            if (!$error)
	            {
	            	Log::Log(sprintf(_("BeanStream response: %s"), $response), E_USER_NOTICE);
	            	
	            	mparse_str($response, $response);
	            	if ($response["trnApproved"] == 0)
	            	{
	            		$this->FailureReason = trim(strip_tags(str_replace("<LI>", "\n", htmlspecialchars_decode($response["messageText"]))));
	            	}
	            	else 
	            	{
	            		$result = true;
	            	}
	            }
	            else 
	            	$this->FailureReason = sprintf(_("Request to BeanStream failed: %s"), $error);
            }
            catch (Exception $e)
            {
            	$this->FailureReason = sprintf(_("Request to BeanStream failed: %s"), $e->getMessage());
            }
            
            return ($result) ? true : false;
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