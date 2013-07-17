<?php

	class WebMoneyPaymentModule extends AbstractPaymentModule implements IPostBackPaymentModule  
	{
		/**
		 * @var DataForm
		 */
		public $Config;
		
		/**
		 * This method is called on early module initialization stage. 
		 * @param DataForm $ConfigDataForm DataForm object that you returned in GetConfigurationForm(), filled with values.
		 * @return void
		 */
		public function InitializeModule($ConfigDataForm)
		{
			$this->Config = $ConfigDataForm;
		}
		
		/**
		 * Must return module name.
		 * @return string Module name
		 */
		public function GetModuleName()
		{
			return "WebMoney";
		}
		
		/**
		* You must construct and return DataForm object.
		* @return DataForm Form template object
		*/
		public function GetConfigurationForm()
		{			
			$Form = new DataForm();
			$Form->SetInlineHelp(sprintf("You must configure your Webmoney account before you can use this module. <br>"
				. "Log in to Merchant WebMoney Transfer and open configuration screen.<br>"
				. "&bull; Set <b>Result URL</b> to <b>%s</b><br>"
				. "&bull; Set <b>Success URL</b> to <b>%s</b><br>"
				. "&bull; Set <b>Fail URL</b> to <b>%s</b><br>"
				. "&bull; Set <b>Checksum method</b> to <b>MD5</b><br>"
				. "&bull; Make sure that <b>Secret key</b> in the form below matches the corresponding field in your Merhant configuration form.", 
				CONFIG::$SITE_URL."/ipn.php", 
				CONFIG::$SITE_URL."/payment_success.php",
				CONFIG::$SITE_URL."/payment_failed.php"
			));
			$Form->AppendField(new DataFormField('Purse', FORM_FIELD_TYPE::TEXT, 'Purse ID', true));
			$Form->AppendField(new DataFormField('SecretKey', FORM_FIELD_TYPE::TEXT, 'Secret key', true));
			// TODO: in future versions
			//$Form->AppendField(new DataFormField('ChecksumAlgo', FORM_FIELD_TYPE::SELECT, 'Checksum algorithm', true, array('MD5' => 'MD5', 'WM' => 'SIGN')));
			//$Form->AppendField(new DataFormField('WMCertPath', FORM_FIELD_TYPE::TEXT, 'WebMoney certificate path', true));
			$Form->AppendField(new DataFormField('TestPaymentSuccess', FORM_FIELD_TYPE::CHECKBOX, 'Test payment success'));
			return $Form;
		}
		
		
		/**
		 * Must return a DataForm object that will be used to draw a payment form for this module.
		 * @return DataForm object. 
		 */
		public function GetPaymentForm()
		{
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
		 * FIXME:
		 *
		 * @return float or false if ��� �����������
		 */
		public function GetMinimumAmount()
		{
			return false;
		}

		/**
		 * This method is called in postback routine, to check either this (and not some other) module must process this postback.
		 * If you return true, OnPaymentComplete() will be called.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool true, if it does seem that payment postback is posted for this module.
		 */
		public function CheckSignature($request)
		{
			return isset($request['LMI_PAYMENT_NO']) 
				&& isset($request['LMI_PAYMENT_AMOUNT'])
				&& isset($request['LMI_PAYEE_PURSE']);
		}
		
		/**
		 * This method is called when we received a postback from payment proccessor and CheckSignature() returned true. 
		 *
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return bool True if payment succeed or false if failed. If payment is failed and you return false, $this->GetFailureReason() will also be called.
		 */
		public function OnPaymentComplete($request)
		{
			if ($request['LMI_PREREQUEST'])
			{
				// Handle preliminary request
				die('YES');
			}
			
			// Verify completed payment
			
			$chkstr = $request['LMI_PAYEE_PURSE']
					. $request['LMI_PAYMENT_AMOUNT']
					. $request['LMI_PAYMENT_NO']
					. $request['LMI_MODE']
					. $request['LMI_SYS_INVS_NO']
					. $request['LMI_SYS_TRANS_NO']
					. $request['LMI_SYS_TRANS_DATE']
					. $this->Config->GetFieldByName('SecretKey')->Value
					. $request['LMI_PAYER_PURSE']
					. $request['LMI_PAYER_WM'];
					
			//$signer_cls = $this->Config->GetFieldByName('ChecksumAlgo')->Value . 'Signer';
			$signer_cls = 'MD5Signer';
			if (!class_exists($signer_cls))
			{
				throw new Exception(sprintf(_("Class not exists: %s"), $signer_cls));
			}
			$Signer = new $signer_cls($this->Config);
			$checksum = $Signer->Sign($chkstr);
			
			// Validate checksum
			$result = $checksum == $request['LMI_HASH'];
			
			if ($result)
			{
			    return true;
			}
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
		 * @param Order $order Order object
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return void
		 */
		public function RedirectToGateway(Order $order, $post_values = array())
		{
			$host = "https://merchant.webmoney.ru/lmi/payment.asp";
			
			$params = array(
				'LMI_PAYMENT_NO' => $order->ID,
				'LMI_PAYEE_PURSE' => $this->Config->GetFieldByName('Purse')->Value,
				'LMI_PAYMENT_AMOUNT' => number_format($order->GetBillingTotal(), 2, '.', ''), // php locale workaround
				'LMI_PAYMENT_DESC' => $order->Description,
				'LMI_SIM_MODE' => $this->Config->GetFieldByName('TestPaymentSuccess')->Value ? '0' : '1',
				'LMI_RESULT_URL' => CONFIG::$IPNURL
			);
			
			if ($post_values['return_url']) $params['LMI_SUCCESS_URL'] = $post_values['return_url'];
			
			UI::RedirectPOST($host, $params);
			exit();			
		}
		
		/**
		 * This method is called if you return false in OnPaymentComplete();
		 * If your payment gateway supports this, you can provide a user with an URL, which can be used to pay this invoice later. This URL will be emailed to user.
		 * This most likely will be the URL that you are redirecting to inside RedirectToGateway().
		 * @param Order $order Order object
		 * @param array $post_values Array of fields, posted back by your payment form. Array keys are equal to field names you returned in IPaymentModule::GetPaymentForm()
		 * @return string URL of the payment page, with all parameters.
		 */
		public function GetDeferredPaymentURL(Order $order, $post_values = array())
		{
			return false;
		}
		
		/**
		 * Must return Order ID. You passed it to payment gateway in RedirectToGateway(), remember? Now it should return it back, so you know what this payment is for. And you return it.
		 * @param array $request Anything that we received from payment gateway (basically $_REQUEST).
		 * @return int order ID
		 */
		public function GetOrderID($request)
		{
			return $request['LMI_PAYMENT_NO'];
		}		
	}

	
	class MD5Signer
	{
		public function Sign ($str)
		{
			return strtoupper(md5($str));
		}
	}
	
	class WMSigner
	{
		public function Sign ($str)
		{
			//TODO: implement
		}
	}
?>