<?php

/**
 * @name RegistryResponse
 * @category   EPP-DRS
 * @package    Modules
 * @sdk
 * @subpackage RegistryModules
 * @author Marat Komarov <http://webta.net/company.html>
 * @author Igor Savchenko <http://webta.net/company.html>
 */

/**
 * Base class for all registry responses 
 */
class RegistryResponse
{
	
	/**
	 * Status of response one of REGISTRY_RESPONSE_STATUS::SUCCESS, REGISTRY_RESPONSE_STATUS::FAILED, REGISTRY_RESPONSE_STATUS::PENDING.  
	 * @var int
	 */
	public $Status;
	
	/**
	 * Registry error message
	 *
	 * @var string
	 */
	public $ErrMsg;
	
	/**
	 * Registry error code
	 *
	 * @var int
	 */
	public $Code;
	
	/**
	 * Registry internal operation id.
	 * Used in combination with status PENDING 
	 *
	 * @var string
	 */
	public $OperationId;
	
	protected $Properties;
	
	/**
	 * Raw registry response (EPP, RRP, Rest...)
	 * @var mixed
	 */
	public $RawResponse;
	
	/**
	 * Response constructor
	 *
	 * @param int $status
	 * @param string $err_msg
	 * @param string $code
	 */
	public function __construct($status, $err_msg = null, $code = null)
	{
		$this->Status = $status;
		$this->ErrMsg = $err_msg;
		$this->Code = $code;
	}
	
	/**
	 * Return True when response was success (REGISTRY_RESPONSE_STATUS::SUCCESS)
	 *
	 * @return bool
	 */
	public final function Succeed()
	{
		 return ($this->Status == REGISTRY_RESPONSE_STATUS::SUCCESS);
	}
	
	/**
	 * Return True when response was pending (REGISTRY_RESPONSE_STATUS::PENDING)
	 *
	 * @return bool
	 */
	public final function Pending()
	{
		return ($this->Status == REGISTRY_RESPONSE_STATUS::PENDING);
	}
	
	/**
	 * Return True when response was failed (REGISTRY_RESPONSE_STATUS::FAILED)
	 *
	 * @return bool
	 */
	public final function IsFailed()
	{
		return ($this->Status == REGISTRY_RESPONSE_STATUS::FAILED);
	}
	
	/**
	 * @ignore 
	 */
	public function __set ($name, $value)
	{
		if (array_key_exists($name, $this->Properties))
		{
			switch ($this->Properties[$name])
			{
				case 'timestamp':
					if (!is_int($value))
					{
						throw new Exception(sprintf(_("%s->%s is not a valid timestamp"), __CLASS__, $name));
					}
					break;
					
				case 'int':
				case 'integer':
					if (!is_int($value))
					{
						throw new Exception(sprintf(_('%s->%s must be integer'), __CLASS__, $name));
					}
					break;
					
				case 'bool':
				case 'boolean':
					if (!is_bool($value))
					{
						throw new Exception(sprintf(_('%s->%s must be boolean'), __CLASS__, $name));
					}
					break;
					
				case 'float':
					if (!is_float($value))
					{
						throw new Exception(sprintf(_('%s->%s must be float'), __CLASS__, $name));
					}
					break;

				case 'string':
					if (!is_string($value))
					{
						throw new Exception(sprintf(_('%s->%s must be string'), __CLASS__, $name));
					}
					break;
					
				case 'mixed':
					break;
					
				default:
					throw new Exception(sprintf(_('Undefined type "%s" specified for %s->%s'), $this->Properties[$name]));
			}
			
			$this->{$name} = $value;
		}
		else
		{
			throw new Exception(sprintf(_("%s is not a property of %s object"), $name, __CLASS__));
		}
	}
}


/**
 * Response for ChangeDomainOwner request
 * 
 * @property bool $Result result of domain trade operation
 */
final class ChangeDomainOwnerResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}


/**
 * Response for ContactCanBeCreated request
 * 
 * @property bool $Result True when domain can be created
 */
final class ContactCanBeCreatedResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
}

/**
 * Response for CreateContact request
 * 
 * @property string $CLID clid of created contact
 */
final class CreateContactResponse extends RegistryResponse
{
	protected $Properties = array(
		"CLID" => "mixed",
		"AuthCode" => "string"
	);
}

/**
 * Response for CreateDomain request
 * 
 * @property time $CreateDate Domain create date
 * @property time $ExpireDate Domain expire date
 * @property string $AuthCode Auth code (password)
 * @property string $Protocol Domain Application protocol
 */
final class CreateDomainResponse extends RegistryResponse
{
	protected $Properties = array(
		"CreateDate" 	=> "timestamp", 
		"ExpireDate" 	=> "timestamp", 
		"AuthCode" 		=> "string", 
		"Protocol" 		=> "string"
	);
}

/**
 * Response for CreateNameserverHost request
 * 
 * @property bool $Result True when namserver was created
 */
final class CreateNameserverHostResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for CreateNameserver request
 * 
 * @property bool $Result True when namserver waqs created
 */
final class CreateNameserverResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for DeleteContact request
 * 
 * @property bool $Result True when contact was deleted
 */
final class DeleteContactResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for DeleteDomain request
 * 
 * @property bool $Result True when domain was deleted
 */
final class DeleteDomainResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for DeleteNameserverHost request
 * 
 * @property bool $Result True when namserver was deleted
 */
final class DeleteNameserverHostResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for DomainCanBeRegistered request
 * 
 * @property bool $Result True when domain is available for registration
 * @property string $Reason Why it cannot be registered
 */
final class DomainCanBeRegisteredResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool",
		"Reason" => "string"
	);
}

/**
 * Response for DomainCanBeTransferred request
 * 
 * @property bool $Result True when domain is available for transfer 
 */
class DomainCanBeTransferredResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
}

/**
 * Response for GetRemoteContact request
 */
final class GetRemoteContactResponse extends RegistryResponse
{		
	private $Discloses = array();
	
	/**
	 * @ignore 
	 */
	public function __set($name, $value)
	{
		// We must check this object in uplevel code using Registry Manifest 
		
		$this->{$name} = $value;
	}
	
	/**
	 * Set disclose flag for contact field.
	 * Show/hide $field in WHOIS
	 *
	 * @param string $field
	 * @param bool $hideIt
	 */
	public function SetDiscloseValue($field, $showIt)
	{
		$this->Discloses[$field] = (int)$showIt;
	}
	
	/**
	 * This method return list of contact fields discloses 
	 *
	 * @return array (field=>bool)
	 */
	public function GetDiscloseList ()
	{
		return $this->Discloses;
	}
}

/**
 * Response for GetRemoteDomain request
 *
 * @property string $CLID Domain clID in registry
 * @property string $CRID Domain crID in registry
 * @property string $RegistrantContact Registrant contact clID
 * @property string $BillingContact Billing contact clID
 * @property string $AdminContact Admin contact clID
 * @property string $TechContact Tech contact clID
 * @property time $CreateDate Domain create date
 * @property time $ExpireDate Domain expire date 
 * @property string $AuthCode Auth code (password)
 * @property string $Protocol Domain Application protocol
 * @property string $RegistryStatus Status flag of domain
 */
final class GetRemoteDomainResponse extends RegistryResponse
{
	protected $Properties = array(
		"CLID" 				=> "string", 
		"CRID" 				=> "string", 
		"RegistrantContact" => "string", 
		"BillingContact" 	=> "string", 
		"AdminContact" 		=> "string", 
		"TechContact" 		=> "string", 
		"CreateDate" 		=> "timestamp", 
		"ExpireDate" 		=> "timestamp", 
		"AuthCode" 			=> "string", 
		"Protocol" 			=> "string", 
		"RegistryStatus" 	=> "string",
		"IsLocked"			=> "bool"
	);
	
	private $Nameservers = array();
	
	private $Flags = array();
	
	private $ExtraData = array();
	
	/**
	 * This method set list of domain nameservers
	 *
	 * @param array $nslist Array of nameserver objects
	 */
	public function SetNameserverList(array $nslist)
	{
		$this->Nameservers = $nslist;
	}
	
	/**
	 * This method return list of domain nameservers
	 *
	 * @return array Array of Namserver objects
	 */
	public function GetNameserverList()
	{
		return $this->Nameservers;
	}
	
	/**
	 * This method set list of domain flags 
	 *
	 * @param array $flags
	 */
	public function SetFlagList ($flags)
	{
		$this->Flags = $flags;
	}
	
	/**
	 * This method return list of domain flags
	 *
	 * @return array
	 */
	public function GetFlagList ()
	{
		return $this->Flags;
	}  
	
	public function SetExtraData ($extra)
	{
		$this->ExtraData = $extra;
	}
	
	public function SetExtraField ($key, $value)
	{
		$this->ExtraData[$key] = $value;
	}
	
	public function GetExtraData ()
	{
		return $this->ExtraData;
	}
}

/**
 * Response for LockDomain request
 *
 * @property bool $Result True when domain was locked 
 */
final class LockDomainResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for NameserverCanBeCreated request
 * 
 * @property bool $Result True when namserver can be created 
 */
final class NameserverCanBeCreatedResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
}

/**
 * Response for RenewDomain request
 *
 * @property time $ExpireDate domain new expiration date
 */
final class RenewDomainResponse extends RegistryResponse
{
	protected $Properties = array(
		"ExpireDate" => "timestamp"
	);
}

/**
 * Response for TransferApprove request
 *
 * @property bool $Result True when transfer approve success 
 */
final class TransferApproveResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for TransferReject request
 *
 * @property bool $Result True when transfer reject success 
 */
final class TransferRejectResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for TransferRequest request
 *
 * @property bool $Result True when transfer request success
 * @property string $TransferID unique id of transfer 
 */
final class TransferRequestResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" 	=> "bool", 
		"TransferID"=> "string"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UnlockDomain request
 *
 * @property bool $Result True when domain was unlocked 
 */
final class UnLockDomainResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UpdateContact request
 *
 * @property bool $Result True when contact was updated
 */
final class UpdateContactResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UpdateAuthCode request
 *
 * @property bool $Result True when auth code was changed 
 */
final class UpdateDomainAuthCodeResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UpdateDomainContact request
 *
 * @property bool $Result True when domain contact was updated
 */
final class UpdateDomainContactResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UpdateDomainFlags request
 *
 * @property bool $Result True when domain flags was updated
 */
final class UpdateDomainFlagsResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UpdateDomainNameservers request
 *
 * @property bool $Result True when domain namservers was updated
 */
final class UpdateDomainNameserversResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}

/**
 * Response for UpdateNameserverHost request
 *
 * @property bool $Result True when nameserver was updated
 */
final class UpdateNameserverHostResponse extends RegistryResponse
{
	protected $Properties = array(
		"Result" => "bool"
	);
	
	public function __construct($status, $err_msg = null, $code = null)
	{
		parent::__construct($status, $err_msg, $code);
		$this->Result = ($this->Succeed() || $this->Pending());
	}
}
?>
