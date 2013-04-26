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
     * @package    Data
     * @subpackage Text
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	/**
	 * Load HTMLParser
	 */
	Core::Load("Data/Text/HTMLParser");

	/**
	 * Load ShellFactory
	 */	
	Core::Load("System/Independent/Shell/ShellFactory");
	
	
    /**
     * @name DiffTool
     * @category   LibWebta
     * @package    Data
     * @subpackage Text
     * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	
	class DiffTool extends Core
	{
		/**
		 * Default path to diff binaries
		 *
		 */
		const DEFAULT_BIN_PATH = "/usr/bin";
		
		/**
		 * Path to diff binaries
		 *
		 * @var string
		 * @access public
		 */
		public $BinPath;
		
		
		/**
		 * Diff Tool Constructor.
		 * Makes differences and applies patches to string
		 * 
		 * @param string $bin_path Path to bin directory
		 */
		function __construct($bin_path = null)
		{
			$this->BinPath 	= is_null($bin_path) ? self::DEFAULT_BIN_PATH : $bin_path;
			
			$this->Statements = array("/^> (.*?)$/m");
			$this->Replacements = array("> <span style='color:red'>\\1</span>");
		}
		
		/**
		 * Find differences between two strings
		 * 
		 * @param string $string_old Old string
		 * @param string $string_new New string
		 * @return string Patch string (.diff)
		 * @access public
		 */
		public function Diff($string_old, $string_new) 
		{
			$tmp_file = tempnam(md5(uniqid(rand(), TRUE)), '');
			
			$handle = @fopen($tmp_file, "w");
			@fwrite($handle, $string_new . "\n");
			@fclose($handle);
			@chmod($tmp_file, 0777);
			
			$result = $this->Execute("{$this->BinPath}/diff - {$tmp_file}", $string_old . "\n");

			@unlink($tmp_file);
			return $result;
		}
		
		/**
		 * Apply a diff file (patch) to an original
		 * 
		 * @param string $string String to be patched
		 * @param string $patch Diff text
		 * @return string Patched string
		 * @access public
		 */
		public function Patch($string, $patch) 
		{
			$tmp_file_old = tempnam(md5(uniqid(rand(), TRUE)), '');
			$tmp_file_out = tempnam(md5(uniqid(rand(), TRUE)), '');
			
			$handle = @fopen($tmp_file_old, "w");
			@fwrite($handle, $string . "\n");
			@fclose($handle);
			@chmod($tmp_file_old, 0755);
			@chmod($tmp_file_out, 0777);

			$this->Execute("{$this->BinPath}/patch -i - -o '{$tmp_file_out}' '{$tmp_file_old}'", $patch);
			
			$result = file_get_contents($tmp_file_out);

			@unlink($tmp_file_old);
			@unlink($tmp_file_out);
			return $result;
		}
		
		
		/**
		 * Set patterns for formatting new content by using GetHighlitedDiff
		 * method
		 * 
		 * @param array $statements Statements to be replaced
		 * @param array $replacements Replacement for statement
		 * @access public
		 * <pre>
		 * $diff->SetReplacementPatterns(
		 * 	array	("/^> (.*?)$/m", "/((< (.*?))+)\n\-\-\-\n>/ms"),
		 * 	array	("> <div class='new'>\\1</div>", "\\1\n---\n> <div
		 * class='old'>\\3</div>")
		 * );
		 * </pre>
		 */
		public function SetReplacementPatterns(array $statements, array $replacements) 
		{
			if (count($statements) != count($replacements))
				return;
				
			$this->Statements = $statements;
			$this->Replacements = $replacements;
		}
		
		
		/**
		 * Find differences between multiline strings and return formatted new
		 * string
		 * 
		 * @param string $string_old Old string
		 * @param string $string_new New string
		 * @return string New formatted string
		 * @uses HTMLParser HTML Parser method StripTags
		 * @access public
		 */
		public function GetHighlitedDiff($string_old, $string_new) 
		{
			$string_new = HTMLParser::StripTags($string_new);
			$string_old = HTMLParser::StripTags($string_old);
			
			$patch = $this->Diff($string_old, $string_new);

			if ($patch)
			{
				$patch = preg_replace($this->Statements, $this->Replacements, $patch);
				$string_new = $this->Patch($string_old, $patch);
			}

			return $string_new;
		}
		
		
		/**
		 * Execute some command and supply stdin if needed
		 * 
		 * @param string $string Command string
		 * @param string $stdin Standard input
		 * @return string Execution result
		 * @access private
		 */
		private function Execute($string, $stdin = "")
		{
			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
				2 => array("pipe", "w")   // stderr is a pipe that the child will write to
			);			
			
			$pipes = null;
			$process = proc_open($string, $descriptorspec, $pipes);
			
			if (is_resource($process)) 
			{
				if ($stdin)
					@fwrite($pipes[0], $stdin);
				@fclose($pipes[0]);
				
				// try to read the output
				$stdout = '';
				while (!feof($pipes[1])) 
				{
					$data = @fread($pipes[1], 1024);

					if (strlen($data) == 0) 
						break;
						
					$stdout .= $data;
				}

				$data = null;
				unset($data);
				
				@fclose($pipes[1]);

				// reads the error message from stderr
				$stderr = '';
				while (!feof($pipes[2])) 
				{
					$data = @fread($pipes[2], 1024);
					
					if (strlen($data) == 0) break;
					$stderr .= $data;
				}
				@fclose($pipes[2]);

				// Close proccess. If all successfully write data to bufer and return true. Elese Throw Warning
				proc_close($process);
				if (!$stderr && $stdout)
					return $stdout;
			} 
		}
	}
?>