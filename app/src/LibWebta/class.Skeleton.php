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
     * @package    PackageName
     * @subpackage SubpackageName
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	define ("BAR_TIMEOUT", 5);
	
	Core::Load("Bar/Baz");
	
    /**
     * @name Boo Class
     * @category   LibWebta
     * @package    PackageName
     * @subpackage SubpackageName
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	class Boo extends Core
	{
	
		/**
		* What the cows say
		* @var int
		* @access public
		*/
		public $Moo;
		
		
		/**
		* Get filesystem mount points
		* @access public
		* @return array Mounts
		*/
		function __construct()
		{
			parent::__construct();
			//$this->Shell = Core::GetShellInstance();
			//$this->DB = Core::GetDBInstance();
			//$this->Smarty = Core::GetSmartyInstance();
			//$this->Validator = Core::GetValidatorInstance();
		}
		
		
		/**
		* Get Bar and return Baz
		* @access public
		* @return bool Baz
		*/
		public function Foo()
		{
			//
		}
		
	}
	
?>
