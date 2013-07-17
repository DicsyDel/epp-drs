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
     * @package    Reflection
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
	 * @name ReflectionMethodEx
	 * @category LibWebta
	 * @package Reflection
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
    class ReflectionMethodEx extends ReflectionMethod 
	{
	    private $stdClassConstructArgs;
	    
	    function __construct($class_or_method, $name = null, $stdClassConstructArgs = array())
	    {
	        parent::__construct($class_or_method, $name);
	        $this->stdClassConstructArgs = $stdClassConstructArgs;
	    }
	    
	    /**
	     * Invoke method
	     *
	     * @return method
	     */
	    function invoke()
	    {
	        if (count($this->stdClassConstructArgs) > 1)
	           $stdClass = $this->getDeclaringClass()->newInstanceArgs($this->stdClassConstructArgs);
	        else 
	           $stdClass = $this->getDeclaringClass()->newInstance();
	           
	        return parent::invokeArgs($stdClass, func_get_args());
	    }
	    
	    /**
	     * Invoke method with args
	     *
	     * @param arrays $args
	     * @return method
	     */
	    function invokeArgs($args)
	    {
	        if (count($this->stdClassConstructArgs) > 1)
	           $stdClass = $this->getDeclaringClass()->newInstanceArgs($this->stdClassConstructArgs);
	        else 
	           $stdClass = $this->getDeclaringClass()->newInstance();
	           
	        return parent::invokeArgs($stdClass, $args);
	    }
	}
?>