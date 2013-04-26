<?
	class OfflineBankPaymentModule extends AbstractPaymentModule implements IPostBackPaymentModule
	{
		/**
		 * Module config
		 *
		 * @var DataForm
		 */
		public $Config;
		
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
			return "Offline Bank Payment";
		}
		
		/**
		 * Called in postback routine, to check either this (and not some other) module must process this postback.
		 * If you return true, OnPaymentComplete() will be called.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool true, if it does seem that payment postback is posted for this module.
		 */
		public function CheckSignature($args)
		{
			return false;
		}
		
		/**
		 * Return invoice ID
		 *
		 * @param array $request
		 * @return string
		 */
		public function GetOrderID($request)
		{
			return false;
		}
		
		/**
		 * Must return a DataForm object that will be used to draw a configuration form for this module.
		 * @return DataForm object
		 */
		public function GetConfigurationForm()
		{						
			return new DataForm();
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
			return true;
		}
		
		/**
		 * Redirect user to gateway payment form, using HTTP 'Location:' header or UI:RedirectPOST($host, $values);
		 * 
		 * @param float $amount Purchase amount
		 * @param int $orderid Order ID. Can be used as an unique identifier.
		 * @param string $payment_for Human-readable description of the payment
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public final function RedirectToGateway(Order $order, $post_values = array())
		{
			if (CONTEXTS::$APPCONTEXT == APPCONTEXT::REGISTRANT_CP)
			{
				UI::Redirect("invoice_print.php?id={$order->ID}");
			}
			elseif (CONTEXTS::$APPCONTEXT == APPCONTEXT::ORDERWIZARD)
			{
				$smarty = Core::GetSmartyInstance("SmartyExt");				
				$smarty->assign($GLOBALS["display"]);
				$smarty->display("order_complete.tpl");
				exit();
			}
				
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
			return true;
		}
		
		/**
		 * This method is called if payment failes.
		 * Must return a string with explanation of a payment reason.
		 * @return string
		 */
		public function GetFailureReason()
		{
			return true;
		}
		
		public function GetMinimumAmount()
		{
			return false;
		}
	}
?>