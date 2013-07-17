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
     * @subpackage Basic
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
    
	/**
	 * IO Tool
	 * 
     * @name IOTool
     * @category   LibWebta
     * @package    IO
     * @subpackage Basic
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class IOTool extends Core 
	{
	
		public static function GetFileMimeType($path)
		{
		    if (file_exists($path))
		    {
                try
                {
    		        if (class_exists("finfo")) 
                    {
                    	// Probe magick database file
                    	$magic_db_path = dirname(__FILE__) . "/magic"; 
                    	
						if (!file_exists($magic_db_path))
							$magic_db_path = "/usr/share/file/magic";
                    	
						// Create fifo instance
                        $finfo = new finfo(FILEINFO_MIME, $magic_db_path);

                        
                        if ($finfo)
                            $retval = @$finfo->file($path);
                        else 
                            Core::RaiseError("Cannot open FIFO database. Tried {$magic_db_path}", E_ERROR);

                        return $retval;
                    }
                    elseif (function_exists("mime_content_type"))
                    {
                        return mime_content_type($path);
                    }
                    else 
                    {
                        Core::RaiseError("Cannot determine file mime type.", E_ERROR);
                        return "";
                    }
		        }
                catch (Exception $e)
                {
                    Core::RaiseError($e->getMessage(), E_ERROR);
                }
		    }
		    else
		    {
		        Core::RaiseWarning("File not found.");
		        return "";
		    }
		}
	    
	    /**
		 * Delete directory and its contents recursively. Foreva.
		 *
		 * @param string $path Directory path
		 * @static 
		 * @return bool Success
		 */
		public static function UnlinkRecursive($path)
		{
			$s = DIRECTORY_SEPARATOR;
			$dir_contents = @scandir($path);
			foreach ((array)$dir_contents as $item) 
			{
				if (@is_dir("{$path}{$s}{$item}") && $item != '.' && $item != '..') 
				{
            		self::UnlinkRecursive("{$path}{$s}{$item}/");
           		}
				elseif (@file_exists("{$path}{$s}{$item}") && $item != '.' && $item != '..')
               		@unlink("{$path}{$s}{$item}");
          	}
          	@rmdir($path);
          	
          	return (!file_exists($path));
		}
		
		/**
		 * Read file swith speed shaping
		 *
		 * @param string $filepath Path to file
		 * @param int $speed Download speed in bytes
		 * @static 
		 * @return bool
		 */
		public static function ReadFileShaped($filepath, $speed)
		{
			$size = @filesize($filepath);
			$chunksize = (int)$speed;
			
			return self::ReadFileChunked($filepath, $chunksize, true);
		}
		
		/**
		 * Read file chunked
		 *
		 * @param string $filepath Path to file
		 * @param int $chunksize Chunk size in bytes
		 * @static 
		 * @param bool $shaped Shaped
		 * @return bool
		 */
		public static function ReadFileChunked($filepath, $chunksize = 1048576, $shaped = false) 
		{  
			$handle = @fopen($filepath, 'rb');  
			if ($handle === false) 
				return false;  
			 
			while (!@feof($handle)) 
			{  
				echo @fread($handle, $chunksize);  
				@ob_flush();  
				@flush();  
				
				if ($shaped)
					sleep(1);
			}
			  
			$status = @fclose($handle); 
			 
			return $status;  
		 }  
	}
?>