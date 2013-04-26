<?php
	
	Core::Load("PE/PipedChain");
	
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
     * @name ImageMagickLite
     * Lightweight operations with ImageMagick binaries in POSIX environment
     * @category   LibWebta
     * @package    Graphics
     * @subpackage ImageMagick
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class ImageMagickLite extends Core 
	{
		
		/**
		 * Default path to IM binaries
		 *
		 */
		const DEFAULT_BIN_PATH = "/usr/local/bin";
		
		/**
		 * Path to ImageMagick binaries
		 *
		 * @var string
		 * @access public
		 */
		public $BinDir;
		
		/**
		* PipedChain instance
		* @var PipedChain $PipedChain
		* @access protected
		*/
		protected $PChain;
		
		/**
		* Output image format
		* @var string $OutputFormat
		* @access public
		*/
		public $OutputFormat;
		
		/**
		* InputImage binary
		* @var $StdIn
		* @access protected
		*/
		protected $StdIn;
		
		/**
		* Output file path that Process() will write to
		* @var $OutFilePath
		* @access public
		*/
		public $OutFilePath;
		
		
		/**
		* In file path that Process() will read from
		* @var $InFilePath
		* @access public
		*/
		public $InFilePath;
		
		
		/**
		* Either this is a 1st link in chain and we need to load original image
		* @var bool $DoLoadImage
		* @access protected
		*/
		protected $DoLoadImage;
		
		
		/**
		* Either image-based exception will be thrown. Core::RaiseError() will be called if this parameter set to false
		* @var bool $DoThrowAdvancedException
		* @access public
		*/
		public $DoThrowAdvancedException;
		
		/**
		* Result image size
		* @var string $OutSize
		* @access public
		*/
		public $OutSize;
		
		/**
		 * Constructor
		 * @var string $bin_path Path to ImageMagick binaries
		 */
        function __construct($bin_path = null) 
        {
        	$this->PChain = new PipedChain();
			$this->Init();
			
			// Set default values
			$this->BinDir 		= is_null($bin_path) ? self::DEFAULT_BIN_PATH : $bin_path;
			$this->ConvertPath = "{$this->BinDir}/convert";
			$this->IdentifyPath = "{$this->BinDir}/identify";
			$this->AnimatePath = "{$this->BinDir}/animate";
			$this->CompositePath = "{$this->BinDir}/composite";
			$this->MogrifyPath = "{$this->BinDir}/mogrify";
			$this->MontagePath = "{$this->BinDir}/montage";
			$this->SetOutputFormat("png");
			$this->DoThrowAdvancedException = true;
			$this->OutFilePath = null;
			$this->InFilePath = null;
			
        }
        
        /**
         * Reset internal stuff
         * @return void
         */
        protected function Init()
        {
        	#$this->PChain = new PipedChain();
			$this->PChain->ClearLinks();
        	$this->DoLoadImage = true;
			$this->StdIn = null;
        }
        
        public function SetOutputFormat($format = "png")
        {
        	$this->OutputFormat = $format;
        }
        
        
        /**
         * Load image binary stream. It will be piped to STDIN in Execute();
         *
         * @param string $bin_content Binary string
         */
        public function LoadBinaryString($bin_content)
        {
        	$this->StdIn = $bin_content;
        	$this->InputImagePath = null;
        }
        
		/**
         * Set InputImagePath
         *
         * @param string $path Local image path
         */
        public function LoadImageFromFile($path)
        {
        	$this->InFilePath = $path;
        	$this->StdIn = null;
        }
        
        
        /**
         * 
         * @return string Resulting image binary string.
         */
		public function Process()
        { 
			$result = $this->PChain->Execute($this->StdIn, $this->OutFilePath, $this->InFilePath);
			
			if (!empty($this->PChain->StdErr))
				Core::RaiseError(trim($this->PChain->StdErr), E_ERROR);
			elseif (!$this->OutFilePath)
				$retval = $this->PChain->StdOut;
		    else 
		        $retval = $result;
		        
		    // Reset some stuff for a fresh new chain
			$this->Init();
			
			return $retval;
			
        }       
        
        public function ConvertRaw($options)
        {
            $this->PChain->AddLink("{$this->ConvertPath} {$options}");
        }
        
		/**
         * @var string $options Options command line
         * @return Resulting image binary string.
         */
		public function Convert($options)
        {
        	$size = $this->OutSize ? "-size {$this->OutSize}" : "";
        	$this->ConvertRaw("- {$options} {$size} {$this->OutputFormat}:-");
        }
        
        public function CompositeRaw($options)
        {
            $this->PChain->AddLink("{$this->CompositePath} {$options}");
        }
        
        /**
         * @var string $options Options command line
         * @return Resulting image binary string.
         */
		public function Composite($options)
        {
        	$size = $this->OutSize ? "-size {$this->OutSize}" : "";
        	
        	$this->CompositeRaw("- {$options} {$size} {$this->OutputFormat}:-");
        }
        
        /**
         * Identify image
         *
         * @param string $options
         */
        public function Identify($options)
        {
            $command_line = "{$this->IdentifyPath} {$options}";
            $this->PChain->AddLink($command_line);
            return $this->Process();
        }
	}

?>