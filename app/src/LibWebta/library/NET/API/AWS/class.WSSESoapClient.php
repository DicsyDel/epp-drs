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
     * @subpackage AWS
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	Core::Load("NET/API/AWS/WSSESOAP");
	
    /**
     * @name WSSESoapClient
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	class WSSESoapClient extends SoapClient
	{
		
		/**
		 * Path to an AWS certificate file
		 *
		 * @var string
		 */
		public $CertPath;
		
		/**
		 * Path to Amazon private key file  
		 *
		 * @var string
		 */
		public $KeyPath;
		
		function __doRequest($request, $location, $saction, $version) 
		{		    
		    $doc = new DOMDocument('1.0');
			$doc->loadXML($request);
			
			$objWSSE = new WSSESoap($doc);
			#echo "<pre>"; var_dump($request); #die();
			/* add Timestamp with no expiration timestamp */
		 	$objWSSE->addTimestamp();
		
			/* create new XMLSec Key using RSA SHA-1 and type is private key */
			$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
		
			/* load the private key from file - last arg is bool if key in file (TRUE) or is string (FALSE) */
			$objKey->loadKey($this->KeyPath, TRUE);
		
			try
			{
                /* Sign the message - also signs appropraite WS-Security items */
                $objWSSE->signSoapDoc($objKey);
			}
			catch (Exception $e)
			{
			    Core::RaiseError("[".__METHOD__."] ".$e->getMessage(), E_ERROR);
			}
		
			/* Add certificate (BinarySecurityToken) to the message and attach pointer to Signature */
			$token = $objWSSE->addBinaryToken(file_get_contents($this->CertPath));
			$objWSSE->attachTokentoSig($token);
					
			try
			{
				return parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version);
			}
			catch (Exception $e)
			{
				Core::RaiseError("[".__METHOD__."] ".$e->__toString(), E_ERROR);
			}
		}
	}
?>
