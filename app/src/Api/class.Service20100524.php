<?php


require_once (dirname(__FILE__) . "/class.Service20100408.php");

class EppDrs_Api_Service20100524 extends EppDrs_Api_Service20100408
{
	function GetTldInfo ($params=null)
	{
		$ret = parent::GetTldInfo($params);
		$registry = $this->registry_factory->GetRegistryByExtension($params["tld"]);
		$manifest = $registry->GetManifest();		
		
		$contacts = new stdClass();
		
        // Get contact type groups from module manifest
		$groups = array();
		$XmlGroups = $manifest->GetSectionConfig()->xpath('contact_groups/group');
		foreach ($XmlGroups as $XmlGroup)
		{
            $group = new stdClass();
            $group->name = "{$XmlGroup->attributes()->name}";
            $group->title = _($XmlGroup->attributes()->title);
            $group->fields = new stdClass();

            $this->ExportDataFormFields($XmlGroup->fields->field, $group->fields);
			$this->ExportDataFormFields($XmlGroup->extra_fields->field, $group->fields);
			
			$groups[] = $group;
		}
		
		
		$types = array();
		foreach ($manifest->GetContactTypes() as $contact_type) {
			$config = $manifest->GetContactConfig($contact_type);
			$type = new stdClass();
			$type->name = "{$config->attributes()->type}";
			$type->group = "{$config->attributes()->group}";
			$type->required = "{$config->attributes()->required}";

			$types[] = $type;
		}
		
		$contacts->groups = new stdClass();
		$contacts->groups->group = $groups;
		$contacts->types = new stdClass(); 
		$contacts->types->type = $types;
		$ret->contacts = $contacts;
		
		return $ret;
	}
	
	/**
	 * @param $params = array(
	 * 		tld 		string
	 * 		group 		string
	 * 		type		string
	 * 		userId		int			User ID (Only in Admin mode)
	 * 		.. fields ..
	 * )
	 * @return stdClass
	 */
	function CreateContact ($params=null)
	{
		if ($this->access_mode == self::ACCESS_MODE_ADMIN)
		{
			if (!$params["userId"])
				throw new Exception(sprintf("'%s' parameter is required", "userId"));
		}
		if (!$params["tld"]) {
			throw new Exception(sprintf("'%s' parameter is required", "tld"));
		}
		if (!($params["group"] || $params["type"])) {
			throw new Exception("'group' or 'type' parameter must be provided");
		}
		
		$registry = $this->registry_factory->GetRegistryByExtension($params["tld"]);
		$contact = $params["group"] ? 
				$registry->NewContactInstanceByGroup($params["group"]) : $registry->NewContactInstance($params["type"]);
		$contact->UserID = $this->access_mode == self::ACCESS_MODE_ADMIN ?
				$params["userId"] : $this->user_id;
				
		$fields = $params;
		unset($fields["userId"], $fields["tld"], $fields["group"], $fields["type"]);
		try {
			$contact->SetFieldList($fields);
		} 
		catch (ErrorList $e) {
			throw new Exception(join("; ", $e->getAllMessages()));
		}
		$registry->CreateContact($contact);
		
		$ret = new stdClass();
		$ret->clid = $contact->CLID;
		return $ret;
	}
}