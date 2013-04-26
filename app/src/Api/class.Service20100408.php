<?php

require_once (dirname(__FILE__) . "/class.Service20090622.php");

class EppDrs_Api_Service20100408 extends EppDrs_Api_Service20090622
{
	/**
	 * Renew domain
	 * @param $params = array(
	 *  	name		string
	 *   	period		int
	 *    	userId		int						User ID (In admin mode)
	 *    	noBilling	bool					Disable billing for domain opeartion (In admin mode)
	 * )
	 * @return object
	 */
	function RenewDomain($params=null)
	{
		// Check params
		if ($this->access_mode == self::ACCESS_MODE_ADMIN)
		{
			if (!$params["userId"])
				throw new Exception(sprintf("'%s' parameter is required", "userId"));
		}
		else
		{
			// Reset user disabled params 
			$params["noBilling"] = false;
		}
		$user_id = $this->user_id ? $this->user_id : $params["userId"];
		if (!$params["name"])
			throw new Exception(sprintf("'%s' parameter is required", "name"));
		$period = (int)$params["period"] ? (int)$params["period"] : 1;
		
		list($name, $tld) = explode(".", $params["name"], 2);
		$registry = $this->registry_factory->GetRegistryByExtension($tld);
		$domain = DBDomain::GetInstance()->LoadByName($name, $tld);
		$domain->Period = $period;
		
		if (!$params["noBilling"]) 
		{
			// Check that enougth money
			$client = Client::Load($user_id);
			$balance = DBBalance::GetInstance()->LoadClientBalance($user_id);
			$invoice = new Invoice(INVOICE_PURPOSE::DOMAIN_RENEW, $domain, $domain->UserID);
			$invoice->Description = sprintf(_("%s domain name renewal for %s year(s)"), 
				$domain->GetHostName(), $period);
			
			$this->CheckEnoughtMoney($client, $balance, $invoice);
			
			$invoice->ItemID = $domain->ID;
			$this->MakePayment($client, $balance, $invoice);			
		}
		
		$registry->RenewDomain($domain, array('period' => $domain->Period));
		return new stdClass();
	}
} 