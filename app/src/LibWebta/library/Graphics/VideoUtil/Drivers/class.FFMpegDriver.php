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
     * @name FFMpegDriver
     * @category   LibWebta
     * @package    Graphics
     * @subpackage VideoUtil
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class FFMpegDriver extends AbstractDriver implements IVideoUtilDriver 
	{
		
		/**
		 * FFMpeg binary path
		 *
		 * @var string
		 * @access protected
		 */
		protected $FFMpegPath;
		
		/**
		 * FFMpegDriver Constructor
		 *
		 * @param string $filename
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
			// Set delay
			$delay = round($this->GetLength()/$parts, 1);
			
			for($part = 0; $part < $parts; $part++)
			{
				// Set Time offset
				$TimeOffset = $part*$delay;
				
				// Set output file
				$out_file = "{$this->OutputPath}/{$part}.jpg";
				
				$command = "{$this->FFMpegPath} -i {$this->FileName} -y -ss {$TimeOffset} -vframes 1 -an -sameq -f image2 -s {$this->ImageWidth}x{$this->ImageHeight} {$out_file} 2>&1";				
				
				$this->Shell->ExecuteRaw($command);
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
				case "FFMpegPath":
					$this->FFMpegPath = $val;
				break;
			}
			
			return true;
		}
	}
?>