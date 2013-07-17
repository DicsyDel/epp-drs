<?
	class TwoCheckoutPaymentModule extends AbstractPaymentModule implements IPostBackPaymentModule 
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
			return "2Checkout";
		}
		
		/**
		 * This method is called in postback routine, to check either this (and not some other) module must process this postback.
		 * If you return true, OnPaymentComplete() will be called.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool true, if it does seem that payment postback is posted for this module.
		 */
		public function CheckSignature($args)
		{
			return (isset($args['credit_card_processed']) && 
					isset($args['order_number']) && 
					isset($args['total']) && 
					isset($args['key']) && 
					isset($args['merchant_order_id'])
				   );
		}
		
		/**
		 * Must return Order ID. You passed it to payment gateway in RedirectToGateway(), remember? Now it should return it back, so you know what this payment is for. And you return it.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return int order ID
		 */
		public function GetOrderID($request)
		{
			return $request["cart_id"];
		}
		
		/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetConfigurationForm()
		{			
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->SetInlineHelp(sprintf("You must configure your 2checkout account before you can use this module. <br>Log in to 2Checkout, go to 'Look and Feel' and set both 'Approved URL' and 'Pending URL' to <b>%s</b><br><b>Secret key</b> you can find on 'Look and Feel page as well.'", CONFIG::$SITE_URL."/ipn.php"));
			$ConfigurationForm->AppendField( new DataFormField("hash", FORM_FIELD_TYPE::TEXT, "Secret key"));
			$ConfigurationForm->AppendField( new DataFormField("merchid", FORM_FIELD_TYPE::TEXT, "Merchant ID"));
			$ConfigurationForm->AppendField( new DataFormField("isdemo", FORM_FIELD_TYPE::CHECKBOX , "Test mode", 1));
			
			return $ConfigurationForm;
		}
		
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
		
		/**
		 * This method is called when we received a postback from payment proccessor and CheckSignature() returned true. 
		 *
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public function OnPaymentComplete($request)
		{
			// temporary commented checking the `key` parameter 
			$result = strtoupper( md5($this->Config->GetFieldByName("hash")->Value . $this->Config->GetFieldByName("merchid")->Value . $request["order_number"] . $request["total"])) == $request["key"];
			$result &= ($request['credit_card_processed'] == 'Y');
			$result |= (bool)$this->Config->GetFieldByName("isdemo")->Value;			
			
			if ($result)
			    return true;
			else
			{
				$this->FailureReason = _("Payment notify validation failed.");
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
			$host = "https://www.2checkout.com/checkout/purchase";
			
			$data = array(
							"sid"			=> $this->Config->GetFieldByName("merchid")->Value,
							"cart_order_id"	=> $order->ID,
							"total"			=> number_format($order->GetBillingTotal(), 2, '.', ''),
							"card_holder_name" => $post_values["firstname"] . " " . $post_values["lastname"],
							"street_address" => $post_values["address"],
							"city" 			=> $post_values["city"],
							"state" 		=> $post_values["state"],
							"zip" 			=> $post_values["zip"],
							"country" 		=> $post_values["country"],
							"email" 		=> $post_values["email"],
							"phone" 		=> $post_values["phone"],
							"ship_street_address" => $post_values["address"],
							"ship_city" 	=> $post_values["city"],
							"ship_zip" 		=> $post_values["zip"],
							"ship_country" 	=> $post_values["country"],
							"ship_state" 	=> $post_values["state"],
							"Product_description" => $order->Description,
							"tco_currency"	=> CONFIG::$BILLING_CURRENCYISO,
							"fixed"			=> "Y"
						);
			
			if ($post_values["return_url"]) $data["x_receipt_link_url"] = $post_values["return_url"];
			if ($post_values["product_id"]) $data["product_id"] = $post_values["product_id"];
			
			if ($this->Config->GetFieldByName("isdemo")->Value == 1) $data["demo"] = "Y";
			
			UI::RedirectPOST($host, $data);
			exit();
		}
		
		/**
		 * This method is called if you return false in OnPaymentComplete();
		 * If your payment gateway supports this, you can provide a user with an URL, which can be used to pay this invoice later. This URL will be emailed to user.
		 * This most likely will be the URL that you are redirecting to inside RedirectToGateway().
		 * @param float $amount Purchase amount
		 * @param int $invoiceid Comma-separated IDs of invoices that are being paid. Can be used as an unique identifier.
		 * @param string $payment_for Human-readable description of the payment
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return string URL of the payment page, with all parameters.
		 */
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