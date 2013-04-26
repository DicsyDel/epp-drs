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
     * @subpackage Captcha
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
    /**
     * @name Captcha
     * @category   LibWebta
     * @package    Graphics
     * @subpackage Captcha
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */	
	class Captcha extends Core
	{
		
		/**
		 * Captchas string length
		 *
		 */
		const StringLength = 5;
		
		/**
		 * Accepted chars
		 *
		 */
		const AcceptedChars = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
		
		/**
		 * A value between 0 and 100 describing how much color overlap
		 * there is between text and other objects.  Lower is more
		 * secure against bots, but also harder to read.
		 *
		 */
		const Contrast = 60;
		
		/**
		 * Number of triangles to draw.  0 = none
		 *
		 */
		const Polygons = 3;
		
		/**
		 * Number of ellipses to draw.  0 = none
		 *
		 */
		const Ellipses = 6;
		
		/**
		 * Number of lines to draw.  0 = none
		 *
		 */
		const Lines = 0;
		
		/**
		 * Number of dots to draw.  0 = none
		 *
		 */
		const Dots = 0;
		
		/**
		 * Minimum thickness in pixels of lines
		 *
		 */
		const MinThickness = 2;
		
		/**
		 * Maximum thickness in pixles of lines
		 *
		 */
		const MaxThickness = 8;
		
		/**
		 * Minimum radius in pixels of ellipses
		 *
		 */
		const MinRadius = 5;
		
		/**
		 * Maximum radius in pixels of ellipses
		 *
		 */
		const MaxRadius = 15;
		
		/**
		 * How opaque should the obscuring objects be. 0 is opaque, 127 is transparent.
		 *
		 */
		const Alpha = 75;
		
		
		/**
		 * String length
		 *
		 * @var integer
		 * @access private
		 */
		private $StringLength;
		
		/**
		 * Accepted Chars
		 *
		 * @var array
		 * @access private
		 */
		private $AcceptedChars;
		
		/**
		 * Contrast
		 *
		 * @var integer
		 * @access private
		 */
		private $Contrast;
		
		/**
		 * Number of Polygons
		 *
		 * @var integer
		 * @access private
		 */
		private $Polygons;
		
		/**
		 * Number of Ellipses
		 *
		 * @var integer
		 * @access private
		 */
		private $Ellipses;
		
		/**
		 * Number of Lines
		 *
		 * @var integer
		 * @access private
		 */
		private $Lines;
		
		/**
		 * Number of Dots
		 *
		 * @var integer
		 * @access private
		 */
		private $Dots;
		
		/**
		 * Min Thickness
		 *
		 * @var integer
		 * @access private
		 */
		private $MinThickness;
		
		/**
		 * Max Thickness
		 *
		 * @var integer
		 * @access private
		 */
		private $MaxThickness;
		
		/**
		 * Min Radius
		 *
		 * @var integer
		 * @access private
		 */
		private $MinRadius;
		
		/**
		 * Max Radius
		 *
		 * @var integer
		 * @access private
		 */
		private $MaxRadius;
		
		/**
		 * Alpha
		 *
		 * @var integer
		 * @access private
		 */
		private $Alpha;
		
		/**
		 * CAPTCHA Phrase
		 *
		 * @var string
		 * @access private
		 */
		private $Phrase;
		
		/**
		* Captcha Constructor - set captcha options
		* @access public
		* @param array $options
		*/
		function __construct($options)
		{
			$this->StringLength = ($options["length"]) ? $options["length"] : self::StringLength;
			$this->AcceptedChars = ($options["chars"]) ? $options["chars"] : self::AcceptedChars;
			$this->Contrast = ($options["contrast"]) ? $options["contrast"] : self::Contrast;
			$this->Polygons = ($options["polygons"]) ? $options["polygons"] : self::Polygons;
			$this->Ellipses = ($options["ellipses"]) ? $options["ellipses"] : self::Ellipses;
			$this->Lines = ($options["lines"]) ? $options["lines"] : self::Lines;
			$this->Dots = ($options["dots"]) ? $options["dots"] : self::Dots;
			$this->MinThickness = ($options["min_thickness"]) ? $options["min_thickness"] : self::MinThickness;
			$this->MaxThickness = ($options["max_thickness"]) ? $options["max_thickness"] : self::MaxThickness;
			$this->MinRadius = ($options["min_radius"]) ? $options["min_radius"] : self::MinRadius;
			$this->MaxRadius = ($options["max_radius"]) ? $options["max_radius"] : self::MaxRadius;
			$this->Alpha = ($options["alpha"]) ? $options["alpha"] : self::Alpha;
			
			$this->Phrase = ($options["phrase"]) ? $options["phrase"] : $this->GeneratePhrase();
			
			if (!function_exists("imagecreatetruecolor"))
				$this->RaiseError(_("Please install GD extension for use CAPTCHA!"));
		}
		
		/**
		*	Sets Accepted Chars
		*	@param string $chars
		*/
		public function SetAcceptedChars($chars)
		{
			$this->AcceptedChars = $chars;
		}
		
		/**
		* Generate random string
		* @access private
		* @return string $string
		*/
		public function GeneratePhrase()
		{
			$string = "";
			for ($i = 1; $i <= $this->StringLength; $i++)
			{
				$n = rand(0, strlen($this->AcceptedChars)-1);
				$string .= $this->AcceptedChars[$n];
			}
			
			return $string;	
		}
		
		/**
		 * Get CAPTCHA Phrase
		 *
		 * @return string
		 */
		public function GetPhrase()
		{
			return $this->Phrase;
		}
		
		/**
		 * Generate image
		 *
		 * @param bool $draw
		 * @return string
		 */
		public function GetImage($draw = false)
		{
			// Keep #'s reasonable.
			$this->MinThickness = max(1,$this->MinThickness);
			$this->MinThickness = min(20,$this->MinThickness);
			
			// Make radii into height/width
			$this->MinRadius *= 2;
			$this->MaxRadius *= 2;
			// Renormalize contrast
			$this->Contrast = 255 * ($this->Contrast / 100.0);
			$o_contrast = 1.3 * $this->Contrast;
			
			$width = 15 * imagefontwidth (5);
			$height = 2.5 * imagefontheight (5);
			$image = imagecreatetruecolor ($width, $height);
			imagealphablending($image, true);
			
			// Add string to image
			$rotated = imagecreatetruecolor (70, 70);
			for ($i = 0; $i < $this->StringLength; $i++) 
			{
				$buffer = imagecreatetruecolor (20, 20);
				$buffer2 = imagecreatetruecolor (40, 40);
				
				// Get a random color
				$red = mt_rand(0,255);
				$green = mt_rand(0,255);
				$blue = 255 - sqrt($red * $red + $green * $green);
				$color = imagecolorallocate ($buffer, $red, $green, $blue);
			
				// Create character
				imagestring($buffer, 5, 0, 0, $this->Phrase[$i], $color);
			
				// Resize character
				imagecopyresized ($buffer2, $buffer, 0, 0, 0, 0, 25 + mt_rand(0,12), 25 + mt_rand(0,12), 20, 20);
			
				// Rotate characters a little
				$rotated = imagerotate($buffer2, mt_rand(-25, 25),imagecolorallocatealpha($buffer2,0,0,0,0)); 
				imagecolortransparent ($rotated, imagecolorallocatealpha($rotated,0,0,0,0));
			
				// Move characters around a little
				$y = mt_rand(1, 3);
				$x += mt_rand(2, 6); 
				imagecopymerge ($image, $rotated, $x, $y, 0, 0, 40, 40, 100);
				$x += 22;
			
				imagedestroy ($buffer); 
				imagedestroy ($buffer2); 
			}
			
			// Draw polygons
			if ($this->Polygons > 0) 
				for ($i = 0; $i < $this->Polygons; $i++) 
				{
					$vertices = array (
						mt_rand(-0.25*$width,$width*1.25),mt_rand(-0.25*$width,$width*1.25),
						mt_rand(-0.25*$width,$width*1.25),mt_rand(-0.25*$width,$width*1.25),
						mt_rand(-0.25*$width,$width*1.25),mt_rand(-0.25*$width,$width*1.25)
					);
					$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), $this->Alpha);
					imagefilledpolygon($image, $vertices, 3, $color);  
				}
			
			// Draw random circles
			if ($this->Ellipses > 0) 
				for ($i = 0; $i < $this->Ellipses; $i++) 
				{
					$x1 = mt_rand(0,$width);
					$y1 = mt_rand(0,$height);
					$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), $this->Alpha);
				//	$color = imagecolorallocate($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast));
					imagefilledellipse($image, $x1, $y1, mt_rand($this->MinRadius,$this->MaxRadius), mt_rand($this->MinRadius,$this->MaxRadius), $color);  
				}
			
			// Draw random lines
			if ($this->Lines > 0) 
				for ($i = 0; $i < $this->Lines; $i++) 
				{
					$x1 = mt_rand(-$width*0.25,$width*1.25);
					$y1 = mt_rand(-$height*0.25,$height*1.25);
					$x2 = mt_rand(-$width*0.25,$width*1.25);
					$y2 = mt_rand(-$height*0.25,$height*1.25);
					$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), $this->Alpha);
					imagesetthickness ($image, mt_rand($this->MinThickness,$this->MaxThickness));
					imageline($image, $x1, $y1, $x2, $y2 , $color);  
				}
			
			// Draw random dots
			if ($this->Dots > 0) 
				for ($i = 0; $i < $this->Dots; $i++) 
				{
					$x1 = mt_rand(0,$width);
					$y1 = mt_rand(0,$height);
					$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast),$this->Alpha);
					imagesetpixel($image, $x1, $y1, $color);
				}
			
			if (!$draw)
			{
				ob_start();
	            imagepng($image);
	            $data = ob_get_contents();
	            ob_end_clean();
	            imagedestroy($image);
	            
	            return $data;
			}
			else 
			{
				header('Content-type: image/png');
				imagepng($image);
				imagedestroy($image);
			}
		}
	}
	
?>