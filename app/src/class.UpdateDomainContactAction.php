<?php

class UpdateDomainContactAction_Result
{
	const OK = 1;
	const PENDING = 2;
	const INVOICE_GENERATED = 3;
}

class UpdateDomainContactAction
{
	
	/**
	 * @var Domain
	 */
	private	$Domain;
	
	/**
	 * @var string
	 */
	private $contact_type;
	
	/**
	 * @var Contact 
	 */
	private $NewContact;

	/**
	 * Database connection
	 */
	private $Db;

	/**
	 * @var Invoice
	 */
	private $Invoice;
	
	/**
	 * Action contructor:
	 * 	__construct($Domain, $contact_type, $NewContact)
	 *  __construct($TradeInvoice)
	 * @throws LogicException 
	 */
	public function __construct()
	{
		$this->Db = Core::GetDBInstance();
		$args = func_get_args();
		if ($args[0] instanceof Domain)
		{
			call_user_func_array(array($this, 'init'), $args);
		}
		else if ($args[0] instanceof Invoice)
		{
			call_user_func_array(array($this, 'initFromInvoice'), $args);
		}
		else
		{
			throw new LogicException("Invalid arguments");
		}
		if ($this->Domain->Status != DOMAIN_STATUS::DELEGATED)
		{
			throw new LogicException("Cannot execute trade command on non delegated domain name");
			
		}
	}
	
	/**
	 * @param Domain $Domain
	 * @param string $contact_type
	 * @param Contact $NewContact
	 * @throws LogicException 
	 */
	private function init ($Domain, $contact_type, $NewContact)
	{
		$this->Domain = $Domain;
		$this->contact_type = $contact_type;
		$this->NewContact = $NewContact;
	}
	
	/**
	 * @param Invoice $Invoice Paid trade invoice
	 * @throws LogicException
	 */
	private function initFromInvoice (Invoice $Invoice)
	{
		if ($Invoice->Status != INVOICE_STATUS::PAID)
		{
			throw new LogicException("Invoice is unpaid");
		}
		if ($Invoice->Purpose != INVOICE_PURPOSE::DOMAIN_TRADE)
		{
			throw new LogicException("Invoice status must be INVOICE_PURPOSE::DOMAIN_TRADE. '{$Invoice->Purpose}' taken");
		}

		try
		{
			$this->Domain = DBDomain::GetInstance()->Load($Invoice->ItemID);
			$this->contact_type = CONTACT_TYPE::REGISTRANT;
			$this->NewContact = DBContact::GetInstance()->LoadByCLID($this->Domain->NewRegistrantCLID);
			$this->Invoice = $Invoice;			
		} 
		catch (Exception $e)
		{
			$this->Domain = null;
			$this->contact_type = null;
			$this->NewContact = null;
			$this->Invoice = null;
			throw new LogicException(sprintf(_("Cannot initialize action. Reason: %s"), $e->getMessage()));
		}
	}
	
