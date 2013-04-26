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
     * @subpackage Archive
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
     * @name ZipArchiveEx
     * @category   LibWebta
     * @package    IO
     * @subpackage Archive
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class ZipArchiveEx extends Core 
	{
		
		const MaxFilesInArchive = 5;
		
		const DefaultArchiveName = "package";
		
		/**
		 * End of Central directory record
		 */
		const EndOfCentralDirectory = "\x50\x4b\x05\x06\x00\x00\x00\x00";
		
		
		/**
		 * Number of maximum allowed files in archive  
		 * @var integer 
		 * @access private
		 */
		private $MaxFilesInArchive;
		
		/**
		 * Array of file paths
		 * @var array
		 * @access private
		 */
		private $Files;

		/**
		 * Central directory
		 * @var string
		 * @access private
		 */
		private $CentralDirectory;
		

		/**
		 * Directory offset in archive
		 * @var string
		 * @access private
		 */
		private $OldOffset;


		/**
		 * Compressed data
		 * @var string
		 * @access private
		 */
		private $CompressedData;		
	
	
	
		/**
		 * ZipArchive constructor
		 */	
		public function __construct()
		{
			parent::__construct();
			
			$this->MaxFilesInArchive = self::MaxFilesInArchive;
			$this->Files = array();
			$this->CompressedData = array();
			$this->CentralDirectory = array();
			$this->OldOffset = 0;
		}
		
		
		
		/**
		 * Set Max files in archive
		 * @param integer $number
		 */
		public function SetMaxFilesInArchive($number)
		{
			$this->MaxFilesInArchive = (integer)$number;
		}
		
		
		
		/**
		 * Check Max Files in archive
		 * @return bool
		 */
		private function CheckFilesCount()
		{
			return ($this->GetFilesCount() <= $this->GetMaxFilesInArchive());
		}
		
		
		
		/**
		 * Get Max fils in archive
		 * @return integer
		 */
		public function GetMaxFilesInArchive()
		{
			return $this->MaxFilesInArchive;
		}
		
		
		
		/**
		 * Get Number of files to beeing archived
		 * @return integer
		 */
		public function GetFilesCount()
		{
			return count($this->Files);
		}
		
		
		
		/**
		 * Add file to archive
		 * @param $path (full path to file)
		 */
		public function AddFile($path)
		{
			$this->Files[] = $path;
		}
		
		
		
		/**
		 * Function to pack added files
		 * @return bool
		 */
		public function Pack()
		{
			if (!$this->GetFilesCount())
			{
				$this->RaiseWarning(_("No files added to archive"));
				return false;
			}
			
			if (!$this->CheckFilesCount())
			{
				$this->RaiseWarning(sprintf(_("Files in archive exceed max allowed [%s]"), $this->GetMaxFilesInArchive()));
				return false;
			}
			
			foreach($this->Files as $file)
			{
				$content = @file_get_contents($file);
				$name = basename($file);
				
				$this->AddArchiveFile($content, $name);
			}
			
			return true;
		}
		
		
		

		/**
		 * Function to create the directory where the file(s) will be unzipped
		 *
		 * @param $DirectoryName string 
		 *
		 */
		
		private function AddArchiveDirectory($DirectoryName) 
		{
			$DirectoryName = str_replace("\\", "/", $DirectoryName);  
	
			$FeedArrayRow  = "\x50\x4b\x03\x04";
			$FeedArrayRow .= "\x0a\x00";
			$FeedArrayRow .= "\x00\x00";
			$FeedArrayRow .= "\x00\x00";
			$FeedArrayRow .= "\x00\x00\x00\x00";
	
			$FeedArrayRow .= pack("V",0);
			$FeedArrayRow .= pack("V",0);
			$FeedArrayRow .= pack("V",0);
			$FeedArrayRow .= pack("v", strlen($DirectoryName) );
			$FeedArrayRow .= pack("v", 0 );
			$FeedArrayRow .= $DirectoryName;
	
			$FeedArrayRow .= pack("V",0);
			$FeedArrayRow .= pack("V",0);
			$FeedArrayRow .= pack("V",0);
	
			$this->CompressedData[] = $FeedArrayRow;
			
			$NewOffset = strlen(implode("", $this->CompressedData));
	
			$AddCentralRecord = "\x50\x4b\x01\x02";
			$AddCentralRecord .="\x00\x00";
			$AddCentralRecord .="\x0a\x00";
			$AddCentralRecord .="\x00\x00";
			$AddCentralRecord .="\x00\x00";
			$AddCentralRecord .="\x00\x00\x00\x00";
			$AddCentralRecord .= pack("V",0);
			$AddCentralRecord .= pack("V",0);
			$AddCentralRecord .= pack("V",0);
			$AddCentralRecord .= pack("v", strlen($DirectoryName) );
			$AddCentralRecord .= pack("v", 0 );
			$AddCentralRecord .= pack("v", 0 ); 
			$AddCentralRecord .= pack("v", 0 ); 
			$AddCentralRecord .= pack("v", 0 ); 
			// $ext = "\x00\x00\x10\x00";
			// $ext = "\xff\xff\xff\xff";  
			$AddCentralRecord .= pack("V", 16 ); 
	
			$AddCentralRecord .= pack("V", $this->OldOffset ); 
			$this->OldOffset = $NewOffset;
	
			$AddCentralRecord .= $DirectoryName;
	
			$this->CentralDirectory[] = $AddCentralRecord;
		}	 
		
		
		
		
		
		
		/**
		 * Function to add file(s) to the specified directory in the archive 
		 *
		 * @param $DirectoryName string 
		 *
		 */
		
		private function AddArchiveFile($data, $DirectoryName)
		{
	 
			$DirectoryName = str_replace("\\", "/", $DirectoryName);  
		
			$FeedArrayRow  = "\x50\x4b\x03\x04";
			$FeedArrayRow .= "\x14\x00";    
			$FeedArrayRow .= "\x00\x00";    
			$FeedArrayRow .= "\x08\x00";    
			$FeedArrayRow .= "\x00\x00\x00\x00"; 
	
			$UncompressedLength = strlen($data);  
			$Compression = crc32($data);  
			$gzCompressedData = gzcompress($data);  
			$gzCompressedData = substr( substr($gzCompressedData, 0, strlen($gzCompressedData) - 4), 2); 
			$CompressedLength = strlen($gzCompressedData);  
			$FeedArrayRow .= pack("V",$Compression); 
			$FeedArrayRow .= pack("V",$CompressedLength); 
			$FeedArrayRow .= pack("V",$UncompressedLength); 
			$FeedArrayRow .= pack("v", strlen($DirectoryName) ); 
			$FeedArrayRow .= pack("v", 0 ); 
			$FeedArrayRow .= $DirectoryName;  
	
			$FeedArrayRow .= $gzCompressedData;  
	
			$FeedArrayRow .= pack("V",$Compression); 
			$FeedArrayRow .= pack("V",$CompressedLength); 
			$FeedArrayRow .= pack("V",$UncompressedLength); 
	
			$this->CompressedData[] = $FeedArrayRow;
	
			$NewOffset = strlen(implode("", $this->CompressedData));
	
			$AddCentralRecord  = "\x50\x4b\x01\x02";
			$AddCentralRecord .= "\x00\x00";
			$AddCentralRecord .= "\x14\x00";
			$AddCentralRecord .= "\x00\x00";
			$AddCentralRecord .= "\x08\x00";
			$AddCentralRecord .= "\x00\x00\x00\x00"; 
			$AddCentralRecord .= pack("V",$Compression); 
			$AddCentralRecord .= pack("V",$CompressedLength); 
			$AddCentralRecord .= pack("V",$UncompressedLength); 
			$AddCentralRecord .= pack("v", strlen($DirectoryName) ); 
			$AddCentralRecord .= pack("v", 0 );
			$AddCentralRecord .= pack("v", 0 );
			$AddCentralRecord .= pack("v", 0 );
			$AddCentralRecord .= pack("v", 0 );
			$AddCentralRecord .= pack("V", 32 ); 
	
			$AddCentralRecord .= pack("V", $this->OldOffset ); 
			$this->OldOffset = $NewOffset;
	
			$AddCentralRecord .= $DirectoryName;  
	
			$this->CentralDirectory[] = $AddCentralRecord;  
		}
	
	
	
	
	
		/**
		 * Fucntion to return the zip file
		 *
		 * @return string (zipped data)
		 */
	
		public function GetArchive()
		{	
			$data = implode("", $this->CompressedData);  
			$controlDirectory = implode("", $this->CentralDirectory);  
	
			return   
				$data.  
				$controlDirectory.  
				self::EndOfCentralDirectory.  
				pack("v", sizeof($this->CentralDirectory)).     
				pack("v", sizeof($this->CentralDirectory)).     
				pack("V", strlen($controlDirectory)).             
				pack("V", strlen($data)).                
				"\x00\x00";                             
		}
		
		
		/**
		 * Return attached zip archive
		 * @param string $ArchiveName - filename of archive
		 */
		public function GetArchiveInAttachment($ArchiveName = null)
		{
			$ArchiveName = $ArchiveName ? $ArchiveName : self::DefaultArchiveName;
			
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: Binary");
			
			header("Content-disposition: attachment; filename=\"{$ArchiveName}.zip\"");
			echo $this->GetArchive();
		}

	}
?>