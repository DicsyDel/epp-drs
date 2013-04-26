<?php
	
	class SermepaPaymentTests extends UnitTestCase 
	{
		private $Module;
		
		function __construct ()
		{
			parent::__construct("Sermepa");
			
			$Factory = PaymentModuleFactory::GetInstance();
			$this->Module = $Factory->GetModuleObjectByName("Sermepa");
		}
		
		function testAbs ()
		{
			$Order = new Order();
			$Order->UserID = 1;
			$Order->ID = 18;
			$Order->AddInvoice(new Invoice(INVOICE_PURPOSE::DOMAIN_TRANSFER, 208, 1));
			
			$post_values = array
			(
				"CCN" => "4150070010040445",
				"ExpDate_Y" => "11",
				"ExpDate_m" => "09",
				"CVV2" => "998"
			);
			
			$this->Module->RedirectToGateway($Order, $post_values);
		}
	}