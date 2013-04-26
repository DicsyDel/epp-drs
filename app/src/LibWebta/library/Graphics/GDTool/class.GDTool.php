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
     * @subpackage GDTool
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
     * @name GDTool
     * @category   LibWebta
     * @package    Graphics
     * @subpackage GDTool
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class GDTool extends Core
	{
		
		/**
		 * GDTool Constructor
		 *
		 */
		function __construct()
		{
			
		}
		
		/**
		 * Return new width and height with aspect ratio
		 *
		 * @param integer $width
		 * @param integer $height
		 * @param integer $old_width
		 * @param integer $old_height
		 * @return array
		 */
		public static function MaintainAspectRatio($width, $height, $old_width, $old_height)
		{
			// Calculate aspect ratio
			$aspectRat = (float)($old_width / $old_height);
	
			//calculate new width/height size, maintaining original aspect ratio
			if ($aspectRat > 1)
			{
				$width = $width;
				$height = round($height / $aspectRat);
			}
			else 
			{
				$width = round($width * $aspectRat);
				$height = $height;
			}
			
			return array($width, $height);
		}
		
		/**
		 * Resize image ad add size text to bottom
		 *
		 * @param string $image Path to source image
		 * @param string $thumb Path to result image
		 * @param integer $width Result image width
		 * @param integer $height Result image height
		 * @param bool $addtext Add size text to image bottom
		 * @return bool
		 */
		public function Resize($image, $thumb, $width, $height, $addtext = false)
		{
			$pi = pathinfo($image);
			
			list($old_width, $old_height) = getimagesize($image);
			
			if ($old_width < $width && $old_height < $height)
				return false;
			
			list($width, $height) = self::MaintainAspectRatio($width, $height, $old_width, $old_height);
				
			// Open source image
			switch(exif_imagetype($image))
			{
				case IMAGETYPE_JPEG:
					$source = @imagecreatefromjpeg($image);	
					break;
				
				case IMAGETYPE_JPEG:
					$source = @imagecreatefromjpeg($image);	
					break;
					
				case IMAGETYPE_GIF:
					$source = @imagecreatefromgif($image);	
					break;
					
				case IMAGETYPE_PNG:
					$source = @imagecreatefrompng($image);	
					break;
					
				case IMAGETYPE_BMP:
					$source = @imagecreatefromwbmp($image);	
					break;
				
				case IMAGETYPE_WBMP:
					$source = @imagecreatefromwbmp($image);	
					break;
				
				default:
					return false;
				break;
			}
			
			// Create rezult image
			$result = @imagecreatetruecolor($width, $height);
				
			@imagecopyresampled($result, $source, 0, 0, 0, 0, $width, $height, $old_width, $old_height);
			
			// Add text
			if ($addtext)
			{
				// fill the background color
				$bg = @imagecolorallocate($result, 102, 102, 102);
				$textcolor = @imagecolorallocate($result, 255, 255, 255);
				
				//
				// Get size
				//
				$size = @filesize($image);
				
				if ($size < 1024)
					$size_units = "Bytes";
				elseif ($size >= 1024 && $size < 1024*1024)
				{
					$size = round($size/1024);
					$size_units = "KB";
				}
				else
				{
					$size = round(($size/1024/1024), 2);
					$size_units = "MB";
				}
				
				$x1 = 0;
		        $y1 = $height-12;
		        $x2 = $width;
		        $y2 = $height;
				$text = "{$old_width}x{$old_height} {$size}{$size_units}  {$pi['extension']}";
				
				// Fill background on the bottom of image for size text
				@imagefilledrectangle($result, $x1, $y1, $x2, $y2, $bg);
				
				// Set font size and char width
				$fsize = 6;
				$char_width = 6;
				$font_path = dirname(__FILE__)."/../../../../../fonts/Hooge_0556.ttf";
				
				// Add text
				@imagettftext($result, $fsize, 0, round($width/2-(strlen($text)/2*$char_width)), $y1+9, $textcolor, $font_path, $text);
			}
			
			@imagejpeg($result, $thumb);
			
			return true;
		}
	}
?>