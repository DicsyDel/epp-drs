<?php

	/**
	 * Registry module manifest. Simplifies access to module.xml manifest.
	 * @name RegistryManifest
	 * @category   EPP-DRS
	 * @package    Modules
	 * @subpackage RegistryModules
	 * @sdk-doconly
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Marat Komarov <http://webta.net/company.html> 
	 */
	
	class RegistryManifest
	{
		private static $XmlCache = array();
		
		/**
		 * Current section XML
		 *
		 * @var SimpleXMLElement
		 */
		private $SectionConfig;
		
		private $SectionAttributes;
		
		/**
		 * Section abilities
		 *
		 * @var SimpleXMLElement
		 */
		private $SectionAbilities;
		
		/**
		 * Manifest XML
		 *
		 * @var SimpleXMLElement
		 */
		private $Manifest;
		
		private $ContactConfig = array();
		private $DomainConfig;
		private $FormSchemas;
		private $ManifestPath;
		
		/**
		 * Loads manifest from a given file.
		 *
		 * @param string $path Path to XML file
		 */
		function __construct ($path)
		{
			if (!file_exists($path))
				throw new Exception(sprintf(_("Module manifest not fount at %s"), $path));

			$this->ManifestPath = realpath($path);
			if (! key_exists($path, self::$XmlCache))
			{
				$DOC = new DOMDocument();
				$XSL = new XSLTProcessor();
				
				$DOC->load(CONFIG::$PATH."/modules/registries/Manifest.xsl");
				$XSL->importStyleSheet($DOC);
				$DOC->loadXML(self::LoadCompiledManifest($this->ManifestPath));
				$string_manifest = $XSL->transformToXML($DOC);
					
				self::$XmlCache[$this->ManifestPath] = simplexml_load_string($string_manifest);
				
				if (!self::$XmlCache[$this->ManifestPath])
					throw new Exception(sprintf(_("Malformed module manifest at %s"), $this->ManifestPath));
			}

			$this->Manifest = self::$XmlCache[$this->ManifestPath];
		}
		
		public static function LoadCompiledManifest($path, $force_compile = false)
		{
			$path = realpath($path);
			$module_name = basename(dirname($path));
			$compiled_path = CONFIG::$PATH."/cache/manifest.{$module_name}.xml";
			if (!file_exists($compiled_path) || $force_compile)
			{				
				$Manifest = new DOMDocument();
				$Manifest->load($path);	
				
				//
				// Process defines and extends 
				//
				$Xpath = new DOMXPath($Manifest);
				$defines = $Xpath->query("//define");
				foreach ($defines as $define)
				{
					$define_name = $define->getAttribute('name');
					$extends = $Xpath->query("//*[@extends = '{$define_name}']");
					foreach ($extends as $extend)
					{
						foreach ($define->childNodes as $node)
						{
							if ($node instanceof DOMElement)
								self::ManifestMergeRecursive($node, $extend, $Manifest);
						}
							
						$extend->removeAttribute("extends");
					}
					
					$define->parentNode->removeChild($define);
				}
				
				$string_manifest = $Manifest->saveXML();
				file_put_contents($compiled_path, $string_manifest);				
				
				return $string_manifest;
			}
			else
				return file_get_contents($compiled_path);
		}
		

		private static function ManifestMergeRecursive(DOMElement & $node, DOMElement & $root_node, DOMDocument & $Manifest)
		{
			 $exists = false;
			 foreach ($root_node->childNodes as $c_node)
			 {
			 	if ($c_node->nodeName == $node->nodeName &&
			 		self::CalculateAttributesHash($c_node) == self::CalculateAttributesHash($node))
			 			$exists = true;
			 }
			 
			 if (!$exists)
			 {
			 	$root_node->appendChild($node->cloneNode(true));
			 	$root_node->appendChild($Manifest->createTextNode("\n"));
			 }	
			 else
			 {
			 	foreach ($root_node->childNodes as $cnode)
			 	{
			 		if ($cnode->nodeName == $node->nodeName && $node->nodeName != '#text')
			 		{
			 			foreach ($node->childNodes as $ccnode)
			 			{
			 				if ($ccnode instanceof DOMElement)
			 					self::ManifestMergeRecursive($ccnode, $cnode, $Manifest);
			 			}
			 			
			 			break;
			 		}
			 	}
			 }
		}
		
		private static function CalculateAttributesHash(DOMElement $node)
		{
			if (count($node->attributes) == 0)
				return "";
			else
			{
				$str = "";
				foreach ($node->attributes as $attr)
					$str .= "{$attr->name}={$attr->value}";
				
				return md5($str);
			}
		}
		
		/**
		 * Perform full validation 
		 *
		 * @param string $path
		 * @return bool|string Success true value or manifest validation error message 
		 */
		public static function Validate($path)
		{
			$result = self::ValidateAgainstXSD($path);
			if ($result !== true)
				return $result;
				
			$result = self::ValidateAgainstXSLT($path);
			if ($result !== true)
				return $result;
				
			return true;
		}
		
		/**
		 * Validate module manifest against XSL table
		 *
		 * @param string $path
		 * @return bool|string Success true value or manifest validation error message 
		 */
		private static function ValidateAgainstXSLT($path)
		{
			$DOC = new DOMDocument();
			$XSL = new XSLTProcessor();
			
			$DOC->load(CONFIG::$PATH."/modules/registries/ManifestValidator.xsl");
			$XSL->importStyleSheet($DOC);
			
			$DOC->loadXML(self::LoadCompiledManifest($path));
			$res = $XSL->transformToXML($DOC);
			
			if (preg_match("/<error>([^<]+)<\/error>/si", $res, $matches))
			{
				return $matches[1];
			}
			
			return true;
		}
		
		/**
		 * Validate module manifest against XSD schema
		 *
		 * @param string $path
		 * @return bool|string Success true value or manifest validation error message 
		 */
		private static function ValidateAgainstXSD($path)
		{
			libxml_use_internal_errors(true);
	
		    $doc = new DOMDocument('1.0', 'utf-8');
		    $doc->loadXML(self::LoadCompiledManifest($path));
		    
		   	if ($doc->schemaValidate(CONFIG::$PATH."/modules/registries/manifest.xsd"))
		   		return true;
		   	else
		   	{
			    $errors = libxml_get_errors();
			    
			    if (empty($errors))
			        return true;
			
			    $error = $errors[0];
			
			    $lines = file($path);
			    $line = $lines[($error->line)-1];
			
			    $message = $error->message.' at line '.$error->line.':<br />'.htmlentities($line).' in '.$path;
			
			    return $message;
		   	}
		}
		
		/**
		 * Set current extension to $extension
		 *
		 * @param string $extension
		 */
		public function SetExtension($extension)
		{
			$sections = $this->Manifest->xpath("//section");
			foreach ($sections as $section)
			{	
				$tlds_string = (string)$section["tlds"];
				
				$tlds = explode(",", $tlds_string);
				if (in_array($extension, $tlds))
				{
					$this->SectionConfig = $section->config;
					$this->SectionAttributes = $section->attributes();
					if ($section->ability)
						$this->SectionAbilities = $section->ability;
					
					break;
				}
			}
			
			if (!$this->SectionConfig)
				throw new Exception(sprintf(_("There is no '%s' domain extension defined in module manifest"), $extension));
				
			$this->ContactConfig = array();
			$this->DomainConfig = null;
		}
		
		public function SetSection ($section_name)
		{
			if (is_numeric($section_name))
			{
				// Section index 
				$section = $this->Manifest->section[$section_name];
			}
			else
			{
				// Name attribute
				list($section) = $this->Manifest->xpath("//section[@name='{$section_name}']");
			}
			if (!$section)
			{
				throw new Exception(sprintf(_("There is no section '%s' defined in module manifest"), $section_name));
			}
			
			$this->SectionConfig = $section->config;
			$this->SectionAttributes = $section->attributes();
			if ($section->ability)
				$this->SectionAbilities = $section->ability;
			
			$this->ContactConfig = array();
			$this->DomainConfig = null;
		}
	
		public function GetRegistrationMinNS () 
		{
			return (int)$this->GetDomainConfig()->registration->min_ns;
		}
		
		public function GetRegistrationMaxNS () 
		{
			$max_ns = (int)$this->GetDomainConfig()->registration->max_ns;
			if (!$max_ns)
			{
				return (int)$this->GetRegistryOptions()->host_objects->max_ns;
			}
		}
		
		/**
		 * Return Registry errors override
		 *
		 * @return array
		 */
		public function GetOverriddenErrors()
		{
			$retval = array();
			$errors = $this->Manifest->xpath("//registry_errors_override/error");
			if (is_array($errors))
			{
				foreach ($errors as $error)
				{
					$retval[] = array(
						"match" => (string)$error->attributes()->match,
						"str"	=> (string)$error->attributes()->str,
						"error" => trim((string)$error)
					);
				}
			}
			
			return $retval;
		}
		
		/**
		 * Returns array of contact field names for specified $contact_type 
		 *
		 * @param string $contact_type
		 * @return array Contact field names
		 */
		public function GetContactFields ($contact_type)
		{
			$fields = array();
			$ContactConfig = array();
			
			$groupname = $this->GetGroupNameByContactType($contact_type);
			if ($this->ContactFields[$groupname])
				return $this->ContactFields[$groupname];
			
			$contact = $this->SectionConfig->contacts->xpath('contact[@type = "'.$contact_type.'"]');
			
			if (!($contact[0] instanceof SimpleXMLElement))
				throw new Exception(sprintf(_("Contact with type %s not defined in manifest"), $contact_type));
			
			$manifest_fields = $contact[0]->xpath("fields/field");
			foreach ($manifest_fields as $field)
	        {
				settype($field, "array");	        
				$fields[] = $field["@attributes"]["name"];
			}
			
			if ($this->SectionConfig->contacts->extra_fields->if)
			{
			    $iffields = ($this->SectionConfig->contacts->extra_fields->if[1]) ? $this->SectionConfig->contacts->extra_fields->if : array($this->SectionConfig->contacts->extra_fields->if);
			    		    
			    $efields = ($iffields[0]->field[1]) ? $iffields[0]->field : array($iffields[0]->field);
			    
			    foreach ($efields as $k=>$field)
					$fields[] = (string)$field["name"];
			}
			
			$this->ContactFields[$groupname] = $fields;
			
			return $fields;
		}
		
		/**
		 * This method return list of supported contact types
		 *
		 * @return array Contact types
		 */
		public function GetContactTypes ()
		{
			$ret = array();
			foreach ($this->SectionConfig->xpath('contacts/contact/@type') as $contact_type)
				$ret[] = (string)$contact_type;
			return $ret;
		}
		
			
		public function GetContactConfig ($contact_type)
		{
			$section_config = $this->GetSectionConfig();
			
			if (!$this->ContactConfig[$contact_type])
			{			
				$contact_config = $section_config->xpath('contacts/contact[@type="'.$contact_type.'"]');	
				$this->ContactConfig[$contact_type] = $contact_config[0]; 
			}
				
			return $this->ContactConfig[$contact_type];
		}
		
		public function GetContactConfigByGroup ($contact_group)
		{
			$section_config = $this->GetSectionConfig();
						
			$contact_config = $section_config->xpath('contacts/contact[@group="'.$contact_group.'"]');
			if (!($contact_config[0] instanceof SimpleXMLElement))
				throw new Exception(sprintf(_("Cannot get contact config. Contact with group name '%s' not found"), $contact_group));	
			
			return $contact_config[0];
		}
		
			
		public function GetDomainConfig ()
		{
			if (!$this->DomainConfig)
			{
				$section = $this->GetSectionConfig();
				$this->DomainConfig = $section->domain;
			}
			
			return $this->DomainConfig;
		}
		
		public function GetGroupNameByContactType ($contact_type)
		{
			$section = $this->GetSectionConfig();
			
			$group_object = $section->xpath('contacts/contact[@type="'.$contact_type.'"]/@group');
			$group_object = $group_object[0];
			if (!($group_object instanceOf SimpleXMLElement))
				throw new Exception(sprintf(_("%s contact type not defined in registry manifest"), $contact_type));		
			
			$attrs = (array)$group_object;			
			return (string)$attrs["@attributes"]["group"];
		}
		
		
		/**
		 * @return string
		 */
		public function GetSectionName ()
		{
			return (string) $this->SectionAttributes->name;
		}
		
		public function GetContactTargetIndex ($TLD, $contact_group)
		{
			$i = 0;
			foreach ($this->GetContactConfigByGroup($contact_group)->targets->target as $target)
			{
				$tlds = explode(',', $target->attributes()->tlds);
				if (in_array($TLD, $tlds))
				{
					return $i;
				}
				$i++;
			}
		}
		
		public function GetContactTargetTitle ($TLD, $contact_group)
		{
			$section_name = $this->GetSectionName();
			$target_index = $this->GetContactTargetIndex($TLD, $contact_group);
			$module_name = basename(dirname(realpath($this->ManifestPath)));
			if (count($this->GetContactConfigByGroup($contact_group)->targets->target) > 1)
			{
				return sprintf("%s %s, %s", $module_name, ucfirst($section_name), $target_index);
			}
			else 
			{
				return sprintf("%s %s", $module_name, ucfirst($section_name));
			}
		}
		
		private static $PhoneField;
		
		public function MakePhoneField ($attrs=array())
		{
			return Phone::GetInstance()->GetWidget($attrs);
		} 
		
		/**
		 * This method return contact form schema
		 * Called by EPPDRS core 
		 *
		 * @param string $contact_type
		 * @param string $contact_group
		 * @return array
		 */
		public function GetContactFormSchema ($contact_type = null, $contact_group = null)
		{
			if (!$contact_type && !$contact_group)
				throw new Exception(_('$contact_type OR $contact_group must be passed into GetContactFormSchema'));
			
			$fields = array();
			$ContactConfig = array();
			
			$groupname = $contact_group ? $contact_group : $this->GetGroupNameByContactType($contact_type);
			if ($this->FormSchemas[$groupname])
				return $this->FormSchemas[$groupname]; 
					
			if ($contact_type)
				$ffields = $this->SectionConfig->contacts->xpath("contact[@type = '{$contact_type}']");
			else		
				$ffields = $this->SectionConfig->contact_groups->xpath("group[@name = '{$contact_group}']");
					
			if (!($ffields[0] instanceof SimpleXMLElement))
				throw new Exception(sprintf(_("Contact with type %s not defined in manifest"), $contact_type));
			
			$manifest_fields = $ffields[0]->fields->xpath("field");
			foreach ($manifest_fields as $field)
			{
				$attrs = (array)$field;
				$attrs = $attrs['@attributes'];    		        
				$ContactConfig[(string)$attrs["name"]] = (array)$attrs;
				
				if ($attrs['type'] == 'phone')
				{
					$ContactConfig[(string)$attrs['name']] = $this->MakePhoneField((array)$attrs);
				}
				else if ($attrs["type"] == "select")
				{
					foreach($field as $k=>$value)
					{
	                    switch($k)
	                    {
	                    	case "values":
	                    		
	                    		$childs = $value->xpath("value");
	                    		foreach ($childs as $child)
	                    		{
									settype($child, "array");
	                    			$vattrs = (array)$child['@attributes'];
		                	        $ContactConfig[(string)$attrs["name"]]["values"][trim($vattrs["value"])] = _(trim($vattrs["name"]));
		                	        if ($vattrs["selected"])
		                	        	$ContactConfig[(string)$attrs["name"]]["default_selected"] = trim($vattrs["value"]);
	                    		}
	                    	break;
	                    	
	                    	case "database":
	                    	
		                    	$db = Core::GetDBInstance();
		                        $dbinfo = (array)$field->database->attributes();
		                        $dbinfo = $dbinfo['@attributes'];
		        	            
		                        if ($field->database->sql->getName() != '')
		                        {
		                        	$sql = (string)$field->database->sql;
		                        	$sql = str_replace("%current_user%", "'{$_SESSION["userid"]}'", $sql);
		                        	$values = $db->Execute($sql);
		                        	while ($value = $values->FetchRow())
		                        		$ContactConfig[(string)$attrs["name"]]["values"][$value[$dbinfo['value_field']]] = _($value[$dbinfo['name_field']]);
		                        }
		                        else
		                        {
                        			$values = $db->Execute("SELECT `{$dbinfo['value_field']}` as `key`, `{$dbinfo['name_field']}` as `name` FROM `{$dbinfo['table']}`");
			        	            while ($value = $values->FetchRow())
										$ContactConfig[(string)$attrs["name"]]["values"][$value["key"]] = _($value["name"]);
		                        }
	                    	
	                    	break;
	                    	
	                    	case "csv":
	                    		
	                    		$csvinfo = (array)$field->csv->attributes();
		                        $csvinfo = $csvinfo['@attributes'];
	                    		
	                    		$csv = @file(dirname($this->ManifestPath)."/{$csvinfo['file']}");
	                    		if (!$csv)
	                    			throw new Exception(_("%s not found in %s", $csvinfo['file'], dirname($this->ManifestPath)));
	                    		
	                    		foreach($csv as $line)
	                    		{
	                    			$chunks = explode($csvinfo["separator"], $line);
	                    			$tmp[trim($chunks[$csvinfo["value_index"]])] = _(trim($chunks[$csvinfo["name_index"]]));
	                    		}
	                    			
	                    		asort($tmp);                    		
	                    		$ContactConfig[(string)$attrs["name"]]["values"] = array_merge((array)$ContactConfig[(string)$attrs["name"]]["values"],$tmp);
	                    		
	                    	break;
	                    }
					}
					
					if (strtolower($attrs["name"]) == "country" || strtolower($attrs["name"]) == "cc")
					{
						$ContactConfig[(string)$attrs["name"]]["default_selected"] = CONFIG::$DEFAULT_COUNTRY;
					}					
	            }
	        }
	        
	        foreach ($ContactConfig as $fieldname=>$fieldinfo)
			{
			    $field_key = _($fieldinfo["description"]);
				$fields[$field_key] = array(
			      "type"          => $fieldinfo["type"],
			      "name"          => $fieldname,
			      "isrequired"    => $fieldinfo["required"],
			      "note"          => $fieldinfo["note"],
			      "ctype"		  => $contact_type,
			      "groupname"	  => $fieldinfo["group"],
			      "iseditable"    => (int)$fieldinfo["iseditable"]
			    );
			    
			    if ($fieldinfo['type'] == 'checkbox')
			    	$fields[$field_key]['value'] = $fieldinfo['value'];
			    
			    if ($fieldinfo["type"] == "select")
			    {
			    	$fields[$field_key]["values"] = $fieldinfo["values"];
			    	$fields[$field_key]["default_selected"] = $fieldinfo["default_selected"];
			    }
			      
				if ($fieldinfo["type"] == "phone")
				{
					$fields[$field_key]['format'] = $fieldinfo['format'];
					$fields[$field_key]['items'] = $fieldinfo['items'];
					$fields[$field_key]['display_name'] = $fieldinfo['name'] . '_display';
				}
			}
		    
			$manifest_extra_fields = $ffields[0]->extra_fields;
			if ($manifest_extra_fields)
			{ 
				$iffields = $manifest_extra_fields->xpath("if");
				foreach($iffields as $iffield)
				{
					$attrs = (array)$iffield;
				    $attrs = $attrs['@attributes'];
				    
				    $js = "
				    if (typeof(fnamestart_{$contact_type}) == 'undefined') fnamestart_{$contact_type} = '';\n
				    if (typeof(fnameend_{$contact_type}) == 'undefined') fnameend_{$contact_type} = '';\n
				     
				    var isIE = (navigator.appName == 'Microsoft Internet Explorer');
				     
				    function ch_handler_{$contact_type}(){\n";

				    $efields = $iffield->xpath("field");
				    
				    foreach ($efields as $k=>$field)
					{
						$field_key = _((string)$field["description"]);
						$fields[$field_key] = array
						(
							"type" 			 => (string)$field["type"],
							"name" 			 => (string)$field["name"],
							"isrequired"       => (string)$field["required"],
							"display"        => "none",
							"ctype"			 => $contact_type,
							"jsname"         => "f".rand(0, 999999),
							"iseditable"    => (int)$field["iseditable"]
						);
										
						if ($field["type"] == "select")
						{
							foreach($field as $k=>$value)
							{
			                    switch($k)
			                    {
			                    	case "values":
			                    		
			                    		$childs = $value->xpath("value");
			                    		foreach ($childs as $child)
			                    		{
											settype($child, "array");
			                    			$vattrs = (array)$child['@attributes'];
				                	        $fields[$field_key]["values"][$vattrs["value"]] = _($vattrs["name"]);                    			
			                    		}
			                    	break;
			                    	
			                    	case "database":
			                    	
				                    	$db = Core::GetDBInstance();
				                        $dbinfo = (array)$field->database->attributes();
				                        $dbinfo = $dbinfo['@attributes'];
				        	            
				        	            $values = $db->Execute("SELECT `{$dbinfo['value_field']}` as `key`, `{$dbinfo['name_field']}` as `name` FROM `{$dbinfo['table']}`");
				        	            while ($value = $values->FetchRow())
											$fields[$field_key]["values"][$value["key"]] = _($value["name"]);
			                    	
			                    	break;
			                    	
			                    	case "csv":
		                    		
			                    		$csvinfo = (array)$field->csv->attributes();
				                        $csvinfo = $dbinfo['@attributes'];
			                    		
			                    		$csv = @file(dirname($this->ManifestPath)."/{$csvinfo['file']}");
			                    		if (!$csv)
			                    			throw new Exception(_("%s not found in %s", $csvinfo['file'], dirname($this->ManifestPath)));
			                    		
			                    		foreach($csv as $line)
			                    		{
			                    			$chunks = explode($csvinfo["separator"], $line);
			                    			$fields[$field_key]["values"][trim($chunks[$csvinfo["value_index"]])] = _(trim($chunks[$csvinfo["name_index"]]));
			                    		}
			                    			
			                    		asort($fields[$field_key]["values"]);
			                    		
			                    	break;
			                    }
							}				
						}
								
					    $js .= "if ($(fnamestart_{$contact_type}+'{$attrs['field']}'+fnameend_{$contact_type}).value == '{$attrs['value']}') {\n";
					    $js .= "$('".$fields[$field_key]["jsname"]."').style.display = '';\n";
					    $js .= "}else{\n";
					    $js .= "$('".$fields[$field_key]["jsname"]."').style.display = 'none';\n";
					    $js .= "}\n";
					}
				    
				    $js .= "}
				    Event.observe($(fnamestart_{$contact_type}+'{$attrs['field']}'+fnameend_{$contact_type}), 'change', function(){ch_handler_{$contact_type}();});
					Event.observe(window, 'load', function(){ch_handler_{$contact_type}();});
					try { ch_handler_{$contact_type}(); }catch(e){}
		    		";
				    
				    $fields[$field_key]["js"] = $js;
				}
			}
			
			$this->FormSchemas[$groupname] = $fields;
			
			return $this->FormSchemas[$groupname];
		}
		
		/**
		 * Returns a list of supported domain extensions  
		 *
		 * @return array Domain extensions
		 */
		public function GetExtensionList ()
		{
			$exportTLDs = array();

			$sections = $this->Manifest->xpath("//section");
			foreach($sections as $section)
			{
				$tlds_string = $section["tlds"];
				$tlds = explode(",", $tlds_string);
				
				foreach ($tlds as $tld)
					array_push($exportTLDs, $tld);
			}
			
			return $exportTLDs;
		}
		
		/**
		 * Returns registry options 
		 *
		 * @return SimpleXMLElement
		 */
		public function GetRegistryOptions()
		{
			if ($this->SectionAbilities)
			{
				$abbils = (array)$this->SectionAbilities;
				foreach ($abbils as $name => $value)
				{
					if ($this->Manifest->registry_options->ability->{$name})
						$this->Manifest->registry_options->ability->{$name} = $value;
				}
			}
			
			return $this->Manifest->registry_options;
		}
		
		public function GetOptions ()
		{
			
		}
		
		/**
		 * Returns module description
		 *
		 * @return string
		 */
		public function GetModuleDescription()
		{
			return (string)$this->Manifest->attributes()->description;
		}
		
		public function GetModuleCodebase ()
		{
			return (string)$this->Manifest->attributes()->codebase;
		}
		
		/**
		 * Get current section set up by RegistyManifest::SetExtension
		 *
		 * @return SimpleXMLElement
		 */
		public function GetSectionConfig ()
		{
			return $this->SectionConfig;
		}
		
		public function GetPath ()
		{
			return $this->ManifestPath;
		}
	}

?>