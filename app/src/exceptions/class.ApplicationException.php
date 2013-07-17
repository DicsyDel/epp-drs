<?
	/**
	 * Base exception to be derived by all exceptions.
	 * Can be thrown too (not recommended).
	 * @sdk-doconly
	 * @category EPP-DRS
	 * @package Common
	 */
	class ApplicationException extends Exception
	{ 
		
		/**
		 * Backtrace
		 *
		 * @var string
		 */
		protected $BackTrace;
		
		/**
		 * A cow
		 *
		 * @param string $message
		 * @param int $code One of PHP's internal E_
		 */
		function __construct($message, $code = null)
	 	{
	 		// Defaultize $code. Not sure if we can place a constant in param default, since constants are kind of late-binded
	 		$code = ($code == null) ? E_USER_ERROR : $code;
	 		
	 		// Call Exception constructor
	 		parent::__construct($message, $code);
	 		
	 		// Generate backtrace if debug mode flag set
 			if (CONFIG::$DEV_DEBUG)
 				$this->BackTrace = Debug::Backtrace();
 			
 			// Log exception
	 		if ( class_exists("Log") && Log::HasLogger("EPPDRSLogger"))
		 	{
				Log::$DoRaiseExceptions = false;
				Log::Log("{$this->getMessage()}", $code, array("backtrace" => $bt), "EPPDRSLogger");
		 	}
	 	}
	}	 
?>