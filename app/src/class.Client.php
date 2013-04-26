<?php
		
	final class ClientSettings
	{
		const API_ENABLED = "api_enabled";
		const API_KEY = "api_key";
		const API_KEY_ID = "api_key_id";
		const API_ALLOWED_IPS = "api_allowed_ips";
		
		const SHOW_INLINE_HELP = "inline_help";
		const AUTO_PAY_FROM_BALANCE = "auto_pay";
		const AUTO_PAY_NO_RENEW = "auto_pay_no_renew";
		const LOW_BALANCE_NOTIFY = "low_balance_notify";
		const LOW_BALANCE_VALUE = "low_balance_value";
		const PREFILL_CONTACT = "prefill_contact";
		
		// Start notify client about expiring domain `days` before it's expiration
		const EXPIRE_NOTIFY_START_DAYS = "expire_notify_start_days";
		// Notify about expiring domain each `step` days
		//const EXPIRE_NOTIFY_STEP = "expire_notify_step";

		const NS1 = "ns1";
		const NS2 = "ns2";
	}

	class Client
	{
		public $ID;
		public $Login;
		public $Password;
		public $Email;
		public $Status;
		public $RegisteredAt;
		public $PackageID;
		public $PackageFixed;
		public $Name;
		public $Organization;
		public $Business;
		public $Address;
		public $Address2;
		public $City;
		public $State;
		public $Country;
		public $ZipCode;
		public $Phone;
		public $Fax;
		public $VAT;
		public $NewEmail;
				
		private $Settings;
		
		private $DB;
		
		private $CustomFields = array();
		
		/**
		 * DB Field Property map
		 *
		 * @var array
		 */
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'status'		=> 'Status',
			'dtregistered'	=> 'RegisteredAt',
			'packageid'		=> 'PackageID',
			'package_fixed'	=> 'PackageFixed',
			'name'			=> 'Name',
			'org'			=> 'Organization',
			'business'		=> 'Business',
			'address'		=> 'Address',
			'address2'		=> 'Address2',
			'city'			=> 'City',
			'state'			=> 'State',
			'country'		=> 'Country',
			'zipcode'		=> 'ZipCode',
			'phone'			=> 'Phone',
			'fax'			=> 'Fax',
			'vat'			=> 'VAT',
			'nemail'		=> 'NewEmail'
		);
		
		/**
		 * Client constructor
		 *
		 * @param string $login
		 * @param string $password
		 * @param string $email
		 */
		function __construct($login, $password, $email)
		{
			$this->DB = Core::GetDBInstance();
			
			$this->Login = $login;
			$this->Password = $password;
			$this->Email = $email;
			$this->VAT = CONFIG::$USER_VAT ? CONFIG::$USER_VAT : -1;
			$this->PackageFixed = 0;
			$this->Settings = array();
			
			$fields = array();
			foreach((array)$this->DB->GetAll("SELECT * FROM client_fields") as $field)
				$this->CustomFields[$field['name']] = $field["id"];
		}
		
		/**
		 * @ignore
		 *
		 */
		public function __set($name, $value)
		{
			if ($this->CustomFields[$name])
				$this->{$name} = $value;
			else
				throw new Exception(sprintf(_("Undefined property Client->%s"), $name));
		}
		
		/**
		 * Save client in database
		 *
		 * @return Client
		 */
		public function Save()
		{
			$skip_fields = array('id', 'dtregistered');
			$fields = array();
			$values = array();
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
								
				if (!in_array($k, $skip_fields))
				{
					array_push($fields, $k);
					array_push($values, $this->{$v});
				}
			}
			
			array_push($fields, 'login');
			array_push($values, $this->Login);
			
			array_push($fields, 'password');
			array_push($values, $this->Password);
			
			array_push($fields, 'email');
			array_push($values, $this->Email);
			
			if ($this->ID)
			{
				foreach($fields as $field)
					$update_fields .= "{$field} = ?, ";
				
				$update_fields = trim($update_fields, ", ");	
					
				$this->DB->Execute("UPDATE users SET {$update_fields}
					WHERE id={$this->ID}", 
					$values
				);
			}
			else
			{
				$this->DB->Execute("INSERT INTO users (" . implode(", ", $fields) . ", dtregistered) VALUES (" . str_repeat("?, ", count($fields)) . " UNIX_TIMESTAMP())", 
					$values
				);
				$this->ID = $this->DB->Insert_ID();
				$this->DB->Execute("INSERT INTO balance SET clientid = ?, total = ?", array($this->ID, 0));
			}
			
			$this->DB->Execute("DELETE FROM client_info WHERE clientid=?", array($this->ID));
			foreach ($this->CustomFields as $fieldname => $fieldid)
			{
				$this->DB->Execute("INSERT INTO client_info SET clientid=?, fieldid=?, value=?",
					array($this->ID, $fieldid, $this->{$fieldname})
				);
			}

			/*
			$this->DB->Execute("DELETE FROM user_settings WHERE userid=?", array($this->ID));
			foreach ($this->Settings as $name => $value)
			{
				$this->DB->Execute("INSERT INTO user_settings SET userid=?, `key`=?, value=?",
					array($this->ID, $name, $value)
				);
			}
			*/
			
			return $this;
		}
		
		/**
		 * Delete client from database
		 *
		 */
		public function Delete()
		{
			$this->DB->Execute("DELETE FROM users WHERE id='{$this->ID}'");
			$this->DB->Execute("DELETE FROM client_info WHERE clientid='{$this->ID}'");
			$this->DB->Execute("DELETE FROM user_settings WHERE userid='{$this->ID}'");
			$this->DB->Execute("DELETE FROM invoices WHERE userid='{$this->ID}'");
			$this->DB->Execute("DELETE FROM domains WHERE userid='{$this->ID}'");
			$this->DB->Execute("DELETE FROM contacts WHERE userid='{$this->ID}'");
		}
		
		/**
		 * Set user setting
		 *
		 * @param string $name
		 * @param string $value
		 */
		public function SetSettingValue($name, $value)
		{
			$this->Settings[$name] = $value;
			$this->DB->Execute(
					"REPLACE INTO user_settings SET `userid` = ?, `key` = ?, `value` = ?", 
					array($this->ID, $name, $value));
		}
		
		/**
		 * Get setting value by setting name
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function GetSettingValue($name)
		{
			if (!$this->Settings[$name])
			{
				$this->Settings[$name] = $this->DB->GetOne(
						"SELECT `value` FROM user_settings WHERE `key` = ? AND userid = ?", 
						array($name, $this->ID));
			}
			
			return $this->Settings[$name];
		}
		
		public function ClearSettings ($filter)
		{
			$this->DB->Execute("DELETE FROM user_settings WHERE `key` LIKE '{$filter}'");
			
		}
		
		/**
		 * Load Client by Email
		 *
		 * @param string $email
		 * @return Client
		 */
		static function LoadByEmail($email)
		{
			$db = Core::GetDBInstance();
			
			$userid = $db->GetRow("SELECT id FROM users WHERE email=?", array($email));
			if (!$userid)
				throw new Exception(sprintf(_("Client with email=%s not found in database"), $email));
				
			return self::Load($userid);
		}
		
		/**
		 * Load client by login
		 *
		 * @param string $login
		 * @return Client
		 */
		static function LoadByLogin($login)
		{
			$db = Core::GetDBInstance();
			
			$userid = $db->GetRow("SELECT id FROM users WHERE login=?", array($login));
			if (!$userid)
				throw new Exception(sprintf(_("Client with login=%s not found in database"), $login));
				
			return self::Load($userid);
		}
		
		static function LoadByApiKeyID ($key_id)
		{
			$db = Core::GetDBInstance();
			
			$userid = $db->GetRow
			(
				"SELECT userid FROM user_settings WHERE `key` = ? AND `value` = ?", 
				array(ClientSettings::API_KEY_ID, $key_id)
			);
			if (!$userid)
				throw new Exception(sprintf(_("Client with api_key_id=%s not found in database"), $key_id));
				
			return self::Load($userid);
		}
		
		/**
		 * Load client by ID
		 *
		 * @param integer $id
		 * @return Client
		 */
		static function Load($id)
		{
			$db = Core::GetDBInstance();
			
			$userinfo = $db->GetRow("SELECT * FROM users WHERE id=?", array($id));
			if (!$userinfo)
				throw new Exception(sprintf(_("Client ID#%s not found in database"), $id));
				
			$Client = new Client($userinfo["login"], $userinfo["password"], $userinfo["email"]);
			$Client->ID = $id;
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				// Не помню почему игнорировать пустые. Но нужно устанавливать vat в 0
				if ($userinfo[$k] || $k == "vat")
				{ 
					$Client->{$v} = $userinfo[$k];
				}
			}
			
			$custom_fields = $db->Execute("SELECT *, (SELECT name FROM client_fields 
				WHERE id=client_info.fieldid) as fieldname FROM client_info WHERE clientid=?", 
				array($id)
			);
			while($field = $custom_fields->FetchRow())
				$Client->{$field['fieldname']} = $field['value'];
			
			$settings = $db->Execute("SELECT * FROM user_settings WHERE userid=?", array($id));
			while($setting = $settings->FetchRow())
				$Client->SetSettingValue($setting['key'], $setting['value']);
				
			return $Client;
		}
		
		/**
		 * Convert Client object to Array
		 *
		 * @return array
		 */
		public function ToArray()
		{
			return array(
				'ID' 		=> $this->ID,
				'Login'		=> $this->Login,
				'Password'  => $this->Password,
				'Email'		=> $this->Email,
				'Status'	=> $this->Status,
				'RegisteredAt' => $this->RegisteredAt,
				'PackageID' => $this->PackageID,
				'Name'		=> $this->Name,
				'Organization' => $this->Organization,
				'Business'	=> $this->Business,
				'Address'	=> $this->Address,
				'Address2'	=> $this->Address2,
				'City'		=> $this->City,
				'State'		=> $this->State,
				'Country'	=> $this->Country,
				'ZipCode'	=> $this->ZipCode,
				'Phone'		=> $this->Phone,
				'Fax'		=> $this->Fax,
				'VAT'		=> $this->VAT,
				'Settings'	=> $this->Settings
			);
		}
	}
?>
