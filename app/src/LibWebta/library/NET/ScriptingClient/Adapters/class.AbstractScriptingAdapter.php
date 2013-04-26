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
     * @subpackage ScriptingClient
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
	 * @name AbstractScriptingAdapter
	 * @package NET
	 * @subpackage ScriptingClient
	 * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @abstract 
	 *
	 */
    abstract class AbstractScriptingAdapter extends Core 
    {
        
        /**
         * Prepare script line
         *
         * @param string $line
         * @param array $params
         * @return string
         */
        protected function PrepareScriptLine($line, $params)
        {
            @ksort($params);
            $params = @array_reverse($params);
            
            
            foreach ((array)$params as $key => $value)
                $line = str_replace("\${$key}", $value, $line);
            
            return $line;
        }
        
        /**
         * Remove empty lines and comments from script
         *
         * @param string $script
         * @return array Lines of script
         */
        protected function ParseScript($script)
        {
            $lines = explode("\n", $script);
            $retval = array();
            foreach($lines as $line)
            {
                $l = trim($line);
                if (strlen($l) == 0 || substr($l, 0, 1) == "#")
                    continue;
                    
                $retval[] = $l;
            }
            
            return $retval;
        }
    }
?>