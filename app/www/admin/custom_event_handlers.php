<?php
	require_once('src/prepend.inc.php');

    $display["help"] = "This page lists all custom event handlers. <a href=\"http://epp-drs.com/docs/wiki/event.handlers\">Click here</a> to learn how to add your own event handlers or configure existing ones.";
		
	$supported_interfaces = array(
		"IInvoiceObserver" => "invoice", 
		"IRegistryObserver" => "registry", 
		"IPaymentObserver" => "payment", 
		"IGlobalObserver" => "app"
	);
	
	$rows = array();
	// Create directory iterator for events directory
	$DirIterator = new DirectoryIterator(CONFIG::$PATH."/events");
	foreach ($DirIterator as $item)
	{
		if ($item->isFile() && $item->isReadable() && preg_match("/^class.(.*).php$/si", $item->getFilename(), $matches))
		{
			// get class name
			$class_name = $matches[1];

			// eval user file if class not exists
			if (!class_exists($class_name))
			{
				// eval file
				PHPParser::LoadPHPFile($item->getPathname());
				
				// Check for class existence
				if (!class_exists($class_name))
					throw new Exception(sprintf(_("%s must contain %s class."), $item->getPathname(), $class_name), E_ERROR);
			}
				
			// Check class validity
			$ReflectionClass = new ReflectionClass($class_name);
			$interfaces = $ReflectionClass->getInterfaceNames();
			
			// Check for required interfaces
			$ints = array_intersect(array_keys($supported_interfaces), $interfaces);
			sort($ints);			
			if (count($ints) != 1)
				throw new Exception(sprintf(_("Class %s must implements one of the following interfases: %s"), $class_name, implode(", ", $supported_interfaces)), E_ERROR);
			
			$enabled = $db->GetOne("SELECT enabled FROM eventhandlers WHERE name=?", array($class_name));
			$rows[$supported_interfaces[$ints[0]]][] = array("name" => $class_name, 
				"enabled" => $enabled,
				"hasconfig" => $enabled && $ReflectionClass->hasMethod('GetConfigurationForm')
			);
			
			$handlers[$class_name] = array("Reflect" => $ReflectionClass, "interface" => $ints[0]);
		}
	}
		
	$display["rows"] = $rows;
	
	if ($req_action && $handlers[$req_ext])
	{
		switch($req_action)
		{
			case "enable":
				
				$db->Execute("REPLACE INTO eventhandlers SET name=?, interface=?, enabled='1'", array($req_ext, $handlers[$req_ext]['interface']));
				
				if ($handlers[$req_ext]['Reflect']->hasMethod('GetConfigurationForm'))
				{
					$db->Execute("DELETE FROM eventhandlers_config WHERE handler_name = ?", array($req_ext));
						
					$settings_fields = $handlers[$req_ext]['Reflect']->getMethod('GetConfigurationForm')->invoke(NULL);

					foreach ($settings_fields->ListFields() as $field)
					{
						$defval = (string)$field->DefaultValue;
						
						$db->Execute("INSERT INTO eventhandlers_config 
												SET `title`	= ?, 
													`type`	= ?, 
													`key`	= ?, 
													`value`	= ?,
													`handler_name` = ?
										", array(	$field->Title, 
													$field->FieldType, 
													$field->Name, 
													$defval ? $defval : "", 
													$req_ext
												)
									);
					}
				}
				
				$okmsg = _("Event handler successfully enabled");
				CoreUtils::Redirect("custom_event_handlers.php");
				
				break;
				
			case "disable":
				
				$db->Execute("REPLACE INTO eventhandlers SET name=?, interface=?, enabled='0'", array($req_ext, $handlers[$req_ext]['interface']));
				$db->Execute("DELETE FROM eventhandlers_config WHERE handler_name = ?", array($req_ext));
				
				$okmsg = _("Event handler successfully disabled");
				CoreUtils::Redirect("custom_event_handlers.php");
				
				break;
				
			case "configure":
								
				if ($handlers[$req_ext]['Reflect']->hasMethod('GetConfigurationForm'))
				{
					$config_form = $handlers[$req_ext]['Reflect']->getMethod('GetConfigurationForm')->invoke(NULL);
					if (!($config_form instanceof DataForm))
						break;
					$phace = new DataFormField("__phace", FORM_FIELD_TYPE::SELECT, "Procession phase", true, 
						array(
							EVENT_HANDLER_PHACE::BEFORE_SYSTEM => "Before system",
							EVENT_HANDLER_PHACE::AFTER_SYSTEM => "After system"
						)
					);
					array_unshift($config_form->Fields, $phace);
					
						
					$display["rows"] = $db->GetAll("SELECT * FROM eventhandlers_config WHERE handler_name=?", array($req_ext));
					array_unshift($display["rows"], array(
						"title" => $phace->Title, 
						"key" => $phace->Name,
						"value" => $db->GetOne("SELECT phace FROM eventhandlers WHERE name = ?", array($req_ext)),
						"type" => $phace->FieldType,
						"options" => $phace->Options
					));
				
					foreach ($display["rows"] as &$row)
					{
					    if (!$_POST)
					    {
							if (stristr($row['key'], 'pass'))
						       $row["type"] = 'password';
						       
						    if (CONFIG::$DEV_DEMOMODE == 1)
							    $row["value"] = "";
							else
							{
								if ($row["value"] != '')
									$row["value"] = $row["value"];
							}
							
							$row["hint"] = $config_form->GetFieldByName($row['key'])->Hint;
					    }
					    else
					    {
					    	if ($row["key"] != "__phace")
					    	{
						    	
						    	$nval = ($_POST[$row["key"]]) ? $_POST[$row["key"]] : "";
						    	
						    	$db->Execute("UPDATE eventhandlers_config SET 
										`value`=? WHERE 
										`key`=? 
										AND handler_name=?", 
								array($nval, $row['key'], $req_ext));
					    	}
					    	else
					    	{
					    		$db->Execute("UPDATE eventhandlers SET phace = ? WHERE name = ?", 
					    				array($_POST[$row["key"]], $req_ext));
					    	}
					    }
					}
					
					if ($_POST)
					{
						$okmsg = _("Configuration successfully saved");
						CoreUtils::Redirect("custom_event_handlers.php");
					}
					
					$display["TLD"] = $v;
					
					$display["help"] = $config_form->GetInlineHelp();
					
					$display["ext"] = $req_ext;
					$template_name = "admin/custom_event_handler_config";
				}
				
				break;
		}
	}
	
	require_once('src/append.inc.php');
?>