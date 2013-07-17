<?php
	require_once(dirname(__FILE__).'/class.Transport.php');
	require_once(dirname(__FILE__).'/class.RegistryModule.php');
	
	class DotMXRegistryTests extends UnitTestCase 
	{
		private $Module;
		
		private $Registry;
		
		function setUp ()
		{
			$this->Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension("net.mx");
			$this->Module = $this->Registry->GetModule();
		}
		
		function testPoll ()
		{
			while ($Resp = $this->Module->ReadMessage()) {
				var_dump($Resp);
				$this->Module->AcknowledgeMessage($Resp);	
			}
			
		}
	}
?>