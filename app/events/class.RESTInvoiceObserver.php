<?php

	if (!class_exists('RESTObserver'))
		PHPParser::SafeLoadPHPFile('events/includes/class.RESTObserver.php');

	class RESTInvoiceObserver extends RESTObserver implements IInvoiceObserver, IConfigurable
	{		
		public function __construct (DataForm $Config)
		{
			$this->Config = $Config;
		}
		
		public static function GetConfigurationForm()
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->SetInlineHelp("");
			
			$methods = get_class_methods(__CLASS__);
			foreach ($methods as $method)
			{
				if ($method != '__construct' && $method != 'GetConfigurationForm')
					$ConfigurationForm->AppendField( new DataFormField("{$method}URL", FORM_FIELD_TYPE::TEXT, "{$method} URL"));
			}
			
			return $ConfigurationForm;
		}
		
		public function OnIssued (Invoice $Invoice)
		{
			$this->Request('OnIssued', array('Invoice' => $Invoice->ToArray()));
		}
		
		public function OnPaid (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			$module_name = ($payment_module !== null) ? $payment_module->GetModuleName() : "Manual";
			$this->Request('OnPaid', array('Invoice' => $Invoice->ToArray(), 'payment_module' => $module_name));
		}
		
		public function OnFailed (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			$module_name = ($payment_module !== null) ? $payment_module->GetModuleName() : "Manual";
			$this->Request('OnFailed', array('Invoice' => $Invoice->ToArray(), 'payment_module' => $module_name));
		}
	}
?>