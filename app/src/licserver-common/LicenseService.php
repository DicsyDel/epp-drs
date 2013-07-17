<?php
	
interface LicenseService
{
	const TARGET_NAMESPACE = "http://webta.net/ws/licserver";
	
	/**
	 * Sign license
	 * @param ZendLicense|string $license
	 * @return ZendLicense
	 */
	function SignLicense ($license, $expire_date); 

	/**
	 * Renew license
	 * @param ZendLicense|string $license
	 * @param string(date) $new_expire_date
	 * @return void
	 */
	function RenewLicense ($license, $new_expire_date);
	
	/**
	 * Check license
	 * @param ZendLicense|string|string(sha256) $license
	 * @return CheckLIcenseResult
	 */
	function CheckLicense ($license); 
}


?>