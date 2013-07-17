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
     * @package    IO
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

    /**
     * @name Sample Proccess Object
     * @category Libwebta
     * @package IO
     * @subpackage PCNTL
     * @copyright Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class testProcess implements IProcess
    {
        public $ProcessDescription = "Test process for test job";
        
        /**
         * Thread arguments
         *
         * @var array
         */
        public $ThreadArgs;
        
       
        /**
         * In this function we must create $this->ThreadArgs array. One element of this array = one thread
         *
         */
        function OnStartForking()
        {
            $this->ThreadArgs = array(1,2,3,4,5,6,7,8,9,10);
        }
        
        /**
         * What we do aftrer threading
         *
         */
        function OnEndForking()
        {
            //
        }
        
        /**
         * What we do in thread
         *
         * @param mixed $args
         */
        public function StartThread($args)
        {
            sleep(5);
            var_dump($args);
            exit();
        }
    }
?>