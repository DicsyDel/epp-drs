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
     * @package    NET
     * @subpackage Mail
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	Core::Load("NET/Mail/PHPMailer");
	Core::Load("Observable");
	
	/**
	 * @name PHPSmartyMailer
	 * @category LibWebta
	 * @package NET
	 * @subpackage Mail
	 * @todo Enable in HTTP Client socket connections if curl functions are disabled
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	class PHPSmartyMailer extends PHPMailer
	{
		
		 /**
		* Sets the Body of the message.  This can be either an HTML or text body.
		* If HTML then run IsHTML(true).
		* @var string
		*/
		public $Body;
		
		/**
		* Instance of Smarty object
		* @var object
		*/
		public $Smarty;
		
		/**
		 * Observer injection
		 * @var Observable
		 */
		private $Observable;
		
		/**
		* Constructor
		* @access public
		* @param string $smtp_dsn SMTP DSN.
		* @return array Mounts
		*/
		public function __construct($smtp_dsn = false)
		{
			$this->Observable = new Observable();
			$this->Observable->DefineEvents(array(
				/**
				 * @param PHPSmartyMailer Mailer
				 */
				'BeforeSend'
			));
			
			$this->Smarty = new Smarty();
			
			$this->Smarty->template_dir = CF_TEMPLATES_PATH;
			$this->Smarty->compile_dir = CF_SMARTYBIN_PATH;
			$this->Smarty->cache_dir = CF_SMARTYCACHE_PATH;
			
			$this->Smarty->caching = false;
			
			if (!$smtp_dsn || $smtp_dsn == "")
				$this->Mailer = "sendmail";
			else
			{
				$this->Mailer = "smtp";
				
				//
				// parseDSN
				//
				preg_match_all("/(.+):(.*)@([^:]+):?([0-9]+)?/", $smtp_dsn, $matches);
				
				$this->Host = $matches[3][0];
				$this->Port = $matches[4][0] ? $matches[4][0] : 25;
				$this->Username = $matches[1][0];
				$this->Password = $matches[2][0];
				
				if ($this->Username && $this->Password)
					$this->SMTPAuth = true;
			}
		}
		
		/**
		 * Sets Smaerty templates DIR
		 *
		 * @param string $dir
		 */
		public function SetSmartyTemplateDir($dir)
		{
			$this->Smarty->template_dir = $dir;
		}
		
		/**
		* Set Smarty variables
		* @access public
		* @param array $vars
		* @return void
		*/
		public function SetTemplateVars($vars)
		{
			$this->Smarty->assign($vars);
		}
		
		public function LoadTemplate($templatename)
		{
			$templ = @file("{$this->Smarty->template_dir}/{$templatename}");
			if (count($templ) > 0)
			{
				$this->Subject = array_shift($templ);
				$this->Body = $this->Smarty->fetch("string:".implode("", $templ));
			}
			else
				RaiseError(_("Cannot read template {$templatename}"));
		}
		
		/**
		* Setter
		* @access public
		* @return array Mounts
		*/
		public function __set($name, $value)
		{
			if ($name == "SmartyBody")
			{
				if (is_array($value))
				{
					$this->Smarty->assign($value[1]);
					$template_name = $value[0];					
				}
				else
					$template_name = $value[0];
					
				$body = $this->Smarty->fetch($template_name);
				
				preg_match_all("/\[([A-Za-z0-9]+)([^\]]*)\]((.*)\[\/\\1\])?/si", $body, $matches);
				foreach ($matches[0] as $index=>$variable)
				{
					switch($matches[1][$index])
					{
						case "subject":
							$this->Subject = $matches[4][$index];
							break;
						
						case "settings":
							
							$str = str_replace(' ', '&', trim($matches[2][$index]));
							parse_str($str, $settings);
							
							if ($settings['priority'])
								$this->Priority = $settings['priority'];

							if ($settings['charset'])
								$this->CharSet = $settings['charset']; 
														
							break;
					}
					
					$body = str_replace($matches[0][$index], "", $body);
				}

				$this->Body = trim($body);
								
				if ($settings['type'] == 'html')
					$this->AltBody = strip_tags(trim($body));					
			}
		}
		
		public function Send($template_name = null, $mail_args = null, $email = null, $name = null)
		{
			if ($template_name && is_array($mail_args) && $email)
			{
				if (class_exists("Log", false))
					Log::Log("Sending email '{$template_name}' to '{$email}'", E_USER_NOTICE);
				
				$this->ClearAddresses();
				$this->SmartyBody = array($template_name, $mail_args);
				$this->AddAddress($email, $name);
				$this->Observable->Fire('BeforeSend', $this);
			}
				
			if (!parent::Send() && class_exists("Log", false))
				Log::Log("Cannot send email:".$this->ErrorInfo, E_ERROR);
				
			$this->ClearAddresses();
		}
		
		/**
		 * @return Observable
		 */
		public function GetObservable ()
		{
			return $this->Observable;
		}
	}
	
?>
