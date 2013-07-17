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
     * @package    Graphics
     * @subpackage VideoUtil
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Load AbstractDriver
	 */
	Core::Load("Graphics/VideoUtil/Drivers/class.AbstractDriver.php");
	
	/**
	 * Load VideoUtilDriver
	 */
	Core::Load("Graphics/VideoUtil/Drivers/interface.VideoUtilDriver.php");
	
	/**
     * @name VideoUtil
     * @category   LibWebta
     * @package    Graphics
     * @subpackage VideoUtil
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class VideoUtil extends Core 
	{
		/**
		 * File name
		 *
		 * @var string
		 * @access private
		 */
		private $FileName;
		
		/**
		 * File Extension
		 *
		 * @var string
		 * @access private
		 */
		private $Extension;
		
		/**
		 * Drivers extensions 
		 *
		 * @var array
		 * @access public
		 */
		public $DriverExtensions;
		
		/**
		 * Driver
		 *
		 * @var Object
		 * @access private
		 */
		private $Driver;
	
		/**
		 * VideoUtil Constructor
		 *
		 */
		function __construct()
		{
			$this->DriverExtensions = array("FFMpeg", "wmv" => "Mplayer");			
		}
		
		/**
		 * Load video file
		 *
		 * @param string $filename
		 * @return bool
		 */
		public function LoadFile($filename)
		{
			$pi = pathinfo($filename);
			$this->Extension = strtolower($pi["extension"]);	
			$this->FileName = $filename;
			
			if (file_exists($this->FileName))
			{
				if ($this->DriverExtensions[$this->Extension])
					$this->SetDriver($this->DriverExtensions[$this->Extension]);
				else
					$this->SetDriver($this->DriverExtensions[0]);
				
					return true;
			}
			else
				return false;
		}
		
		/**
		 * Sets Driver to use
		 * @param string $Driver
		 * @throws CoreException
		 * @todo Use Reflection API instead of eval function
		 */
		public function SetDriver($driver)
		{
			$driverspath = dirname(__FILE__)."/Drivers";
			
			try
			{
				if (file_exists("{$driverspath}/class.{$driver}Driver.php"))
				{
					require_once("{$driverspath}/class.{$driver}Driver.php");
					eval("\$this->Driver = &new {$driver}Driver('{$this->FileName}');");
				}
				else
					throw new CoreException("No such driver \"{$driver}\" implemented.");
				
			} catch (Exception $e)
			{
				throw new CoreException("Failed to load VideoUtil driver \"{$driver}\". " . $e->__toString());
			}
		}
		
		/**
		 * Return length of movie
		 *
		 * @return int
		 */
		public function GetLength()
		{
			return $this->Driver->GetLength();
		}
		
		/**
		 * Cut movie
		 *
		 * @param integer $parts
		 * @return bool
		 */
		public function Cut($parts)
		{
			return $this->Driver->Cut($parts);
		}
		
		/**
		 * Set thumbnails demensions
		 *
		 * @param integer $width
		 * @param integer $height
		 * @return bool
		 */
		public function SetDimensions($width, $height)
		{
			return $this->Driver->SetDimensions($width, $height);
		}
		
		/**
		 * Set output images path
		 *
		 * @param string $path
		 * @return bool
		 */
		public function SetOutputPath($path)
		{
			return $this->Driver->SetOutputPath($path);
		}
	}
?>