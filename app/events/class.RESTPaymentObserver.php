<?php

	if (!class_exists('RESTObserver'))
		PHPParser::SafeLoadPHPFile('events/includes/class.RESTObserver.php');

	class RESTPaymentObserver extends RESTObserver implements IPaymentObserver, IConfigurable
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
		
		public function Notify (AbstractPaymentModule $payment_module, $status)
		{
			$this->Request('Notify', array('payment_module' => $payment_module->GetModuleName(), 'status' => $status));
		}
	}
?>