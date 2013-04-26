<?php

require_once (dirname(__FILE__) . "/class.Service20110217.php");

class EppDrs_Api_Service20111014 extends EppDrs_Api_Service20110217
{
	/**
	 * @param $params = array(
	 * 		clid		string		Contact CLID
	 * 		mode		string		'registry' - get info from registry server
	 * 								'local' - get info from local database
	 * )
	 * @return eppdrs-api.xsd#getContactInfoResponse
	 */
	function GetContactInfo($params) {
		$this->CheckContactAccess($params['clid']);

		// Do
		$contact = DBContact::GetInstance()->LoadByCLID($params['clid']);		
		if (strtolower($params['mode']) == self::INFO_MODE_REGISTRY) { // Request registry server 
			$registry = $contact->ModuleName ? 
				$this->registry_factory->GetRegistryByName($contact->ModuleName) :
				$this->registry_factory->GetRegistryByExtension($contact->Extension);
			
			$registry->GetRemoteContact($contact);
		}

		$ret = new stdClass();
		$ret->clid = $contact->CLID;
		$ret->name = $contact->GetFullName();
		$ret->email = $contact->GetEmail();
		$ret->fields = new stdClass();
		foreach ($contact->GetFieldList() as $name => $value) {
			if (! in_array($name, array('fax_display', 'voice_display'))) {
				$ret->fields->${name} = $value;
			}
		}

		return $ret;
	}


	/**
	 * @param $params = array(
	 * 		clid		string		Contact CLID
	 * 		fields		array		
	 * )
	 * @return eppdrs-api.xsd#updateContactResponse
	 */
	function UpdateContact($params) {
		$this->CheckContactAccess($params['clid']);
		
		$contact = DBContact::GetInstance()->LoadByCLID($params['clid']);
		$registry = $contact->ModuleName ? 
			$this->registry_factory->GetRegistryByName($contact->ModuleName) :
			$this->registry_factory->GetRegistryByExtension($contact->Extension);
			
		try {
			$contact->SetFieldList($params['fields']);
		} catch (ErrorList $e) {
			throw new Exception(join("\n", $e->GetAllMessages()));
		}
		$registry->UpdateContact($contact);

		return new stdClass();
	}
}