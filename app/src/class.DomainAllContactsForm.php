<?php

class DomainContactForm
{
	
}

class DomainAllContactsForm
{
	const MAX_ITEMS = 25;
	
	// Properties, accessible for the setting through the configuration
	private 
		$tld,
		$Registry,
		$Domain,
		$userid,
		$defer_render = false,
		$form_title,
		$button_text,
		$form_action = '',
		$form_method = 'POST',
		$form_fields = array();
		
	private $Db;
		
	private $rendered_data;
	
	public function __construct($config)
	{
		$Ref = new ReflectionObject($this);
		foreach ($config as $k => $v)
		{
			if ($Ref->hasProperty($k))
			{
				$this->{$k} = $v;
			}
		}
		
		// Data validation
		if (!($this->tld || $this->Registry))
		{
			throw new LogicException('$tld or $Registry must be defined');
		}
		if (!$this->userid)
		{
			throw new LogicException('Property $userid is undefined');
		}
		
		// Initialization
		$this->Db = Core::GetDBInstance();
		if (!$this->Registry)
		{
			$Factory = RegistryModuleFactory::GetInstance();
			try
			{
				$this->Registry = $Factory->GetRegistryByExtension($this->tld);
			}
			catch (Exception $e)
			{
				throw new RuntimeException(sprintf(_("Cannot init registry module. Reason: %s"), $e->getMessage()));
			}
		}
		else
		{
			$this->tld = $this->Registry->GetModule()->Extension;
		}
		if (!$this->form_title)
		{
			$this->form_title = _("Select contacts");
		}
		if (!$this->button_text)
		{
			$this->button_text = _("Next");
		}
		
		
		if (!$this->defer_render)
		{
			$this->Render();	
		}
	}
	
	/**
	 * Enter description here...
	 */
	public function Render ()
	{
		$this->rendered_data = array();
		$Manifest = $this->Registry->GetManifest();
		$section_shared_contacts = (bool)$Manifest->GetRegistryOptions()->ability->section_shared_contacts;		
		$contacts_config = UI::GetContactsListForSmarty($Manifest->GetSectionConfig());	
		$DbContact = DBContact::GetInstance();
	
		
        foreach ($contacts_config as $v)
        {
        	$smarty_contact = $v;
        	$smarty_contact["groupname"] = $Manifest->GetGroupNameByContactType($v["type"]);
        	if (!$section_shared_contacts)
        	{
        		$section_name = $Manifest->GetSectionName();
				$target_index = $Manifest->GetContactTargetIndex($this->tld, $smarty_contact["groupname"]);
        		$smarty_contact['target_title'] =  $Manifest->GetContactTargetTitle($this->tld, $smarty_contact["groupname"]);        		
        	}
        	else
        	{
        		$section_name = "";
        		$target_index = 0;
        		$smarty_contact['target_title'] = $this->Registry->GetModuleName();
        	}
			
			
        	// Calculate contact num in group
        	$num_items = (int) $this->Db->GetOne("
        		SELECT COUNT(clid) FROM contacts 
        		WHERE userid=? AND 
        		(TLD = ? OR (module_name=? AND section_name=? AND target_index=?)) AND 
        		groupname=?", 
        		array(
					$this->userid, 
					//
					$this->tld, 
					$this->Registry->GetModuleName(),
					$section_name,
					$target_index, 
					//
					$smarty_contact["groupname"]
        		)
        	);
			
        	
        	if ($num_items < self::MAX_ITEMS)
        	{
        		// Render simple contact select list
	        	$smarty_contact["exists"] = $this->Db->GetAll("
	        		SELECT clid FROM contacts 
	        		WHERE userid=? AND 
	        		(TLD = ? OR (module_name=? AND section_name=? AND target_index=?)) AND 
	        		groupname=? 
	        		ORDER BY id ASC", 
					array(
						$this->userid, 
						//
						$this->tld, 
						$this->Registry->GetModuleName(), 
						$section_name,
						$target_index, 
						//
						$smarty_contact["groupname"]
					)
				);
				
				$DomainContact = null;
				if ($this->Domain)
				{
					$DomainContact = $this->Domain->GetContact($v["type"]);
					
				}
				
				$smarty_contact["disabled"] = array();
				foreach ($smarty_contact["exists"] as &$c)
	            {
	                try
	                {
	            		$Contact = $DbContact->LoadByCLID($c["clid"]);
	            		$smarty_contact["disabled"][$c["clid"]] = count($Contact->GetPendingOperationList()); 
	            		
	                	$c['title'] = $Contact->GetTitle();
	                	
	                	// Check selected
	                	if ($DomainContact && $DomainContact->CLID == $c["clid"])
	                	{
		                	$c["selected"] = true;
		                	$smarty_contact['selected'] = $c['clid'];
	                	}
	                }
	                catch(Exception $e)
	                {
	                	Log::Log($e->getMessage(), E_USER_ERROR);
	                	unset($c);
	                	continue;
	                }
	           		catch(ErrorList $e)
	                {
	                	Log::Log(join('; ', $e->GetAllMessages()), E_USER_ERROR);
	                	unset($c);
	                	continue;
	                }
	            }
	            
	            $smarty_contact['list'] = array();
	            foreach ($smarty_contact['exists'] as $ex)
	            {
	            	$smarty_contact['list'][$ex['clid']] = $ex['title'];
	            }
        	}
        	else
        	{
        		// External contact select
            	$smarty_contact['too_many_items'] = true;
        	}
            
            $this->rendered_data['contacts'][] = $smarty_contact;
		}
		
		$this->rendered_data['tld'] = $this->tld;
		$this->rendered_data['form_title'] = $this->form_title;
		$this->rendered_data['button_text'] = $this->button_text;
		$this->rendered_data['form_action'] = $this->form_action;
		$this->rendered_data['form_method'] = $this->form_method;
		$this->rendered_data['form_fields'] = $this->form_fields;
	}
	
	public function GetRenderedData ()
	{
		return $this->rendered_data;
	}
}
?>