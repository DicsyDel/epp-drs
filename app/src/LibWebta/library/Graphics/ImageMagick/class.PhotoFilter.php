<?php

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
     * @subpackage PhotoFilter
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
    
	Core::Load("Graphics/ImageMagick/ImageMagickLite");
	
	/**
	 * Image Filter
     * @name ImageFilter
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class PhotoFilter extends ImageMagickLite 
	{
		
		/**
		 * Constructor
		 */
        function __construct($bin_path = null, $textures_path = null) 
        {
			parent::__construct($bin_path);	
			$this->TexturesPath = $textures_path;
		}
		
		
        /**
         * @access public
         * @param string $bgcolor Background color
         * @param int $angle Angle [0-180]
         * @param string $font Font Face
         * @param int $pointsize Font point size
         * @param string $fillcolor Fill color
         * @param string $strokecolor Font stroke color
         * @version 2
         */
		function Polaroid(
			$bgcolor = "grey",
			$angle = 5, 
			$caption = "Pretty cool eh?", 
			$font = "Arial", 
			$pointsize = 18, 
			$fillcolor = "darkgrey", 
			$strokecolor = "darkgrey"
		)
        { 
        	
        	$capt = is_null($caption) ? "" : "-font {$font} -pointsize {$pointsize} -fill {$fillcolor} -stroke {$strokecolor} -gravity center -set caption \"{$caption}\"";
			
        	//TODO: fix above so it outputs JPEG
        	$this->Convert("-background {$bgcolor} {$capt} -polaroid {$angle}");
        }
        
        /**
         * BlackAndWhite (Implemented)
         *
         * @version 2
         */
        function BlackAndWhite()
        { 
        	// Slow '-fx B'
			$this->Convert("-modulate 100,0 -normalize");
        }
        
        
		function _testReplaceColorByCoordinates($x = 250, $y = 250, $fuzz = "5%", $color = "white")
        {
        	//$this->IMTool->Convert("- -crop 1x1+0+0 txt:-");
        	//$pixelinfo = $this->IMTool->LoadFromBuffer();
        	//die("->".$pixelinfo);
			$this->IMTool->Convert("- -fuzz 4% -fill red -opaque '#4B583C' %stdout%");
        }
        
        
		/**
         * Normalize (Implemented)
         *
         * @version 2
         */
        function Normalize()
        {
        	$this->Convert("-normalize");
        }
        
        
		function AddTexture($texture_filename = "fabric.gif")
        {
        	$this->IMTool->Composite("{$this->TexturesPath}/{$texture_filename} - -tile -compose Hardlight %stdout%");
        	
        	$this->Display();		
        }
        
        
        /**
         * Charcoal (Implemented)
         *
         * @param int $strength [1-5] Strength
         * @version 2
         */
		function Charcoal($strength = 4)
        {
        	$this->Convert("-charcoal {$strength}");
        }
        
        /**
         * Abstract Outlines (Implemented)
         * @version passed, but not exactly
         */
		function Outline()
        {
        	$this->Convert("-edge 1 -negate -normalize -colorspace Gray -contrast-stretch 50x0%");
        }
        
        
		/**
         * Negative (Implemented)
         * @version passed
         */
		function Negate()
        {
        	$this->Convert("-negate");
        }
        
		/**
         * Pensil Sketch Outlines
         *
         * @version passed, but very slow
         */
		function PensilSketchEx()
        {
        	$this->Convert("-density 24 -depth 8 -colorspace gray \( +clone -tile {$this->TexturesPath}/pencil.gif -draw \"color 0,0 reset\"   +clone +swap -compose color_dodge -composite \)   -fx 'u*.2+v*.8'");
        }
        
        
		/**
         * Pensil Sketch Outlines -- (Implemented)
         *
         * @version 2
         */
		function PensilSketch($radius = 0, $sigma=20, $angle=120)
        {
        	$this->Convert("-density 24 -depth 8 -colorspace gray -sketch {$radius}x{$sigma}+{$angle}");
        }
        
        
		/**
         * Color Pensil Sketch
         * @version 2
         */
		function ColorPensilSketchEx($radius = 0, $sigma=20, $angle=120)
        {
        	$this->Convert("-density 24 -depth 8 -sketch {$radius}x{$sigma}+{$angle}");
        }
        
		/**
         * Color Pensil Sketch  -- (Implemented)
         * @version 2
         */
		function ColorPensilSketch($radius = 0, $sigma=20, $angle=120)
        {
        	$this->Convert("-density 24 -depth 8 -sketch {$radius}x{$sigma}+{$angle}");
        }
        
        /**
         * Adjust -> Increase Contrast (Implemented)
         * @var int $contrast [0-20] Contrast
         * @var int $midpoint, % [0-100] Middle point 
         * @version 2
         */
        function ContrastIncrease($contrast = 4, $midpoint = 30)
        {
            $this->AdjustContrast($contrast, $midpoint, true);
        }
        
        /**
         * Adjust -> Decrease Contrast (Implemented)
         * @var int $contrast [0-20] Contrast
         * @var int $midpoint, % [0-100] Middle point 
         * @version 2
         */
        function ContrastDecrease($contrast = 4, $midpoint = 30)
        {
            $this->AdjustContrast($contrast, $midpoint, false);
        }
        
        /** (Implemented)
         * Adjust -> Increase Contrast
         * Adjust -> Decrease Contrast
         * @var int $contrast [0-20] Contrast
         * @var int $midpoint, % [0-100] Middle point 
         * @var bool $reduct Either to decrease or increase
         * @version 2
         */
		function Contrast($contrast = 4, $midpoint = 30, $reduct = false)
        {
        	$r = $reduct ? "+" : "-";
        	$this->Convert("{$r}sigmoidal-contrast {$contrast}x{$midpoint}%");
        }
        
        /**
         * Sepiatone (Implemented)
         *
         * @param int $threshold [0-99] Percent threshold of the intensity
         * @version 2
         */
		public function Sepiatone($threshold = 80)
        {
        	$this->Convert("-sepia-tone {$threshold}%");
        }
        
        
		/**
         * Vingette (Implemented)
         * @var int $radius [0-100] Radius
         * @var int $sigma [0-100] Sigma
         *
         * @version 2
         */
		function Vingette($radius = 50, $sigma=30)
        {
        	$this->Convert("-matte -background none -vignette {$radius}x{$sigma}%");
        }
        
        
		/**
         * Painted (Implemented)
         *
         * @var int $radius [0-10] Radius
         * @version 2
         */
		function Painted($radius = 3)
        {
        	$this->Convert("-paint {$radius}");
        }
    
		/**
         * Emboss (Implemented)
         * @var int $radius Radius [0-10] Looks like it does not affect anything 
         * @version passed
         */
		function Emboss($radius = 0)
        {
        	$this->Convert("-emboss {$radius}");
        }
		
		/**
         * Pixelize (Implemented)
         * @version 2
         */
		function Pixelize()
        {
        	$this->Convert("-resize 10% -sample 1000%");
        }
        
		/**
         * Butonize (Implemented)
         * @var int $w [1-100] Border height
         * @version 2
         */
		function Buttonize($border = 5)
        {
        	$this->Convert("-raise {$border}x{$border}");
        }
        
        
		/**
         * Flip (Implemented)
         *
         * @version 2
         */
		function Flip()
        {
        	$this->Convert("-flip");
        }
        		
		/**
         * Implode (TODO)
         * @var int $factor [0-2, step .2] Strength
         * @version 2
         */
		function Implode($factor = .6)
        {
        	$this->Convert("-implode {$factor}");
        }
        
        
		/**
         * Explode (TODO)
         * @var int $factor [0-2, step .2] Strength
         * @version 2
         */
		function Explode($factor = -.6)
        {
        	$this->Convert("-implode {$factor}");
        }
        
        
		/**
         * Mirror (Implemented)
         *
         * @version 2
         */
		function Mirror()
        {
        	$this->Convert("-flop");
        }
        
        
		/**
         * Blur
         * @var int $radius [0-10] Radius
         * @var int $sigma [0-10] Sigma
         * @version 2
         */
		function Blur($radius = 3, $sigma = 4)
        {
        	$this->Convert("-blur {$radius}x{$sigma}");
        }
        
        // --------
        
		/**
         * Blur
         *
         * @version 2
         */
		function MotionBlur($radius = 0, $sigma = 12, $angle = 45)
        {
        	$this->Convert("-motion-blur {$radius}x{$sigma}+{$angle}");
        }
        
        
		/**
         * Sharpen
         *
         * @version 2
         */
		function Sharpen($radius = 3, $sigma = 5)
        {
        	$this->Convert("-sharpen {$radius}x{$sigma}");
        }
        
        
		/**
         * Unsharp
         *
         * @version 2
         */
		function Unsharp($radius = 3, $sigma = 5)
        {
        	$this->Convert("-unsharp {$radius}x{$sigma}");
        }
        
		/**
         * Equalize
         *
         * @version passed
         */
		function _testEqualize()
        {
        	$this->Convert("- -equalize %stdout%");

        	$this->Display();		
        }
        
        
        
		/**
         * Brightness
         *
         * Set brightness.
         * @var int $value Brightness mod, %. [0-500]
         * @version passed
         */
		function _testBrightness($value = 150)
        {
        	$this->Modulate($value);

        	$this->Display();		
        }
        
		
        /**
         * Adjust color saturation
         *
         * Set brightness.
         * @var int $value Color saturation mod, %. [0-500]
         * @version passed
         */
		function _testSaturate($value = 150)
        {
        	$this->Modulate(100, $value);

        	$this->Display();		
        }
        
		/**
         * Vary the brightness, saturation, and hue of an image
         *
         * Set brightness.
         * @var int $value Brightness mod, %. [0-500]
         */
		function Modulate($brightness = 100, $saturation = 100, $hue = 100)
        {
        	$this->Convert("- -modulate {$brightness},{$saturation},{$hue} %stdout%");		
        }
        
        
		/**
         * Color Spotting
         *
         * Leave one color and reset the rest to b&w.
         * @var int $value Brightness mod, %. [0-500]
         * @version 
         * @see http://www.imagemagick.org/discourse-server/viewtopic.php?f=1&t=7053&hilit=
         */
		function _testColorSpotting($value = 150)
        {
        	$this->Convert("- -matte \( +clone  -fuzz 5% -transparent '#455139' \) -compose DstOut -composite  %stdout%");

        	$this->Display();		
        }
        
		
        /**
         * Color book
         *
         * Leave one color and reset the rest to b&w.
         * @var int $value Brightness mod, %. [0-500]
         * @version 
         */
		function _testColorBook($value = 150)
        {
        	$this->Convert("- -matte \( +clone  -fuzz 5% -transparent '#455139' \) -compose DstOut -composite  %stdout%");
        	
        	$this->Display();		
        }
        
		
        /**
         * Shade
         *
         * Add shade effect
         * @var in $azimuth Azimuth, % [10-80]
         * @var in $elevation Elevation, % [10-80]
         * @version passed
         */
		function _testShade($azimuth = 30, $elevation = 30)
        {
        	$this->Convert("- -preview shade  %stdout%");
        	
        	$this->Display();		
        }
        
                
		/**
         * Noise. Actually we'll need Poison, Laplacian and Impulse. 
         * The rest really look the same.
         * @var string $type One of [Uniform, Gaussian, Multiplicative, Impulse, Laplacian, Poisson]
         * @version passed 
         */
		public function Noise($type = "Poisson")
        {
        	$this->Convert("- +noise {$type}");
        }
        
		/**
         * Alias to Poisson('Poisson')
         * @version passed 
         */
		function PoissonNoise()
        {
        	$this->Noise("Poisson");
        }
        
		/**
         * Alias to Poisson('Impulse')
         * @version passed 
         */
		function ImpulseNoise()
        {
        	$this->Noise("Impulse");
        }
        
		/**
	     * Alias to Poisson('Gaussian')
         * @version passed 
         */
		function GaussianNoise()
        {
        	$this->Noise("Gaussian");
        }
        
		/**
         * Alias to Poisson('Laplacian')
         * @version passed 
         */
		function LaplacianNoise()
        {
        	$this->Noise("Laplacian");
        }
		
        /**
         * Spread
         * Displace image pixels by a random amount.
         * $var int $amount [0-100] Amount. Defines the size of 
         * the neighborhood around each pixel to choose a candidate pixel to swap.
         * 
         * @version passed
         * @see http://www.imagemagick.org/script/command-line-options.php#spread
         */
		function Spread($amount = 1)
        {
        	$this->Convert("-spread {$amount}");
        }
        
        
		/**
         * posterize
         * Reduce the image to a limited number of color levels..
         * $var int $levels [2-100]
         * 
         * @version passed
         * @see http://www.imagemagick.org/script/command-line-options.php#posterize
         */
		function Posterize($levels = 6)
        {
        	$this->Convert("- -posterize {$levels}  %stdout%");

        	$this->Display();		
        }
        
        function PosterizeNew()
        {
        	$MagickWand = NewMagickWand();
        	MagickReadImage($MagickWand, "/home/alex/src/imws-server/app/www/turtlz.jpg");
        	#$e = MagickGetExceptionString($MagickWand);
        	#die($e);
        	MagickSetImageFormat($MagickWand, 'png');
        	
        	MagickWriteImage($MagickWand, "/tmp/zz");
        }
        
		/**
         * Edge
         * Detect edges within an image.
         * $var int $radius [1-10] Radius
         * 
         * @version passed
         * @see http://www.imagemagick.org/script/command-line-options.php#edge
         */
		function Edge($radius = 2)
        {
        	$this->Convert("- -edge {$radius}  %stdout%");

        	$this->Display();		
        }
        
        
		/**
         * Solarize
         * Negate all pixels above the threshold level.
         * $var int $threshold %,[0-99] Percent threshold of the intensity
         * 
         * @version passed
         * @see http://www.imagemagick.org/script/command-line-options.php#solarize
         */
		function _testSolarize($threshold = 50)
        {
        	$this->Convert("- -solarize {$threshold}  %stdout%");

        	$this->Display();		
        }
        
        
		/**
         * Reduce colors
         * Reduce colors in colorspace
         * $var int $colors [4,8,16,32,64,128,256,512] Amount of colors
         * 
         * @version passed
         * @see http://www.imagemagick.org/script/command-line-options.php#quantize
         */
		function _testQuantize($colors = 16)
        {
        	$this->Convert("- -colors {$colors} %stdout%");

        	$this->Display();		
        }

		
        /**
         * Gamma
         * Gamma correction
         * $var int $gamma [0-255] Level of gamma correction
         * 
         * @version passed
         * @see http://www.imagemagick.org/script/command-line-options.php#gamma
         */
		function _testGamma($gamma = 3)
        {
        	$this->Convert("- -gamma {$gamma} %stdout%");

        	$this->Display();		
        }
        
        
		/**
         * BadTV
         * BadTV effect
         * $var int $height_offset %, Height offset in percents
         * 
         * @version passed
         * @see http://www.imagemagick.org/Usage/distorts/#roll
         */
		function _testBadTV($height_offset = 30)
        {
        	$h = $this->GetSize();
        	// Required offset is input height * % offset. 
        	$h = $height_offset * $h[1]/100;
        	$this->Convert("- -roll +0-{$h} %stdout%");

        	$this->Display();		
        }
        
        
		/**
         * Color Box
         * Color Box
         * $var int $height_offset %, Height offset in percents
         * 
         * @version passed
         * @see http://www.imagemagick.org/Usage/distorts/#roll
         */
		function testColorBox($x = 10, $y = 10, $w = 50, $h = 50)
        {
        	#$this->Convert("- -type Grayscale %stdout%");
        	#$this->SetBufferIndex(2);
        	$this->Convert("- -type Grayscale %stdout%");
        	#-region widthxheight{+-}x{+-} y

        	$this->Display();		
        }
        
		/**
         * Wave
         *
         * @see http://www.imagemagick.org/Usage/distorts/#rotate
         */
		function _testWave($angle = 30)
        {
        	#$this->Convert("- -background none -rotate {$angle} %stdout%");
        }
        
        /**
         * SmartThumbnail
         *
         * @version 2
         */
		function SmartThumbnail($width, $height)
        {
        	$image_info = $this->Identify();
        	        	
            if ($image_info["size"][0] < $width && $image_info["size"][1] < $height)
                return false;
        	        
            $this->AdjustResize($width, $height);
        }
        
		/**
         * Resize (Implemented)
         *
         * @version 2
         */
		function Resize($width, $height)
        {       	        
            $width = intval($width);
			$height = intval($height);
			
			if ($width < 1) $width = 1;
			if ($height < 1) $height = 1;
			
			$this->OutSize = "{$width}x{$height}";
        }
        
		
		/**
         * AddBorder (Implemented)
         *
         * @version 2
         */
		function AddBorder($color, $size = 1)
        {
        	$size = (int)$size;
			if ($size > 5 || $size < 1) $size = 1;
				
			$color = escapeshellarg($color);
			
			$this->Convert("-bordercolor {$color} -border {$size}x{$size}");
        }
		
        
		/**
         * Crop (Implemented)
         *
         * @version 2
         */
		function Crop($width = 0, $height = 0, $x = 0, $y = 0)
        {
        	$this->Convert("-crop {$width}x{$height}+{$x}+{$y} +repage");
        }
		
		/**
         * NewRotate (TODO)
         * @var int $angle [0-180] Angle
		 * @var string $direction left or right
         * @version 2
         */
		function Rotate($angle = 30, $direction = 'left')
        {
        	$angle = (int)$angle;			
			if ($direction == 'left')
				$angle = $angle*-1;
		
			$this->Convert("-rotate {$angle} -trim");
        }
        
		/**
         * Convert image format. 
         * @var string $format. Use tags listed on page in @see section
         * @var int $quality. JPEG/MIFF/PNG compression level in percents [0-100].
         * @see http://www.imagemagick.org/script/formats.php
         */
		function ChangeImageFormat($format, $quality = null)
        {
        	$isjpeg = (strtolower($format) == "jpeg");
        	// Quality is for JPEG only
        	if ($quality != null && !$isjpeg)
        		throw new CustomImageException("{$format} does not support quality setting. Use JPEG with quality.");
        	
        	$this->SetOutputFormat($format);
        	
        	$q = $isjpeg ? "-quality {$quality}" : "";
        	$this->Convert("{$q}");
        }
        
        
   
	}

?>