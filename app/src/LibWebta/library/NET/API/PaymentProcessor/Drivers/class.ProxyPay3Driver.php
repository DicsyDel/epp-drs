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
	
	Core::Load("NET/HTTP");
	
	/**
     * @name       ProxyPay3Driver
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class ProxyPay3Driver extends AbstractPaymentProcessorDriver implements IPaymentProcessorDriver
	{
		
		public $Config = array(
								"merchantID" => array("title" => "Merchant ID", "type"=> "text"),
								"password" => array("title" => "Password", "type"=> "text"),
								"test"     => array("title" => "Test mode", "type"=> "checkbox")
							);
		
	    public $PaymentForm = array(
	                                   "Credit Card Number" => array("type" => "text", "name" => "ccn", "required" => 1),
	                                   "Credit Card Type"   => array("type" => "select", "name" => "ctype", "values" => array('VISA' => 'VISA', 'American Express' => 'American Express (AMEX)', 'Euroline' => 'Euroline', 'MasterCard' => 'MasterCard')),
	                                   "Credit Card Expiration"        => array("type" => "date", "name" => "Expdate", "format"=>"m/Y", "required" => 1),
	                                   "Credit Card CVV Number"           => array("type" => "text", "name" => "cvv", "required" => 1)
	                               );
	    
		
	    private $CurrencySymbol;
        private $URL;
	    
	    /**
		 * Constructor
		 *
		 */
		function __construct()
		{			
            $this->CurrencySymbol = 978;
            
            if (defined("CF_PAYMENTS_PROXYPAY3_TEST") && CF_PAYMENTS_PROXYPAY3_TEST == 1)
                $this->URL = "https://eptest.eurocommerce.gr/proxypay/apacsonline";
            else 
                $this->URL = "https://ep.eurocommerce.gr/proxypay/apacsonline";
		}
						
		/**
		 * Return name of payment method
		 * @return string
		 */
		public final function GetName()
		{
			return "ProxyPay3";
		}
		
		/**
		 * Return OrderID
		 *
		 * @return string
		 */
		public final function GetOrderID()
		{
		    return false;
		}
		
		/**
		 * Return true if current application use this payment
		 *
		 * @return bool
		 */
		public final function IPNApplicable()
		{
			return false;
		}
		
		/**
		 * Proccess payment
		 *
		 * @return bool
		 */
		public final function ProccessPayment($expected_amount = false, $expected_currency = false)
		{
			//
			/*
			if ($_POST["mode"] && $_POST["user_1"])
				$this->ThrowSuccessTransactionFunction();
			else 
				$this->ThrowFailedTransactionFunction();			
		    */
		}
		
		/**
		 * Validate payment data
		 *
		 * @param array $data
		 */
		public function ValidatePaymentData($data)
		{
		    $retval = false;
		    
		    if (!preg_match("/^[0-9]{16}$/", $data["ccn"]))
                $retval[] = _("Incorrect credit card number");
                
		    if ((int)$data["Expdate_m"] < 0 || (int)$data["Expdate_m"] > 12)
		        $retval[] = _("Incorrect Expiry date");
		        
		    if (!preg_match("/^[0-9]{2}$/", $data["Expdate_Y"]))
		        $retval[] = _("Incorrect Expiry date");
		        
		    if (!preg_match("/^[0-9]{3,4}$/", $data["cvv"]))
		        $retval[] = _("Incorrect CVC code");
		    
		    return $retval;
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
    		$merchant_reference = "REF ".implode("", explode(" ", microtime()));
		    $amount = $amount*100;
    		
		    $request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                        <JProxyPayLink>
                        	<Message>
                        		<Type>PreAuth</Type>
                        		<Authentication>
                        			<MerchantID>".CF_PAYMENTS_PROXYPAY3_MERCHANTID."</MerchantID>
                        		       <Password>".CF_PAYMENTS_PROXYPAY3_PASSWORD."</Password>
                        		</Authentication>
                        		<OrderInfo>
                        			<Amount>{$amount}</Amount>
                        			<MerchantRef>{$merchant_reference}</MerchantRef>
                        			<MerchantDesc>".htmlspecialchars($description)."</MerchantDesc>
                        			<Currency>{$this->CurrencySymbol}</Currency>
                        			<CustomerEmail>{$extra["email"]}</CustomerEmail>
                        			<Var1>InvoiceID: {$invoiceid}</Var1>
                        			<Var2 />
                        			<Var3 />
                        			<Var4 />
                        			<Var5 />
                        			<Var6 />
                        			<Var7 />
                        			<Var8 />
                        			<Var9 />
                        		</OrderInfo>
                        		<PaymentInfo>
                             		<CCN>{$extra["ccn"]}</CCN>
                             		<Expdate>{$extra["Expdate_m"]}{$extra["Expdate_Y"]}</Expdate>
                             		<CVCCVV>{$extra["cvv"]}</CVCCVV>
                             		<InstallmentOffset>0</InstallmentOffset>
                             		<InstallmentPeriod>0</InstallmentPeriod>
                             	</PaymentInfo>
                            </Message>
                            </JProxyPayLink>
                        ";
		    	    	    
		    $response = $this->SendRequest($request);
		    
		    if(!$response)
		        return false;

		    $response = @simplexml_load_string($response);		    
		    if (intval($response->ERRORCODE) != 0)
		    {
		        Core::RaiseWarning(sprintf(_("Cannot proceed request. Error code: %s. Please contact Administrator."), $response->ERRORCODE));
		        return false;
		    }
		    else
		        return true;
		}		
		
		private function SendRequest($request)
		{
		    $HTTPClient = new HTTPClient();
		    $params = array("APACScommand" => "NewRequest", "Data" => $request);		    
		    
		    return $HTTPClient->Fetch($this->URL, $params, true);
		}
	}
?>