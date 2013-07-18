<?php
	
	function __autoload($class_name)
	{
		$paths = array(
						"Client" => SRC_PATH."/class.Client.php",
						"ClientSettings" => SRC_PATH."/class.Client.php",
					  	"Order" => SRC_PATH."/class.Order.php",
						"Invoice" => SRC_PATH."/class.Invoice.php",
						"MailInvoiceObserver" => SRC_PATH."/observers/class.MailInvoiceObserver.php",
						"RegistryInvoiceObserver" => SRC_PATH."/observers/class.RegistryInvoiceObserver.php",
						"BalanceInvoiceObserver" => SRC_PATH."/observers/class.BalanceInvoiceObserver.php",
						"DataForm"	=> SRC_PATH."/class.DataForm.php",
						"DataFormField"	=> SRC_PATH."/class.DataFormField.php",
						"Punycode" => SRC_PATH."/class.Punycode.php",
						"String" => SRC_PATH."/class.String.php", 
						"PHPParser" => SRC_PATH."/class.PHPParser.php",
						"Application" => SRC_PATH."/class.Application.php",
						"TaskQueue" => SRC_PATH."/class.TaskQueue.php",
						"Task" => SRC_PATH."/class.TaskQueue.php", 
						"DomainAllContactsForm" => SRC_PATH."/class.DomainAllContactsForm.php",

						"Module" => MODULES_PATH."/class.Module.php",
						"ModuleFactory" => MODULES_PATH."/class.ModuleFactory.php",
		
						"PaymentModuleFactory" => MODULES_PATH."/payments/class.PaymentModuleFactory.php",
						"AbstractPaymentModule" => MODULES_PATH."/payments/class.AbstractPaymentModule.php",
						"DBPaymentObserver" => MODULES_PATH."/payments/observers/class.DBPaymentObserver.php",
		
						"AbstractRegistryModule" => MODULES_PATH."/registries/class.AbstractRegistryModule.php",
						"GenericEPPRegistryModule" => MODULES_PATH."/registries/class.GenericEPPRegistryModule.php",
						"GenericEPPTransport" => MODULES_PATH."/registries/class.GenericEPPTransport.php",
						"SSLTransport" => MODULES_PATH."/registries/class.SSLTransport.php",
						"EmailToRegistrantObserver" => MODULES_PATH."/registries/observers/class.EmailToRegistrantObserver.php",
						"OperationHistory" => MODULES_PATH."/registries/class.OperationHistory.php",
						"ManagedDNSRegistryObserver" => MODULES_PATH."/registries/observers/class.ManagedDNSRegistryObserver.php",
						"RegistryModuleFactory" => MODULES_PATH."/registries/class.RegistryModuleFactory.php",
						"Registry" => MODULES_PATH."/registries/class.Registry.php",
						"RegistryAccessible" => MODULES_PATH."/registries/class.RegistryAccessible.php",
						"Phone" => SRC_PATH."/class.Phone.php",
						"DBDomain" => MODULES_PATH."/registries/class.DBDomain.php",
						"Domain" => MODULES_PATH."/registries/class.Domain.php",
						"DBContact" => MODULES_PATH."/registries/class.DBContact.php",
						"Contact" => MODULES_PATH."/registries/class.Contact.php",
						"DBNameserverHost" => MODULES_PATH."/registries/class.DBNameserverHost.php",
						"Nameserver" => MODULES_PATH."/registries/class.Nameserver.php",
						"NameserverHost" => MODULES_PATH."/registries/class.Nameserver.php",
						"RegistryManifest" => MODULES_PATH."/registries/class.RegistryManifest.php",
						"PendingOperation" => MODULES_PATH."/registries/class.PendingOperation.php",
						"JWhois" => MODULES_PATH."/registries/class.JWhois.php",
						"OteTestSuite" => MODULES_PATH."/registries/class.OteTestSuite.php",
						"OteTestRunner" => MODULES_PATH."/registries/class.OteTestRunner.php",
		
						"Balance" => SRC_PATH."/class.Balance.php",
						"BalanceOperation" => SRC_PATH."/class.Balance.php",
						"BalanceOperationType" => SRC_PATH."/class.Balance.php",
						"DBBalance" => SRC_PATH."/class.DBBalance.php",
		
						"UpdateDomainContactAction" 	=> SRC_PATH."/class.UpdateDomainContactAction.php",
						"UpdateDomainNameserversAction" => SRC_PATH."/class.UpdateDomainNameserversAction.php",
						"RegisterDomainAction" 			=> SRC_PATH."/class.RegisterDomainAction.php",
						"DomainRegistrationController" 	=> SRC_PATH."/class.DomainRegistrationController.php",
						"DomainAllContactsForm" 		=> SRC_PATH."/class.DomainAllContactsForm.php",
						
						
						// API
						"EppDrs_Api_RestServer" => SRC_PATH."/Api/class.RestServer.php",
						"EppDrs_Api_Service" => SRC_PATH."/Api/class.Service.php",
						"EppDrs_Api_KeyTool" => SRC_PATH."/Api/class.KeyTool.php"
		              );

		if (key_exists($class_name, $paths))
			require_once $paths[$class_name];
	}
?>
