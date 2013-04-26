<?
	class ManifestTest extends UnitTestCase
	{
		function __construct()
		{
			$this->UnitTestCase('Manifests test');
		}
		
		function testManifests()
		{
			$manifests = glob(CONFIG::$PATH.'/modules/registries/*/module.xml');
			foreach ($manifests as $manifest)
			{
				preg_match('/(.*)\/(.*)\/module\.xml/', $manifest, $matches);
				$module_name = $matches[2];				
				try
				{	
					$res = RegistryManifest::Validate($manifest);
					$this->assertTrue(($res === true), "{$module_name} registry module passed manifest test!");
					if ($res !== true)
						$this->error(E_USER_ERROR, $res, null, null);
				}
				catch(Exception $e)
				{
					$this->error(E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
				}
			}
		}
	}

	class LibTest extends UnitTestCase 
	{
        function LibTest() 
        {
            $this->UnitTestCase('Lib test');
        }
        
        function testCore() 
        {
			
			//
			// load()
			//
			
			$loadbase = dirname(__FILE__)."/Lib";
			load("ADODB/adodb.inc.php", $loadbase);
			
			$this->assertTrue(class_exists("ADOConnection"), "NewADOConnection class loaded");

							
        }
        
        function testRegistryApi () 
        {
        	$registry = RegistryFactory::GetRegistryByTLD('test');
        	
        	$registry = new EPPTestRegistry();
        	
        	$domain = new Domain(17);
        	$domain->Name = 'marat4324';
        	
        	$domain = $registry->CreateDomain($domain);
        	$domain->SetData($data);
        	$domain->Update();
        	
        	
        	$info = $registry->DomainInfo($domain->Name);
        	$domain->SetData($info);
        	
        	
        	$domain->Update();
        	$host->Update();
        }
    }
?>