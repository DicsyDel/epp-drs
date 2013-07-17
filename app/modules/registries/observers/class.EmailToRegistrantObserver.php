<?php
	
	class EmailToRegistrantObserver extends RegistryObserverAdapter
	{
		private $DB;
		
		public function __construct ()
		{
			$this->DB = Core::GetDBInstance();
		}
		
		public function OnDomainOwnerChanged(Domain $domain, $period)
		{
			$userinfo = $this->DB->GetRow("SELECT * FROM users WHERE id=?", array($domain->UserID));
			$args = array(	"client" 		=> $userinfo, 
							"domain_name"	=> $domain->Name,
							"extension"		=> $domain->Extension
					 );
			mailer_send("trade_complete.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnDomainCreated(Domain $domain)
		{
			$userinfo = $this->DB->GetRow("SELECT * FROM users WHERE id=?", array($domain->UserID));
			$args = array(
				"client" 		=> $userinfo, 
				"domain_name"	=> $domain->Name,
				"extension"		=> $domain->Extension
			);
			mailer_send("registration_complete.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnDomainRenewed(Domain $domain)
		{
			$userinfo = $this->DB->GetRow("SELECT * FROM users WHERE id=?", array($domain->UserID));
			$args = array(
				"client" 		=> $userinfo, 
				"domain_name"	=> $domain->Name,
				"extension"		=> $domain->Extension,
				"expdate"		=> date("Y-m-d H:i:s", $domain->ExpireDate),
				"years"			=> $domain->Period
			 );
			mailer_send("renewal_complete.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnDomainTransferDeclined (Domain $domain)
		{
			$this->DomainTransferFailed($domain, true);
		}
		
		public function OnDomainTransferFailed (Domain $domain)
		{
			$this->DomainTransferFailed($domain, false);
		}
		
		private function DomainTransferFailed (Domain $domain, $wasDeclined)
		{
			$userinfo = $this->DB->GetRow('SELECT * FROM users WHERE id=?', array($domain->UserID));

			$args = array
			(
				"client" 		=> $userinfo, 
				"domain_name"	=> $domain->Name, 
				"extension"		=> $domain->Extension,
				"reason"		=> $wasDeclined ? 
					_("Transfer was rejected by the current domain owner.") : 
					_("We gave up while waiting for transfer authorization from domain owner.")
			);
						
			mailer_send("transfer_failed.eml", $args, $userinfo["email"], $userinfo["name"]);	
		}
		
		public function OnDomainTransferApproved (Domain $domain)
		{
			$userinfo = $this->DB->GetRow('SELECT * FROM users WHERE id=?', array($domain->UserID));
			$args = array
			(
				"client" 		=> $userinfo, 
				"domain_name"	=> $domain->Name,
				"extension"		=> $domain->Extension
			);
			
			mailer_send("transfer_complete.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnDomainOutgoingTransferRequested (Domain $domain) 
		{
			$userinfo = $this->DB->GetRow('SELECT * FROM users WHERE id=?', array($domain->UserID));
			$args = array
			(
				"client"	=> $userinfo,
				"domain_name"	=> $domain->Name,
				"extension"		=> $domain->Extension,
				"confirm_url" 	=> CONFIG::$SITE_URL . "/client/incomplete_orders.php?op=" . INCOMPLETE_OPERATION::DOMAIN_OUTGOING_TRANSFER
			);
			
			mailer_send("outgoing_transfer_requested.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnDomainTransferredAway (Domain $domain)
		{
			$userinfo = $this->DB->GetRow('SELECT * FROM users WHERE id=?', array($domain->UserID));
			$args = array
			(
				"client"	=> $userinfo,
				"domain_name"	=> $domain->Name,
				"extension"		=> $domain->Extension
			);
			
			mailer_send("outgoing_transfer_away.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
		
		public function OnDomainOperation (Domain $domain, $optype, $failed=false, $errmsg=null)
		{
			// Used from multiprocess cronjob
			// so we need this ugly staff 
			$DB = Core::GetDBInstance(null, true);

			$userinfo = $DB->GetRow("SELECT * FROM users WHERE id=?", array($domain->UserID));
			$args = array(
				"client" 		=> $userinfo, 
				"domain_name"	=> $domain->Name,
				"extension"		=> $domain->Extension,
				"operation_type" => ucfirst(strtolower($optype)),
				"result"		=> !$failed,
				"reason"		=> $errmsg
			);
			mailer_send("pending_operation_complete.eml", $args, $userinfo["email"], $userinfo["name"]);
		}

		public static function OnNewChangeContactRequest ($Contact, $op_type)
		{
			$DB = Core::GetDBInstance();
			
			// Send mail to registrant
			$userinfo = $DB->GetRow("SELECT * FROM users WHERE id=?", array($Contact->UserID));
			$args = array
			(
				"client" => $userinfo,
				"Contact" => $Contact
			);
			mailer_send("new_contact_change_request.eml", $args, $userinfo["email"], $userinfo["name"]);
			
			// Send mail to admin
			$args = array
			(
				"client" => $userinfo,			
				"Contact" => $Contact					
			);
			mailer_send("root_new_contact_change_request.eml", $args, CONFIG::$EMAIL_ADMIN, CONFIG::$EMAIL_ADMINNAME);
		}
		
		public static function OnCompleteChangeContactRequest ($Contact, $op_type, $approved, $request_ok, $error=null)
		{
			$DB = Core::GetDBInstance();
			$userinfo = $DB->GetRow("SELECT * FROM users WHERE id=?", array($Contact->UserID));
			
			$args = array
			(
				"client" => $userinfo,
				"Contact" => $Contact,
				"approved" => $approved,
				"request_ok" => $request_ok,
				"error" => $error
			);
			mailer_send("contact_change_request_complete.eml", $args, $userinfo["email"], $userinfo["name"]);
		}
	}
?>