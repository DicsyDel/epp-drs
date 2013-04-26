<?
	/**
	 * API exception. Thrown upon internal API exceptions.
	 * @sdk-doconly
	 * @package Modules
	 */
	class APIException extends ApplicationException
	{ 
		function __construct ($message, $code = null)
	 	{
	 		$message = trim($message, ".");
	 		$message = "{$message}. Please consult API documentation.";
	 		parent::__construct($message, $code);		
	 	}
	}	 
?>