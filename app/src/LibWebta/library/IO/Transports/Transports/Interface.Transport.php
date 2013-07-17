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
     * @subpackage Transports
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
    
    /**
     * Transport Interface
     * 
     * @name       ITransport
     * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	interface ITransport
	{
	    public function Read($filename);
	    
	    public function Write($filename, $content, $overwrite = true);
	    
	    public function Copy($old_path, $new_path, $recursive = true);
	    
	    public function Remove($path, $recursive = true);
	    
	    public function Chmod($path, $perms, $recursive = true);
	    
	    public function Move($old_path, $new_path, $recursive = true);
	    
	    public function MkDir($path);
	    
	    public function Execute($command);
	}
?>