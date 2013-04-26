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
     * @package    NET
     * @subpackage REST
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    Core::Load("Reflection");
    
	/**
	 * @name RESTServer
	 * @category LibWebta
	 * @package NET
	 * @subpackage REST
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	class RESTServer extends Core 
	{
		
	    /**
	     * REST Server methods
	     *
	     * @var array
	     */
	    private $Methods = array();
	    
	    private $DefaultNS = "base";
	    private $Request = null;
		/**
		 * Constructor
		 */
        function __construct() 
        {
			
		}

		public function Handle($request = false, $defaultNS = "")
        {
            if (!$request)
                $request = $_REQUEST;
            else 
            {
                if (!is_array($request))
                    @parse_str($request, $request);
            }
            
            if (!$request["method"])
            {
            	Core::RaiseError("Malformed request", E_USER_ERROR);
            }

            $this->Request = $request;
                
            // Determine namespace & method            
            if ($this->Methods[$request["method"]] instanceof ReflectionMethodEx)
            {
                $reflection_method = $this->Methods[$request["method"]];
                        
                $req_args = $reflection_method->getParameters();
                        
                foreach ($req_args as $arg)
                {
                    if ($request[$arg->getName()] || $arg->isOptional())
                    {
                        $given_args++;
                        $args[$arg->getName()] = $request[$arg->getName()];
                    }
                    else
                        $missing_args = $arg->getName();
                        
                }
                
                if (count($req_args) != $given_args) 
                    Core::RaiseError("Invalid Method Call to '{$this->Request['method']}'. Requires '".count($req_args)."' arguments, '".count($given_args)."' given.", E_USER_ERROR);
                
                try
                { 
                    if ($req_args == 0)
                        $result = $reflection_method->invoke();
                    else 
                        $result = $reflection_method->invokeArgs($args);
                }
                catch (Exception $e)
                {
                    Core::RaiseError($e->getMessage(), E_ERROR);
                }    
            }
            elseif ($this->Methods[$request["method"]] instanceof ReflectionFunction)
            {
                $req_args = $this->Methods[$request["method"]]->getParameters();
                        
                foreach ($req_args as $arg)
                {
                    if ($request[$arg->getName()] || $arg->isOptional())
                    {
                        $given_args++;
                        $args[$arg->getName()] = $request[$arg->getName()];
                    }
                    else
                        $missing_args = $arg->getName();
                        
                }
                
                if (count($req_args) != $given_args) 
                    Core::RaiseError("Invalid Method Call to '{$this->Request['method']}'. Requires '".count($req_args)."' arguments, '".count($given_args)."' given.", E_USER_ERROR);
                    
                try
                { 
                    if ($req_args == 0)
                        $result = $this->Methods[$request["method"]]->invoke();
                    else 
                        $result = $this->Methods[$request["method"]]->invokeArgs($args);
                }
                catch (Exception $e)
                {
                    Core::RaiseError($e->getMessage(), E_ERROR);
                }
            }
            else 
                Core::RaiseError("Method not implemented", E_USER_ERROR);
            
            
            if ($result instanceof SimpleXMLElement) 
                $response = $result->asXML();
            elseif ($result instanceof DOMDocument)
                $response = $result->saveXML();
            elseif ($result instanceof DOMNode)
                $response = $result->ownerDocument->saveXML($result);
            elseif (is_array($result) || is_object($result))
                $response = $this->HandleStruct($result);
            else
                $response = $this->HandleScalar($result);
                
            @header("Content-type: text/xml");
            @header("Content-length: ".strlen($response));
                
            print $response;
        }
		 
        private function HandleStruct($struct)
        {    
            $dom    = new DOMDocument('1.0', 'UTF-8');
            $method   = $dom->createElement($this->Request['method']);

            $dom->appendChild($method);
    
            $this->StructValue($struct, $dom, $method);
    
            $struct = (array) $struct;
            if (!isset($struct['status'])) {
                $status = $dom->createElement('status', 'success');
                $method->appendChild($status);
            }
    
            return $dom->saveXML();
        }
        
        private function StructValue($struct, DOMDocument $dom, DOMElement $parent)
        {
            $struct = (array) $struct;
    
            foreach ($struct as $key => $value) 
            {
                if ($value === false) {
                    $value = 0;
                } elseif ($value === true) {
                    $value = 1;
                }
    
                if (ctype_digit((string) $key)) {
                    $key = 'key_' . $key;
                }
    
                if (is_array($value) || is_object($value)) {
                    $element = $dom->createElement($key);
                    $this->StructValue($value, $dom, $element);
                } else {
                    $element = $dom->createElement($key, $value);
                }
    
                $parent->appendChild($element);
            }
            
        }
        
        private function HandleScalar($value)
        {
   
            $dom = new DOMDocument('1.0', 'UTF-8');
            
            $xml = $dom->createElement("Response");
            $methodNode = $xml;

            $dom->appendChild($xml);
    
            if ($value === false)
                $value = 0;
            elseif ($value === true)
                $value = 1;
            
    
            if (isset($value))
                $methodNode->appendChild($dom->createElement($this->Request['method'], $value));
            else
                $methodNode->appendChild($dom->createElement($this->Request['method']));
            
    
            $methodNode->appendChild($dom->createElement('status', 'success'));
    
            return $dom->saveXML();
        }
        
        public function AddFunction($functionname)
        {
            $namespace = "";
            
            if (function_exists($functionname))
            {
                $reflectionFunction = new ReflectionFunction($functionname);
                if ($namespace == "")
                    $this->Methods[$reflectionFunction->getName()] = $method;
	            else 
                    $this->Methods[$namespace][$reflectionFunction->getName()] = $reflectionFunction;
            }
            else 
		    {
		        Core::RaiseWarning("Class '{$classname}' not found");
		        return false;
		    }
        }
		        
        /**
         * Add Class to REST
         *
         * @param string $classname
         * @param string $namespace
         * @return bool
         */
		public function AddClass($classname, $args = array())
		{
		    $namespace = "";
		    
		    if (class_exists($classname))
		    {
		        $reflectionClass = new ReflectionClassEx($classname);
		        $methods = $reflectionClass->getPublicMethods();        
		        
		        foreach ($methods as $method)
		        {
		            if ($namespace == "")
                        $this->Methods[$method->getName()] = $method;
		            else 
                        $this->Methods[$namespace][$method->getName()] = $method;
		        }
		    }
		    else 
		    {
		        Core::RaiseWarning("Class '{$classname}' not found");
		        return false;
		    }
		}
		
		public static function Fault($message, $code)
		{
		    $dom = new DOMDocument('1.0', 'UTF-8');
            $xml = $dom->createElement('Response');
    
            $dom->appendChild($xml);
            
            $xml->appendChild($dom->createElement("message", $message));
            $xml->appendChild($dom->createElement('status', 'failed'));
            
            return $dom->saveXML();
		}
	}
?>