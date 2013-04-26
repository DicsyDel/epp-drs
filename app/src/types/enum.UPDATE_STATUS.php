<?
	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Common
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */

	/**
     * Contains possible values of EPP-DRS update status. Use CONFIG::UPDATE_STATUS to retrieve current update status in your code.
     * @name UPDATE_STATUS
     * @category   EPP-DRS
     * @package    Common
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Marat Komarov <http://webta.net/company.html>
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */
	final class UPDATE_STATUS
	{
		const NO_UPDATES = 0;

		const AVAILABLE = 1;

		const SCHEDULED = 2;
		
		const AVAILABLE_AND_EMAIL_SENT = 3;
		
		const RUNNING = 4;
	}
?>