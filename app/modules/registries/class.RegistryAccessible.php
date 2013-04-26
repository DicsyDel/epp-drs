<?php

/**
 * @name RegistryAccesible
 * @category   EPP-DRS
 * @package    Modules
 * @subpackage RegistryModules
 * @sdk
 * @author Marat Komarov <http://webta.net/company.html>
 */


/**
 * A window to Registry object.
 * 
 * @method Contact NewContactInstance($contact_type)  Create new contact object of specified type
 * @method Domain NewDomainInstance() Create new domain object
 * @method bool IsIDNHostName($hostname) Return True if hostname contains non-ascii multibyte characters
 * @method string PunycodeEncode($utf8_string) Encode a given UTF-8 string into ACE string
 * @method string PunycodeDecode($ace_string) Decode a given ACE string into UTF-8 string
 * @method Contact GetRemoteContact(Contact $Contact) syncronizes contact with registry
 * @method Domain GetRemoteDomain(Domain $Domain) syncronizes domain with registry
 */
class RegistryAccessible
{
	/**
	 * Methods that can be accessible from registry module
	 *
	 * @var array
	 * @ignore 
	 */
	private $AccessibleMethods = array
	(
		'NewContactInstance',
		'NewDomainInstance',
		'IsIDNHostName',
		'PunycodeEncode',
		'PunycodeDecode',
		'GetRemoteContact',
		'GetRemoteDomain'
	);
	
	/**
	 * Registry instance
	 *
	 * @var Registry
	 */
	private $Registry;
	
	public function __construct (Registry $registry)
	{
		$this->Registry = $registry;
	}
	
	/**
	 * @ignore 
	 */
	public function __call ($name, $arguments)
	{
		if (! in_array($name, $this->AccessibleMethods))
			throw new Exception(sprintf(_('%s is not a method of %s'), $name, __CLASS__));
			
		return call_user_func_array(array($this->Registry, $name), $arguments);
	}
}

?>