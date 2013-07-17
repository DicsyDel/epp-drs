<?php

class DomainBaseController
{
	// Common properties
	protected 
		$attr,
		$display,
		$Db;
		
	// Wizard forming properties
	protected
 		$state_storage_key,
		$stepno,
		$wizard_mode;
		
	public function __construct($attr=array())
	{
		$this->attr = $attr;
		$this->display = &$GLOBALS['display'];
		$this->Db = Core::GetDBInstance();		
	}

	public function WizardModeOn()
	{
		$this->wizard_mode = true;
		$this->stepno = isset($this->attr['stepno']) ? $this->attr['stepno'] : $this->attr['step'];
		if (!$this->stepno)
		{
			$this->stepno = 1;
		}
	
		if ($this->stepno == 1)
		{	
			$this->ClearState();
		}
		else
		{
			$this->RestoreState();
		}
	}
	
	public function NextStep ()
	{
		if ($this->wizard_mode)
		{
			$this->stepno++;
			$this->SaveState();
		}
	}
	
	public function GetStepNum ()
	{
		return $this->stepno;
	}
	
	protected function _IsStateful ()
	{
		return isset($this->state_storage_key);
	}
	
	protected function _ClearState ()
	{
		$_SESSION[$this->state_storage_key] = array();
	}
	
	protected function _LoadState ()
	{
		return $_SESSION[$this->state_storage_key];
	}
	
	protected function _SaveState ($arr)
	{
		foreach ($arr as $k => $v)
		{
			$_SESSION[$this->state_storage_key][$k] = $v;
		}
	}
	
	public function ClearState ()
	{
		$this->_ClearState();
	}
	
	public function SaveState ()
	{
		if ($this->_IsStateful())
		{
			$this->_ClearState();
			$save = array();
			$this->DoSaveState($save);
			$this->_SaveState($save);
		}
	}
	
	protected function DoSaveState (&$save)
	{
	}
	
	public function RestoreState ()
	{
		if ($this->_IsStateful())
		{
			$saved = $this->_LoadState();
			$this->DoRestoreState($saved);
		}
	}
	
	protected function DoRestoreState ($saved)
	{
	}
}

class BulkRegistrationSteps
{
	const DOMAINNAMES 	= 1;
	const CHECK_AVAILABILITY = 2;
	const PERIOD 	= 3;	
	const CONTACTS 	= 4;
	const EXTRA 	= 5;
	const NS 		= 6;
}

class DomainRegistrationController extends DomainBaseController 
{
	protected $state_storage_key = 'domain_reg';
	
	protected $attr, $data;
	
	public function __construct($attr)
	{
		parent::__construct($attr);
		$this->WizardModeOn();
	}
		
	protected $GETHandlers = array(
		BulkRegistrationSteps::DOMAINNAMES => "ShowDomainNames",
		BulkRegistrationSteps::CHECK_AVAILABILITY => "ShowCheckAvailability",
		BulkRegistrationSteps::PERIOD => "ShowPeriod",
		BulkRegistrationSteps::CONTACTS => "ShowContacts",
		BulkRegistrationSteps::EXTRA => "ShowExtra",
		BulkRegistrationSteps::NS => "ShowNS"
	);
	
	protected $POSTHandlers = array(
		BulkRegistrationSteps::DOMAINNAMES => "PostDomainNames",
		BulkRegistrationSteps::CHECK_AVAILABILITY => "PostCheckAvailability",
		BulkRegistrationSteps::PERIOD => "PostPeriod",
		BulkRegistrationSteps::CONTACTS => "PostContacts",
		BulkRegistrationSteps::EXTRA => "PostExtra",
		BulkRegistrationSteps::NS => "PostNS"
	);
	
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	public function __get($name)
	{
		return $this->data[$name];
	}
	
	protected function DoSaveState (&$save)
	{
		$save = array_merge($save, $this->data);
	}
	
	protected function DoRestoreState ($saved)
	{
		foreach ($saved as $k => $v)
			$this->{$k} = $v;
	}
	
	public function Run ()
	{
		if ($_SERVER['REQUEST_METHOD'] == "POST")
			if ($this->CallMethod($this->POSTHandlers[$this->stepno]))
				if ($this->stepno == BulkRegistrationSteps::NS)
					$this->CreateTask();
				else
					$this->NextStep();
		
		$this->CallMethod($this->GETHandlers[$this->stepno]);
		
		$this->display['stepno'] = $this->stepno;	
		$this->display['attr'] = $this->attr;
		$GLOBALS['template_name'] = "client/bulk_reg_step{$this->stepno}";			
	}
	
	protected function CallMethod ($methodname)
	{
		if (method_exists($this, $methodname))
		{		
			try
			{
				$this->{$methodname}();
			}
			catch (ErrorList $e)
			{
				foreach ($e->GetAllMessages() as $errmsg)
					$GLOBALS["err"][] = $errmsg;
				return false;
			}
			catch (Exception $e2)
			{
				$GLOBALS["errmsg"] = $e2->getMessage();
				return false;
			}
		}
		
		return true;
	}
	
