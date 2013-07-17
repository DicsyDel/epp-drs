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

	Core::Load("System/Independent/Shell/ShellFactory");	

	/**
     * @name VideoUtilDriver
     * @abstract 
     * @category   LibWebta
     * @package    Graphics
     * @subpackage VideoUtil
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	abstract class AbstractDriver
	{
		/**
		 * Image width
		 *
		 * @var integer
		 */
		public $ImageWidth;
		
		/**
		 * Image Height
		 *
		 * @var integer
		 */
		public $ImageHeight;
		
		/**
		 * File Name
		 *
		 * @var string
		 */
		public $FileName;
		
		/**
		 * Video length
		 *
		 * @var integer
		 */
		public $Length;
		
		/**
		 * Images output path
		 *
		 * @var string
		 */
		public $OutputPath;
		
		/**
		 * Shell Instance
		 *
		 * @var Shell
		 */
		public $Shell;
		
		/**
		 * Constructor
		 * @ignore
		 */
		function __construct()
		{
			$this->Shell = ShellFactory::GetShellInstance();
			$this->ImageWidth = 100;
			$this->ImageHeight = 100;
			$this->OutputPath = ini_get("session.save_path") ? ini_get("session.save_path") : "/tmp";
			
			$this->MplayerPath = "/usr/local/bin/mplayer";
			$this->FFMpegPath = "/usr/local/bin/ffmpeg";
		}
		
		/**
		 * Sets images output path
		 *
		 * @param string $path
		 */
		public function SetOutputPath($path)
		{
			$this->OutputPath = $path;
			@mkdir($this->OutputPath, 0777, true);
		}
		
		/**
		 * Set image demensions
		 *
		 * @param integer $width
		 * @param integer $height
		 */
		public function SetDimensions($width, $height)
		{
			$this->ImageWidth = $width;
			$this->ImageHeight = $height;
		}
		
		/**
		 * Get movie length
		 *
		 * @return integer
		 */
		public function GetLength()
		{
			if (!$this->Length)
			{
				$command = "{$this->FFMpegPath} -i {$this->FileName} 2>&1 | grep Duration";
				
				$out = $this->Shell->QueryRaw($command);
				
				$matches = array();
				preg_match_all("/Duration:[^0-9]*([0-9:]+)/si", $out, $matches);
				
				if ($matches[1][0])
				{
					$expl = explode(":", $matches[1][0]);
					$this->Length = (int)$expl[0]*60*60+(int)$expl[1]*60+(int)$expl[2];
					return $this->Length;
				}
				else
					return false;
			}
			else
				return $this->Length;
		}
	}
?>