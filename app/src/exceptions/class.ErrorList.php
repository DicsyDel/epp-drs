<?php

	/**
	 * Exception and a list. Like I.F. Kruzenshtern, the man and the steamboat.
	 * @name ErrorList
	 * @sdk-doconly
	 * @category EPP-DRS
	 * @package Common
	 */
	class ErrorList extends Exception
	{
		private $messages = array();
		
		public function __construct()
		{
			parent::__construct("Error list. Call GetAllMessages()");
		}
		
		public function HasMessages ()
		{
			return count($this->messages) > 0;
		}
		
		public function AddMessage ($message)
		{
			$this->messages[] = $message;
		}
		
		public function GetAllMessages ()
		{
			return $this->messages;
		}
	}
?>