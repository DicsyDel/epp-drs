<?php
	
	/**
	 * @ignore 
	 */
	class RegistryInvoiceObserver implements IInvoiceObserver
	{
		public function __construct ()
		{
			$this->HandledPurposes = array(INVOICE_PURPOSE::DOMAIN_CREATE, 
				INVOICE_PURPOSE::DOMAIN_TRANSFER, 
				INVOICE_PURPOSE::DOMAIN_RENEW, 
				INVOICE_PURPOSE::DOMAIN_TRADE
			);
		}
		
		public function OnIssued (Invoice $Invoice)
		{
		}
		
		public function OnPaid (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			$db = Core::GetDBInstance();			
			
			if (!in_array($Invoice->Purpose, $this->HandledPurposes))
				return;
				
			Log::Log("RegistryInvoiceObserver::OnPaid(InvoiceID={$Invoice->ID})", E_USER_NOTICE);
			
			// Get domain information
			try
			{
				$Domain = DBDomain::GetInstance()->Load($Invoice->ItemID);
			}
			catch(Exception $e)
			{
				Log::Log("RegistryInvoiceObserver::OnPaid() thown exception: {$e->getMessage()}", E_USER_ERROR);
			}
			
			if ($Domain)
			{
				Log::Log("Invoice purpose: {$Invoice->Purpose}", E_USER_NOTICE);
				
				// Get user information
				$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($Domain->UserID));
				
				// Check command
				switch ($Invoice->Purpose)
				{
					case INVOICE_PURPOSE::DOMAIN_TRADE:

						try
						{
							$Action = new UpdateDomainContactAction($Invoice);
							try
							{
								$Action->Run();	
							}
							catch (UpdateDomainContactAction_Exception $e)
							{
								Log::Log(sprintf("Trade failed. %s", $e->getMessage()), E_ERROR);
								DBDomain::GetInstance()->Save($Action->GetDomain());	
								
								// Send mail
								$args = array(
									"client" 		=> $userinfo, 
									"Invoice" 		=> $Invoice,
									"domain_name"	=> $Domain->Name,
									"extension"		=> $Domain->Extension,
									"domain_trade_failure_reason" => $e->getMessage()
							    );
								mailer_send("domain_trade_action_required.eml", $args, $userinfo["email"], $userinfo["name"]);
							}
						}
						catch (LogicException $e2)
						{
							Log::Log($e2->getMessage(), E_ERROR);
						}
	

						break;
					
					case INVOICE_PURPOSE::DOMAIN_CREATE:
						
						if ($Domain->Status == DOMAIN_STATUS::AWAITING_PAYMENT || 
							$Domain->Status == DOMAIN_STATUS::REJECTED)
						{
							$Domain->Status = DOMAIN_STATUS::PENDING;
							$Domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_CREATE;
							$Domain = DBDomain::GetInstance()->Save($Domain);
							
							// If domain has incomplete information skip domain creation. Update status to Pending.
							if (count($Domain->GetContactList()) == 0 || count($Domain->GetNameserverList()) == 0)
							{
								//
								// Send mail
								//
								Log::Log("Domain registration process not completed. Need more information from client.", E_USER_NOTICE);
								
								$args = array(
									"client" 		=> $userinfo, 
									"Invoice" 		=> $Invoice,
									"domain_name"	=> $Domain->Name,
									"extension"		=> $Domain->Extension
								);
								mailer_send("domain_registration_action_required.eml", $args, $userinfo["email"], $userinfo["name"]);
								
								// Write information in invoice
								$Invoice->ActionStatus = INVOICE_ACTION_STATUS::FAILED;
								$Invoice->ActionFailReason = _('Domain registration process not completed. Need more information from client.');
							}
							else 
							{
								Log::Log("Trying to register domain", E_USER_NOTICE);
								
								///// get Registry instance and connect to registry server
								try
								{
									$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($Domain->Extension);
								}
								catch(Exception $e)
								{
									Log::Log($e->getMessage(), E_ERROR);
									return;
								}
								
								// Validate license for this module
								if (!License::IsModuleLicensed($Registry->GetModuleName()))
									throw new LicensingException("Your license does not permit module {$Registry->ModuleName()}");
								//
								
								$extra_data = $db->GetAll("SELECT * FROM domains_data WHERE domainid=?", array($Domain->ID));
								if ($extra_data && count($extra_data) > 0)
								{
									foreach ($extra_data as $v)
										$extra[$v["key"]] = $v["value"];
								}
								else 
									$extra = array();
									
								// Try to create domain name
								try
								{
									$cr = $Registry->CreateDomain($Domain, $Domain->Period, $extra);
								}
								catch(Exception $e)
								{
									$args = array(
										"client" 		=> $userinfo, 
										"Invoice" 		=> $Invoice,
										"domain_name"	=> $Domain->Name,
										"extension"		=> $Domain->Extension,
										"domain_reg_failure_reason" => $e->getMessage()
									);
									mailer_send("domain_registration_action_required.eml", $args, $userinfo["email"], $userinfo["name"]);
									
									// If domain not created
									Log::Log("Cannot register domain name. Server return: ".$e->getMessage(), E_ERROR);
									
									$Invoice->ActionStatus = INVOICE_ACTION_STATUS::FAILED;
									$Invoice->ActionFailReason = $e->getMessage();
								}
								
								if ($cr)
								{
									// If domain created
									Log::Log(sprintf("Domain %s successfully registered. Updating database", $Domain->GetHostName()), E_USER_NOTICE);
									
									$Invoice->ActionStatus = INVOICE_ACTION_STATUS::COMPLETE;
								}	
							}
						}
						else 
						{
							Log::Log("Domain status '{$Domain->Status}'. Expected 'Awaiting payment'", E_ERROR);
							$retval = false;
							
							$Invoice->ActionStatus = INVOICE_ACTION_STATUS::FAILED;
							$Invoice->ActionFailReason = sprintf(_("Domain status '%s'. Expected 'Awaiting payment'"), $Domain->Status);
						}
						
						break;
						
					case INVOICE_PURPOSE::DOMAIN_TRANSFER:
						
						if ($Domain->Status == DOMAIN_STATUS::AWAITING_PAYMENT)
						{
							//
							// Send mail
							//
							$args = array(
								"client" => $userinfo, 
								"domain_name"	=> $Domain->Name,
								"extension"		=> $Domain->Extension,
								"Invoice"		=> $Invoice
							);
							mailer_send("domain_transfer_action_required.eml", $args, $userinfo["email"], $userinfo["name"]);
							
							Log::Log("Domain transfer process not completed. Need more information from client.", E_USER_NOTICE);
							
							$Domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_TRANSFER;
							$Domain->Status = DOMAIN_STATUS::PENDING;
							DBDomain::GetInstance()->Save($Domain);
							
							$Invoice->ActionStatus = INVOICE_ACTION_STATUS::COMPLETE;
						}
						
						break;
						
					case INVOICE_PURPOSE::DOMAIN_RENEW:
						
						// Renew domain name
						
						Log::Log("Trying to renew domain", E_USER_NOTICE);
						///// Get registry instance and connect to registry server
						try
						{
							$Registry = RegistryModuleFactory::GetInstance()->GetRegistryByExtension($Domain->Extension);
						}
						catch(Exception $e)
						{
							Log::Log($e->getMessage(), E_ERROR);
							return;
						}
												
						try
						{
							$renew = $Registry->RenewDomain($Domain, array('period' => $Domain->Period));
						}
						catch(Exception $e)
						{
							$renew = false;
							$err = $e->getMessage();
						}
						
						if ($renew)
						{
							Log::Log("Domain successfully renewed.", E_USER_NOTICE);
							$Invoice->ActionStatus = INVOICE_ACTION_STATUS::COMPLETE;
							$Domain->DeleteStatus = DOMAIN_DELETE_STATUS::NOT_SET;
							DBDomain::GetInstance()->Save($Domain);
						}
						else
						{
							$Domain->SetExtraField('RenewInvoiceID', $Invoice->ID);
							DBDomain::GetInstance()->Save($Domain);
							//
							// Send mail here
							//
							$args = array(	"client" => $userinfo, 
										"domain_name"	=> $Domain->Name,
										"extension"		=> $Domain->Extension,
										"reason"		=> $err,
										"years"			=> $Domain->Period
									 );
							mailer_send("renewal_failed.eml", $args, $userinfo["email"], $userinfo["name"]);
							
							// If renew failed
							Log::Log("Cannot renew domain name. Server return: ".$err, E_ERROR);
							
							$Invoice->ActionStatus = INVOICE_ACTION_STATUS::FAILED;
							$Invoice->ActionFailReason = $err;
						}
						/////
						break;
				}
				
				$Invoice->Save();
				
			}
			else 
			{
				// Domain not found
				Log::Log(sprintf("Domain width ID '%s' not found.", $Invoice->ItemID), E_ERROR);
			}
			
			// OnPaymentComplete routine succeffully completed.
			Log::Log("RegistryInvoiceObserver::OnPaid Successfully completed.", E_USER_NOTICE);
		}
		
		public function OnFailed (Invoice $Invoice, AbstractPaymentModule $payment_module = null)
		{
			/*
			 * 
			 try
			{
				$Domain = DBDomain::GetInstance()->Load($inv_info['domainid']);
			}
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
			
			if ($Domain)
			{
				if ($Domain->Status == DOMAIN_STATUS::DELEGATED && $inv_info["command"] == "Create")
				{
				    // get TLD object
				    try
				    {
            			$Registry = $RegistryModuleFactory->GetRegistryByExtension($Domain->Extension);
				    }
				    catch(Exception $e)
				    {
				    	$errmsg = $e->getMessage(); 
				    }
					
				    if ($Registry)
				    {
            			try
            			{
            				$del = $Registry->DeleteDomain($Domain);
            			}
            			catch(Exception $e)
            			{
            				$errmsg = $e->getMessage();
            			}
				    }
				}
				elseif ($Domain->Status == DOMAIN_STATUS::AWAITING_PAYMENT)
				{
					DBDomain::GetInstance()->Delete($Domain);
				}
			}
			 */			
		}
	}
?>