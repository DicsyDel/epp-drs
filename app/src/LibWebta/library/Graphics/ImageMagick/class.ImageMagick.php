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
     * @name ImageMagick
     * @category   LibWebta
     * @package    Graphics
     * @subpackage ImageMagick
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @deprecated This class is old and very dumb, use ImageMagickLite instead
     */
	class ImageMagick extends Core   
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
		 * Minimum IM version (major.minor) before we raise a warning
		 *
		 * @var string
		 * @access public
		 */
		public $MinIMVersion;
		
			
		/**
		 *  Internal buffer
		 * @access protected
		 * @var array Internal buffers
		 */
		protected $Buffers;
		
		/**
		*	Current Buffer (index)
		* @var integer
		* @access protected
		*/
		protected $CurrentBuffer;
		
		
		/**
		 * ImageMagic Constructor
		 *
		 * @param string $bin_path Path to ImageMagick binaries
		 */
		function __construct($bin_path = null)
		{
			parent::__construct();
			
			// Default values
			$this->BinPath 		= is_null($bin_path) ? self::DEFAULT_BIN_PATH : $bin_path;
			$this->MinIMVersion = 62;
		}
		
		
		/**
		 * Check needed environment requirements
		 * @access public
		 */
		public function CheckEnv()
		{			
			// Check IM existance
			// I would use is_executable(), but its shaky.
			$cv = $this->BinPath . "/convert";
			if (!is_readable($cv))
			{
				$this->RaiseError(sprintf(_("ImageMagick convert binary '%s' does not exist"),$cv));
			}
			
			// Check either IM version higher or equal than MinIMVersion
			// Version: ImageMagick 6.2.5 04/25/06 Q16 http://www.imagemagick.org
			$version = $this->ExecuteRoutine("convert", "-version", false);
			if(preg_match("/ImageMagick\s+(\d)\.(\d)/", $version, $m))
			{
				$curver = intval($m[1].$m[2]);
				
				if ($this->MinIMVersion > $curver)
				{
					$this->RaiseWarning(sprintf(_("ImageMagick version '%s' installed. %s or higher required."), $curver, $this->MinIMVersion));
				}
			}
		}
		
	/**
		 * Load image file
		 *
		 * @param string $image_path Full path to image
		 * @param integer $buffer_number - Buffer index
		 * @return bool
		 */
		public function LoadImage($image_path, $buffer_number = null)
		{
			$this->SaveToBuffer(@file_get_contents($image_path), $buffer_number);
			
			if ($this->LoadFromBuffer($buffer_number))
			{
				$this->ImageType = $this->GetType();
				
				if ($this->ImageType)
					return true;
			}
			else
				Core::RaiseWarning(_("Cannot read image from file:image_path"));
				
			return false;
		}
		
		
		/**
		* Load image from buffer
		* @param integer $buffer_number - Buffer index
		* @return string Buffer
		*/
		public function LoadFromBuffer($buffer_number = null)
		{
			$index = ($buffer_number != null) ? $buffer_number : $this->GetBufferIndex();
			return $this->Buffers[$index];
		}
		
		/**
		* Set Buffer content
		* @param string Buffer content
		* @param string Buffer index
		*/
		public function SaveToBuffer($buffer_content, $buffer_number = null)
		{
			$index = ($buffer_number != null) ? $buffer_number : $this->GetBufferIndex();
			$this->Buffers[$index] = $buffer_content;
		}
		
		/**
		* Get Current buffer index
		* @param integer Buffer Index
		*/
		public function SetBufferIndex($index)
		{
			$this->CurrentBuffer = $index;
		}
		
		/**
		* Get Current buffer index
		* @return integer
		*/
		public function GetBufferIndex()
		{
			return ($this->CurrentBuffer != null) ? $this->CurrentBuffer : 0;
		}
		
		/**
		* Clear Buffer
		* @param integer Buffer index
		* @access protected
		*/
		protected function ClearBuffer($buffer_number = null)
		{
			$this->SaveToBuffer(null, $buffer_number);
		}
		
		/**
		* Replace data from buffer #1 to buffer #2
		* @param integer Buffer 1 index
		* @param integer Buffer 2 index
		*/
		public function BufferCopy($index1, $index2)
		{
			$this->SaveToBuffer($this->LoadFromBuffer($index1), $index2);
		}
		
		/**
		 * Execute command using PROC function
		 *
		 * @param string $binaryname - Name of bunary program
		 * @param string $args - commandline parametrs for binary program
		 * @param bool $to_buffer - Write result to buffer
		 * @return bool
		 */
		private function ExecuteRoutine($binaryname, $args, $to_buffer = true)
		{
			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
				2 => array("pipe", "w")   // stderr is a pipe that the child will write to
			);
			
			
			//
			// maintain buffer 
			//
			$args = preg_replace("/\%stdin[0-9]*\%/i", '-', $args);
			$args = preg_replace("/\%stdout[0-9]*\%/i", "{$this->ImageType}:-", $args);
			
			preg_match("/\%stdin([0-9]*)\%/i", $args, $match);
			$in_buffer_index = $match[1] ? $match[1] : $this->GetBufferIndex();

			preg_match("/\%stdout([0-9]*)\%/i", $args, $match);
			$out_buffer_index = $match[1] ? $match[1] : $this->GetBufferIndex();

			
			$pipes = null;
			
			$exec_str = "{$this->BinPath}/{$binaryname} {$args}";
			
            Log::Log("ImageMagick::Before Proc Open. Execute: {$exec_str}", LOG_LEVEL_DEBUG);
			
			$process = @proc_open($exec_str, $descriptorspec, $pipes);
			$stdout = "";
			$stderr = "";
			
			if (is_resource($process)) 
			{
				$buffer = $this->LoadFromBuffer($in_buffer_index);
				
				// Write image to stdin
				try
				{
					if (!empty($buffer)) 
						fwrite($pipes[0], $buffer);
					fclose($pipes[0]);
					// try to read the output
					while (!feof($pipes[1])) 
					{
						$data = fread($pipes[1], 1024);
						
						if (strlen($data) == 0) 
							break;
							
						$stdout .= $data;
					}
					
					$data = null;
					unset($data);
					
					@fclose($pipes[1]);
					
					//
					// Read the error message from stderr
					//
					$stderr = '';
					while (!feof($pipes[2])) 
					{
						$data = fread($pipes[2], 1024);
						
						if (strlen($data) == 0) 
							break;
						$stderr .= $data;
					}
					@fclose($pipes[2]);
					
					//
					// Close the proccess
					// 	
					if (@proc_close($process) == 0)
					{
						if ($to_buffer)
						{	
							$this->ClearBuffer();
							$this->SaveToBuffer($stdout, $out_buffer_index);
							
							$stdout = null;
							unset($stdout);
							
							return true;
						}
						else
							return $stdout;
					}
					else 
					{
						echo $stderr;
						Core::RaiseError($stderr);
					}
				} 
				catch (exception $ex)
				{
					 throw new ApplicationException($ex->__toString(), 0);
				}
			} 
			else
				Core::RaiseError(_("Unable to fork {$binaryname}"));
			
			return false;
		}
		
		/**
		 * Execute identify binary with a $str command line string
		 *
		 * @param string $str command line string
		 * @return array Raw shell output
		 */
		public function Identify($str)
		{
			return $this->ExecuteRoutine("identify", $str, false);
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