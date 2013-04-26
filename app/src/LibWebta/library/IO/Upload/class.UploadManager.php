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
     * @package    IO
     * @subpackage Upload
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	Core::Load("IO/Basic");
	
	/**
     * @name UploadManager
     * @category   LibWebta
     * @package    IO
     * @subpackage Upload
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class UploadManager extends Core 
	{
		/**
		 * Minimum file size to upload (in Kb)
		 *
		 */
		const	MIN_FILE_SIZE = 0; 
		
		/**
		 * Maximum file size to upload (in Kb)
		 *
		 */
		const	MAX_FILE_SIZE = 1000;
		
		
		/**
		* Available types to upload
		* @var array
		* @access private 
		*/
		private $ValidMIMETypes;
			
		/**
		* Allowed files extensions to upload
		* @var array
		* @access private 
		*/
		private $ValidExtensions;

		/**
		* Disallowed files extensions to upload
		* @var array
		* @access private 
		*/
		private $InvalidExtensions;
		
		/**
		 * Upload file target directory
		 *
		 * @var string
		 */
		private $DestinationDir;
		
		/**
		* Max file size
		 *
		 * @var string
		*/
		private $MaxFileSize;
		
		/**
		* Min file size
		 *
		 * @var string
		*/
		private $MinFileSize;
		
		/**
		 * Uploaded file
		 *
		 * @var array
		 */
		private $File;
		
		/**
		 * File extension
		 *
		 * @var string
		 */
		public $FileExtension;
		
		public $MimeType;
		
		
		/**
		 * Constructor
		 * @ignore 
		 */
		public function __construct()
		{
			parent::__construct();
			
			// Default MIMEs
			$this->SetValidMIMETypes(array("*"));
			
			// Default extensions
			$this->SetValidExtensions(array("*"));
			$this->SetInvalidExtensions(array("php", "pl", "exe", "js", "com", "bat", "sql", "php4", "php5", "cgi", "php3"));
		}
		
		
		/**
		 * Set destination to uploaded files
		 *
		 * @param string $path
		 * @return bool success
		 */
		public function SetDestinationDir($path)
		{
			$dir = dirname($path);
			if (@is_writable($dir))
			{
    			$this->Destination = $path;
	   		    return true;
			}
			else 
			    Core::RaiseError("Directory {$dir} does not exist or not writable.", E_ERROR);
		}
		

		/**
		* Generate path
		* @access public
		* @param string $path Start Destination
		* @param string $seed 
		* @param int $depth
		* @return string
		*/
		public static function BuildDir($path, $seed, $depth = 2)
		{
			if (substr($path, -1) == "/")
				$path = substr($path, 0, -1);
		
			$crc = md5($seed);

			for ($i = 1; $i <= $depth; $i++)
				$path .= "/".substr($crc, $i*2-2, 2);
		
		    if (!file_exists($path))
			{
				// Make folder is not exists
				$res = @mkdir($path, 0777, true);
				$res &= @chmod($path, 0777);
				
				if (!$res)
				    Core::RaiseError("Cannot create or chmod '{$path}' directory.", E_ERROR);
			}
				
			return $path;	
		}
		
		/**
		 * Return file size
		 *
		 * @return integer
		 */
		public function GetFileSize()
		{
			return $this->File["size"];
		}
		
		
		
		/**
		 * Upload file from URL
		 *
		 * @param string $url
		 * @return bool
		 * @todo Pass custom headers, like User-Agent
		 */
		public function UploadFromURL($url, $headers = array())
		{
			$urlinfo = parse_url($url);
			
			$file = array("name" => $this->NormalizeFileName(basename($url)));
			// Open socket connection
			$sock = @fsockopen($urlinfo['host'], ($urlinfo["port"]) ? $urlinfo["port"] : 80, $errno, $errstr, 10);
			@stream_set_blocking($sock, 1);
			
			// If cannot open socket connection raise warning and return false
			if (!$sock)
			{
				$this->RaiseWarning(_("Failed to copy a file. Cannot connect to " . $urlinfo['host'] . "."), false);
				return false;
			}
			else 
			{
				if (substr($urlinfo['path'], 0, 1) != '/')
				    $urlinfo['path'] = "/{$urlinfo['path']}";
			    
			    // Define request
				$request = "GET ".$urlinfo['path'].($urlinfo['query'] ? "?{$urlinfo['query']}" : "")." HTTP/1.1\r\n";
				
				if (count($headers) > 0 && is_array($headers))
				    $request .= implode("\r\n", $headers)."\r\n";
				    
   				$request .= "Host: {$urlinfo['host']}\r\n";
  				$request .= "Connection: Close\r\n\r\n";
			
  				// Send request
  				@fwrite($sock, $request);
  				
  				$headers = "";
  				while ($str != "\r\n")
  				{
  				    $str = @fgets($sock, 2048);
  				    $headers .= $str;
  				}
  				
  				if (stristr($headers, "200 OK"))
				{  				
    				while (!feof($sock) && !$meta['eof'])
    				{
				        @file_put_contents($this->Destination, @fgets($sock, 2048), FILE_APPEND);
				        $meta = stream_get_meta_data($sock);
    				}
    				
    				// Generate real file info
    				$this->File = array("name" => basename($url), 
    									"type" => IOTool::GetFileMimeType($this->Destination),
    									"size" => @filesize($this->Destination)
    								   );				   
    				// Validate real file info		   
    				if ($this->Validate())
    				{
    					
    				    if (file_exists($this->Destination))
    				        return true;
    				    else 
    				        Core::RaiseError(_("Cannot write file."), E_ERROR);
    				}
    				
    				@unlink($this->Destination);
    				return false;
				}
				else 
				{
				    $tmp = split("\n", $headers);
				    $error = trim($tmp[0]);
				    Core::RaiseWarning($error);
				    return false;
				}

				@fclose($sock);			
			}
		}
		
		/**
		 * Try copy file from $source to $this->Destination
		 *
		 * @param string $source
		 * @return bool
		 */
		public function CopyFile($source)
		{
		    // Try to copy
			if (!@copy($source, $this->Destination))
			{			    
			    $this->RaiseWarning(_("Failed to copy a file. Please check filesystem access permissions."), false);
				return false;
			}
			else 
			{
				// Generate real file info
				$this->File = array("name" => basename($source), 
									"type" => IOTool::GetFileMimeType($this->Destination),
									"size" => filesize($this->Destination)
								   );
								   
				// Validate real file info		   
				if ($this->Validate())
				{
					return true;
				}
				else 
				{
					// If Validate returned false remove file and return false
					@unlink($this->Destination);
					return false;
				}
			}
		}
		
		/**
		 * Upload file
		 *
		 * @param array $file
		 * @return bool success
		 */
		public function Upload($file)
		{
			$this->File = $file;
			$this->MimeType = $this->File['type'];
			if ($this->Validate())
			{
				if (!@move_uploaded_file($file["tmp_name"], $this->Destination))
				{
					$this->RaiseWarning(_("Failed to copy a file. Please check filesystem access permissions."), false);
					return false;
				}
				
				// File successfully uploaded
				@chmod($this->Destination, 0777);
				@unlink($file["tmp_name"]);
				
				return true;
			}
			return false;
		}
		
		
		
		/**
		 * Set allowed MIME types
		 *
		 * @param array or string $mimes
		 */
		public function SetValidMIMETypes(array $mimes)
		{
			$this->ValidMIMETypes = $mimes;
		}
		
		
		
		/**
		 * Set allowed extension
		 *
		 * @param array or string $extensions
		 */
		public function SetValidExtensions(array $extensions)
		{
			$extensions = array_map('strtolower', $extensions);
			$this->ValidExtensions = $extensions;
		}
		
		/**
		 * Set denied extension
		 *
		 * @param array or string $extensions
		 */
		public function SetInvalidExtensions(array $extensions)
		{
			$this->InValidExtensions = $extensions;
		}
		
		/**
		 * Check availability of MIME type
		 *
		 * @param string $mime
		 * @return bool 
		 */
		private function CheckMIMEType()
		{
			return in_array($this->File['type'], $this->ValidMIMETypes) || in_array("*", $this->ValidMIMETypes);
		}
		
		/**
		 * Deprecated
		 *
		 * @return unknown
		 */
		private function CheckMIME()
		{
			return $this->CheckMIMEType();
		}
		
		
		/**
		 * Check availability of file extension
		 *
		 * @param string $ext
		 * @return bool
		 */
		private function CheckExtension()
		{
			$pi = pathinfo($this->File['name']);
			$this->FileExtension = $pi['extension'];
			return in_array(strtolower($pi['extension']), $this->ValidExtensions) || in_array("*", $this->ValidExtensions);
		}

		/**
		 * Check invalid extensions of file
		 *
		 * @param string $ext
		 * @return bool
		 */
		private function CheckInvalidExtension()
		{
			$pi = pathinfo($this->File['name']);
			return !in_array(strtolower($pi['extension']), $this->InValidExtensions);
		}
		
		/**
		 * Check file size
		 *
		 * @param int $size
		 * @return bool
		 */
		private function CheckSize()
		{
			$size = $this->File['size'] / 1024;
			$max = ($this->MaxFileSize) ? $this->MaxFileSize : self::MAX_FILE_SIZE;
			$min = ($this->MinFileSize) ? $this->MinFileSize : self::MIN_FILE_SIZE;
			return (($size <= $max) && ($size >= $min));
		}
		
		
		/**
		 * Validate upload file parameters
		 *
		 * @param array $file
		 * @return bool
		 */
		public function Validate()
		{
			// Validate all needed limits
			$valid["Name"] 		= (integer)(bool)$this->File["name"];
			$valid["Extension"] = (integer)(bool)($this->CheckExtension() && $this->CheckInvalidExtension());
			$valid["MIMEType"] = (integer)(bool)$this->CheckMIME();
			$valid["Filesize"] 	= (integer)$this->CheckSize();
			$valid["Error"] 	= (integer)(bool)!$this->File["error"];
			
			$max = ($this->MaxFileSize) ? $this->MaxFileSize : self::MAX_FILE_SIZE;
			
			if (array_sum($valid) != count($valid))
			{
				if (!$valid["Name"])
					$this->RaiseWarning(_("Name required"), false);
				if (!$valid["Error"])
					$this->RaiseWarning(_("Failed to upload a file. ({$this->File['error']})"), false);
				if (!$valid["Extension"])
				{
					$extlist = implode(", ", $this->ValidExtensions);
					$this->RaiseWarning(_("File extension ({$this->FileExtension}) is not in allowed list ({$extlist})"), false);
				}
				if (!$valid["MIMEType"])
				{
					$mimelist = implode(", ", $this->ValidMIMETypes);
					$this->RaiseWarning(_("File MIME type ({$this->File['type']}) is not in allowed list ({$mimelist})"), false);
				}
				if (!$valid["Filesize"])
					$this->RaiseWarning(_("File size exceeded allowed limit of {$max} KB"), false);
				return false;
			}			
			return true;
		}
		

		/**
		 * Set Minimum file size
		 *
		 * @param int $size
		 */
		public function SetMinFileSize($size)
		{
			$this->MinFileSize = $size;
		}
		
		
		
		/**
		 * Set Maximum file size
		 *
		 * @param int $size
		 */
		public function SetMaxFileSize($size)
		{
			$this->MaxFileSize = $size;
		}
		

		/**
		 * Remove non-ASCII symbols from file name
		 *
		 * @param string $filename
		 */
		public function NormalizeFileName($filename)
		{
			return preg_replace("/[^A-Za-z0-9_,@.-]+/si", "_", preg_replace("/[.]+/", ".", $filename));
		}
		
		/**
		* Set File to upload
		*
		* @param string filename
		*/
		public function SetFile($file)
		{
			$this->File = $file;
		}
		
	}
?>