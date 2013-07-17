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
     * @name ImageMagickEx
     * @category   LibWebta
     * @package    Graphics
     * @subpackage ImageMagick
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @deprecated This class is old and very dumb, use ImageMagickLite instead
     */
	class ImageMagickEx extends Core   
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
		public $BinPath;
		
		
			
		/**
		 * Buffers stack
		 * @access protected
		 * @var array
		 */
		protected $Buffers;
		
		/**
		* Current buffer index
		* @var integer
		* @access protected
		*/
		protected $CurrentBufferIndex;
		
		
		/**
		* Identify cache. 
		* We store identify results here to avoid repeating binary calls. 
		* @var integer
		* @access protected
		*/
		protected $IdentCache;
		
		
		/**
		 * ImageMagic Constructor
		 *
		 * @param string $bin_path Path to ImageMagick binaries
		 */
		function __construct($bin_path = null)
		{
			parent::__construct();
			
			// Default values
			$this->BinPath 	= is_null($bin_path) ? self::DEFAULT_BIN_PATH : $bin_path;
		}
		
		/**
		 * Desctructor
		 *
		 */
		function __destruct()
		{
			
		}
		
		
		
		/**
		 * Get image type
		 *
		 * @return string
		 */
		public function GetImageType()
		{
			$result = $this->Identify("-");
			
			if (preg_match("/\s(\w+)\s/i", $result, $m))
				return strtolower($m[1]);
			else 
				false;
			
		}
		
		/**
		* Set image type
		*
		*/
		public function SetType($type)
		{
			$this->ImageType = $type;
		}
		
		
		
		
		
		/**
		 * Execute command using PROC function
		 *
		 * @param string $binaryname - Name of bunary program
		 * @param string $args - commandline parametrs for binary program
		 * @param bool $to_buffer - Write result to buffer
		 * @return bool
		 */
		private function ExecuteRoutine($binaryname, $args)
		{
			
		}
		
		
		/**
		 * Execute identify binary with a $str command line string
		 *
		 * @param string $str command line string
		 * @return array Raw shell output
		 */
		public function Identify($str)
		{
			$this->IdentCache = $this->ExecuteRoutine("identify", $str, false);
			return ($this->IdentCache);
		}
		
		
		/**
		 * Execute convert binary with a $str command line string
		 *
		 * @param string $str command line string
		 * @return array Raw shell output
		 */
		public function Convert($str)
		{
			return $this->ExecuteRoutine("convert", $str);
		}
		
		
		/**
		 * Execute composite binary with a $str command line string
		 *
		 * @param string $str command line string
		 * @return array Raw shell output
		 */
		public function Composite($str)
		{
			return $this->ExecuteRoutine("composite", $str);
		}
		
	}

?>