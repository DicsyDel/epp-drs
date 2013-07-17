<?php
	 class LicensingException extends ApplicationException
	 {
	 	function __construct ($message, $code = null)
	 	{
	 		// Call ApplicationException constructor
	 		parent::__construct("Licensing error: {$message}");
	 	}
	 }
?>