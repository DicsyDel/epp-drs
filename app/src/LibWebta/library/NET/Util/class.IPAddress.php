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
     * @package    NET
     * @subpackage Util
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */	

    /**
	 * @name IPAddress
	 * @package NET
	 * @subpackage Util
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */	
    class IPAddress extends Core 
    {
        /**
         * IP address
         *
         * @var string
         */
        public $IP;
        
        /**
         * Validaror Instance
         *
         * @var Validator
         */
        private $Validator;
        
        
        /**
         * IPAddress Constructor
         *
         * @param string $ip IP address string, q.x.y.z notation.
         * @uses Validaror
         */
        function __construct($ip)
        {
            $this->Validator = Core::GetInstance("Validator");
            $this->IP = $ip;
                       
            if (!$this->Validator->IsIPAddress($ip))
                $this->IP = false;
        }
        
        
        /**
         * Either $this->IP belongs to internal network
         *
         * @return bool
         */
        public function IsInternal()
        {
            return !$this->Validator->IsExternalIPAddress($this->IP);
        }
        
        
        /**
         * Either $this->IP belongs to external network
         *
         * @return bool
         */
        public function IsExternal()
        {
            return $this->Validator->IsExternalIPAddress($this->IP);
        }
        
        
        /**
         * Return a sting IP address representation, in q.x.y.z notation
         *
         * @return string
         */
        function __toString()
        {
            return $this->IP;
        }
    }
?>