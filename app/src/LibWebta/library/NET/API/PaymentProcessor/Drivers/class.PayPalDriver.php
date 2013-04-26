<?php
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
     * @name       PayPalDriver
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class PayPalDriver extends AbstractPaymentProcessorDriver implements IPaymentProcessorDriver
	{
		public $Config = array(
								"business" => array("title" => "Business E-mail", "type"=> "text"),
								"receiver" => array("title" => "Receiver E-mail", "type"=> "text"),
								"debug"	   => array("title" => "Test mode", "type"=> "checkbox")
							);
	
		/**
		 * Business E-mail
		 *
		 * @var string
		 * @access private
		 */
		private $Business;
	
		/**
		 * Currency ISO code
		 *
		 * @var string
		 * @access private
		 */
		private $CurrencyISO;
		
		/**
		 * PayPal Driver Constructor
		 * @uses CF_PAYMENTS_PAYPAL_BUSINESS
		 * @uses CF_CURRENCYISO
		 */
		function __construct()
		{
			$this->Business = defined("CF_PAYMENTS_PAYPAL_BUSINESS") ? CF_PAYMENTS_PAYPAL_BUSINESS : "paypaltest1@webta.net";
			$this->IPNURL = "http://{$_SERVER['HTTP_HOST']}/ipn.php";
			
			$this->CurrencyISO = defined("CF_CURRENCYISO") ? CF_CURRENCYISO : "USD";
		}
						
		/**
		 * Return name of payment method
		 * @return string
		 */
		public final function GetName()
		{
			return "PayPal";	
		}
		
		/**
		 * Return true if current application use this payment
		 *
		 * @return bool
		 */
		public final function IPNApplicable()
		{
			return (
    				$_POST['verify_sign'] && 
    				$_POST['receiver_email'] && 
    				$_POST['payer_email'] &&
    				$_POST['payer_id']
				   );
		}
		
		/**
		 * Return OrderID
		 *
		 * @return string
		 */
		public final function GetOrderID()
		{
		    return $_POST["custom"];
		}
		
		/**
		 * Proccess payment
		 *
		 * @return bool
		 */
		public final function ProccessPayment($expected_amount = false, $expected_currency = false, $check_txn_type = true, $check_payment_status = true)
		{
			//
			$res = $this->PostBack();
			
			$result =  strcmp($res, "VERIFIED") == 0 && 
			$_POST['mc_currency'] == $this->CurrencyISO && 
			(strtolower($_POST['business']) == strtolower($this->Business) || (defined("CF_PAYMENTS_PAYPAL_RECEIVER") && strtolower(CF_PAYMENTS_PAYPAL_RECEIVER) == strtolower($_POST['receiver_email'])));
			
			if ($check_payment_status)
				$result = ($result && ($_POST["payment_status"] == "Completed" || ($_POST["payment_status"] == "Pending" && CF_PAYMENTS_PAYPAL_DEBUG == 1)));
			
			if ($check_txn_type)
			    $result = ($result && ($_POST["txn_type"] == "subscr_payment" || $_POST["txn_type"] == "web_accept"));
			
			if ($result)
			{
				if ($expected_amount)
				{
				    if ($expected_amount != $_POST["mc_gross"])
				        $this->ThrowFailedTransactionFunction("Postback amount does not match purchase amount.");
				}
				
			    $this->ThrowSuccessTransactionFunction();
			}
			else
			{
				try 
    			{
        			$reason = "PayPal Postback validation: ";
        			$reason .= (strcmp($res, "VERIFIED") == 0) ? "passed" : "failed ({$res})";
    			    
    			    $reason .= "\nCurrency validation: ";
    			    if ($_POST['mc_currency'] == $this->CurrencyISO)
                        $reason .="passed";
    			    else 
                        $reason .="failed ({$this->CurrencyISO})";
                        
                    $reason .="\nBussines validation: ";
                    if (strtolower($_POST['business']) == strtolower($this->Business))
                        $reason .="passed";
    			    else 
                        $reason .="failed ({$this->Business})";
                        
                    if (defined("CF_PAYMENTS_PAYPAL_RECEIVER"))
                    {
                        $reason .="\nReceiver validation: ";
                        if (strtolower(CF_PAYMENTS_PAYPAL_RECEIVER) == strtolower($_POST['receiver_email']))
                            $reason .="passed";
        			    else 
                            $reason .="failed (".CF_PAYMENTS_PAYPAL_RECEIVER.")";
                    }
                    
                    $reason .="\n TNX Type validation: ";
                    if ($_POST["txn_type"] == "subscr_payment" || $_POST["txn_type"] == "web_accept")
                        $reason .="passed";
    			    else 
                        $reason .="failed ({$_POST["txn_type"]})";
                        
                    $reason .="\n Payment status validation: ";
                    if (($_POST["payment_status"] == "Completed" || ($_POST["payment_status"] == "Pending" && CF_PAYMENTS_PAYPAL_DEBUG == 1)))
                        $reason .="passed";
    			    else 
                        $reason .="failed ({$_POST["payment_status"]}, ".CF_PAYMENTS_PAYPAL_DEBUG.")";
    			}
    			catch(Exception $e)
    			{
    			    $reason = $e->toString();
    			}
			    
			    $this->ThrowFailedTransactionFunction($reason);
			}
		}
		
		/**
		 * Send request to payment server
		 * @param float $amount
		 * @param integer $invoiceid
		 * @param string $description
		 * @param string $type 'single or subscription'
		 * @param array $extra
		 * @uses CF_PAYMENTS_PAYPAL_DEBUG
		 * 
		 * @return bool
		 */
		public final function ProceedToPayment($amount, $invoiceid, $description, $type = 'single', $extra = array(), $forceredirect = true)
		{
			$host = (defined("CF_PAYMENTS_PAYPAL_DEBUG") && CF_PAYMENTS_PAYPAL_DEBUG) ? "www.sandbox.paypal.com" : "www.paypal.com";
			
			if ($type == 'single')
			{
				$data = array(
								"cmd"			=> "_xclick",
								"business"		=> $this->Business,
								"item_name"		=> $description,
								"amount"		=> $amount,
								"no_shipping"	=> 1,
								"custom"		=> $invoiceid,
								"return"		=> $extra["returnurl"],
								"notify_url"	=> $this->IPNURL,
								"currency_code"	=> $this->CurrencyISO
							);
				
				
				$url = "http://{$host}/cgi-bin/webscr?".http_build_query($data);
			}
			elseif ($type == 'subscription') 
			{
				$term_unit = ($extra["term_unit"]) ? $extra["term_unit"] : "M";
				
				$data = array(
								"business" 		=> $this->Business,
								"page_style"	=> "PayPal",
								"item_name"		=> $description,
								"notify_url"	=> $this->IPNURL,
								"no_shipping"	=> 1,
								"return"		=> $extra["returnurl"],
								"no_note"		=> 1,
								"currency_code"	=> $this->CurrencyISO,
								"a3"			=> $amount,
								"p3"			=> $extra['term'],
								"t3"			=> $term_unit,
								"src"			=> 1,
								"sra"			=> 1,
								"custom"		=> $invoiceid
							);
							
                if ($extra["modify"])
                {
                    $data["modify"] = $extra["modify"];
                    $data["subscr_id"] = $extra["subscr_id"];
                }
							
				$url = "https://{$host}/subscriptions/?".http_build_query($data);	
			}
			
			if ($forceredirect)
                header("Location: {$url}");
			else 
                return $url;
			
			exit();
		}
		
		/**
		 * Validate payment data
		 *
		 * @param array $data
		 */
		public function ValidatePaymentData($data)
		{
		    return false;
		}
		
		/**
		* Send postback to PayPal server
		* @return string $res
		*/
		private final function PostBack()
		{
			// read the post from PayPal system and add 'cmd'
			$req = 'cmd=_notify-validate';
			
			foreach ($_POST as $key => $value) 
			{
				$value = urlencode(stripslashes($value));
				$req .= "&{$key}={$value}";
			}
			
			// post back to PayPal system to validate
			$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
			
			// open socket
			$host = (defined("CF_PAYMENTS_PAYPAL_DEBUG") && CF_PAYMENTS_PAYPAL_DEBUG) ? "www.sandbox.paypal.com" : "www.paypal.com";
			$fp = @fsockopen($host, 80, $errno, $errstr, 30);
						
			if (!$fp)
				return false;
			else 
			{
				//Push response
				@fputs($fp, $header . $req);
				// Read response from server
				while (!feof($fp)) 
					$res = @fgets($fp, 128);
				
				// Close pointer
				@fclose ($fp);
				
				return $res;
			}
		}
	}
?>