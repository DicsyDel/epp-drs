<?php
	class UnionPayPaymentModule extends AbstractPaymentModule implements IPostBackPaymentModule
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
			return "UnionPay";
		}
		
		/**
		 * This method is called in postback routine, to check either this (and not some other) module must process this postback.
		 * If you return true, OnPaymentComplete() will be called.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool true, if it does seem that payment postback is posted for this module.
		 */
		public function CheckSignature($args)
		{
			return (
    				$args['billno'] && 
    				$args['attach'] &&
    				$args['signature']
				   );
		}
		
		/**
		 * Must return Order ID. You passed it to payment gateway in RedirectToGateway(), remember? Now it should return it back, so you know what this payment is for. And you return it.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return int order ID
		 */
		public function GetOrderID($request)
		{
			return $request["attach"];
		}
		
		/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField("merch_code", FORM_FIELD_TYPE::TEXT, "Merchant Code"));
			$ConfigurationForm->AppendField( new DataFormField("password", FORM_FIELD_TYPE::TEXT, "Password"));
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
			
			$ConfigurationForm->AppendField( new DataFormField("Currency", FORM_FIELD_TYPE::SELECT, "Card type", 1, array('01' => 'PRC debit card', '02' => 'International credit card (Visa/MasterCard)')));
			$ConfigurationForm->AppendField( new DataFormField("Lang", FORM_FIELD_TYPE::SELECT, "Language", 1, 
			array(1 => "Simplified Chinese (GB)",
				  2 => "English",
				  3 => "Traditional Chinese (Big-5)",
				  4 => "Japanese(JP)"
				  )
			));
			
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
			$result = ($request["succ"] == "Y");
			
			$valid_sign = md5($request["billno"].$request["amount"].$request["date"].$request["succ"].$request["ipsbillno"].$this->Config->GetFieldByName("password")->Value);
			
			$result &= ($request["signature"] == $valid_sign);
			
			if ($result)
			    return true;
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
			$host = ($this->Config->GetFieldByName("isdemo")->Value == 1) ? "www.ips.net.cn/ipay/test_ipayment.asp" : "www.ips.com.cn/ipay/ipayment.asp";
			
			$data = array(
							"mer_code"		=> $this->Config->GetFieldByName("merch_code")->Value,
							"billno"		=> $this->Config->GetFieldByName("merch_code")->Value.substr(time(), -6),
							"amount"		=> number_format($order->GetBillingTotal(), 2, '.', ''),
							"date"			=> date("Ymd"),
							"currency"		=> $post_values["Currency"],
							"merchanturl"	=> CONFIG::$IPNURL,
							"lang"			=> $post_values["Lang"],
							"attach"		=> $order->ID,
							"retencodetype"	=> 2,
							"orderencodetype" => 1,
							"rettype"		=> 0
						);

			$data["signmd5"] = md5($data["billno"].$data["amount"].$data["date"].$this->Config->GetFieldByName("password")->Value);
			
			UI::RedirectPOST("http://{$host}", $data);
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