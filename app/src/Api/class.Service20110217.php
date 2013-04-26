<?php

require_once (dirname(__FILE__) . "/class.Service20100524.php");

class EppDrs_Api_Service20110217 extends EppDrs_Api_Service20100524 {
	

	/**
	 * 
	 * @param $params = array(
	 * 		query		string		Search over contact fields and clid
	 * 		userId		int 		User ID (Only in admin mode)
	 * 
	 * 		start		int			Data slice start. Default 0
	 * 		limit		int			Date slice size. Default 25. -1 Unlimited 
	 * )
	 * @return object
	 */ 
	function ListContacts ($params=null) 
	{
		$sql = "SELECT DISTINCT c.* from contacts AS c";
		$where = array();
		$bind = array();
		
		// Apply filter
		if ($params["query"])
		{
			$query = mysql_escape_string($params["query"]);
			$sql .= " LEFT JOIN contacts_data as cd ON c.clid = cd.contactid";
			$where[] = "(c.clid LIKE '%{$query}%' OR cd.`value` LIKE '%{$query}%')";
		}
		
		if ($this->access_mode == self::ACCESS_MODE_ADMIN)
		{
			if ($params["userId"])
			{
				$where[] = "c.userid = ?";
				$bind[] = $params["userId"]; 
			}
		}
		else // User access mode
		{
			// Users can search only in their domains
			$where[] = "c.userid = ?";
			$bind[] = $this->user_id;
		}			
		
		// Build SQL
		$sql .= $where ? " WHERE ".join(" AND ", $where) : "";
		$sql_total = preg_replace('/DISTINCT c\.\*/', 'COUNT(DISTINCT c.id)', $sql, 1);
		
		// Sorting
		$sql .= " ORDER BY id ASC";
		
		// Apply limits
		if ($params["limit"] != -1)
		{
			$sql .= sprintf(
				" LIMIT %d, %d", 
				$params["start"] ? $params["start"] : 0,
				$params["limit"] ? $params["limit"] : 25
			);
		}			
				
		$rows = $this->db->GetAll($sql, $bind);
		$total = $this->db->GetOne($sql_total, $bind);
		
		$ret = new stdClass();
		$ret->contactList = new stdClass();
		$ret->total = $total;
		
		$db_contact = DBContact::GetInstance();
		foreach ($rows as $row)
		{
			$ret_contact = new stdClass();
			$ret_contact->clid = $row["clid"];
			
			try
			{
				$contact = $db_contact->Load($row["id"]);
				$ret_contact->name = $contact->GetFullName();
				$ret_contact->email = $contact->GetEmail();
			}
			catch (Exception $e)
			{
				$ret_contact->name = $ret_contact->email = "Unknown";
			}
			
			$ret->contactList->contact[] = $ret_contact;
		}
		
		return $ret;
	}


