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
     * @filesource
     */

    /**
     * @category   LibWebta
     * @package    Graphics
     * @subpackage ImageMagick
     * @name       Graphics_ImageMagick_Test
     */
    ob_start();
	class Graphics_ImageMagick_Test extends UnitTestCase 
	{
		
		private $ImageDir; 
		private $ImageMagickLite;
		
        function __construct() 
        {
        	
        	$this->ImageDir = dirname(__FILE__) . "/tests";
        	
            $this->UnitTestCase('ImageMagick Core test');
            
            // Go hellaz
            Core::Load("Graphics/ImageMagick/ImageMagickLite");
            Core::Load("Graphics/ImageMagick/PhotoFilter");
            $this->ImageMagickLite = new ImageMagickLite();
        }
        
        
        function _testLiteConvertFromFile()
        {
        	#$this->ImageMagickLite = new ImageMagickLite();
        	$f = "{$this->ImageDir}/in.jpg";	
        	$this->ImageMagickLite->LoadImageFromFile($f);
        	$this->ImageMagickLite->Convert("-negate");
        	$result = $this->ImageMagickLite->Process();
        	$this->AssertFalse(empty($result), "Received non-empty output");
        	file_put_contents("$f.1.jpg", $result);
        }
        
		function _testLiteConvertFromBin()
        {
        	#$this->ImageMagickLite = new ImageMagickLite();
        	$f = "{$this->ImageDir}/in.jpg";	
        	$this->ImageMagickLite->LoadBinaryString(file_get_contents($f));
        	$this->ImageMagickLite->Convert("-type Grayscale");
        	$result = $this->ImageMagickLite->Process();
        	$this->AssertFalse(empty($result), "Received non-empty output");
        	file_put_contents("$f.2.jpg", $result);
        }
        
        
		function testPhotoFilter()
        {
        	ob_end_clean();
        	$base = dirname(__FILE__);
        	$IF = new PhotoFilter("/usr/local/bin", "{$base}/tests/textures");
        	//$IF->OutSize = "320x320";
        	$IF->LoadImageFromFile("{$base}/tests/spidar.jpg");
        	$IF->OutFilePath = "/tmp/123.jpg";
        	#$IF->BlackAndWhite();
			#$IF->Polaroid();
			#$IF->Sepiatone(80);
			#$IF->Normalize();
			#$IF->Charcoal();
			#$IF->Outline();
			#$IF->Negate();
			#$IF->PensilSketchEx();
			#$IF->PensilSketch();
			#$IF->ColorPensilSketch();
			#$IF->Contrast(10, 50, true);
			#$IF->Sepiatone(80);
			#$IF->Vingette(50, 30);
			#$IF->Painted(10);
			#$IF->Emboss(0);
			#$IF->Pixelize();
			#$IF->Buttonize(10);
			#$IF->Flip();
			#$IF->Rotate(30);
			#$IF->Implode(0.3);
			#$IF->Explode(0.3);
			#$IF->Mirror();
			#$IF->Blur(5, 10);
			#$IF->Rotate(30, 'left');
			#$IF->MotionBlur(10, 10, 10);
			#$IF->Sharpen(80, 10);
			#$IF->Unsharp(3, 5);
			#$IF->Noise("Gaussian");
			#$IF->PoissonNoise();
			#$IF->ImpulseNoise();
			#$IF->LaplacianNoise();
			#$IF->GaussianNoise();
			#$IF->Spread();
			#$IF->PosterizeNew();
			
			
        	$out = $IF->Process();
        	die($out);
        }
        
    }


?>
