<?php

/**
 * @name PendingOperationResponse
 * @category   EPP-DRS
 * @package    Modules
 * @subpackage RegistryModules
 * @sdk
 * @author Marat Komarov <http://webta.net/company.html> 
 */


/**
 * Base class for pending operation responses
 */
class PendingOperationResponse extends RegistryResponse
{
	/**
	 * ID of poll message (Used in registry modules that implement IRegistryModuleServerPollable)
	 * 
	 * @var string
	 */
	public $MsgID;
	
	/**
	 * Reason of failure
	 *
	 * @var string
	 */
	public $FailReason;
}


/**
 * Poll response for transfer
 * 
 * @property int $TransferStatus
 * @property string $HostName
 */
final class PollTransferResponse extends PendingOperationResponse
{
	protected $Properties = array(
		"TransferStatus" 	=> "int", 
		"HostName" 			=> "string"
	);
	
	/**
	 * @ignore 
	 */
	public function __set($name, $value)
	{
		if ($name == 'TransferStatus')
		{
			if ($value == TRANSFER_STATUS::APPROVED || 
				$value == TRANSFER_STATUS::DECLINED ||
				$value == TRANSFER_STATUS::PENDING ||
				$value == TRANSFER_STATUS::FAILED)
			{
				$this->{$name} = $value;
			}
			else
			{
				throw new Exception(sprintf(_("%s->%s must be one of TRANSFER_STATUS constants"), __CLASS__, $name));
			}
		}
		else
		{
			parent::__set($name, $value);
		}
	}
}

/**
 * Poll response for outgoing transfer
 * 
 * @property string $TransferStatus
 * @property string $HostName
 *
 */
final class PollOutgoingTransferResponse extends PendingOperationResponse
{	
	protected $Properties = array(
		"TransferStatus" 	=> "string", 
		"HostName" 			=> "string"
	);

	/**
	 * @ignore 
	 */	
	public function __set($name, $value)
	{
		if ($name == 'TransferStatus')
		{
			if ($value == OUTGOING_TRANSFER_STATUS::REQUESTED || 
				$value == OUTGOING_TRANSFER_STATUS::AWAY || 
				$value == OUTGOING_TRANSFER_STATUS::REJECTED ||
				$value == OUTGOING_TRANSFER_STATUS::APPROVED)
			{
				$this->{$name} = $value;
			}
			else
			{
				throw new Exception(sprintf(_("%s->%s must be one of OUTGOING_TRANSFER_STATUS constants"), __CLASS__, $name));
			}
		}
		else
		{
			parent::__set($name, $value);
		}
	}
}

/**
 * Poll response for create domain operation
 * 
 * @property string $HostName Domain name
 * @property time $ExpireDate Domain expiration date
 * @property bool $Result True if domain was created
 */
final class PollCreateDomainResponse extends PendingOperationResponse
{
	protected $Properties = array(
		'ExpireDate' => 'timestamp', 
		'HostName' => 'string', 
		'Result' => 'bool'
	);
}

/**
 * Poll response for delete domain operation
 *  
 * @property string $HostName Domain name
 * @property bool $Result True if domain was deleted
 */
final class PollDeleteDomainResponse extends PendingOperationResponse 
{
	protected $Properties = array(
		'Result' => 'bool', 
		'HostName' => 'string'
	);
}

/**
 * Poll response for change domain owner operation
 * 
 * @property string $HostName Domain name
 * @property string $Period delegation period
 * @property bool $Result True if owner was changed
 */
final class PollChangeDomainOwnerResponse extends PendingOperationResponse 
{
	protected $Properties = array(
		'Result' 	=> 'bool', 
		'HostName'	=> 'string', 
		'Period' 	=> 'int'
	);
}

/**
 * Poll response for update domain operation
 * 
 * @property string $HostName Domain name
 * @property bool $Result True if domain was updated
 */
final class PollUpdateDomainResponse extends PendingOperationResponse 
{
	protected $Properties = array(
		'Result' => 'bool', 
		'HostName' => 'string'
	);
}

/**
 * Poll response for update contact operation
 *
 * @property string $CLID clid of updated contact
 */
final class PollUpdateContactResponse extends PendingOperationResponse 
{
	protected $Properties = array(
		'CLID' => 'string',
		'Result' => 'bool'
	);
}

/**
 * Poll response for delete contact operation
 *
 * @property string $CLID clid of deleted contact
 */
final class PollDeleteContactResponse extends PendingOperationResponse 
{
	protected $Properties = array(
		'CLID' => 'string',
		'Result' => 'bool'
	);
}

/**
 * Poll response for delete namserver operation
 *
 * @property string $HostName Host name
 */
final class PollDeleteNamserverHostResponse extends PendingOperationResponse 
{
	protected $Properties = array(
		'HostName' => 'string',
		'Result' => 'bool'
	);
}

?>