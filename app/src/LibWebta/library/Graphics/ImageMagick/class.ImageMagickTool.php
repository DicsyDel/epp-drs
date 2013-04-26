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
     * @subpackage ImageMagick
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Load ImageMagick
	 */
	Core::Load("Graphics/ImageMagick/ImageMagick");
	
	/**
     * @name ImageMagickTool
     * @category   LibWebta
     * @package    Graphics
     * @subpackage ImageMagick
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @deprecated This class is old and very dumb, use ImageMagickLite instead
     */
	class ImageMagickTool extends ImageMagick 
	{
		
		
		/**
		 * Temp result image path
		 *
		 * @var string
		 * @access protected
		 */
		protected $TempResultPath;
		
		/**
		 *  Density for loaded image
		 * @var integer
		 * @access public
		 */
		protected $Density;
		
		/**
		 *  JPEG/MIFF/PNG compression level
		 * @var float
		 * @access protected
		 */
		protected $Quality;
		
		/**
		 *  Coefficient for dimensions transform (1 by default)
		 *  Changed when called SetDensity method
		 * @var float
		 * @access protected
		 */
		protected $SizeCoefficient;
		
		/**
		 * ImageMagickTool constructor.
		 * @param string $image_path - Path to image
		 */
		function __construct($image_path = null)
		{
			parent::__construct();
			
			$this->SizeCoefficient = 1;
			
			if ($image_path != null)
				$this->LoadImage($image_path);
		}
		
		
		
		/**
		 * Save image file
		 *
		 * @param string $image_path Full path to image
		 * @param integer $buffer_number Buffer index
		 * @param bool $clear Clear buffer
		 */
		public function Save($image_path, $buffer_number = null, $clear = true)
		{
			$retval = file_put_contents($image_path, $this->LoadFromBuffer($buffer_number));
			
			//
			if ($clear)
				$this->ClearBuffer($buffer_number);
				
			return $retval;
		}
		
		
		/**
		 * Save result as PDF
		 *
		 * @param string $image_path Full path to image
		 * @param integer $buffer_number Buffer index
		 * @param bool $clear Clear buffer
		 */
		public function SavePDF($image_path, $buffer_number = null, $clear = true)
		{
			$index = $this->GetBufferIndex();
			$this->SetBufferIndex($buffer_number);
			$this->Convert("- '{$image_path}'");
			
			if ($clear)
				$this->ClearBuffer($buffer_number);
				
			$this->SetBufferIndex($index);
		}
		
		/**
		 * Set image density
		 *
		 * @param integer $density
		 */
		public function SetDensity($density = 72)
		{
			$this->Density = $density;
			$density = "{$density}x{$density}";
			$oldsize = $this->GetSize();
			$this->Convert("- -resample {$density} -quality 75 {$this->ImageType}:-");
			$newsize = $this->GetSize();
			
			$this->SizeCoefficient = ($oldsize[0]) ? $newsize[0] / $oldsize[0] : 0;
		}
		
		/**
		 * Set image quality
		 *
		 * @param integer $quality
		 */
		public function SetQuality($quality = 85)
		{
			$this->Quality = $quality;
			$this->Convert("- -quality {$quality} {$this->ImageType}:-");
		}
		
		/**
		 * Set size coefficient
		 *
		 * @return float
		 */
		public function GetSizeCoefficient()
		{
			return $this->SizeCoefficient;
		}
		
		/**
		* Composite all buffers to one image
		*
		*
		public function CompositeAll($buffer_number = "")
		{
			if (is_array($this->Buffers))
			{
				// save files 
				foreach ($this->Buffers as $k => $Buffer)
				{
					$tmpfname = "/tmp/ImageMagick_{$k}.{$this->ImageType}";
					$this->Save($tmpfname, $k, false);
					$files[] = $tmpfname;
					@chmod($tmpfname, 0777);
				}

				// composite saved files
				$this->Composite("/tmp/ImageMagick_* %stdout{$buffer_number}%");
				// remove files
				foreach ($files as $file)
				{
					@unlink($file);
				}
			}
		} 
		/* end composite all func */
		 
		/**
		* Composite two images 
		*
		* @param integer $image_inp First buffer index
		* @param integer $image_out Second buffer index
		* @param integer $w dimansions and coordinates of image to be compose 
		* @param integer $h dimansions and coordinates of image to be compose 
		* @param integer $x dimansions and coordinates of image to be compose 
		* @param integer $y dimansions and coordinates of image to be compose 
		*/
		function CompositeImage($image_inp, $image_out, $w, $h, $x = 0, $y = 0)
		{
			$temporary_image = "/tmp/ImageMagickTemp.png";
			@chmod($temporary_image, 0777);
			$this->SetBufferIndex($image_out);
			$this->Convert("%stdin% {$temporary_image}");
			$this->SetBufferIndex($image_inp);
			$this->Convert("%stdin% -draw \"Image Over {$x},{$y} {$w},{$h} '{$temporary_image}'\" -flatten %stdout%");
		}
		
		
		/**
		* Show Image on Browser
		* @param integer Buffer number
		* @param bool send_headers Either send Content-Type and Content-Length headers
		*/
		public function Show($buffer_number = null, $send_headers = true)
		{
			$content = $this->LoadFromBuffer($buffer_number);

			// Clean buffer
			#ob_clean();
				
			if ($send_headers)
			{
				header("Content-Type: image/" . strtolower($this->ImageType));
				header("Content-Length: " .strlen($content));
			}
				
			die($content);
		}
		
		/**
		* Alias for Show
		* @param integer Buffer number
		* @param bool send_headers Either send Content-Type and Content-Length headers
		*/
		public function Display($buffer_number = null, $send_headers = true)
		{
			$this->Show($buffer_number, $send_headers);
		}
		
		/**
		 * Get image width and height
		 *
		 * @return array
		 */
		public function GetSize()
		{
			$result = $this->IdentCache ? $this->IdentCache : $this->Identify("-");
			
			if (preg_match("/\s(\d+)x(\d+)/i", $result, $m))
				return array($m[1], $m[2], $m[1]."x".$m[2]);
			else 
				false;
			
		}
		
		/**
		 * Get information about image amination
		 *
		 * @return bool
		 */
		public function IsAnimated()
		{
			$result = $this->Identify("-format %n -");
			preg_match("/(\d+)/msi", $result, $m);

			return ($m[1] > 1);
		}
		

		/**
		 * Add border
		 *
		 * @param string $border Border params
		 * @return bool
		 */
		public function AddBorder($border = "1")
		{
			return $this->Convert("- {$border}
			-transparent white 
			-compose Src_Over 
			-composite {$this->ImageType}:-");
			
		}

		
		/**
		 * Crop a shape area in image
		 *
		 * @param int $w Width
		 * @param int $h Height
		 * @param int $x Offset x
		 * @param int $y Offset y
		 * @return bool
		 */
		public function Crop($w, $h, $x = 0, $y = 0)
		{
			return $this->Convert("- -crop {$w}x{$h}+{$x}+{$y} {$this->ImageType}:-");
		}
		
		
		
		/**
		 * Resize Image, optionally keep aspect ratio
		 *
		 * @param string $w Width in pixels
		 * @param string $h Height in pixels
		 * @return bool
		 */
		public function Resize($w = null, $h = null, $keepratio = true, $animated = null)
		{
			$ratio	= $w/$h;
			$isize	= $this->GetSize();
			
			if (!$keepratio)
				$size = "{$w}x{$h}";
			else
			{
				//
				// Maintain aspect ratio
				//
				// No need to resize
				if ($isize[0] == $w && $isize[1] == $h)
					return true;
						
				if ($isize[0] <= $w && $isize[1] <= $h)
				{
					$size = $isize[0]."x";
				}
				else
				{
					$size = ($isize[0]/$isize[1] > $ratio) ? "{$w}x" : "x{$h}";
				}
			}
			
			// Add coalesce and layers optimize for animated img
			$isanimated = (!isnull($animated)) ? $animated : $this->IsAnimated();
			$coalesce = $isanimated ? " -coalesce " : "";
			$optimize = $isanimated ? " -layers optimize " : "";
			return $this->Convert("- {$coalesce} -resize {$size} {$optimize} {$this->ImageType}:-");
			
		}
		
		
		/**
		 * Making thumbnail with aspect ratio preserving
		 * 
		 * @param integer $width Width of result thumbnail
		 * @param integer $height Height of result thumbnail
		 * @param boolean $samequality Use same quality as input image
		 * @param boolean $animated Image is animated
		 * @return bool
		 */
		public function MakeThumb($width, $height, $samequality = null, $animated = null) 
		{
			return $this->Convert("- ".($animated ? "-coalesce" : "")." ".($samequality ? "" : "-depth 8 -colors 256  -quality 95")." -thumbnail {$width}x{$height}\> {$this->ImageType}:-");
		}
		
		
		/**
		 * Make image box
		 *
		 * @param integer $w
		 * @param integer $h
		 * @return bool
		 */
		public function MakeBox($w = 100, $h = 100)
		{
			return $this->Convert("-size {$w}x{$h} xc:transparent -background transparent -gravity Center -draw \"Image Over 0,0 0,0 -\" {$this->ImageType}:-");
		}
		
		
		/**
		 * Rotate Image rightward on specified amount of angle 
		 * 
		 * @param integer $angle Angle in degree (90 deg. by default)
		 */
		public function Rotate($angle = 90)
		{
			$angle = (int)$angle;
			$this->Convert("- -rotate {$angle} %stdout%");
		}
		
	}

?>