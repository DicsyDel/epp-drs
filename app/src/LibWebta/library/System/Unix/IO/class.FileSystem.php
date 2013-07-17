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
     * @package    System_Unix
     * @subpackage IO
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    /**
	 * @name       FileSystem
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage IO
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class FileSystem extends Core 
	{
		/**
		 * @ignore
		 *
		 */
		function __construct()
		{
			$this->Shell = ShellFactory::GetShellInstance();
		}
		
		
		/**
		* Get filesystem mount points
		* @access public
		* @return array Mounts
		*/
		public final  function GetMounts()
		{
			$file = file("/proc/mounts");
			foreach ($file as $line)
			{
				$retval[] = explode(" ", $line);
			}
			return $retval;
		}
		
		
		/**
		* Get mountpoint for specific folder
		* @access public
		* @return string Mountpoint
		*/
		public final  function GetFolderMount($path)
		{
			foreach ($this->GetMounts() as $mount)
			{
				if ($mount[0][0] == "/" && substr($path, 0, strlen($mount[1])) ==  $mount[1])
				{
					$retval = $mount;
				}
			}
			return $retval;
		}
		
		
		/**
		* Get mountpoint for home root
		* @access public
		* @return string Mountpoint
		*/
		public final  function GetHomeRootMount()
		{
			return $this->GetFolderMount(CF_ENV_HOMEROOT);
		}
		
		
		/**
		* Get filesystem block size
		* @access public
		* @param string $device Device
		* @return int block size in bytes
		*/
		public function GetFSBlockSize($device)
		{
			$retval = $this->Shell->QueryRaw("/sbin/dumpe2fs $device 2>/dev/null | grep \"Block size\" | awk '{print \$3}'");
			if (!is_int($retval))
			{
				Core::RaiseWarning(_("Cannot determine filesystem block size"));
				$retval = false;
			}
				
			return ($retval);
		}
		
		
	}
	
?>