	protected function SkipStep () 
	{
		$this->NextStep();
		$this->CallMethod($this->GETHandlers[$this->stepno]);
		$this->display['stepno'] = $this->stepno;		
		$GLOBALS['template_name'] = "client/bulk_reg_step{$this->stepno}";		
	}

	protected function ShowDomainNames ()
	{
		// Get available TLDs
		foreach ($GLOBALS['TLDs'] as $v)
			$tlds[$v] = $v;
		$this->display["tlds"] = $tlds;
	}
	
	protected function PostDomainNames ()
	{
		// Accept domains input. 
		// Split domain names by newline separator
		$attr = &$this->attr;
		
		$k = "domains";
		$attr[$k] = array_filter(array_map('trim', explode("\n", (string)$attr[$k])));
		if (!empty($attr[$k]))
			$this->{$k} = $attr[$k];
		else
			throw new Exception(_("Please domains, one per line"));
			
		$k = "default_tld";
		$this->{$k} = $attr[$k];
	}
	
	protected function ShowCheckAvailability ()
	{
		require_once SRC_PATH . "/LibWebta/library/Data/JSON/JSON.php";		
		$_SESSION["JS_SESSIONID"] = $this->display["JS_SESSIONID"] = md5(mt_rand().microtime(true));
			
		$domains = $this->domains;
		foreach ($domains as $i => &$domainname)
			if (strpos($domainname, ".") === false)
				$domainname .= ".{$this->default_tld}";
		$this->domains = $domains;
		
		$Json = new Services_JSON();
		$this->display["domains"] = $Json->encode($this->domains);  
	}	
	
	protected function PostCheckAvailability ()
	{
		// Accept checked domain names
		$attr = &$this->attr;
		
		$k = "domains";
		if ($attr[$k])
		{
			$avail = array();
			foreach ($attr[$k] as $d)
				if ($d["avail"])
					$avail[] = $d["name"];
					
			if (!$avail)
				throw new Exception(_("No domains available for registration"));
					
			$this->{$k} = $avail; 
		}
		else
			throw new Exception(_("Please check at least one domain"));
	}
	
	protected function ShowPeriod ()
	{
		$Factory = RegistryModuleFactory::GetInstance();
		$Client = Client::Load($_SESSION["userid"]);		
		$tlds = $this->GetTLDs();
		$period_forms = array();
		foreach ($tlds as $tld)
		{
			$Registry = $Factory->GetRegistryByExtension($tld);
			$ConfigXml = $Registry->GetManifest()->GetSectionConfig();
			$min = (int)$ConfigXml->domain->registration->min_period;
			$max = (int)$ConfigXml->domain->registration->max_period;
			
    	    $discount_pc = $Client->PackageID ? (float)$this->Db->GetOne(
    	    		"SELECT discount FROM discounts WHERE TLD=? AND purpose=? AND packageid=?", 
    	    		array($tld, INVOICE_PURPOSE::DOMAIN_CREATE, $Client->PackageID)) : 0;
    	    		
			$periods = array();
			for ($period = $min; $period <= $max; $period++)
			{
				$price = $this->Db->GetOne(
					"SELECT cost FROM prices WHERE purpose=? AND TLD=? AND period=?", 
					array(INVOICE_PURPOSE::DOMAIN_CREATE, $tld, $period)
				);
				$discount = $discount_pc ? round($price/100*$discount_pc, 2) : 0;
				$periods[] = array("period" => $period, "price" => $price - $discount);
			}
			$this->display["period_forms"][] = array(
				"title" => sprintf(_("%s registration period"), strtoupper($tld)),
				"periods" => $periods,
				"tld" => $tld
			);
		}
	}
	
	protected function PostPeriod ()
	{
		$k = "periods";
		$this->{$k} = $this->attr[$k];
	}
	
	protected function ShowContacts ()
	{
		$Factory = RegistryModuleFactory::GetInstance();
		$tlds = $this->GetTLDs();
		foreach ($tlds as $tld)
		{
			$CForm = new DomainAllContactsForm(array(
				'userid' => $_SESSION['userid'],
				'tld' => $tld,
				"form_title" => sprintf(_("%s contacts"), strtoupper($tld))
			));
			$this->display["contact_forms"][] = $CForm->GetRenderedData();			
		}
	}
	
	protected function PostContacts ()
	{
		$k = "contact_list";

		$DbContact = DBContact::GetInstance();
		$ErrList = new ErrorList();
		foreach ($this->attr[$k] as $tld => $contacts)
		{
			foreach ($contacts as $clid)
			{
				if ($clid)
				{
					try
					{
						$Contact = $DbContact->LoadByCLID($clid);
						if ($Contact->HasPendingOperation(Registry::OP_CREATE_APPROVE)
		        			|| $Contact->HasPendingOperation(Registry::OP_UPDATE_APPROVE))
		        		{
		        			throw new Exception(sprintf(
		        					_("Contact <%s> is not yet approved by administrator"), 
		        					$Contact->GetTitle()));
		        		}
					}
					catch (Exception $e)
					{
						$ErrList->AddMessage($e->getMessage());
					}
				}
			}
		}
		if ($ErrList->HasMessages())
			throw $ErrList;
		
		$this->{$k} = $this->attr[$k];
	}
	
