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
	
	Core::Load("Graphics/ImageMagick/ImageMagickTool");
	
	/**
     * @name MplayerDriver
     * @category   LibWebta
     * @package    Graphics
     * @subpackage VideoUtil
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class MplayerDriver extends AbstractDriver implements IVideoUtilDriver 
	{	
		/**
		 * Mplayer binary path
		 *
		 * @var string
		 * @access protected
		 */
		protected $MplayerPath;
		
		/**
		 * Constructor
		 *
		 * @param string $filename
		 * @ignore 
		 */
		function __construct($filename)
		{
			$this->FileName = $filename;
			parent::__construct();
			
		}
		
		/**
		 * Cut video file to $parts images
		 *
		 * @param integer $parts
		 */
		public function Cut($parts)
		{
			// Get Cut delay
			$delay = round($this->GetLength()/$parts, 1);
			
			// Set TMP dir
			$tmp_dir = ini_get("session.save_path") ? ini_get("session.save_path") : "/tmp";
			
			// Get ImageMagic Tool
			$ImageMagickTool = new ImageMagickTool();
						
			for($part = 0; $part < $parts; $part++)
			{
				// Set offset
				$TimeOffset = $part*$delay;
				
				// Composite command
				$command = "{$this->MplayerPath} -nosound -xy 9 -vo jpeg:noprogressive:baseline:optimize=100:smooth=0:quality=75:outdir={$tmp_dir} -frames 1 -ss {$TimeOffset} {$this->FileName}";
				
				$this->Shell->ExecuteRaw($command);
				
				$tmp_file = "{$tmp_dir}/00000002.jpg";
				
				if (file_exists($tmp_file))
				{
					// Resize cutted image to normal size.
					$ImageMagickTool->LoadImage($tmp_file);
					$ImageMagickTool->Resize($this->ImageWidth, $this->ImageHeight);
					$ImageMagickTool->Save("{$this->OutputPath}/{$part}.jpg");
					@unlink($tmp_file);					
				}
			}
			
			return true;
		}
		
		/**
		 * Set Driver options
		 *
		 * @param string $key
		 * @param string $val
		 */
		public function SetDriverOption($key, $val)
		{
			switch($key)
			{
				case "MplayerPath":
					$this->MplayerPath = $val;
				break;
			}
			
			return true;
		}
	}
?>