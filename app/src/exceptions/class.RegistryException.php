<?
	
	/**
	 * Base exception for registry errors.
	 * @sdk-doconly
	 * @package Modules
	 * @subpackage RegistryModules
	 */
	class RegistryException extends ApplicationException
	{ 
		function __construct ($message, $code = E_USER_WARNING)
	 	{
	 		$message = trim($message, ".");
	 		$message = "Registry error: {$message}";
	 		$message = trim($message, ".");
	 		parent::__construct($message, $code);		
	 	}
	}
	
	/**
	 * (Should be) thrown when the module does not implement particular operation. 
	 * @package Modules
	 * @subpackage RegistryModules
	 */
	class NotImplementedException extends RegistryException 
	{
		function __construct()
		{
			parent::__construct(_("Operation not implemented in registry module."));
		}
	}
	
	/**
	 * (Should be) Thrown when the current object state does not permit operation.
	 * @package Modules
	 * @subpackage RegistryModules
	 */
	class ProhibitedTransformException extends RegistryException
	{
		function __construct($message=null)
		{
			parent::__construct(_("The current object state does not permit this operation.")
					. ($message ? " Protocol error: $message" : ""));
		}
	}
	
	/**
	 * (Should be) thrown when the object yo are trying to perform operation on, doesn't exist.
	 * @package Modules
	 * @subpackage RegistryModules
	 */
	class ObjectNotExistsException extends RegistryException  
	{
		function __construct($message=null)
		{
			parent::__construct(_("The object you are trying to use does not exist.") 
					. ($message ? " Protocol error: $message" : ""));
		}
	}
	
	/**
	 * (Should be) thrown when object that you're trying to create already exists in registry.
	 * @package Modules
	 * @subpackage RegistryModules
	 *
	 */
	class ObjectExistsException extends RegistryException
	{
		function __construct($message=null)
		{
			parent::__construct(_("Object already exists and cannot be created.")
					. ($message ? " Protocol error: $message" : ""));
		}
	}	
?>