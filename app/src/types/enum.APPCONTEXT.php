<?
	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Common
     * @sdk-doconly
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */

	/**
     * Contains possible values of application context. 
     * Use CONTEXTS::APPCONTEXT to retrieve current application context in your code.  
     * @name APPCONTEXT
     * @category   EPP-DRS
     * @package    Common
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */
	final class APPCONTEXT
	{
		/**
		 * We are in the order wizard
		 *
		 */
		const ORDERWIZARD = 1;
		
		/**
		 * We are in registrar CP
		 *
		 */
		const REGISTRAR_CP = 2;
		
		/**
		 * We are in registrant CP
		 *
		 */
		const REGISTRANT_CP = 3;
		
		/**
		 * We are inside cronjob
		 *
		 */
		const CRONJOB = 4;
		
		/**
		 * Context not yet determined (you should never get this)
		 *
		 */
		const NOT_YET_DEFINED = 5;
	}
	
?>