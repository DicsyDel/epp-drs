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
    
    Core::Load("System/Independent/Shell/ShellFactory");
    
    /**
     * Local Transport
     * 
     * @name       LocalTransport
     * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class LocalTransport implements ITransport 
    {
        /**
         * Shell Instance
         *
         * @var Object
         */
        private $Shell;
        
        /**
         * Local Transport constructor
         * @ignore
         */
        public function __construct()
        {
            $this->Shell = ShellFactory::GetShellInstance();
        }
        
        /**
         * @ignore
         *
         */
        public function __destruct()
        {
            unset($this->Shell);
        }
        
        /**
         * Read file
         *
         * @param string $filename
         * @return bool
         */
        public function Read($filename)
        {              
            return @file_get_contents($filename);
        }
	    
        /**
         * Write file
         *
         * @param string $filename
         * @param string $content
         * @param bool $overwrite
         * @return bool
         */
	    public function Write($filename, $content, $overwrite = true)
	    {
            $tp = ($overwrite) ? FILE_APPEND : null;
            return @file_put_contents($filename, $content, $tp);
	    }
	    
	    /**
	     * Copy file or folder
	     *
	     * @param string $old_path
	     * @param string $new_path
	     * @return bool
	     */
	    public function Copy($old_path, $new_path, $recursive = true)
	    {
            $params = ($recursive) ? "-R" : "";
            return $this->Shell->ExecuteRaw("cp {$params} {$old_path} {$new_path}");
	    }
	    
	    /**
	     * Remove file or folder
	     *
	     * @param string $path
	     * @return bool
	     */
	    public function Remove($path, $recursive = true)
	    {              
            $params = ($recursive) ? "-rf" : "-f";
            return $this->Shell->ExecuteRaw("rm {$params} {$path}");
	    }
	    
	    /**
	     * Chmod file or folder
	     *
	     * @param string $path
	     * @param integer $perms
	     * @return bool
	     */
	    public function Chmod($path, $perms, $recursive = true)
	    {
            $params = ($recursive) ? "-R" : "";
            return $this->Shell->ExecuteRaw("chmod {$params} {$perms} {$path}");
	    }
	    
	    /**
	     * Move file or folder (rename)
	     *
	     * @param string $old_path
	     * @param string $new_path
	     * @return bool
	     */
	    public function Move($old_path, $new_path, $recursive = true)
	    {
            $params = ($recursive) ? "-R" : "";
            return $this->Shell->ExecuteRaw("mv {$params} {$old_path} {$new_path}");
	    }
	    
	    /**
	     * Create net directory
	     *
	     * @param string $path
	     * @return bool
	     */
	    public function MkDir($path)
	    {              
            return $this->Shell->ExecuteRaw("mkdir {$path}");
	    }
	    
	    /**
	     * Execute command
	     *
	     * @param string $command
	     * @return bool
	     */
	    public function Execute($command)
	    {  
            return $this->Shell->QueryRaw($command);
	    }
    }
?>