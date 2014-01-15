<?php
	class ProxyPay3PaymentModule extends AbstractPaymentModule implements IDirectPaymentModule
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
			return "ProxyPay3";
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
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetConfigurationForm()
		{						
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("merchantID", FORM_FIELD_TYPE::TEXT, "Merchant ID"));
			$ConfigurationForm->AppendField( new DataFormField("password", FORM_FIELD_TYPE::TEXT, "Password hash", null, null, null, null, "Password hash, provided by ProxyPay. Note: this is NOT the plain password."));
			$ConfigurationForm->AppendField( new DataFormField("csymbol", FORM_FIELD_TYPE::TEXT, "Currency symbol", false, array(), 978));
			$ConfigurationForm->AppendField( new DataFormField("isdemo", FORM_FIELD_TYPE::CHECKBOX , "Test mode", 1));
						
			return $ConfigurationForm;
		}
		
		/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetPaymentForm()
		{			
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("ccn", FORM_FIELD_TYPE::TEXT, "Credit Card Number", 1));
			$ConfigurationForm->AppendField( new DataFormField("ctype", FORM_FIELD_TYPE::SELECT, "Credit Card Type", 1, array('VISA' => 'VISA', 'American Express' => 'American Express (AMEX)', 'Euroline' => 'Euroline', 'MasterCard' => 'MasterCard')));
			$ConfigurationForm->AppendField( new DataFormField("Expdate", FORM_FIELD_TYPE::DATE , "Credit Card Expiration", 1));
			$ConfigurationForm->AppendField( new DataFormField("cvv", FORM_FIELD_TYPE::TEXT , "Credit Card CVV Number", 1));			
			
			return $ConfigurationForm;
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
			$retval = array();
		    
		    if (!preg_match("/^[0-9]{16}$/", $post_values["ccn"]))
                $retval[] = _("Incorrect credit card number");
                
		    if ((int)$post_values["Expdate_m"] < 0 || (int)$post_values["Expdate_m"] > 12)
		        $retval[] = _("Incorrect Expiration date");
		        
		    if (!preg_match("/^[0-9]{2}$/", $post_values["Expdate_Y"]))
		        $retval[] = _("Incorrect Expiration date");
		        
		    if (!preg_match("/^[0-9]{3,4}$/", $post_values["cvv"]))
		        $retval[] = _("Incorrect CVC code");
		    		        
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
			
			// Generate Merchant Reference
			$merchant_reference = "REF ".implode("", explode(" ", microtime()));
			
			// Amount in cents.
		    $amount = (int)($order->GetBillingTotal()*100);
    		
		    // Generate Request.
		    $request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                        <JProxyPayLink>
                        	<Message>
                        		<Type>PreAuth</Type>
                        		<Authentication>
                        			<MerchantID>{$this->Config->GetFieldByName("merchantID")->Value}</MerchantID>
                        		       <Password>{$this->Config->GetFieldByName("password")->Value}</Password>
                        		</Authentication>
                        		<OrderInfo>
                        			<Amount>{$amount}</Amount>
                        			<MerchantRef>{$merchant_reference}</MerchantRef>
                        			<MerchantDesc>".htmlspecialchars($order->Description)."</MerchantDesc>
                        			<Currency>{$this->Config->GetFieldByName("csymbol")->Value}</Currency>
                        			<CustomerEmail>{$post_values["email"]}</CustomerEmail>
                        			<Var1>InvoiceID: {$order->ID}</Var1>
                        			<Var2 />
                        			<Var3 />
                        			<Var4 />
                        			<Var5 />
                        			<Var6 />
                        			<Var7 />
                        			<Var8 />
                        			<Var9 />
                        		</OrderInfo>
                        		<PaymentInfo>
                             		<CCN>{$post_values["ccn"]}</CCN>
                             		<Expdate>{$post_values["Expdate_m"]}{$post_values["Expdate_Y"]}</Expdate>
                             		<CVCCVV>{$post_values["cvv"]}</CVCCVV>
                             		<InstallmentOffset>0</InstallmentOffset>
                             		<InstallmentPeriod>0</InstallmentPeriod>
                             	</PaymentInfo>
                            </Message>
                            </JProxyPayLink>
                        ";
			Log::Log("ProxyPay3 request: " . $request, E_USER_NOTICE);
		    	    
		    if ($this->Config->GetFieldByName("isdemo")->Value == 1)
                $URL = "https://eptest.eurocommerce.gr/proxypay/apacsonline";
            else 
                $URL = "https://ep.eurocommerce.gr/proxypay/apacsonline";
		    	    
            try
            {   
			    $ch = curl_init();
			    
			    // Enable SSL
			    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,1);
			    
				// set URL and other appropriate options
				curl_setopt($ch, CURLOPT_URL, $URL);
				curl_setopt($ch, CURLOPT_HEADER, 0);
	            
				$params = array("APACScommand" => "NewRequest", "Data" => $request);     
	            
	            curl_setopt($ch, CURLOPT_POST,1);
	            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
	            
	            $response = curl_exec($ch);
	            $error = curl_error($ch);
	            curl_close($ch);
	            
	            if (!$error)
	            {
	            	Log::Log(sprintf(_("ProxyPay3 response: %s"), $response), E_USER_NOTICE);
	            	
	            	$response = simplexml_load_string($response);
	            	if (!$response || intval($response->ERRORCODE) != 0)
				        $this->FailureReason = sprintf(_("Cannot proceed request. Error code: %s. Please contact Administrator."), $response->ERRORCODE);
				    else
				        $result = true;
	            }
	            else 
	            	$this->FailureReason = sprintf(_("Request to ProxyPay3 failed: %s"), $error);
            }
            catch (Exception $e)
            {
            	$this->FailureReason = sprintf(_("Request to ProxyPay3 failed: %s"), $e->getMessage());
            }
            
            if ($result)
			    return true;
			else
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