	protected function ShowExtra ()
	{
		$Factory = RegistryModuleFactory::GetInstance();
		$tlds = $this->GetTLDs();
		$extra_forms = array();
		foreach ($tlds as $tld)
		{
			$Registry = $Factory->GetRegistryByExtension($tld);
			$ConfigXml = $Registry->GetManifest()->GetSectionConfig();
			$fields = $ConfigXml->domain->registration->extra_fields->xpath("field");
			if (count($fields))
			{
				$extra_forms[] = array(
					"title" => sprintf(_("%s additional information"), strtoupper($tld)),
					"tld" => $tld,
					"fields" => UI::GetRegExtraFieldsForSmarty($ConfigXml)
				);
			}
		}
		if ($extra_forms)
		{
			$this->display["extra_forms"] = $extra_forms;	
		}
		else
		{
			// Skip this step
			$this->SkipStep();
		}
	}
	
	protected function PostExtra ()
	{
		$DForm = new DataForm();
		
		$Validator = new DOMDocument("1.0");
		$Validator->appendChild($Validator->createElement("validator"));
		
		$Factory = RegistryModuleFactory::GetInstance();
		$tlds = $this->GetTLDs();
		$extra_flatten = array();
		foreach ($tlds as $tld)
		{
			$Registry = $Factory->GetRegistryByExtension($tld);
			$ConfigXml = $Registry->GetManifest()->GetSectionConfig();			
			$fields = $ConfigXml->domain->registration->extra_fields->xpath("field");
			if (count($fields))
				foreach ($fields as $field)
				{
					$F = dom_import_simplexml($field);
					$F = $Validator->importNode($F, true);
					$name = "{$tld}-{$field->attributes()->name}";
					$F->setAttribute("name", $name);
					$extra_flatten[$name] = $this->attr["extra"][$tld][(string)$field->attributes()->name];
					$Validator->documentElement->appendChild($F);
				}
		}
		
		$manifest = simplexml_import_dom($Validator->documentElement);
		$DForm->AddXMLValidator($manifest);
		$err = $DForm->Validate($extra_flatten);
		if (!$err)
		{
			$k = "extra";
			$this->{$k} = $this->attr[$k];
		}
		else
		{
			$ErrList = new ErrorList();
			foreach ($err as $msg) $ErrList->AddMessage($msg);
			throw $ErrList;
		}
	}
	
	protected function ShowNS ()
	{
		if ($_SESSION["userid"])
		{
			// Set client default ns 
			$Client = Client::Load($_SESSION["userid"]);
			if ($Client->GetSettingValue(ClientSettings::NS1))
			{
				$this->display["ns1"] = $Client->GetSettingValue(ClientSettings::NS1);
				$this->display["ns2"] = $Client->GetSettingValue(ClientSettings::NS2);
			}
		}
		
		// Set app default ns 
		if (!$this->display["ns1"])
		{
			$this->display["ns1"] = CONFIG::$NS1;
			$this->display["ns2"] = CONFIG::$NS2;
		}
		$this->display["enable_managed_dns"] = ENABLE_EXTENSION::$MANAGED_DNS;	
	}
	
	protected function PostNS ()
	{
		$ErrList = new ErrorList();
		$Validator = Core::GetValidatorInstance();
		if (! $attr["enable_managed_dns"])
		{
			foreach (array("ns1", "ns2") as $k)
				if (!$Validator->IsDomain($this->attr[$k]))
					$ErrList->AddMessage(sprintf(_("%s is not a valid host"), $this->attr[$k]));
			if ($attr["ns1"] && $attr["ns1"] == $attr["ns2"])
				$ErrList->AddMessage(_("You cannot use the same nameserver twice."));
				
			if ($ErrList->HasMessages())
				throw $ErrList;
				
			$this->ns = array($this->attr["ns1"], $this->attr["ns2"]);
		}
		else
		{
			$this->ns = array(CONFIG::$NS1, CONFIG::$NS2);
		}
	}
	
	protected function CreateTask ()
	{
		try
		{
			// Create task and enqueue it in epp-drs system queue.
			// Task will be handled in TaskQueueProcess.
			$Task = new Task(
				$_SESSION['userid'],
				new BulkRegisterDomainJob(
					$this->GetTLDs(),
					$this->periods,
					$this->contact_list,
					$this->ns,
					$this->extra
				),
				$this->domains
			);
			$Queue = TaskQueue::GetInstance();
			$Queue->Put($Task);
			CoreUtils::Redirect("bulk_update_complete.php");
		}
		catch (Exception $e)
		{
			$this->display["err"] = $e->getMessage();
		}
	}
	
	private function GetTLDs()
	{
		return array_unique(preg_replace("/^[^\\.]+\\./", "", $this->domains));
	}

}

?>
