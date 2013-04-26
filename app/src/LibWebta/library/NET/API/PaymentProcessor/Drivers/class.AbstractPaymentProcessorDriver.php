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
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
     * @name       AbstractPaymentProcessorDriver
     * @abstract 
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class AbstractPaymentProcessorDriver extends Core 
	{
		/**
		 * Success Transaction Function
		 *
		 * @var string
		 */
		public $SuccessTransactionFunction;
		
		/**
		 * Faied Transaction function
		 *
		 * @var string
		 */
		public $FailedTransactionFunction;
		
		/**
		 * IPN URL
		 *
		 * @var string
		 */
		public $IPNURL;
		
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			
			
		}
		
		/**
		 * Set IPN URL
		 *
		 * @param string $url
		 */
		function SetIPNURL($url)
		{
			$this->IPNURL = $url;
		}
		
		/**
		 * Trow Success Transaction function
		 *
		 */
		public function ThrowSuccessTransactionFunction()
		{
			$invoiceid = $this->GetOrderID();
		    		    
		    $reflect = new ReflectionFunction($this->SuccessTransactionFunction);
		    $reflect->invoke($invoiceid, $this->GetName());
		}
		
		/**
		 * Trow Failed transaction function
		 *
		 */
		public function ThrowFailedTransactionFunction($reason = "")
		{
		    $invoiceid = $this->GetOrderID();
		    
	        $reflect = new ReflectionFunction($this->FailedTransactionFunction);
	        $reflect->invoke($invoiceid, $reason);
		}
		
		/**
		 * Register Success Transaction function
		 *
		 * @param string $functionname
		 */
		public function RegisterSuccessTransactionFunction($functionname)
		{
			if (function_exists($functionname))
				$this->SuccessTransactionFunction = $functionname;
			else 
				Core::RaiseError(_("Success Transaction Function is not defined!"));
		}
		
		/**
		 * Register Failed Transaction function
		 *
		 * @param string $functionname
		 */
		public function RegisterFailedTransactionFunction($functionname)
		{
			if (function_exists($functionname))
				$this->FailedTransactionFunction = $functionname;
			else 
				Core::RaiseError(_("Failed Transaction Function is not defined!"));
		}
		
	}

?>