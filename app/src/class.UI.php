<?
	/**
     * Swiss-army knife for various methods related to user interface.
     * @name UI
     * @package    Common
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Marat Komarov <http://webta.net/company.html>
     */
	class UI
	{		
		/**
		 * Display error on screen
		 *
		 * @param unknown_type $message
		 * @param int $code One of PHP's error codes. 
		 */
		public static function DisplayException(Exception $e)
		{
			restore_error_handler();
	 		$code = $e->getCode();
	 		$message = $e instanceof ErrorList ? 
	 			"Multiple errors: " . join("; ", $e->GetAllMessages()) : 
	 			$e->getMessage();
			
			
	 		// Defaultize $code. Not sure if we can place a constant in param default, since constants are kind of late-binded
	 		$code = ($code == null) ? E_USER_ERROR : $code;
	 		
	 		// Generate backtrace if debug mode flag set
 			if (CONFIG::$DEV_DEBUG)
 				$bt = Debug::Backtrace($e);
 			
 			// Log exception
	 		if ( class_exists("Log") && Log::HasLogger("EPPDRSLogger"))
		 	{
		 		//TODO: this is not needed anymore?
				Log::$DoRaiseExceptions = false;
				Log::Log($message, E_USER_ERROR, array("backtrace" => $bt), "EPPDRSLogger");
		 	}
 			
		 	// Display
 			switch (CONTEXTS::$APPCONTEXT)
 			{ 
 				case APPCONTEXT::ORDERWIZARD :
		 		    if (!defined("NO_TEMPLATES"))
		 		    {
		 		    	// Display error
		 				$Smarty = Core::GetSmartyInstance("SmartyExt");
		 			    $Smarty->assign(array("backtrace" => $bt, "message" => $message, "lang" => LOCALE));
					    $Smarty->display("exception.tpl");
		 		    }
	 		    	break;
	 		    	
	 		    case APPCONTEXT::CRONJOB :
	 				die($message);

	 			// Exception in Registrant/Registrar panel
	 			case APPCONTEXT::REGISTRANT_CP:
	 			case APPCONTEXT::REGISTRAR_CP:	 				
	 				
	 				if (!defined("NO_TEMPLATES"))
		 		    {
		 		    	try
		 		    	{
			 		    	if ($GLOBALS['enable_json'])
			 		    	{
			 		    		// AJAX request, show error text and 500 status
			 		    		header('HTTP/1.0 500 Internal Server Error');
			 		    		die($e->getMessage());
			 		    	} 
			 		    	else
			 		    	{
			 		    		// Display error
				 				$Smarty = Core::GetSmartyInstance("SmartyExt");
				 			    $Smarty->assign(array("backtrace" => $bt, "message" => $message, "lang" => LOCALE));
				 			    
				 			    $sub_src_dir = realpath(dirname($_SERVER["SCRIPT_FILENAME"]). "/src");
				 			    
				 			    //
								// Load menu
								//
								Core::load("XMLNavigation", $sub_src_dir);
								require("{$sub_src_dir}/navigation.inc.php");
								
								$post_serialized = self::SerializePOST($_POST);
								
								$Smarty->assign(array(
									"dmenu" => $XMLNav->DMenu,
									"post_serialized" => $post_serialized,
									"get_url" => $_SERVER['REQUEST_URI'],
									)
								);
							    $Smarty->display(
							    	(CONTEXTS::$APPCONTEXT == APPCONTEXT::REGISTRANT_CP ? "client" : "admin") 
							    	. "/exception.tpl"
							    );
			 		    	}
		 		    	}
		 		    	catch (Exception $e2)
		 		    	{
		 		    		Log::Log($e2->getMessage(), E_USER_ERROR);
		 		    	}
		 		    }
	 				break;
	 				
	 			// Default to show regular exception
	 			default:
	 				
 					if (!defined("NO_TEMPLATES"))
		 		    {
		 		    	// Display error
		 				$Smarty = Core::GetSmartyInstance("SmartyExt");
		 			    $Smarty->assign(array("backtrace" => $bt, "message" => $message, "lang" => LOCALE));
					    $Smarty->display("exception.tpl");
		 		    }
	 				break;
 			}
 			exit();
		}
		
		public static function DisplayInstallationTip ($message)
		{
			$Smarty = Core::GetSmartyInstance("SmartyExt");
			$Smarty->assign(array("message" => $message));
			$Smarty->display("installation_tip.tpl");
			exit();
		}
		
		
		/**
		 * Simple post serialization
		 *
		 * @return string a mix of hidden fileds, ready to be embedded to HTML form as-is.
		 */
		private function SerializePOST()
		{
			if ($_POST)
			{
				foreach($_POST as $k=>$v)
				{
					if (is_array($v))
					{
						foreach($v as $vv)
						{
							$retval .= "<input type=hidden name='{$k}[]' value='{$vv}'>";
						}
					}
					else
						$retval .= "<input type=hidden name='{$k}' value='{$v}'>";
				}
			}
			return $retval;
		}
		/**
		 * Build an HTML link out of support values
		 *
		 * @return unknown
		 */
		public static function GetSupportEmailLink()
		{
			return sprintf("<a href='mailto:%s'>%s</a>", CONFIG::$SUPPORT_EMAIL, CONFIG::$SUPPORT_NAME);
		}
		
		/**
		 * Redirects page to $url
		 *
		 * @param string $url
		 * @static 
		 */
		public static function Redirect($url)
		{
			if (!$_SESSION["mess"])
				$_SESSION["mess"] = $GLOBALS["mess"];
				
			if (!$_SESSION["okmsg"])
				$_SESSION["okmsg"] = $GLOBALS["okmsg"];
				
			if (!$_SESSION["errmsg"])
				$_SESSION["errmsg"] = $GLOBALS["errmsg"];
			
			if (!$_SESSION["err"])
				$_SESSION["err"] = $GLOBALS["err"];
			
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{
				echo "
				<script type='text/javascript'>
				<!--
				document.location.href='{$url}';
				-->
				</script>
				<meta http-equiv='refresh' content='0;url={$url}'>
				";
	  			exit();
	  			
			} 
			else 
			{
				header("Location: {$url}");
				exit();
			}
		}
		
		/**
		 * Redirect parent URL
		 *
		 * @param string $url
		 * @static 
		 */
		public static function RedirectParent($url)
		{
			echo "
			<script type='text/javascript'>
			<!--
				parent.location.href='{$url}';
			-->
			</script>";
  			die();
		}
		
		/**
		* Submit HTTP post to $url with form fields $fields
		* @access public
		* @param string $url URL to redirect to
		* @param string $fields Form fields
		* @return void
		* @static 
		*/
		public static function RedirectPOST($url, $fields)
		{
			$form = "
			<html>
			<head>
			<script type='text/javascript'>
			function MM_findObj(n, d) { //v4.01
			  var p,i,x;  if(!d) d=document; if((p=n.indexOf('?'))>0&&parent.frames.length) {
				d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
			  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
			  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
			  if(!x && d.getElementById) x=d.getElementById(n); return x;
			}
			</script>
			</head>
			<body>
			<form name='form1' method='post' action='$url'>";
			foreach ($fields as $fk=>$fv)
				$form .= "<input type='hidden' id='$fk' name='$fk' value='$fv'>";
			$form .= "</form>
			<script type='text/javascript'>
			MM_findObj('form1').submit();
			</script>
			</body>
			</html>
			";
			
			die($form);
		}
		
		static public function GetTransferExtraFieldsForSmarty($config)
		{
			$fields = array();
			
			$transfer_conf = $config->domain->transfer; 
			
			$manifest_fields = $transfer_conf->xpath("fields/field");
			foreach ($manifest_fields as $field)
			{
				$attrs = (array)$field;
				$attrs = $attrs['@attributes'];    		        
				$ContactConfig[(string)$attrs["name"]] = (array)$attrs;
				
				/*if ($attrs['type'] == 'phone')
				{
					$ContactConfig[(string)$attrs['name']] = $this->MakePhoneField((array)$attrs);
				}
				else */if ($attrs["type"] == "select")
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
	                    		}
	                    	break;
	                    	
	                    	case "database":
	                    	
		                    	$db = Core::GetDBInstance();
		                        $dbinfo = (array)$field->database->attributes();
		                        $dbinfo = $dbinfo['@attributes'];
		        	            
		        	            $values = $db->Execute("SELECT `{$dbinfo['value_field']}` as `key`, `{$dbinfo['name_field']}` as `name` FROM `{$dbinfo['table']}`");
		        	            while ($value = $values->FetchRow())
	                                $ContactConfig[(string)$attrs["name"]]["values"][$value["key"]] = _($value["name"]);
	                    	
	                    	break;
	                    }
					}
	            }
	        }
	        
	        foreach ($ContactConfig as $fieldname=>$fieldinfo)
			{
			    $fields[_($fieldinfo["description"])] = array(
			      "type"          => $fieldinfo["type"],
			      "name"          => $fieldname,
			      "isrequired"    => $fieldinfo["required"],
			      "note"          => $fieldinfo["note"],
			      "ctype"		  => $contact_type,
			      "groupname"	  => $fieldinfo["group"],
			      "iseditable"    => (int)$fieldinfo["iseditable"]
			    );
			    
			    if ($fieldinfo["type"] == "select")
			    	$fields[$fieldinfo["description"]]["values"] = $fieldinfo["values"];
			      
				if ($fieldinfo["type"] == "phone")
				{
					$fields[$fieldinfo['description']]['format'] = $fieldinfo['format'];
					$fields[$fieldinfo['description']]['items'] = $fieldinfo['items'];
					$fields[$fieldinfo['description']]['display_name'] = $fieldinfo['name'] . '_display';
				}
			}
			
			return $fields;
		}
		
		static public function GetRegExtraFieldsForSmarty($config)
		{
			$manifest_fields = $config->domain->registration->extra_fields->xpath("field"); 
				
			foreach ($manifest_fields as $field)
			{
				$attrs = (array)$field;
				$attrs = $attrs['@attributes'];    		        
				$ContactConfig[(string)$attrs["name"]] = (array)$attrs;
				
				if ($attrs["type"] == "select")
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
	                    			$vattrname = trim($vattrs["name"]);
		                	        $ContactConfig[(string)$attrs["name"]]["values"][trim($vattrs["value"])] = $vattrname ? _(trim($vattrs["name"])) : "";
	                    		}
	                    	break;
	                    	
	                    	case "database":
	                    	
		                    	$db = Core::GetDBInstance();
		                        $dbinfo = (array)$field->database->attributes();
		                        $dbinfo = $dbinfo['@attributes'];
		        	            
		        	            $values = $db->Execute("SELECT `{$dbinfo['value_field']}` as `key`, `{$dbinfo['name_field']}` as `name` FROM `{$dbinfo['table']}`");
		        	            while ($value = $values->FetchRow())
		        	            	$ContactConfig[(string)$attrs["name"]]["values"][$value["key"]] = _($value["name"]);
	                    	
	                    	break;
	                    }
					}
	            }
	        }
	        
	        foreach ($ContactConfig as $fieldname=>$fieldinfo)
			{
			    $kk = _($fieldinfo["description"]);
				$fields[$kk] = array(
			      "type"          => $fieldinfo["type"],
			      "name"          => $fieldname,
			      "isrequired"    => $fieldinfo["required"],
			      "hint"          => $fieldinfo["note"],
			      "ctype"		  => $contact_type,
			      "groupname"	  => $fieldinfo["group"],
			      "iseditable"    => (int)$fieldinfo["iseditable"]
			    );
			    
			    if ($fieldinfo['type'] == 'checkbox')
			    	$fields[$kk]['value'] = $fieldinfo['value'];
			    
			    if ($fieldinfo["type"] == "select")
			    	$fields[$kk]["values"] = $fieldinfo["values"];
			      
				if ($fieldinfo["type"] == "phone")
				{
					$fields[$kk]['format'] = $fieldinfo['format'];
					$fields[$kk]['items'] = $fieldinfo['items'];
					$fields[$kk]['display_name'] = $fieldinfo['name'] . '_display';
				}
			}
			
			return $fields;
		}
		
		
		/**
		 * Return list contacts for transfer routine for smarty
		 *
		 * @param object $config
		 * @return array
		 */
		static public function GetTransferContactsForSmarty($config)
		{
			$contacts = array();
			
			$cc = $config->contacts->xpath("contact"); 
			foreach ($cc as $k=>$contact)
			{
				settype($contact, "array");
				$contact = $contact["@attributes"];
				
				
				$contacts[] = $contact["type"];
			}
	
			return $contacts;
		}
		
		
		/**
		 * Return Domain contacts list for Smarty
		 *
		 * @param object $config
		 * @return array
		 */
		static public function GetContactsListForSmarty($config, $for_transfer = false)
		{
			$contacts = array();
			
			$cc = $config->contacts->xpath("contact");
			
			foreach ($cc as $k=>$contact)
			{		
				settype($contact, "array");
				$contact = $contact["@attributes"];
				
				if ($for_transfer)
				{
					$trn_contact = $config->domain->transfer->contacts->xpath('contact[@type="'.$contact['type'].'"]');
					if ($trn_contact)
					{
						$trn_contact = (array)$trn_contact[0];
						$contact = array_merge($contact, $trn_contact['@attributes']);
					}
				}				
				
				
				$contacts[] = array(
					"type" 			 => (string)$contact["type"],
					"name" 			 => (string)$contact["name"],
					"groupname"		 => (string)$contact["group"],
					"childof"		 => (string)$contact["childof"],
					"isrequired"	 => (string)$contact["required"]
				);
			}
		
			return $contacts;
		}
		
	}
?>