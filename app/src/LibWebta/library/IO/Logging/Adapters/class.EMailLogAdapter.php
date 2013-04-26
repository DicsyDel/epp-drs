<?
	/**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    /**
	 * Load LogAdapter
	 */	
	Core::Load("IO/Logging/Adapters/interface.LogAdapter.php");

	/**
     * @name EMailLogAdapter
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class EMailLogAdapter extends Core implements LogAdapter
	{

		const LOG_EMAIL_SUBJECT 	= "Log Event";
		const LOG_EMAIL_RECIPIENT 	= "log@webta.net";

		/**
		 * Recepient
		 *
		 * @var string
		 * @access private
		 */
		private $EmailRecipient;
		
		/**
		 * Message format
		 *
		 * @var string
		 * @access private
		 */
		private $MassageFormat = "%date%, %message%, %level%\r\n";
		 
	    /**
	    * Class Constructor
	    * @ignore 
	    */
	    public function __construct($email = null)
	    {
	    	parent::__construct();
	    	
	    	if (is_null($email))
	    	{
	    		$email = self::LOG_EMAIL_RECIPIENT;
	    	}
			
			$this->EmailRecipient = $email;	    	
	        return true;
	    }
	
	
	    /**
	    * Class Destructor
	    * @ignore 
	    *
	    * Always check that the file has been closed and the buffer flushed before destruction.
	    */
	    public function __destruct()
	    {
	        $this->Close();
	    }
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param  $optionKey      Key name for the option to be changed.  Keys are adapter-specific
		 * @param  $optionValue    New value to assign to the option
		 * @return bool            True
		 */
	    public function SetOption($optionKey, $optionValue)
	    {
	        return true;
	    }
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param  $optionKey      Key name for the option to be changed.  Keys are adapter-specific
		 * @param  $optionValue    New value to assign to the option
		 * @return bool            True
		 */
		public function Open($filename = null, $accessMode = 'a')
		{
	        return true;
		}
	
	
		/**
		 * Write a message to the log.  This function really just writes the message to the buffer.
		 * If buffering is enabled, the message won't hit the filesystem until the buffer fills
		 * or is flushed.  If buffering is not enabled, the buffer will be flushed immediately.
		 *
		 * @param  $message    Log message
		 * @return bool        True
		 */
	    public function Write($fields)
	    {
			
			// Defaults
			$subject 	= defined("CF_LOG_EMAIL_SUBJECT") 	? CF_LOG_EMAIL_SUBJECT : self::LOG_EMAIL_SUBJECT;
			$recipient 	= $this->EmailRecipient;
			
			$this->PHPMailer = parent::GetPHPSmartyMailerInstance();
			
			// Send email
			$this->PHPMailer->From = defined("CF_EMAIL_ADMIN") ? CF_EMAIL_ADMIN : $recipient;
			$this->PHPMailer->AddAddress($recipient);
			$this->PHPMailer->Subject = $subject;
			$this->PHPMailer->SmartyBody = $this->ParseLogLine($fields);


			
			return $this->PHPMailer->Send();
		}
	
	
		/**
		 * Format a line before sending into the storage.
		 *
		 * @param string $message
		 * @param int $level
		 * @return string
		 */
		protected function ParseLogLine($fields)
		{
	        $output = $this->MassageFormat;
		    foreach ($fields as $fieldName => $fieldValue) 
		    {
		        $output = str_replace("%$fieldName%", $fieldValue, $output);
		    }
		    
	        $output = str_replace("%date%", date("[m-d-Y H:i:s]	"), $output);
		    
		    return $output;
		}
	
	
		/**
		 * Closes the file resource for the logfile.  Calling this function does not write any
		 * buffered data into the log, so flush() must be called before close().
		 *
		 * @return bool        True
		 */
		public function Close()
		{
		    return true;
		}
	
	}
	
?>