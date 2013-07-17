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
     * @name ConsoleLogAdapter
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class ConsoleLogAdapter extends Core implements LogAdapter
	{
	   /**
	    * Log name
	    *
	    * @var string
	    */
		public $LogName;
		
		/**
		 * Options
		 *
		 * @var array
		 * @access protected
		 */
	    protected $Options = array('format' => "\033[%color%m %message% \033[39m\n");
	
	    /**
	     * Colors
	     *
	     * @var array
	     * @access private
	     */
		private $Colors;
	    
		/**
		 * Constructor
		 * @ignore 
		 *
		 */
		public function __construct()
		{
			parent::__construct();
			
			$this->Colors = array(
									E_USER_NOTICE 	=> 33, //DEBUG - yellow
									E_NOTICE	=> 32, //INFO  - green
									E_WARNING 	=> 36, //WARNING - cyan
									E_USER_ERROR	=> 31, //ERRO - red
									E_ERROR	=> 35  //SEVERE - purple
								 );
		}
	
		/**
		 * Destructor
		 * @ignore 
		 *
		 */
		public function __destruct()
		{
			$this->Close();
		}
	   
		/**
		 * Set Adapter option
		 *
		 * @param string $optionKey
		 * @param string $optionValue
		 * @return bool true
		 */
		public function SetOption($optionKey, $optionValue)
		{
		    if (!array_key_exists($optionKey, $this->Options)) 
		    {
		        if (Log::$DoRaiseExceptions)
                    throw Core::$ReflectionException->newInstanceArgs(array(_("Unknown option \"$optionKey\"."), E_WARNING));
		        else 
                    return false;
		    }
		    
		    $this->Options[$optionKey] = $optionValue;
		    return true;
		}
	
		
		/**
		 * Open Log
		 *
		 * @return bool true
		 */
		public function Open()
		{
			return true;
		}
	
		/**
		 * Close Log
		 *
		 * @return bool true
		 */
		public function Close()
		{
			return true;
		}
	
	
		/**
		 * Write Log to output
		 *
		 * @param unknown_type $fields
		 * @return unknown
		 */
		public function Write($fields)
		{
		    $fields['logname'] = $this->LogName;
		    
		    $fields['color'] = $this->Colors[$fields["level"]];
		    
		    echo ($this->ParseLogLine($fields));
		    
			return true;
		}
	
	   /**
	    * Parse Log line
	    *
	    * @param array $fields
	    * @return string
	    */
		protected function ParseLogLine($fields)
		{
	        $output = $this->Options['format'];
	        
		    foreach ($fields as $fieldName => $fieldValue) {
		        $output = str_replace("%$fieldName%", $fieldValue, $output);
		    }
		    
		    return $output;
		}
	
	}

?>