<?php
	// +--------------------------------------------------------------------------+
	// | Automatic update service client                                          |
	// +--------------------------------------------------------------------------+
	// | Copyright (c) 2003-2009 Webta Labs.                                      |
	// +--------------------------------------------------------------------------+
	// | This program is protected by international copyright laws. Any           |
	// | use of this program is subject to the terms of the license               |
	// | agreement included as part of this distribution archive.                 |
	// | Any other uses are strictly prohibited without the written permission    |
	// | of "Webta Inc." and all other rights are reserved.                       |
	// | This notice may not be removed from this source code file.               |
	// | This source file is subject to version 1.0 of the license,               |
	// | that is bundled with this package in the file LICENSE.                   |
	// +--------------------------------------------------------------------------+
	// | Authors: Alex Kovalyov <alex@webta.net>                                  |
	// +--------------------------------------------------------------------------+
	
	Core::Load("IO/Basic/IOTool");
	Core::Load("System/Independent/Shell/ShellFactory");
        Core::Load("PE");

	/**
	 * Update data type structure
	 *
	 */
	class Update
	{
		public $IsAutoUpdatePossible;
		public $Notes;
		public $Requirements;
		public $DateReleased;
		public $Revision;
		public $FilesToUpdate;
		public $FoldersToAdd;
		public $FilesToAdd;
		public $FilesToDelete;
		public $FoldersToDelete;
		public $Chmods;
		public $Commands;
		public $Scripts;
		public $SQLQueries;
		public $ChangelogFixed;
		public $ChangelogAdded;
		public $EventHandler;
		public $LocalArchivePath;
		public $InterruptAfter;
		public $AutoScheduleNextUpdate;
		public $DoBackupDatabase;
		public $DoBackupTemplates;
	}

	class AutoUpdateClient extends Core
	{
		
		const TAR_CMD = "tar"; 
		
		const PHP_CMD = "php";
		
		/**
		 * List of URLs of auto-update services
		 *
		 * @var array
		 */
		public $ServiceURLs;
		
		/**
		 * A list of available updates
		 *
		 * @var array
		 */
		private $AllRevisions;
		
		/**
		 * Target Update object
		 *
		 * @var Update
		 */
		public $TargetUpdate;
		
		/**
		 * Temp directory with Update
		 *
		 * @var TempDir
		 */
		private $TempDir;
		
		/**
		 * Temp director for Update package extraction
		 *
		 * @var TempRoot
		 */
		public $TempRoot;
		
		/**
		 * License string used for validation during download
		 *
		 * @var string
		 */
		private $License;
		
		/**
		 * Local revision ID
		 *
		 * @var int
		 */
		public $LocalRevision;
		
		
		/**
		 * text report of performed updates
		 *
		 * @var string
		 */
		private $ReportRows;
		
		
		/**
		 * Path to tar binary
		 *
		 * @var unknown_type
		 */
		public $TarCmd;
		
		
		/**
		 * Report string
		 *
		 * @var string
		 */
		public $Report;
		
		/**
		 * @var string
		 */
		public $SendReportLater;
		
		/**
		 * APP Path
		 *
		 * @var string
		 */
		private $AppPath;
		
		/**
		 * Shell instance
		 *
		 * @var Shell
		 */
		private $Shell;
		
		/**
		 * ADODB Instance
		 *
		 * @var ADODBConnection
		 */
		private $DB;
		
		/**
		 * System user name
		 *
		 * @var string
		 */
		private $Uname;
		
		/**
		 * Clean temporary files and collect garbage
		 *
		 */
		public function Dispose()
		{
			$this->RaiseEvent("Cleaning temporary folders/files");
			try
			{
				// FIXME:
				IOTool::UnlinkRecursive($this->TempDir);
			} catch (Exception $ex)
			{
				throw new Exception("Failed to clean temporary files");
			}
		}
				
		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			$tmp_dir = ini_get("session.save_path") . "/" . uniqid("update-", true); 
			$this->TempDir = $tmp_dir;
			// Use path from config or default one
			
			$this->TarCmd = self::TAR_CMD;
			$this->PhpCmd = self::PHP_CMD;
			
			// Determine current user name
			$this->Uname = $_SERVER["USER"] ? $_SERVER["USER"] : "current";
			
			// Common objects
			$this->DB = Core::GetDBInstance(null, true);
			$this->Shell = ShellFactory::GetShellInstance();
		}
		
		/**
		 * Probe PHP binary
		 *
		 * @return string path to php binary
		 */
		public function ProbePHPBinary()
		{
			if (!$this->PhpCmd || !@is_executable($this->PhpCmd))
			{
				$ret = $this->Shell->QueryRaw("which php");
				if ($ret && file_exists($ret) && is_executable($ret))
				{
					$this->PhpCmd = $ret;
					$found = true;
				}
				else
				{
					$ret = $this->Shell->QueryRaw("whereis php");
					$paths = explode(" ", $ret);
					foreach ($paths as $path)
					{
						if ($path && @is_executable($path))
						{
							$this->PhpCmd = $path;
							$found = true;
							break;
						}
					}
				}
			}
			else
				$found = true;
			
			if (!$found)
				throw new Exception(sprintf("PHP binary not found or not executable by %s. Please update php binary path in Core Settings and make sure it is executable by %s", $this->Uname, $this->Uname));
				
			return $this->PhpCmd;
		}
		
		/**
		 * Destructor
		 *
		 */
		function __destruct()
		{
			//$this->Dispose();
		}
		
		/**
		 * Set a reference to an object to whom local events will be dispatched
		 *
		 * @param object $object
		 */
		public function SetEventHandler($object)
		{
			$this->EventHandler = $object;
		}
		
		/**
		 * Set app path
		 *
		 * @param object $object
		 */
		public function SetAppPath($path)
		{
			$this->AppPath = $path;
		}
		
		/**
		 * Add a new service url to the end of the list
		 *
		 * @param string $url
		 */
		public function AddService($url)
		{
			$this->ServiceURLs[] = $url;
		}
		
		/**
		 * Set temp directory for Update archive extraction
		 *
		 */
		public function SetTempDir($dir)
		{
			$this->TempRoot = $dir;
		}
		
		/**
		 * Set ProductID
		 *
		 */
		public function SetProductID($product_id)
		{
			$this->ProductID = $product_id;
		}
		
		/**
		 * Set license string for validation during download
		 *
		 * @param string $lic
		 */
		public function SetLicense($lic)
		{
			$this->License = $lic;
		}
		
		/**
		 * Set local revision
		 *
		 * @param int $revision
		 */
		public function SetLocalRevision($revision)
		{
			if (!is_array($this->AllRevisions))
				$this->ListRevisions();
			
			if (!in_array($revision, $this->AllRevisions))
				throw new Exception(sprintf("Unknown local version %s.", $revision));
			
			$this->LocalRevision = $revision;
		}
		
		/**
		 * Add a new service url to the end of the list
		 *
		 * @param string $url
		 * @return string
		 */
		public function FetchManifest($url)
		{
			if (count($this->ServiceURLs) <= 0)
				throw new Exception("No update services defined");
				
			foreach ($this->ServiceURLs as $service_url)
			{
				$manifest_url = "{$service_url}/{$url}";
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $manifest_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);				
				
				$xmlstr = curl_exec($ch);
				if ($xmlstr)
				{
					if ("200" == ($status = curl_getinfo($ch, CURLINFO_HTTP_CODE))) 
					{
						break;
					}
					else 
					{
						$xmlstr = false;
						Log::Log("Cannot fetch manifest. Http status: {$status}", E_USER_NOTICE);
					}
				}
				else
				{
					Log::Log("Cannot fetch manifest. " . curl_error($ch), E_USER_NOTICE);
				}
			}
			
			// No service alive
			if (!$xmlstr)
				throw new Exception(sprintf("All services failed while trying to retrieve %s", $url));
			else
				return $xmlstr;
		}
		
		
		public function GetUpdateObject($revision)
		{
			if (!$this->ProductID)
				throw new Exception("No productID defined");
			$xmlstr = $this->FetchManifest("/{$this->ProductID}/updates/{$revision}.xml");
			$retval = new Update();
			
			// Build an object of xml string
			$xml = new SimpleXMLElement($xmlstr, LIBXML_NOCDATA);
			$retval->IsAutoUpdatePossible = (bool)$xml["autoupdate"];
			$retval->DateReleased = (string)$xml["date"];
			$retval->Revision = (int)$xml["revision"];
			$retval->InterruptAfter = (int)$xml["interrupt_after"];
			$retval->AutoScheduleNextUpdate = (int)$xml["auto_shedule_next_update"];
			$retval->DoBackupDatabase = (int)$xml["do_backup_database"];
			$retval->DoBackupTemplates = (int)$xml["do_backup_templates"];
			$retval->SkipTarballDownload = (int)$xml["skip_tarball_download"]; 
			$retval->Notes = trim((string)$xml->notes);
			
			$send_report_later = (int)$xml["send_report_later"];
			if ($send_report_later)
				$this->SendReportLater = 1;
			
			if ($retval->InterruptAfter)
				$retval->Notes .= "<br/>This update requires application reload. The next update will be installed on next cronjob run automatically.";
						
			// changelog/fixed
			$retval->ChangelogFixed = array();
			foreach($xml->changelog->fixed as $item)
			{
				$retval->ChangelogFixed[] = trim((string)$item);
			}
			
			// changelog/added
			$retval->ChangelogAdded = array();
			foreach($xml->changelog->added as $item)
			{
				$retval->ChangelogAdded[] = trim((string)$item);
			}
			
			
			// delete/folder
			$retval->FoldersToDelete = array();
			foreach($xml->delete->folder as $item)
			{
				$retval->FoldersToDelete[] = (string)$item;
			}
			
			// delete/file
			$retval->FilesToDelete = array();
			foreach($xml->delete->file as $item)
			{
				$retval->FilesToDelete[] = (string)$item;
			}
			
			// add/folder
			$retval->FoldersToAdd = array();
			foreach($xml->add->folder as $item)
			{
				$retval->FoldersToAdd[] = (string)$item;
			}
			
			// add/file
			$retval->FilesToAdd = array();
			foreach($xml->add->file as $item)
			{
				$retval->FilesToAdd[] = (string)$item;
			}
			
			// update/file
			$retval->FilesToUpdate = array();
			foreach($xml->update->file as $item)
			{
				$retval->FilesToUpdate[] = (string)$item;
			}
			
			// chmods
			foreach($xml->xpath("//file[@chmod!='']|//folder[@chmod!='']") as $item)
			{
				$retval->Chmods[(string)$item] = (string)$item["chmod"];
			}
			
			// SQL
			$retval->SQLQueries = array();
			foreach($xml->sql->item as $item)
			{
				$retval->SQLQueries[] = (string)$item;
			}
			
			// Execute
			$retval->Commands = array();
			foreach($xml->execute->command as $item)
			{
				$retval->Commands[] = (string)$item;
			}
			$retval->Scripts = array();
			foreach($xml->execute->script as $item)
			{
				$retval->Scripts[] = (string)$item;
			}
			$retval->Requirements = array();
			
			// versioncompat value strings
			$cmp_strings = array(
			"gt" => ">", 
			"lt" => "<",
			"ge" => ">=",
			"le" => "<="
			);
			
			// requirements
			foreach($xml->requirements->php->class_exists as $item)
			{
				$retval->Requirements["class_exists"][] = array(
					"name" => (string)$item["name"],
					"message" => (string)$item["message"],
					"uri"	=> (string)$item["uri"],
					"mandatory" => (int)$item["mandatory"]
				);
			}
			foreach($xml->requirements->php->function_exists as $item)
			{
				$retval->Requirements["function_exists"][] = array(
					"name" => (string)$item["name"],
					"message" => (string)$item["message"],
					"uri"	=> (string)$item["uri"],
					"mandatory" => (int)$item["mandatory"]
				);
			}
			
			foreach($xml->requirements->php->expr as $item)
			{
				$retval->Requirements["expressions"][] = array(
					"expression" => (string)$item["value"],
					"message" => (string)$item["message"],
					"uri"	=> (string)$item["uri"],
					"mandatory" => (int)$item["mandatory"]
				);
			}
			
			$retval->Requirements["phpversion"] = (string)$xml->requirements->php->version;			
			return($retval);
		}
	
		/**
		 * Get a list of product Updates 
		 * @param string $product_id ProductID
		 */
		public function ListRevisions()
		{
			if (!$this->ProductID)
				throw new Exception("No productID defined");
			
			$retval = array();
			$xmlstr = $this->FetchManifest("{$this->ProductID}/releases.xml");
			$xml = new SimpleXMLElement($xmlstr);
			foreach ($xml->release as $release) 
			{
				$retval[] = (int)$release["revision"];
			}
			
			// Sort descending
			natsort($retval);
			array_reverse($retval);
			$this->AllRevisions = $retval;
			
			return ($retval);
		}
		
		/**
		 * Get a maximum revision
		 *
		 * @return int
		 */
		public function GetLatestRevision()
		{
			return (int)max($this->ListRevisions());
		}
		
		/**
		 * Get a list of all needed updates from $from_revision to $to_revision
		 * @var int $target_revision Target revision
		 * @return array
		 */
		public function ListHops($from_revision, $to_revision)
		{
			if (!is_array($this->AllRevisions))
				$this->ListRevisions();
				
			// Get a slice of array	
			$i=0; $offset=0; $len = 0;
			foreach ($this->AllRevisions as $rel)
			{
				if ($rel == $from_revision)
					$offset = $i;
				if ($rel > $from_revision)
					++$len;
				if ($rel == $to_revision)
					break;
				$i++;		
			}
			$retval = $this->AllRevisions;
			$retval = array_slice($retval, $offset+1, $len);
			return($retval);
		}
		
		/**
		 * Download update archive
		 *
		 */
		private function DownloadUpdate()
		{
			// Set URL and local out file
			$url = "download/{$this->ProductID}/update/{$this->TargetUpdate->Revision}.tar.gz";
			$this->TargetUpdate->LocalArchivePath = "{$this->TempDir}/{$this->TargetUpdate->Revision}.tgz";

			$this->RaiseEvent(sprintf("Downloading update from %s to %s", 
				$url, 
				$this->TargetUpdate->LocalArchivePath));
			
			/*
			* Download
			*/
			$ch = @curl_init();
			$outfile = @fopen($this->TargetUpdate->LocalArchivePath, 'wb');
		    @curl_setopt_array($ch, 
		    	array( 
		    			CURLOPT_FILE => $outfile,
		    			CURLOPT_BINARYTRANSFER => true,
		    			CURLOPT_HEADER => 0, 
		    			CURLOPT_TIMEOUT => 60*5, // Five minutes, sir!
		    			CURLOPT_FAILONERROR => true
		    		)
		    );

		    $headers = array(
		    	"X-Webta-Agent: EPPDRS3000",
		    	"X-CURLVER: ". implode(";", curl_version())
		    );
		    if ($this->License)
		    	$headers[] = "X-License-Id: {$this->License}";
		    	
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		    
			
		    // Try to download from all each registered 'service'
		    foreach ($this->ServiceURLs as $service_url)
			{
				curl_setopt($ch, CURLOPT_URL, "{$service_url}/{$url}");
				$this->RaiseEvent(sprintf("Downloading from '%s/%s'", $service_url, $url));
				$result = curl_exec($ch);
				if ($result === true)
					break;
			}
			
			// No service alive
			if (!$result)
				throw new Exception(sprintf("All services failed while trying to download update archive '%s'. %s", 
					$url,
					curl_error($ch)));
		    
		    @curl_close($ch);
		    @fclose($outfile);
		}
		
		/**
		 * Untar archive
		 *
		 * @return unknown
		 */
		private function ExtractUpdate()
		{
			chdir($this->TempDir);
			
			$cmd = "cd {$this->TempDir} && \"{$this->TarCmd}\" -xzf \"{$this->TempDir}/{$this->TargetUpdate->Revision}.tgz\"";
			$result = $this->Shell->ExecuteRaw($cmd);
			$this->RaiseEvent("Extracting update ('{$cmd}')");
			
			if (!$result)
				$this->RaiseEventAndThrow(sprintf("Failed to extract update package (%s)", $cmd));
			else
				chdir("{$this->TempDir}/app");
			return($result); 
		}
		
		
		/**
		 * Atomic update to a specific Update from a previous one
		 *
		 */
		protected function Update($revision)
		{
			// print memory_get_usage(true) . " " . memory_get_usage()." Before update to r$revision\n";
			
			if (!$this->LocalRevision)
				throw new Exception("No LocalRevision defined");
			
			// Create temp directory
			$this->TempDir = "{$this->TempRoot}/" . uniqid("update-", true);
			$mkresult = @mkdir($this->TempDir);
			if (!$mkresult)
			{
				$this->RaiseEventAndThrow(sprintf("Failed to create temporary directory %s", $this->TempDir));
			}		
				
			// Fetch a Update data and serialize to object
			$this->TargetUpdate = $this->GetUpdateObject($revision);
			
			// print memory_get_usage(true) . " " . memory_get_usage()." GetUpdateObject\n";
			
			// Check php path if php needed and throw exception if no executable php binary found
			if (count($this->TargetUpdate->Commands) > 0)
			{
				$raw_cmd = implode("", $this->TargetUpdate->Commands);
				if (stripos($raw_cmd, "%php_path%") !== false)
				{
					try
					{
						$php_path = $this->ProbePHPBinary();
					}
					catch(Exception $e)
					{
						$this->RaiseEvent($e->getMessage());
						throw new Exception($e->getMessage(), $e->getCode());
					}
					
					foreach ($this->TargetUpdate->Commands as &$cmd)
						$cmd = str_replace("%php_path%", $php_path, $cmd);
				}
			}
			
			if ($this->TargetUpdate->IsAutoUpdatePossible)
			{
				$this->RaiseEvent(sprintf("Begin SQL transaction."));
				// print memory_get_usage(true) . " " . memory_get_usage()." Begin SQL transaction.\n";				
				$this->DB->StartTrans();
			
				try
				{
					if (!$this->TargetUpdate->SkipTarballDownload)
					{
						// Download and extract Update
						// print memory_get_usage(true) . " " . memory_get_usage()." Before Download and extract Update.\n";						
						$this->DownloadUpdate($revision);
						// print memory_get_usage(true) . " " . memory_get_usage()." After Download and extract Update.\n";
						
						// Unpack archive
						// print memory_get_usage(true) . " " . memory_get_usage()." Before Unpack archive.\n";
						$this->ExtractUpdate($revision);
						// print memory_get_usage(true) . " " . memory_get_usage()." After Unpack archive.\n";
						// Now we are in {$this->TempDir}/app folder
					}
					
					// Perform update actions
					// print memory_get_usage(true) . " " . memory_get_usage()." Before Make backups.\n";
					$this->MakeBackups();
					// print memory_get_usage(true) . " " . memory_get_usage()." After Make backups.\n";
					
					$this->ExecuteSQLQueries();

					if (!$this->TargetUpdate->SkipTarballDownload)
					{
						// print memory_get_usage(true) . " " . memory_get_usage()." Before Add folders/files.\n";
						$this->AddFolders();
						$this->AddFiles();
						// print memory_get_usage(true) . " " . memory_get_usage()." After Add folders/files.\n";
					
						$this->UpdateFiles();
					
						$this->SetFSPermissions();
					}
				}
				catch (Exception $ex)
				{
					$this->RaiseEvent(sprintf("Rolling back SQL queries."));
					$this->DB->RollbackTrans();
					$this->RaiseEventAndThrow(sprintf("Update failed. %s", $ex->getMessage()));
				}
				
				$this->RaiseEvent(sprintf("Commiting SQL transaction."));
				$this->DB->CompleteTrans();
				
				//
				// �� ������ ��������� ������� ����� ������ ���������� ����� ��������� �� deadlocks.
				//
				
				$this->ExecuteCommands();
					
				if (!$this->TargetUpdate->SkipTarballDownload)
				{
					$this->ExecuteScripts();
				
					$this->DeleteFolders();
					$this->DeleteFiles();
				}
				
			}
			else 
			{
				$message = sprintf("It is not possible to automatically update to revision %s. Please perform manual update.", $revision);
				throw new Exception($message);
			}
			$this->Dispose();
			$this->SetLocalRevision($this->TargetUpdate->Revision);
			$this->RaiseEvent(sprintf("Update to version %d finished", $revision));
			
			// print memory_get_usage(true) . " " . memory_get_usage()." After update to r$revision\n";
		}
		
		
		private function SetFSPermissions()
		{
			// print "In SetFSPermissions \n";
			
			$this->RaiseEvent("Setting permissions");
			foreach((array)$this->TargetUpdate->Chmods as $path => $perm)
			{
				$this->RaiseEvent("chmod {$perm} {$this->AppPath}/{$path}");
				
				$result = chmod("{$this->AppPath}/{$path}", octdec($perm));
				if (!$result)
					$this->RaiseEvent(sprintf("Failed to set permission '%s' on path '%s'", $perm, $path));
			}
			
			// print "Leave SetFSPermissions\n";
		}
				
		/**
		 * Execute shell commands
		 * @return void
		 */
		private function ExecuteCommands()
		{
			$this->RaiseEvent("Executing commands");
			$MP = new ManagedProcess();
			
			foreach($this->TargetUpdate->Commands as $cmd)
			{
				$cmd = "cd {$this->AppPath} && {$cmd}";
				
				$this->RaiseEvent(sprintf("Executing command '%s'", $cmd));
				
				$MP->StdErr = null;
				$MP->StdOut = null;
				$MP->Execute($cmd);
				
				//$result = $this->Shell->ExecuteRaw($cmd);
				
				$this->RaiseEvent(sprintf("Result: STDOUT = '%s', STDERR= '%s'", $MP->StdOut, $MP->StdErr));
				
				//if (!$result)
				//	$this->RaiseEvent(sprintf("Failed to execute command '%s'", $cmd));
			}
		}
		
		/**
		 * Execute php scripts
		 * @return void
		 */
		private function ExecuteScripts()
		{
			$Mailer = Core::GetPHPSmartyMailerInstance(CONFIG::$EMAIL_DSN);
			$this->RaiseEvent("Executing PHP scripts");
			/* We must prevent update from stopping execution 
			/* if any of invoked scripts raise error
			*/
			foreach($this->TargetUpdate->Scripts as $path)
			{
				$this->RaiseEvent(sprintf("Executing script '%s'", $this->AppPath . "/{$path}"));
				
				try 
				{
					$result = include($this->AppPath . "/{$path}");
				} catch (Exception $e)
				{
					$this->RaiseEvent(sprintf("Failed to execute script %s", $path)); 
				}
				if (!$result)
					$this->RaiseEvent(sprintf("Failed to execute script %s", $path)); 
			}
		}
		
		/**
		 * Make backups of templates and database if these are set in manifest
		 *
		 */
		private function MakeBackups()
		{
			
			// Do we need to do any backups?
			if ($this->TargetUpdate->DoBackupDatabase + $this->TargetUpdate->DoBackupTemplates >= 1)
			{
				$backup_root = "{$this->AppPath}/cache/autoup-backup-".date("Y-m-d-Hi");
			}
			
			// DB backup
			if ($this->TargetUpdate->DoBackupDatabase == 1)
			{
				$backup_path = "{$backup_root}/mysql";
				// Forcefully create folder for mysql dump 
				@mkdir($backup_path, 0777, true);
				@chmod($backup_path, 0777);
				
				$this->RaiseEvent(sprintf("Saving database backup to '%s/backup.sql'", $backup_path));
				$command = "mysqldump -h'{$this->DB->host}' -u'{$this->DB->username}' -p'{$this->DB->password}' {$this->DB->database} > {$backup_path}/backup.sql";
				$result = $this->Shell->ExecuteRaw($command);
				if (!$result)
					$this->RaiseEvent(sprintf("Failed to backup database using mysqldump binary. Make sure that mysqldump is in PATH and executable by %s user", $this->Uname));
			}
			
			// Templates backup
			if ($this->TargetUpdate->DoBackupTemplates == 1)
			{
				$templates_path = "{$this->AppPath}/templates";
				$backup_path = "{$backup_root}/templates";
				@mkdir($backup_path, 0777, true);
				@chmod($backup_path, 0777);
				
				$this->RaiseEvent(sprintf("Saving backup of templates to '%s'", $backup_path));
				
				$cmd = "cp -Rf {$templates_path} {$backup_path}";
				$result = $this->Shell->ExecuteRaw($cmd);
				if (!$result)
					$this->RaiseEvent(sprintf("Failed to backup templates"));
			}
		}
		
		/**
		 * Execute SQL queries within one transaction
		 * @return void 
		 */
		private function ExecuteSQLQueries()
		{
			$this->RaiseEvent("Executing SQL queries");					

			foreach($this->TargetUpdate->SQLQueries as $sql)
			{
				$this->RaiseEvent("Executing SQL: {$sql}");
				$this->DB->Execute($sql);
			}
		}
		
		
		/**
		 * Delete files
		 * @return void
		 */
		private function DeleteFiles()
		{
			$this->RaiseEvent("Deleting files");
			foreach($this->TargetUpdate->FilesToDelete as $f)
			{
				$target_path = $this->AppPath . "/" . $f;
				
				$this->RaiseEvent(sprintf("Deleting '%s'", $target_path));
				
				$result = unlink($target_path);
				if (!$result)
				{
					$this->RaiseEvent(sprintf("Failed to delete file %s (%s)", $f, $target_path));
				}
			}
		}
		
		
		/**
		 * Delete folders
		 * @return void
		 */
		private function DeleteFolders()
		{
			$this->RaiseEvent("Deleting folders");
			foreach($this->TargetUpdate->FoldersToDelete as $f)
			{
				$target_path = $this->AppPath . "/" . $f;
				
				$this->RaiseEvent(sprintf("Deleting '%s'", $target_path));
				
				$result = IOTool::UnlinkRecursive($target_path);
				if (!$result)
				{
					$this->RaiseEvent(sprintf("Failed to delete folder %s (%s)", $f, $target_path));
				}
			}
		}
		
		
		/**
		 * Create folders
		 * @return void
		 */
		private function AddFolders()
		{
			$this->RaiseEvent("Creating folders");
			foreach($this->TargetUpdate->FoldersToAdd as $f)
			{
				$target_path = $this->AppPath . "/" . $f;
				
				$chmod = $this->TargetUpdate->Chmods[$f];
				if (!file_exists($target_path))
				{
					if (!$chmod)
						$chmod = 0755;
						
					$result = mkdir($target_path, $chmod, true);
					@chmod($target_path, $chmod);
					
					if (!$result)
					{
						$this->RaiseEvent(sprintf("Failed to create folder %s (%s)", $f, $target_path));
					}
				}
				else
					$this->RaiseEvent(sprintf("Folder %s (%s) already exists.. skipping", $f, $target_path));
			}
		}
		

		/**
		 * No differences to UpdateFiles() so far
		 * @return void
		 */
		private function AddFiles()
		{
			//set_error_handler(array(&$this, 'CopyErrorHandler'));
			$this->RaiseEvent("Copying files");
			foreach($this->TargetUpdate->FilesToAdd as $f)
			{
				$source_path = "./".$f;
				$target_path = $this->AppPath . "/" . $f;
				
				if (!file_exists($target_path))
				{
					$result = copy($source_path, $target_path);
					

					if (!$result)
					{
//						$this->RaiseEvent(sprintf("Failed to copy file %s ('%s' to '%s')", $f, $source_path, $target_path));
					}
				}
				else
					$this->RaiseEvent(sprintf("Failed to copy file %s ('%s' to '%s'). File already exists", $f, $source_path, $target_path));
			}
		}
		
		
		/**
		 * Copy files over existing ones or update chmods
		 * @return void
		 */
		private function UpdateFiles()
		{
			// print memory_get_usage(true) . " " . memory_get_usage()." Before Update files\n";			
			$this->RaiseEvent("Updating files");
			foreach($this->TargetUpdate->FilesToUpdate as $f)
			{
				$source_path = "./".$f;
				$target_path = $this->AppPath . "/" . $f;
				// print memory_get_usage(true) . " " . memory_get_usage()." Copying $source_path ... \n";
				$result = copy($source_path, $target_path);
				// print memory_get_usage(true) . " " . memory_get_usage(). ($result ? " Ok\n" : " Failed\n");
				
				if (!$result)
				{
//					print sprintf("Failed to copy file %s ('%s' to '%s')", $f, $source_path, $target_path);
					$this->RaiseEvent(sprintf("Failed to copy file %s ('%s' to '%s')", $f, $source_path, $target_path));
				}
			}
			// print memory_get_usage(true) . " " . memory_get_usage()." After Update files\n";
		}
		
		
		/**
		 * Update to a specific Update in few hops
		 *
		 * @param int $target_revision
		 */
		public function UpdateToRevision($target_revision)
		{
			// Get a list of update hops and update step-by-step iteratively
			foreach ($this->ListHops($this->LocalRevision, $target_revision) as $revision)
			{
				$this->RaiseEvent(sprintf("Hop: Updating to version %s", $revision));
				try
				{
					$this->Update($revision);
				} 
				catch (Exception $ex)
				{
					// PHP should have `finally` :(
					$this->Dispose();
					throw new Exception($ex->getMessage());
					break;
				}
				
				if ($this->TargetUpdate->InterruptAfter == 1)
				{
					break;
				}
			}
			$this->RaiseEvent("Update finished");
		}
		
		/**
		 * Update to latest available revision
		 *
		 */
		public function UpdateToLatest()
		{
			$target_revision = $this->GetLatestRevision();
			$this->RaiseEvent(sprintf("Updating to latest version (%s)", $target_revision));
			$this->UpdateToRevision($target_revision);
		}
		
		/**
		 * Notify Event listener
		 *
		 */
		private function RaiseEvent()
		{
			$args = func_get_args();
			$date = date("m.d.y H:i:s");
			$this->EventHandler->OnEvent("LogEvent", $args);
			$this->ReportRows[] =  $date . ":" . implode(";", $args);
		}
		
		/**
		 * Notify Event listener and throw Exception
		 *
		 * @param string $oops
		 */
		private function RaiseEventAndThrow($oops)
		{
			$this->RaiseEvent($oops);
			throw new Exception($oops);
		}
		
		/**
		 * Build s string report
		 *
		 * @return unknown
		 */
		public function BuildReport()
		{
			return(implode("\r\n", $this->ReportRows));
		}
	}
?>
