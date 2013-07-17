<?php
interface PublicLicenseService
{
	/**
	 * Check license
	 * @param ZendLicense|string(sha256) $license
	 * @return unknown_type
	 */
	function CheckLicense ($license);
}
?>