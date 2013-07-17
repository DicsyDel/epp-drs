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

	
    /**
     * @name Boo Class
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
    require('xmlseclibs.inc.php');
	class WSSESoap 
	{
		const WSSENS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
		const WSUNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
		const WSSEPFX = 'wsse';
		const WSUPFX = 'wsu';
		private $SOAPNS, $SOAPPFX;
		private $SOAPDoc = NULL;
		private $Envelope = NULL;
		private $SOAPXPath = NULL;
		private $SecNode = NULL;
		
		private function locateSecurityHeader($bMustUnderstand = TRUE, $setActor = NULL) {
			if ($this->SecNode == NULL) {
				$headers = $this->SOAPXPath->query('//wssoap:Envelope/wssoap:Header');
				$header = $headers->item(0);
				if (! $header) {
					$header = $this->SOAPDoc->createElementNS($this->SOAPNS, $this->SOAPPFX.':Header');
					$this->Envelope->insertBefore($header, $this->Envelope->firstChild);
				}
				$SecNodes = $this->SOAPXPath->query('./wswsse:Security', $header);
				$SecNode = NULL;
				foreach ($SecNodes AS $node) {
					$actor = $node->getAttributeNS($this->SOAPNS, 'actor');
					if ($actor == $setActor) {
						$SecNode = $node;
						break;
					}
				}
				if (! $SecNode) {
					$SecNode = $this->SOAPDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX.':Security');
					$header->appendChild($SecNode);
					if ($bMustUnderstand) {
						$SecNode->setAttributeNS($this->SOAPNS, $this->SOAPPFX.':mustUnderstand', '1');
					}
					if (! empty($setActor)) {
						$SecNode->setAttributeNS($this->SOAPNS, $this->SOAPPFX.':actor', $setActor);
					}
				}
				$this->SecNode = $SecNode;
			}
			return $this->SecNode;
		}
	
		public function __construct($doc, $bMustUnderstand = TRUE, $setActor=NULL) {
			$this->SOAPDoc = $doc;
			$this->Envelope = $doc->documentElement;
			$this->SOAPNS = $this->Envelope->namespaceURI;
			$this->SOAPPFX = $this->Envelope->prefix;
			$this->SOAPXPath = new DOMXPath($doc);
			$this->SOAPXPath->registerNamespace('wssoap', $this->SOAPNS);
			$this->SOAPXPath->registerNamespace('wswsse', WSSESoap::WSSENS);
			$this->locateSecurityHeader($bMustUnderstand, $setActor);
		}
	
		public function addTimestamp($secondsToExpire=3600) {
			/* Add the WSU timestamps */
			$security = $this->locateSecurityHeader();
	
			$timestamp = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS, WSSESoap::WSUPFX.':Timestamp');
			$security->insertBefore($timestamp, $security->firstChild);
			$currentTime = time();
			$created = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS,  WSSESoap::WSUPFX.':Created', gmdate("Y-m-d\TH:i:s", $currentTime).'Z');
			$timestamp->appendChild($created);
			if (! is_null($secondsToExpire)) {
				$expire = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS,  WSSESoap::WSUPFX.':Expires', gmdate("Y-m-d\TH:i:s", $currentTime + $secondsToExpire).'Z');
				$timestamp->appendChild($expire);
			}
		}
	
		public function addUserToken($userName, $password=NULL, $passwordDigest=FALSE) {
			if ($passwordDigest && empty($password)) {
				throw new Exception("Cannot calculate the digest without a password");
			}
			
			$security = $this->locateSecurityHeader();
	
			$token = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS, WSSESoap::WSUPFX.':UsernameToken');
			$security->insertBefore($token, $security->firstChild);
	
			$username = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS,  WSSESoap::WSUPFX.':Username', $userName);
			$token->appendChild($username);
			
			/* Generate nonce - create a 256 bit session key to be used */
			$objKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
			$nonce = $objKey->generateSessionKey();
			unset($objKey);
			$createdate = gmdate("Y-m-d\TH:i:s").'Z';
			
			if ($password) {
				$passType = '#PasswordText';
				if ($passwordDigest) {
					$password = base64_encode(sha1($nonce.$createdate. $password, true));
					$passType = '#PasswordDigest';
				}
				$passwordNode = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS,  WSSESoap::WSUPFX.':Password', $userName);
				$token->appendChild($passwordNode);
				$passwordNode->setAttribute('Type', $passType);
			}
	
			$nonceNode = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS,  WSSESoap::WSUPFX.':Nonce', base64_encode($nonce));
			$token->appendChild($nonceNode);
	
			$created = $this->SOAPDoc->createElementNS(WSSESoap::WSUNS,  WSSESoap::WSUPFX.':Created', $createdate);
			$token->appendChild($created);
		}
	
		public function addBinaryToken($cert, $isPEMFormat=TRUE, $isDSig=TRUE) {
			$security = $this->locateSecurityHeader();
			$data = XMLSecurityDSig::get509XCert($cert, $isPEMFormat);
	
			$token = $this->SOAPDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX.':BinarySecurityToken', $data);
			$security->insertBefore($token, $security->firstChild);
	
			$token->setAttribute('EncodingType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary');
			$token->setAttributeNS(WSSESoap::WSUNS, WSSESoap::WSUPFX.':Id', XMLSecurityDSig::generate_GUID());
			$token->setAttribute('ValueType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3');
			
			return $token;
		}
		
		public function attachTokentoSig($token) {
			if (! ($token instanceof DOMElement)) {
				throw new Exception('Invalid parameter: BinarySecurityToken element expected');
			}
			$objXMLSecDSig = new XMLSecurityDSig();
			if ($objDSig = $objXMLSecDSig->locateSignature($this->SOAPDoc)) {
				$tokenURI = '#'.$token->getAttributeNS(WSSESoap::WSUNS, "Id");
				$this->SOAPXPath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
				$query = "./secdsig:KeyInfo";
				$nodeset = $this->SOAPXPath->query($query, $objDSig);
				$keyInfo = $nodeset->item(0);
				if (! $keyInfo) {
					$keyInfo = $objXMLSecDSig->createNewSignNode('KeyInfo');
					$objDSig->appendChild($keyInfo);
				}
				
				$tokenRef = $this->SOAPDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX.':SecurityTokenReference');
				$keyInfo->appendChild($tokenRef);
				$reference = $this->SOAPDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX.':Reference');
				$reference->setAttribute("URI", $tokenURI);
				$tokenRef->appendChild($reference);
			} else {
				throw new Exception('Unable to locate digital signature');
			}
		}
	
		public function signSOAPDoc($objKey) {
			$objDSig = new XMLSecurityDSig();
	
			$objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
	
			$arNodes = array();
			foreach ($this->SecNode->childNodes AS $node) {
				if ($node->nodeType == XML_ELEMENT_NODE) {
					$arNodes[] = $node;
				}
			}
			
			foreach ($this->Envelope->childNodes AS $node) {
				if ($node->namespaceURI == $this->SOAPNS && $node->localName == 'Body') {
					$arNodes[] = $node;
					break;
				}
			}
			
			$arOptions = array('prefix'=>WSSESoap::WSUPFX, 'prefix_ns'=>WSSESoap::WSUNS);
			$objDSig->addReferenceList($arNodes, XMLSecurityDSig::SHA1, NULL, $arOptions);
	
			$objDSig->sign($objKey);
	
			$objDSig->appendSignature($this->SecNode, TRUE);
		}
		
		public function saveXML() {
			return $this->SOAPDoc->saveXML();
		}
	
		public function save($file) {
			return $this->SOAPDoc->save($file);
		}
	}
	
?>
