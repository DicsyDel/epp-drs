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
     * @name       TwoCheckoutDriver
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class TwoCheckoutDriver extends AbstractPaymentProcessorDriver implements IPaymentProcessorDriver
	{
	    /**
	     * Config
	     *
	     * @var array
	     */
		public $Config = array(
								"hash"     => array("title" => "Secret key", "type"=> "text"),
								"merchid"  => array("title" => "Merchant ID", "type"=> "text"),
								"isdemo"   => array("title" => "Test mode", "type"=> "checkbox")
							);
		
		const HOSTNAME = "https://www.2checkout.com/2co/buyer/purchase";
		//https://www.2checkout.com/cgi-bin/sbuyers/cartpurchase.2c
		
		private $Hash;
		private $MerchantID;
		private $DemoMode;
		
		private $CurrencyISO;
		
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			$this->Hash		  = defined("CF_PAYMENTS_TWOCHECKOUT_HASH") 	? CF_PAYMENTS_TWOCHECKOUT_HASH    : "";
			$this->MerchantID = defined("CF_PAYMENTS_TWOCHECKOUT_MERCHID") 	? CF_PAYMENTS_TWOCHECKOUT_MERCHID : "";
			
			$this->CurrencyISO = defined("CF_CURRENCYISO") ? CF_CURRENCYISO : "USD";
			
			if (defined("CF_PAYMENTS_TWOCHECKOUT_ISDEMO")) $this->SetDemoMode(CF_PAYMENTS_TWOCHECKOUT_ISDEMO); 
		}
						
		/**
		 * Return name of payment method
		 * @return string
		 */
		public final function GetName()
		{
			return "2Checkout";	
		}
		
		/**
		 * Return true if current application use this payment
		 *
		 * @return bool
		 */
		public final function IPNApplicable()
		{
			return (isset($_POST['credit_card_processed']) && 
				isset($_POST['order_number']) && 
				isset($_POST['total']) && 
				isset($_POST['key']) && 
				isset($_POST['merchant_order_id'])
				);
		}
		
		/**
		 * Return OrderID
		 *
		 * @return string
		 */
		public final function GetOrderID()
		{
		    return $_POST["cart_id"];
		}
		
		/**
		 * Proccess payment
		 *
		 * @return bool
		 */
		public final function ProccessPayment($expected_amount = false, $expected_currency = false)
		{
			// temporary commented checking the `key` parameter 
			$result = strtoupper( md5($this->Hash . $this->MerchantID . $_POST["order_number"] . $_POST["total"])) == $_POST["key"];
			$result &= ($_POST['credit_card_processed'] == 'Y');
			$result |= $this->IsDemo();			
			
			if ($result)
			{
				if ($expected_amount)
				{
				    if ($expected_amount != $_POST["total"])
				        $this->ThrowFailedTransactionFunction("Postback amount does not match purchase amount.");
				}
			    
			    $this->ThrowSuccessTransactionFunction();
			}
			else 
				$this->ThrowFailedTransactionFunction("ProccessPayment failed");
		}
		
		/**
		 * Send request to payment server
		 * @param float $amount
		 * @param integer $invoiceid
		 * @param string $description
		 * @param string $type 'single or subscription'
		 * @param array $extra
		 * 
		 * @return bool
		 */
		public final function ProceedToPayment($amount, $invoiceid, $description, $type = 'single', $extra = array())
		{
			$host = self::HOSTNAME;
			
			if ($type == 'single')
			{
				$data = array(
								"sid"			=> $this->MerchantID,
								"cart_order_id"	=> $invoiceid,
								"total"			=> $amount,
								"card_holder_name" => $extra["firstname"] . " " . $extra["lastname"],
								"street_address" => $extra["address"],
								"city" 			=> $extra["city"],
								"state" 		=> $extra["state"],
								"zip" 			=> $extra["zip"],
								"country" 		=> $extra["country"],
								"email" 		=> $extra["email"],
								"phone" 		=> $extra["phone"],
								"ship_street_address" => $extra["address"],
								"ship_city" 	=> $extra["city"],
								"ship_zip" 		=> $extra["zip"],
								"ship_country" 	=> $extra["country"],
								"ship_state" 	=> $extra["state"],
								"Product_description" => $description,
								"tco_currency"	=> $this->CurrencyISO,
								"fixed"			=> "Y"
							);
				
				if ($extra["return_url"]) $data["x_receipt_link_url"] = $extra["return_url"];
				if ($extra["product_id"]) $data["product_id"] = $extra["product_id"];
				//$data["merchant_product_id"] = ($extra["merchant_product_id"]) ? $extra["merchant_product_id"] : time();
				
				if ($this->IsDemo()) $data["demo"] = "Y";
				
				//var_dump($data);
				
				CoreUtils::RedirectPOST($host, $data);
			}
			elseif ($type == 'subscription') 
			{
				# todo	
			}
			exit();
		}
		
		
		/**
		 * Set demo mode
		 * 
		 */
		public function SetDemoMode($mode = true)
		{
			$this->DemoMode = $mode ? true : false;
		}
		
		
		/**
		 * Get mode
		 * @result true if demo-mode
		 */
		public function IsDemo()
		{
			return $this->DemoMode;
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
	}
?>