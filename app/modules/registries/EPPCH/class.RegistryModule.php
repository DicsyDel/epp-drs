<?

	// +--------------------------------------------------------------------------+
	// | Generic EPP Module                                         		      |
	// +--------------------------------------------------------------------------+
	// | Copyright (c) 2003-2006 Webta Inc, http://webta.net/copyright.html       |
	// +--------------------------------------------------------------------------+
	// | This program is protected by international copyright laws. Any           |
	// | use of this program is subject to the terms of the license               |
	// | agreement included as part of this distribution archive.                 |
	// | Any other uses are strictly prohibited without the written permission    |
	// | of "Webta" and all other rights are reserved.                            |
	// | This notice may not be removed from this source code file.               |
	// | This source file is subject to version 1.1 of the license,               |
	// | that is bundled with this package in the file LICENSE.                   |
	// | If the backage does not contain LICENSE file, this source file is        |
	// | subject to general license, available at http://webta.net/license.html   |
	// +--------------------------------------------------------------------------+
	// | Authors: Igor Savchenko <igor@webta.net> 								  |
	// +--------------------------------------------------------------------------+
	
		
	class EPPCHRegistryModule extends GenericEPPRegistryModule implements IRegistryModuleClientPollable
	{
		/**
		 * @return DataForm
		 */
		public function GetTestConfigurationForm ()
		{
			$CF = new DataForm();
			$CF->AppendField(new DataFormField("ServerHost", FORM_FIELD_TYPE::TEXT , "Server host", 1));
			$CF->AppendField(new DataFormField("ServerPort", FORM_FIELD_TYPE::TEXT , "Server port", 1));			
			$CF->AppendField(new DataFormField("Login", FORM_FIELD_TYPE::TEXT, "Login", 1));
			$CF->AppendField(new DataFormField("Password", FORM_FIELD_TYPE::TEXT, "Password", 1));
			$CF->AppendField(new DataFormField("NewPassword", FORM_FIELD_TYPE::TEXT, "New password", 1));
			
			return $CF;
		}
		
		public function RunTest ($DF)
		{
			$filename = '/tmp/eppdrs-eppch-certtest-' . date('YmdHis') . '.log';
		    Log::RegisterLogger("File", "EPPCH", $filename);
			Log::SetDefaultLogger("EPPCH");			
			
			// Run test
			$Test = new EPPCHRegistryModule_CertTest();
			$Test->SetUp($DF);
			try
			{
				$Test->Run();
			}
			catch (Exception $Error) {}
			
			// Check passed
			$passed = $Test->Passed() && (!isset($Error));
			
			// Write output file
			$out_filename = sprintf('eppdrs-eppch-certtest-%s.log', $passed ? 'passed' : 'failed');
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $out_filename . '"');
			
			foreach ($Test->GetReport() as $i => $item)
			{
				$n = $i+1;
				print str_pad("{$n}. {$item['message']}", 60, ' ', STR_PAD_RIGHT);
				printf("[%s]\n", $item['passed'] ? 'OK' : 'FAIL');
			}
			if (isset($Error))
			{
				print "[Exception] {$Error->getMessage()} at {$Error->getFile()} line {$Error->getLine()}\n";
			}
			
			print "\n\n";
			
			// Append system log to output
			print file_get_contents($filename);
			unlink($filename);
			
			die();
		}
		
		public function DomainCanBeTransferred(Domain $domain)
	    {
	    	$Ret = new DomainCanBeTransferredResponse(REGISTRY_RESPONSE_STATUS::SUCCESS);
    	
	    	$CanRegisteredResponse = $this->DomainCanBeRegistered($domain);
	    	if ($CanRegisteredResponse->Result == false)
	    	{ 	
		    	try
		    	{
		    		$params = array(
						"name"	=> $this->MakeNameIDNCompatible($domain->GetHostName()),			
						'authinfo' => ''			
		    		);
		    		$Resp = $this->Request('domain-info', $params);
		    		if ($Resp->Succeed)
		    		{
		    			$infData = $Resp->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
		    			$infData = $infData[0];
		    			
		    			// Check for non existed authInfo node .-) 
		    			$Ret->Result = $infData->authInfo->getName() == ''; 
		    		}
		    		else if ($Resp->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR)
		    		{
		    			$Ret->Result = true;
		    		}
		    		else
		    		{
		    			$Ret->Result = false;
		    		}
		    	}
		    	catch(ObjectNotExistsException $e)
		    	{
		    		$Ret->Result = true;
		    	}
	    	}
	    	else
	    		$Ret->Result = false;
	    	
	    	return $Ret;
	    }		
		
		/**
		 * Return information about domain
		 * 
		 * @access public
		 * @param Domain $domain 
		 * @return GetRemoteDomainResponse Domain info if the following format:
		 */
		public function GetRemoteDomain(Domain $domain)
		{
			$params = array(
				"name"	=> $this->MakeNameIDNCompatible($domain->GetHostName()),			
				'authinfo' => ''			
			);
			if ($domain->AuthCode)
				$params['authinfo'] = "<domain:authInfo><domain:pw>".$this->EscapeXML($domain->AuthCode)."</domain:pw></domain:authInfo>";				
			
			$response = $this->Request("domain-info", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			$resp = new GetRemoteDomainResponse($status, $response->ErrMsg, $response->Code);
	
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children("urn:ietf:params:xml:ns:domain-1.0");
				$info = $info[0];
				
				$resp->CLID = (string)$info->clID[0];
				

				$resp->AuthCode = ($info->authInfo[0]) ? (string)$info->authInfo[0]->pw[0] : "";
				
				if ($info->exDate[0])
				{
					$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
					$resp->CreateDate = $resp->ExpireDate-(86400*365);
				}
				
				foreach ($info->contact as $k=>$v)
				{
					$attrs = $v->attributes();
					$ctype = (string)$attrs["type"];
					
					switch($ctype)
					{
						case "tech":
							$resp->TechContact = (string)$v;
							break;
					}
				}
				
				$resp->RegistrantContact = (string)$info->registrant[0];
				
				// Get nameservers
				$ns_arr = array();
				foreach ($info->ns->hostObj as $v)
				{
					$hostname = (string)$v;
					if (FQDN::IsSubdomain($hostname, $domain->GetHostName()))
					{
						try
						{
							$ip = $this->GetHostIpAddress($hostname);
							$ns_arr[] = new NameserverHost($hostname, $ip);
						}
						catch (Exception $e) 
						{
							$ns_arr[] = new NameserverHost($hostname, '');
						}
					}
					else
					{
						// nameserver
						$ns_arr[] = new Nameserver($hostname);						 
					}					
				}
				
				$resp->SetNameserverList($ns_arr);
				
				if ($info->status[0])
				    $attrs = $info->status[0]->attributes();
				elseif ($info->status)
				    $attrs = $info->status->attributes();
				else 
				    $attrs["s"] = false;
				    
				$resp->RegistryStatus = (string)$attrs["s"];
			}
			
			return $resp;
		}
		
		/**
		 * Create domain and return transaction status
		 *  
		 *  Domain info is an array of the following structure:
		 *  Fill domain Data with this values
		 * 
		 *  		"crDate"   => string Creation DateTime,
		 *			"exDate"   => Expiration DateTime,
		 *			"status"   => string Status code,
		 *			"pw"	   => string Password generated by registry,
		 *			"protocol" => string Protocol
		 *	 
		 * @access public
		 * @param Domain $domain Domain name without TLD
		 * @param array $owner_contact Domain Owner contact array
		 * @param array $admin_contact Domain Admin contact array
		 * @param array $tech_contact Domain Tech contact array
		 * @param array $billing_contact Domain Billing contact array
		 * @param array $extra Domain Billing contact array
		 * @param integer $period Registration period, years
		 * @param array $nameservers Array of strings containing nameservers hostnames 
		 * @return Domain
		 * 
		 * @version v1000
		 */
		public function CreateDomain(Domain $domain, $period, $extra = array())
		{
			$contacts = $domain->GetContactList();
							
			$nameservers = $domain->GetNameserverList();

			$params = array(
				"name"				=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"registrant_id"		=> $contacts['registrant']->CLID,
				"period"			=> $period,
				"pw"				=> rand(100000000, 999999999)
			);
			
			unset($contacts[CONTACT_TYPE::REGISTRANT]);
			$params['contacts'] = '';
			foreach ($contacts as $contact_type => $contact)
				$params['contacts'] .= '<domain:contact type="'.$contact_type.'">'.$contact->CLID.'</domain:contact>';
				
			$params['ns'] = '<domain:ns>' . $this->GetNSXML($nameservers) . '</domain:ns>';
			
			
			$this->BeforeRequest('domain-create', $params, __METHOD__, $domain, $period, $extra);
			$response = $this->Request("domain-create", $params);
		
			if ($response->Code == RFC3730_RESULT_CODE::OK_PENDING)
			    $status = REGISTRY_RESPONSE_STATUS::PENDING;
			elseif ($response->Succeed)
			    $status = REGISTRY_RESPONSE_STATUS::SUCCESS;
			else
				$status = REGISTRY_RESPONSE_STATUS::FAILED;
			
			$resp = new CreateDomainResponse($status, $response->ErrMsg, $response->Code);
			
			if ($response->Succeed)
			{
				$info = $response->Data->response->resData->children($this->XmlNamespaces['domain']);
				$info = $info[0];
	
				$resp->CreateDate = $this->StrToTime((string)$info->crDate[0]); 
				$resp->ExpireDate = $this->StrToTime((string)$info->exDate[0]); 
				
				//$Grd = $this->GetRemoteDomain($domain);
				//$resp->AuthCode = (string)$Grd->AuthCode;
				
				$resp->AuthCode = (string)$params["pw"];
			}
			
			return $resp;			
			
		}
		
		/**
		 * Send domain transfer approval
		 *
		 * @param string $domain Domain required data: name, pw
		 * @param array $extradata Extra fields
		 * @return bool True on success of false on failure
		 */
		//public function TransferApprove(Domain $domain, $extra=array())
		//{
		//	// NOT SUPPORTED BY SWITCH		
		//}
		
		/**
		 * Send domain transfer rejection
		 *
		 * @param string $domain Domain required data: name, pw
		 * @param array $extradata Extra fields
		 * @return bool True on success of false on failure
		 */
		public function TransferReject(Domain $domain, $extra=array())
		{
			// NOT SUPPORTED BY SWITCH			
		}
		
		public function UpdateDomainContact(Domain $domain, $contactType, Contact $oldContact, Contact $newContact)
		{
			if (!$newContact && !$oldContact)
				throw new Exception("At leat one contact (\$newContact or \$oldContact) must be passed into UpdateDomainContact");
			
			if ($contactType == CONTACT_TYPE::TECH && 
				preg_match('/^\d+/', $domain->GetContact(CONTACT_TYPE::REGISTRANT)->CLID))
			{
				throw new Exception("Domain transfer from SWITCH was buggy. You must update registrant contact first");
			}
				
			$params = array(
				"name" 		=> $this->MakeNameIDNCompatible($domain->GetHostName()),
				"change"	=> "",
				"add"		=> "",
				"rem"		=> ""
			);
			
			if ($contactType == CONTACT_TYPE::REGISTRANT)
				$params["change"] = "<domain:chg><domain:registrant>{$newContact->CLID}</domain:registrant></domain:chg>";
			else
			{
				if ($newContact)
					$params['add'] = '<domain:add><domain:contact type="'.$contactType.'">'.$newContact->CLID.'</domain:contact></domain:add>';
				
				if ($oldContact && !preg_match('/^\d+/', $oldContact->CLID)) 
					$params['rem'] = '<domain:rem><domain:contact type="'.$contactType.'">'.$oldContact->CLID.'</domain:contact></domain:rem>';
			}
			
			$response = $this->Request("domain-update-contact", $params);
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			
			// Hack for bad transferred from SWITCH	
			if ($response->Succeed && preg_match('/^\d+/', $oldContact->CLID) && $contactType == CONTACT_TYPE::REGISTRANT)
			{
				$domain->SetContact(null, CONTACT_TYPE::TECH);
			}
			
			return new UpdateDomainContactResponse($status, $response->ErrMsg, $response->Code);
		}		
		
		/**
		 * Delete contact
		 *
		 * @param Contact $contact Contact uniq CLID
		 * @param array $extra Extra fields
		 */
		public function DeleteContact(Contact $contact, $extra = array())
		{
			$params["id"] = $contact->CLID;
		
			$response = $this->Request("contact-delete", $params);
			if ($response->Code == RFC3730_RESULT_CODE::ERR_AUTHORIZE_ERROR)
			{
				throw new ProhibitedTransformException();
			}
			
			$status = ($response->Succeed) ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::FAILED;
			return new DeleteContactResponse($status, $response->ErrMsg, $response->Code);
		}		
		
		/**
		 * @param Domain $domain
		 * @return PollTransferResponse 
		 */
		public function PollTransfer (Domain $domain)
		{
			$RDResponse = $this->GetRemoteDomain($domain);
			
			if ($RDResponse->Succeed())
			{
				if ($RDResponse->AuthCode != '' && $RDResponse->CLID == $this->Config->GetFieldByName('Login')->Value)
					$tstatus = TRANSFER_STATUS::APPROVED;
				else if ($RDResponse->RegistryStatus == 'pendingTransfer')
					$tstatus = TRANSFER_STATUS::PENDING;
				else
					$tstatus = TRANSFER_STATUS::DECLINED;
					
				$status = $tstatus != TRANSFER_STATUS::PENDING ? REGISTRY_RESPONSE_STATUS::SUCCESS : REGISTRY_RESPONSE_STATUS::PENDING;
				
				$resp = new PollTransferResponse($status, $RDResponse->ErrMsg, $RDResponse->Code); 
				$resp->HostName = $domain->GetHostName();
				$resp->TransferStatus = $tstatus;
				if ($tstatus == TRANSFER_STATUS::DECLINED)
				{
					$resp->FailReason = _("Transfer was rejected by the current domain owner.");
				} 				
				
				return $resp;
			}
			else
				return new PollTransferResponse(REGISTRY_RESPONSE_STATUS::FAILED, $RDResponse->ErrMsg, $RDResponse->Code);
		}
		
		public function OnDomainTransferApproved (Domain $domain) 
		{
			$DbDomain = DBDomain::GetInstance();
			$SavedDomain = $DbDomain->GetInitialState($domain);

			//$contacts = $domain->GetContactList();
			
			//$NewReg = $SavedDomain->GetContact(CONTACT_TYPE::REGISTRANT);
			$NewTech = $SavedDomain->GetContact(CONTACT_TYPE::TECH);
			
			// Update transferred domain contacts to our
			/*
			if ($contacts[CONTACT_TYPE::REGISTRANT])
			{
				$this->UpdateDomainContact(
					$domain, 
					CONTACT_TYPE::REGISTRANT, 
					$contacts[CONTACT_TYPE::REGISTRANT],
					$NewReg
				);
				$domain->SetContact($NewReg, CONTACT_TYPE::REGISTRANT);				
			}
			*/
			
			// After registrant change all other contacts clears
			$this->UpdateDomainContact(
				$domain, 
				CONTACT_TYPE::TECH, 
				null,
				$NewTech
			);
			
			$domain->SetContact($NewTech, CONTACT_TYPE::TECH);
		}
		
		/**
		 * @param Domain $domain
		 * @return DomainCreatedResponse
		 */
		public function PollCreateDomain (Domain $domain)
		{
			return;
		}
	
		/**
		 * @param Domain $domain
		 * @return PollDeleteDomainResponse
		 */
		public function PollDeleteDomain (Domain $domain)
		{
			// Not used
			return;
		}
	
		/**
		 * @param Domain $domain
		 * @return PollChangeDomainOwner
		 */
		public function PollChangeDomainOwner (Domain $domain)
		{
			return;
		}
	
		/**
		 * @param Domain $domain
		 * @return DomainUpdatedResponse
		 */
		public function PollUpdateDomain (Domain $domain)
		{
			// Not used
			return;
		}
	
		/**
		 * Called by system when delete contact operation is pending
		 *
		 * @param Contact $contact
		 * @return PollDeleteContactResponse
		 */
		public function PollDeleteContact (Contact $contact)
		{
			// Not used
			return;
		}
	
		/**
		 * Called by system when delete namserver host operation is pending
		 *
		 * @param NamserverHost $nshost
		 * @return PollDeleteNamserverHostResponse
		 */
		public function PollDeleteNamserverHost (NamserverHost $nshost)
		{
			// Not used
			return;
		}
	
		/**
		 * Parse datetime description into a Unix timestamp Ignores timezone
		 */
		protected function StrToTime ($str)
		{
			return strtotime(substr($str, 0, -6));
		}	
	}
	
	
	class EPPCHRegistryModule_CertTest
	{
		private $report = array();
		
		private $passed = true;
		
		public function Passed ()
		{
			return $this->passed;
		}
		
		public function GetReport ()
		{
			return $this->report;
		}
		
		public function AssertTrue ($expr, $message)
		{
			$passed = (bool)$expr;
			$this->passed &= $passed;
			
			$this->report[] = array(
				'passed' => $passed,
				'message' => $message 
			);
		}
		
		// --- //
		
		/**
		 * @var Registry
		 */
		private $Registry;
		
		/**
		 * @var EPPCHRegistryModule
		 */
		private $Module;
		
		/**
		 * @var string
		 */
		private $clID;
		
		public function SetUp (DataForm $TestDF)
		{
			$Manifest = new RegistryManifest(MODULES_PATH . "/registries/EPPCH/module.xml");
			
			$this->Module = new EPPCHRegistryModule($Manifest);
			$this->Module->InitializeModule('ch', $TestDF);
			
			$this->Registry = new Registry($this->Module);
			
			$this->clID = strtolower($TestDF->GetFieldByName('Login')->Value);
		}
		
		public function Run ()
		{
			////
			// 1. Login (will be done automatical in next command)

			
			////
			// 2. Poll. Get transfer auth code
			$Resp = $this->Module->Request('poll-request', array());
			$this->AssertTrue($Resp->Succeed, 'Login');
			
			if ($Resp->Code == RFC3730_RESULT_CODE::OK_ACK_DEQUEUE)
			{
				$msgID = (string)$Resp->Data->response->msgQ->attributes()->id;
				
				$infData = $Resp->Data->response->resData->children('urn:ietf:params:xml:ns:domain-1.0');
				$infData = $infData[0];
				
				$auth_code = (string)$infData->authInfo->pw;
			}
			else
			{
				$auth_code = 'my&p$w#d22.';				
			}
			$this->AssertTrue(isset($msgID, $auth_code), 'Poll');

			////
			// 3. Request transfer

			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = "{$this->clID}-domain-1";
			$Resp = $this->Module->TransferRequest($Domain, array('pw' => $auth_code));
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Transfer domain 1');
		
			
			////
			// 4. Acknowledge message
			
			$Resp = $this->Module->Request('poll-ack', array('msgID' => $msgID));
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Acknowledge message');

						
			////
			// 5. Check contact 1
			
			$Contact1 = $this->Registry->NewContactInstanceByGroup('generic');
			$Contact1->CLID = strtoupper($this->clID)."-1";
			$ok = $this->Registry->ContactCanBeCreated($Contact1);
			$this->AssertTrue($ok, 'Check contact 1');

		
			////
			// 6. Create contact 1
			
			$contact_data = array(
				'name' => 'Firstname Lastname',
				'org' => 'Organisation',
				'street1' => 'Testdivision',
				'street2' => 'Teststrasse 999',
				'city' => 'Bern',
				'pc' => '3001',
				'cc' => 'CH',
				'voice' => '+41-335-555555',
				'email' => 'test1@yourdomain.ch'
			);
			$Contact1->SetFieldList($contact_data);
			$Resp = $this->Module->CreateContact($Contact1);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Create contact 1');
			$Contact1->CLID = $Resp->CLID;

			
			////
			// 7. Create name server 1
			
			$NS3 = new NameserverHost('ns3.' . $Domain->GetHostName(), '240.1.1.1');
			$Resp = $this->Module->CreateNameserverHost($NS3);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Create name server 1');
			
			////
			// 8. Info domain 1
			
			$GrdResp = $this->Module->GetRemoteDomain($Domain);
			$this->AssertTrue($GrdResp->TechContact, 'Info domain 1');
			

			////
			// 9. Info Tech-Contact
			
			$Tech = $this->Registry->NewContactInstanceByGroup('generic');
			$Tech->CLID = $GrdResp->TechContact;
			$Resp = $this->Module->GetRemoteContact($Tech);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Info Tech-Contact');
			
			////
			// 10. Update domain 1 (complete transfer)
			
			$add = '<domain:add>';
			$add .= '<domain:ns><domain:hostObj>'.$NS3->HostName.'</domain:hostObj></domain:ns>';
			$add .= '<domain:contact type="tech">'.$Contact1->CLID.'</domain:contact>';
			$add .= '</domain:add>';
			
			$rem = '<domain:rem>';
			$rem .= '<domain:ns><domain:hostObj>ns1.'.$Domain->GetHostName().'</domain:hostObj></domain:ns>';
			$rem .= '</domain:rem>';
						
			$chg = '<domain:chg>';
			$chg .= '<domain:registrant>'.$Contact1->CLID.'</domain:registrant>';
			$chg .= '</domain:chg>';
			
			$params = array(
				'name' => $Domain->GetHostName(),
				'add' => $add,
				'remove' => $rem,
				'change' => $chg
			);
			
			$Resp = $this->Module->Request('domain-update', $params);
			$this->AssertTrue($Resp->Succeed, 'Update domain 1 (complete transfer)');
				
			
			////
			// 11. Create contact 2
			
			$Contact2 = $this->Registry->NewContactInstanceByGroup('generic');
			$Contact2->CLID = strtoupper($this->clID).'-2';
			$Contact2->SetFieldList(array(
				'name' => 'Firstname2 Lastname2',
				'org' => 'Organisation2',
				'street1' => 'Testdivision',
				'street2' => 'Teststreet 999',
				'city' => 'Bern',
				'cc' => 'CH',
				'pc' => '3001',
				'voice' => '+41-335-555555',
				'email' => 'test2@yourdomain.ch'
			));
			$Resp = $this->Module->CreateContact($Contact2);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Create contact 2');
			$Contact2->CLID = $Resp->CLID;			
			
			
			////
			// 12. Delete domain 1 (for a holder transfer)
			
			$Resp = $this->Module->DeleteDomain($Domain);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Delete domain 1 (for a holder transfer)');


			////
			// 13. Create domain 1
			
			$Domain = $this->Registry->NewDomainInstance();
			$Domain->Name = "{$this->clID}-domain-1";
			$Domain->SetContact($Contact2, CONTACT_TYPE::REGISTRANT);
			$NS2 = new NameserverHost('ns2.'.$Domain->GetHostName(), '240.1.1.1');
			$Domain->SetNameserverList(array($NS2, $NS3));
			
			$Cdr = $this->Module->CreateDomain($Domain, 1);
			$this->AssertTrue($Cdr->CreateDate && $Cdr->ExpireDate, 'Create domain 1');
			
			
			////
			// 14. Delete contact 1
			
			$Resp = $this->Module->DeleteContact($Contact1);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Delete contact 1');
			
			////
			// 15. Delete name server 1
			
			$NS1 = new NameserverHost('ns1.'.$Domain->GetHostName(), '240.1.1.1');
			$Resp = $this->Module->DeleteNameserverHost($NS1);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Delete name server 1');
			
			////
			// 16. Check domain 2
			
			$Domain2 = $this->Registry->NewDomainInstance();
			$Domain2->Name = "{$this->clID}-domain-2";
			$ok = $this->Registry->DomainCanBeRegistered($Domain2)->Result;
			$this->AssertTrue($ok, 'Check domain 2');
			
			////
			// 17. Create domain 2
			
			$Domain2->SetContact($Contact2, CONTACT_TYPE::REGISTRANT);			
			$Domain2->SetNameserverList(array($NS3));
			$Cdr = $this->Module->CreateDomain($Domain2, 1);
			$this->AssertTrue($Cdr->CreateDate && $Cdr->ExpireDate, 'Create domain 2');
			
			////
			// 18. Create name server 2 subordinate of domain 2

			$NS12 = new NameserverHost('ns1.'.$Domain2->GetHostName(), '240.1.1.1');
			$Resp = $this->Module->CreateNameserverHost($NS12);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Create name server 2 subordinate of domain 2');

			////
			// 19. Update domain 1 with ns 2
			
			/*
			$Changes = $Domain->GetNameserverChangelist();
			$Changes->Add($NS12);
			$this->Registry->UpdateDomainNameservers($Domain, $Changes);
			$this->AssertTrue(true, 'Update domain 1 with ns 2');
			*/
			
			$params = array(
				'name' => 'test-partner-a-domain-1.ch',
				'add' => '<domain:add><domain:ns><domain:hostObj>ns1.test-partner-a-domain-2.ch</domain:hostObj></domain:ns></domain:add>',
				'remove' => '',
				'change' => ''
			);
			$Resp = $this->Module->Request('domain-update', $params);
			$this->AssertTrue($Resp->Succeed, 'Update domain 1 with ns 2');
			
			////
			// 20. Update contact 2

			$contact_data = $Contact2->GetFieldList();
			unset($contact_data['org']);
			$contact_data['street1'] = 'New Division';
			$Contact2->SetFieldList($contact_data);
			$Resp = $this->Module->UpdateContact($Contact2);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Update contact 2');
			
			////
			// 21. Delete domain 1
			
			$Resp = $this->Module->DeleteDomain($Domain);
			$this->AssertTrue($Resp->Code == RFC3730_RESULT_CODE::OK, 'Delete domain 1');
			/*
			$params = array(
				'name' => $Domain->GetHostName()
			);
			$Resp = $this->Module->Request('domain-delete', $params);
			$this->AssertTrue($Resp->Succeed, 'Delete domain 1');
			*/
			
			////
			// 22. Update domain 2 with authinfo

			$chg = '<domain:chg>';
			$chg .= '<domain:authInfo><domain:pw>2BARfoo</domain:pw></domain:authInfo>';
			$chg .= '</domain:chg>';
			
			$params = array(
				'name' => $Domain2->GetHostName(),
				'add' => '',
				'remove' => '',
				'change' => $chg
			);
			
			$Resp = $this->Module->Request('domain-update', $params);
			$this->AssertTrue($Resp->Succeed, 'Update domain 2 with authinfo');
			
			////
			// 23. Logout
			
			$Resp = $this->Module->Request('logout', array());
			$this->AssertTrue(true, 'Logout');
		}
	}
?>
