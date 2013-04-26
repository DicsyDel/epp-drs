<?php
	require_once('src/prepend.inc.php');
	
	if (!$req_module)
		CoreUtils::Redirect("modules_view.php");	

	$ModuleFactory = RegistryModuleFactory::GetInstance();
	try
	{
		$Registry = $RegistryModuleFactory->GetRegistryByName($req_module);
		
		$Module = $Registry->GetModule();
		$RefModule = new ReflectionObject($Module);
		if (!$RefModule->hasMethod('RunTest'))
		{
			$errmsg = _('Module has no certification test');
			CoreUtils::Redirect("modules_view.php");			
		}
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$template_name = "admin/module_certtest_result";			
			
			$DF = $Module->GetTestConfigurationForm();
			foreach ($DF->ListFields() as $Field)
			{
				$Field->Value = $_POST[$Field->Name];
			}
			
			ob_start();			
			$Module->RunTest($DF);
			$log = ob_get_clean();
			
			$display['log'] = nl2br($log);
		}
		else
		{
			if ($RefModule->hasMethod('GetTestConfigurationForm'))
			{
				$display['has_configform'] = 1;
				$fields = array();
				foreach ($Module->GetTestConfigurationForm()->ListFields() as $Field)
				{
					$fields[$Field->Title] = array(
						'type' => $Field->FieldType,
						'name' => $Field->Name,
						'value' => $Field->Value,
						'hint' => $Field->Hint
					);
				}
	
				$display['fields'] = $fields;
			}
		}
		
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}	
	
	
	$display["module"] = $req_module;
	$display["title"] = "Settings &raquo; Registry modules &raquo; Run certification test";
	require_once('src/append.inc.php');
?>