	/**
	 * Set domain flags
	 * 
	 * @param $params = array(
	 * 		name		string			Domain name
	 * 		add			array[string]
	 * 		remove		array[string]
	 * )
	 * @return object
	 */ 
	function UpdateDomainFlags ($params=null) {
		list($name, $tld) = $this->SplitNameAndTLD($params["name"]);
		if ($this->access_mode == self::ACCESS_MODE_USER)
			$this->CheckDomainAccess($name, $tld);			

		$db_domain = DBDomain::GetInstance();
		$domain = $db_domain->LoadByName($name, $tld);
			
		$registry = $this->registry_factory->GetRegistryByExtension($tld);
		$flags = $domain->GetFlagChangelist();
		if ($params['add']) {
			foreach ((array)$params['add'] as $flag) {
				$flags->Add($flag);
			}
		}
		if ($params['remove']) {
			foreach ((array)$params['remove'] as $flag) {
				$flags->Remove($flag);
			}
		}
		$registry->UpdateDomainFlags($domain, $flags);
		
		return new stdClass();		
	} 
	
	
	/**
	 * 
	 * @param $params = array(
	 * 		name		string		Domain name
	 * 		mode		string		'remote' - get info from registry server
	 * 								'local' - get info from local database
	 * )
	 * @return eppdrs-api.xsd#getDomainInfoResponse
	 */
	function GetDomainInfo ($params=null) 
	{
		// Accept params
		list($name, $tld) = $this->SplitNameAndTLD($params['name']);
		
		// Check access
		$this->CheckDomainAccess($name, $tld);
		
		// Do
		if (strtolower($params['mode']) == self::INFO_MODE_REGISTRY) // Request registry server 
		{
			$registry = $this->registry_factory->GetRegistryByExtension($tld);
			$domain = $registry->NewDomainInstance();
			$domain->Name = $name;
			
			$grd_response = $registry->GetModule()->GetRemoteDomain($domain);
			if (!$grd_response->Succeed()) {
				throw new RegistryException($grd_response->ErrMsg, $grd_response->Code);
			}

			$ret = new stdClass();
			$ret->name = $domain->GetHostName();
			
			$ret->contacts = new stdClass();
			if ($grd_response->RegistrantContact)
				$ret->contacts->registrant = $grd_response->RegistrantContact;
			if ($grd_response->AdminContact)
				$ret->contacts->admin = $grd_response->AdminContact;
			if ($grd_response->BillingContact)
				$ret->contacts->billing = $grd_response->BillingContact;
			if ($grd_response->TechContact)
				$ret->contacts->tech = $grd_response->TechContact;
				
			if ($grd_response->GetNameserverList())
			{
				$ret->ns = array();
				foreach ($grd_response->GetNameserverList() as $Nameserver)
				{
					$ret->ns[] = $Nameserver->HostName;
				}
			}

			if ($grd_response->CreateDate)					
				$ret->createDate = date($this->date_format, $grd_response->CreateDate);
			if ($grd_response->ExpireDate)
				$ret->expireDate = date($this->date_format, $grd_response->ExpireDate);
			
			$ret->locked = (int)$grd_response->IsLocked;
			
			// Remote specific properties
			
			// Registry status (ok, pendingCreate ...)
			$ret->registryStatus = $grd_response->RegistryStatus;
			$ret->flag = $grd_response->GetFlagList();
			if ($grd_response->AuthCode) {
				$ret->authCode = $grd_response->AuthCode;
			} 
		}
		else // Request local database
		{
			$db_domain = DBDomain::GetInstance();
			$domain = $db_domain->LoadByName($name, $tld);
			
			$ret = new stdClass();
			$ret->name = $domain->GetHostName();
			
			$ret->contacts = new stdClass();
			$contacts = $domain->GetContactList();
			if ($contacts[CONTACT_TYPE::REGISTRANT])
				$ret->contacts->registrant = $contacts[CONTACT_TYPE::REGISTRANT]->CLID;
			if ($contacts[CONTACT_TYPE::ADMIN])
				$ret->contacts->admin = $contacts[CONTACT_TYPE::ADMIN]->CLID;
			if ($contacts[CONTACT_TYPE::BILLING])
				$ret->contacts->billing = $contacts[CONTACT_TYPE::BILLING]->CLID;
			if ($contacts[CONTACT_TYPE::TECH])
				$ret->contacts->tech = $contacts[CONTACT_TYPE::TECH]->CLID;

			if ($domain->GetNameserverList())
			{
				$ret->ns = array();
				foreach ($domain->GetNameserverList() as $ns)
				{
					$ret->ns[] = $ns->HostName;
				}
			}
			
			if ($domain->CreateDate)
				$ret->createDate = date($this->date_format, $domain->CreateDate);
			if ($domain->ExpireDate)
				$ret->expireDate = date($this->date_format, $domain->ExpireDate);
			
			$ret->locked = (int)$domain->IsLocked;

			// Local specific properties.
			
			// Local status. See DOMAIN_STATUS::*
			$ret->localStatus = $domain->Status;
			$ret->flag = $domain->GetFlagList();
			$ret->authCode = $domain->AuthCode;
		}
		
		return $ret;
	}
	

}