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
     * @subpackage Crypto
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	
	define("HASH_ALGO", "SHA256"); 
	define("CRYPT_ALGO", MCRYPT_3DES);
	
	/**
	 * @name       Crypto
	 * @category   LibWebta
     * @package    Security
     * @subpackage Crypto
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */
	class Crypto extends Core
	{
		/**
		 * Crypto algorythm
		 *
		 * @var string
		 */
		protected $Algorythm;
		
		/**
		 * Crypto key
		 *
		 * @var string
		 */
		protected $Key;
		
		/**
		* Crypto Constructor
		* @access public
		* @param string $key Secret key used to Encrypt and Decrypt
		* @param string $cryptoalgo Algorythm to be used in Encrypt and Decrypt. Must be valid mcrypt const
		* @return void
		*/
		function __construct($key, $cryptoalgo=NULL)
		{
			$this->CryptoAlgo = $cryptoalgo ? $cryptoalgo : CRYPT_ALGO;
			$this->Key = $key;
		}
		
		
		/**
		* Check either mcrypt extension available. Raise error if it is not.
		* @access private
		* @return void
		*/
		private final function CheckMcrypt()
		{
			if (!function_exists("mcrypt_module_open"))
				Core::ThrowException("Cannot call mcrypt_module_open(). Mcrypt extension not installed?", E_ERROR);
		}
		
		
		/**
		* Encrypt string using $this->CryptoAlgo
		* @access public
		* @param string $input String to be encrypted
		* @param string $key Password to be used during encryption
		* @return string Encrypted string or false in case of failure
		*/
		public final function Encrypt($input, $key=NULL)
		{
			if (!$key)
				$key = $this->Key;

			$key = substr($key, 0, 24);
			
			$this->CheckMcrypt();
			
			try
			{
				$tdes = mcrypt_module_open($this->CryptoAlgo, '', 'ecb', '');
				$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($tdes), MCRYPT_RAND);
				mcrypt_generic_init($tdes, $key, $iv);
				$ct = mcrypt_generic($tdes, $input);
				mcrypt_module_close($tdes);
				$retval=base64_encode($ct);
			}
			catch (Exception $e)
			{
				Core::ThrowException("Crypto::Encrypt throwed exception: {$e->getMessage()}", E_ERROR);
			}
			return ($retval);
		}
		
		
		/**
		* Decrypt string, previously encrypted using 3DES
		* @access public
		* @param string $input String to be encrypted
		* @param string $key Password that has been used during encryption
		* @return string Decrypted string or false in case of failure
		* @uses mcrypt_module_open() 
		*/
		public final function Decrypt($input, $key=null)
		{
			if (!$key)
				$key = $this->Key;
								
			$this->CheckMcrypt();
			$inputd = base64_decode($input);
			try
			{
				$td = mcrypt_module_open($this->CryptoAlgo, '', 'ecb', '');
				$key = substr($key, 0, mcrypt_enc_get_key_size($td));
				$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
				mcrypt_generic_init($td, $key, $iv);
				$retval = mdecrypt_generic($td, $inputd);
				
				mcrypt_generic_deinit($td);
				mcrypt_module_close($td);
			}
			catch (Exception $e)
			{
				return false;
			}
			return trim($retval, "\x00..\x1F");
		}
			
		
		/**
		* Generate one way hash from the string
		* @access public
		* @param string $input String to be hashed
		* @param string $algo Otpional. Algorythm to be used.
		* @uses crypt();
		* @return string Hashed string or false.
		*/
		public function Hash($input, $algo = NULL)
		{
			// Switch to default algo
			if (!$algo)
				$algo = defined("CF_HASH_ALGO") ? CF_HASH_ALGO : HASH_ALGO;
				
			switch ($algo)
			{
				case "MD5":
					return md5($input);
				break;
				
				case "3DES":
					return crypt($input, substr($this->Key, 0, 9));
				break;
				
				case "SHA256":
					return function_exists("hash") ? hash("sha256", $input) : bin2hex(mhash(MHASH_SHA256, $input));
				break;
				
				default:
					return false;
				break;
			}
		}
	
		/**
		 * Generate Sault
		 *
		 * @param integr $length
		 * @return string
		 */
		public static final function Sault($length = 10)
		{
			return substr(md5(uniqid(rand(), true)),0, $length);
		}
		
		/**
		 * Generate random string
		 *
		 * @param integer $length
		 * @return string
		 */
		public final function Rand($length)
		{
			$retval = substr(md5(rand(str_repeat("1", $length*2), str_repeat("9", $length*2))), 0 , $length);
			return $retval;
		}

		public final static function DigestAuthHash($username, $realm, $password, $uri, $nonce, $nc, $cnonce, $qop)
		{
		    $A1 = self::Hash("{$username}:{$realm}:{$password}", "MD5");
            $A2 = self::Hash("{$_SERVER['REQUEST_METHOD']}:{$uri}", "MD5");
                            
            return self::Hash("{$A1}:{$nonce}:{$nc}:{$cnonce}:{$qop}:{$A2}", "MD5");
		}
	}		
	
?>