	/**
	 * Executor
	 *
	 * @throws UpdateDomainContactTask_Exception
	 */
	public function Run ($userid=null)
	{
		try
		{
			$Factory = RegistryModuleFactory::GetInstance();
			$Registry = $Factory->GetRegistryByExtension($this->Domain->Extension);
	    	$Manifest = $Registry->GetManifest();
	    	
			if ($userid && $this->Domain->UserID != $userid)
			{
				throw new UpdateDomainContactAction_Exception(
					_("You don't have permissions for manage this domain"),
					UpdateDomainContactAction_Exception::DOMAIN_NOT_BELONGS_TO_USER				
				);
			}
			
            $OldContact = $this->Domain->GetContact($this->contact_type);
            
            if ($this->NewContact && $this->NewContact->UserID != $this->Domain->UserID)
            {
            	throw new UpdateDomainContactAction_Exception(
            		_("You don't have permissions for using this contact"),
            		UpdateDomainContactAction_Exception::CONTACT_NOT_BELONGS_TO_USER
            	);
            }
	            
			$trade = $Manifest->GetRegistryOptions()->ability->trade == '1';
			if ($this->contact_type != CONTACT_TYPE::REGISTRANT || !$trade)
			{
				try
				{					
					$Registry->UpdateDomainContact($this->Domain, $this->contact_type, $OldContact, $this->NewContact);
					return $this->Domain->HasPendingOperation(Registry::OP_UPDATE) ?
						UpdateDomainContactAction_Result::PENDING :
						UpdateDomainContactAction_Result::OK; 
				}
				catch(Exception $e)
				{
					throw new UpdateDomainContactAction_Exception(
						sprintf(_("Cannot change contact. Reason: %s"), $e->getMessage())
					);
				}
			}
			else 
			{
				// Execute trade when:
				// - Trade invoice is paid
				// - Previous trade failed
				if ($this->Domain->IncompleteOrderOperation == INCOMPLETE_OPERATION::DOMAIN_TRADE || 
					($this->Invoice && $this->Invoice->Status == INVOICE_STATUS::PAID))
				{
					$this->Domain->SetContact($this->NewContact, CONTACT_TYPE::REGISTRANT);
					$this->Domain->IncompleteOrderOperation = null;
					$extra["requesttype"] = "ownerChange";
					try
					{
						$trade = $Registry->ChangeDomainOwner($this->Domain, 1, $extra);
						return $this->Domain->HasPendingOperation(Registry::OP_TRADE) ?
							UpdateDomainContactAction_Result::PENDING :
							UpdateDomainContactAction_Result::OK;  
					}
					catch(Exception $e)
					{
						$this->Domain->IncompleteOrderOperation = INCOMPLETE_OPERATION::DOMAIN_TRADE;
						if ($this->Invoice)
						{
							$this->Invoice->ActionStatus = INVOICE_ACTION_STATUS::FAILED;
							$this->Invoice->ActionFailReason = $e->getMessage();								
						}
						
						throw new UpdateDomainContactAction_Exception(
							sprintf(_("Cannot change contact. Reason: %s"), $e->getMessage())
						);
					}
				}
				else
				{
					// Issue an invoice for trade operation
					$invoiceid = $this->Db->GetOne(
						"SELECT * FROM invoices WHERE status=? AND itemid=? AND purpose=?",
						array(INVOICE_STATUS::PENDING, $this->Domain->ID, INVOICE_PURPOSE::DOMAIN_TRADE)
					);
					if (!$invoiceid)
					{
						$this->Domain->SetExtraField("NewRegistrantCLID", $this->NewContact->CLID);
						DBDomain::GetInstance()->Save($this->Domain);						
						
						$Invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_TRADE, $this->Domain->ID, $userid);
						$Invoice->Description = sprintf(_("%s domain name trade"), $this->Domain->GetHostName());
						$Invoice->Save();
						$this->Invoice = $Invoice;
						
						return UpdateDomainContactAction_Result::INVOICE_GENERATED;
					}
					else
					{
						throw new UpdateDomainContactAction_Exception(
							_("Another domain trade is already initiated")
						);
					}
				}
			}
		}
		catch (Exception $e)
		{
			throw new UpdateDomainContactAction_Exception($e->getMessage());
		}
	}
	
	public function GetInvoice ()
	{
		return $this->Invoice;
	}
	
	public function GetDomain ()
	{
		return $this->Domain;
	}
	
	public function GetNewContact ()
	{
		return $this->NewContact;
	}
	
	public function GetContactType ()
	{
		return $this->contact_type;
	}
}

class UpdateDomainContactAction_Exception extends Exception 
{
	const DOMAIN_NOT_BELONGS_TO_USER = -1;
	const CONTACT_NOT_BELONGS_TO_USER = -2;
	const INVOICE_NOT_PAID = -3;
}

?>