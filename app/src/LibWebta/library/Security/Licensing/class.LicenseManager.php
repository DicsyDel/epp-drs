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
     * @package    Security
     * @subpackage Licensing
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @ignore
     */
		
	define("LIC_EMAIL", "licensing@webta.net");
	define("LIC_PATH", "../../../../../etc");
	define("LIC_TPL_PATH", dirname(__FILE__)."/license.tpl");
	
	
	Core::Load("Security/Crypto/Crypto");	
	Core::Load("System/Independent/Shell/ShellFactory");
	Core::Load("UI/Smarty/Smarty.class.php", LIB_BASE);
	
	/**
	 * @name       LicenseManager
	 * @category   LibWebta
     * @package    Security
     * @subpackage Licensing
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Sergey Koksharov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @ignore
	 *
	 */
	class LicenseManager extends Core
	{
	
		/**
		* License encryption algorythm version
		* @var int
		* @access private
		*/
		private $AlgoVersion;
		
		/**
		 * Product ID
		 *
		 * @var int
		 */
		public $ProductID;
		
		/**
		* Path to folder with license file
		* @var string
		* @access private
		*/
		private $LicPath;
		
		
		/**
		* Weakest license
		* @var string
		* @access public
		*/
		private $WeakestLic;
		
		
		/**
		* Array of license strings
		* @var string
		* @access public
		*/
		private $Lics;
		
		/**
		 * Array of license files
		 *
		 * @var array
		 */
		public $LicFiles;
		
		/**
		* Secret key used to encrypt/decrypt license string
		* @var string
		* @access private
		*/
		private $LicKey;
		
		/**
		 * How frequently license should be validated, in percents
		 *
		 * @var string
		 */
		private $Frequency;
		
		
		/**
		 * Either RaiseError should be called if lcense is invald
		 * @var bool
		 */
		public $ErrorOnFailure;
		
		/**
		 * Smarty instance
		 *
		 * @var Smarty
		 */
		private $Smarty;
		
		/**
		 * Shell instance
		 *
		 * @var Shell
		 */
		private $Shell;
		
		/**
		* Constructor
		* @access public
		* @return array Mounts
		*/
		function __construct($key = null)
		{
			parent::__construct();
			// Smarty needed to parse lic file template
			$this->Smarty = Core::GetSmartyInstance();
			// Shell needed for ifconfig
			$this->Shell = ShellFactory::GetShellInstance();
			if ($key == null)
			{
				$this->AlgoVersion = 1;
				$this->LicKey = "BK7CqbqDuwPGvHQ,Jx:ErJh4";
			}
			else
			{
				$this->AlgoVersion = 2;
				$this->LicKey = $key;
			};
			
			$this->LicTypes = array("ip", "mac", "domain", "named", "trial");
		}
		
		
		/**
		* Destructor
		* @access public
		* @return array Mounts
		*/
		function __destruct()
		{
			$this->LicKey = NULL;
		}
		
		
		/**
		* How frequently license should be validated, in percents
		* @param string $frequency
		*/
		public function SetFrequency($frequency = 50)
		{
			if ($frequency > 100 || $frequency < 0)
				$this->RaiseError(_("Frequency should be less than 100 and more than 0"));
			$this->Frequency = $frequency;
		}
		
		
		/**
		* How frequently license should be validated, in percents
		* @return bool Either license should be checked this time or not
		*/
		public function DoTriggerValidation()
		{
			$p100 = rand(0, 100);
			$retval = ($p100 <= $this->Frequency);
			return $retval;
		}
	
		
		/**
		* Add a license string
		* @access public
		* @param string $string Full license string
		* @param string $type License type
		* @param string $filename File name (optional, only if license loaded from file).
		* @return void
		*/
		public final function AddLic($string, $type="ip", $filename = null)
		{
			$lic = $this->ParseLic($string);
			if (!in_array($type, $this->LicTypes))
				$this->RaiseWarning("Unsupported license type: $type");
			else
				$this->Lics[$type] = $lic;
				$this->LicFiles[$lic] = $filename;
		}
		
		
		/**
		* Callback function to sort array of licenses according to weakness
		* @access private
		* @return int
		*/
		private final function LicSort($a, $b)
		{
			$order = array(
			"mac" => 0,
			"ip" => 1,
			"domain" => 2,
			"trial" => 3,
			);
			return ($order[$a] > $order[$b]) ? -1 : 1;

		}
		
		
		/**
		* Load .lic files and populate $this->Lics
		* @access public
		* @param string $path Folder to scan for license files
		* @return void
		*/
		public final function LoadLicFiles($path = NULL)
		{
			if (!$path)
				$path = LIC_PATH;
			
			$found = false;
			
			foreach (glob("{$path}/*.lic") as $filename) 
			{
				$b = explode(".", basename($filename));
				if (!in_array($b[0], $this->LicTypes))
					$this->RaiseWarning("Unsupported license type: {$b[0]}");
				
				// Read lic file
				try 
				{
					$content = @file_get_contents($filename);
				}
				catch (Exception $e)
				{
					$this->RaiseError("Cannot read license file {$filename}");
				}
				
				$this->AddLic($content, $b[0], $filename);
				$found = true;
			}
			
			if (!$found && $this->ErrorOnFailure)
			{
				$this->RaiseError("Cannot find suitable license files in $path");
				die();
			}
			
			return $found;
			
		}
		
		/**
		* Parse license string
		* @param $licstring License string
		* @param string $licstring Full license string
		* @return string License string
		*/
		private final function ParseLic($licstring)
		{
			// Extract
			$result = preg_match("/-----BEGIN WEBTA LICENSE-----(.*?)-----END WEBTA LICENSE-----/ms", $licstring, $m);
			if ($result < 1)
			{
				/* 
				 * If we do not have a header and footer (above) and the string in not empty,
				 * let's assume that only license string itself was passed.
				 */
				if (strlen($licstring) > 0)
					$retval = $licstring;
				else
					throw new Exception("Failed to parse license string");
			}
			else
			{
				// Sanitize
				$retval = trim($m[1]);			
				$retval = str_replace(array(" ", "\r", "\n"), "", $retval);
			}
			return $retval;
		}
		
		
		/**
		* Select weakest license 
		* @access public
		* @return void
		*/
		public final function SelectWeakestLic()
		{
			uksort($this->Lics, array($this, "LicSort"));
			$this->WeakestLic = array(key($this->Lics) => $this->Lics[key($this->Lics)]);
			return ($this->WeakestLic);
		}
	
		
		/**
		* Validate license file
		* @access public
		* @return bool True in case if license is valid
		*/
		public final function ValidateLic()
		{
			$this->SelectWeakestLic();
			$licdata = $this->DecryptLic();
			
			$type = key($this->WeakestLic);
			
			// Remove this lic from Lics list
			array_shift($this->Lics);
			
			$contact = "Please contact " . LIC_EMAIL;
			
			$retval = ($type == $licdata[0]);
			
			// Check type matching
			if (!$retval && $this->ErrorOnFailure)
			{
				$this->RaiseError("License types don't match. {$contact}");
				die();
			}
			
			$retval &= ($licdata[2] >= time());
			
			// Check expiration
			if (!$retval && $this->ErrorOnFailure)
			{
				$this->RaiseError("License expired. {$contact}");
				die();
			}
			
			
			$retval &= ($licdata[4] == LIC_PRODUCTID || $licdata[4] == $this->ProductID);
			
			// Check Product ID
			if (!$retval && $this->ErrorOnFailure)
			{
				$this->RaiseError("This license was generated for another product. {$contact}");
				die();
			}
			
			if ($licdata[5])
				$md5_prepend = @md5_file(LIBWEBTA_BASE . "/../../prepend.inc.php");
			if ($licdata[6])
				$md5_lic = @md5_file(__FILE__);
			
			$retval &= ((!$licdata[5] || ($licdata[5] == $md5_prepend)) && (!$licdata[6] || ($licdata[6] == $md5_lic)));
			
			// Checksum
			if (!$retval && $this->ErrorOnFailure)
			{
				$this->RaiseError("Invalid checksum. {$contact}");
				die();
			}
			
		
			// Check value
			switch ($type)
			{
				default:
				case "ip":
					
					$message = "IP address of the server and IP address of license do not match.";
					if (!getenv("windir") && !preg_match("/windows/i", getenv("OS")))
					{
						$retval &= count($this->Shell->QueryRaw("/sbin/ifconfig | grep {$licdata[1]}", false));

						if (!$retval)
						{
						    $ip = @gethostbyname($_SERVER['HTTP_HOST']);
						    $retval &= ($ip == $licdata[1]);
						}
					}
					else 
					{
						$ip = @gethostbyname($_SERVER['HTTP_HOST']);
						$retval &= ($ip == $licdata[1]);
					}
					break;
					
				case "trial":
					
					// Nothing to do here. Expiration already being checked
					break;
					
			}
			
			
			
			if (!$retval)
			{
				// Try to validate next lic in Lics
				while(count($this->Lics) > 0)
					$retval = $this->ValidateLic();
			}
			
			if (!$retval && $this->ErrorOnFailure)
			{
				$this->RaiseError("Invalid license: $message $contact");
				die();
			}
			
			return $retval;
		}
		
		
		/**
		* Decrypt $this->WeakestLic
		* @access public
		* @return array Decrypted lic
		*/
		private final function DecryptLic()
		{
			// Extract sault from license
			if ($this->AlgoVersion < 2)
			{
				$chunks = explode("rmYG", current($this->WeakestLic));
				$sault = $chunks[0];
				$lic = $chunks[1];
			}
			else
			{
				$sault = substr(current($this->WeakestLic), 0, 24);
				$lic = substr(current($this->WeakestLic), 24);
			}
			
			// Prepare and generate key - the base of trust
			$Crypto = new Crypto($this->LicKey);
			if ($this->AlgoVersion == 2)
				$shash = $Crypto->Hash("{$sault}{$this->LicKey}", "SHA256");
			else
				$shash = $Crypto->Hash("{$sault} invalid {$this->LicKey}", "SHA256");
			$key = substr($shash, 0, 12).substr($shash, -12);
			// end
			
			// Decrypt
			$retval = $Crypto->Decrypt($lic, $key);
			$retval = explode("|", $retval);
			
			$retval = array_map('trim', $retval);
			
			$Crypto = null;
			return($retval);
		}		
	}
	
?>
