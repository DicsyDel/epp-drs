<?		
	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Common
     * @sdk
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */

	/**
     * Contains global configuration values.  
     * @name CONFIG
     * @category   EPP-DRS
     * @package    Common
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Marat Komarov <http://webta.net/company.html>
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */

	final class CONFIG
	{
		/**
		 * Encrypted registrar CP password
		 *
		 * @staticvar string
		 */
		public static $PASS;
		public static $PAGING_ITEMS;
		public static $CRYPTOKEY;
		
		/**
		 * Registrar CP login
		 *
		 * @staticvar string
		 */
		public static $LOGIN;
		
		/**
		 * Email address of service owner
		 *
		 * @staticvar string
		 */
		public static $EMAIL_ADMIN;
		
		/**
		 * Email name of static owner
		 *
		 * @var string
		 */
		public static $EMAIL_ADMINNAME;
		
		/**
		 * Email address of support contact
		 *
		 * @staticvar string
		 */
		public static $SUPPORT_EMAIL;
		
		/**
		 * Email name of support contact
		 *
		 * @var string
		 */
		public static $SUPPORT_NAME;
		
		/**
		 * SMTP connection string.
		 *
		 * @staticvar string
		 */
		public static $EMAIL_DSN;
		
		/**
		 * Either to duplicate all emails to service owener
		 *
		 * @staticvar bool
		 */
		public static $EMAIL_COPY;
		
		/**
		 * Default nameserver #1
		 *
		 * @staticvar string
		 */
		public static $NS1;
		
		/**
		 * Default nameserver #2
		 *
		 * @staticvar string
		 */
		public static $NS2;
		
		/**
		 * Number of ns inputs in UI
		 * @staticvar int
		 */
		public static $DISPLAY_NS;
		
		/**
		 * Either managed DNS is enabled
		 *
		 * @var bool
		 */
		public static $ENABLE_MANAGED_DNS;
		
		/**
		 * Either registrants allowed to edit A DNS records
		 *
		 * @staticvar bool
		 */
		public static $ALLOW_A_RECORD;
		
		/**
		 * Either registrants allowed to edit MX DNS records
		 *
		 * @staticvar bool
		 */
		public static $ALLOW_MX_RECORD;
		
		/**
		 * Either registrants allowed to edit NS DNS records
		 *
		 * @staticvar bool
		 */
		public static $ALLOW_NS_RECORD;
		
		/**
		 * Either registrants allowed to edit CNAME DNS records
		 *
		 * @staticvar bool
		 */
		public static $ALLOW_CNAME_RECORD;
		
		/**
		 * Service name
		 *
		 * @staticvar bool
		 */
		public static $COMPANY_NAME;
		
		/**
		 * Display currency symbol
		 *
		 * @staticvar string
		 */
		public static $CURRENCY;
		
		/**
		 * Display currency ISO code
		 *
		 * @staticvar string
		 */
		public static $CURRENCYISO;
		
		/**
		 * Billing currency ISO code
		 *
		 * @staticvar string
		 */
		public static $BILLING_CURRENCYISO;
		
		/**
		 * Currency rate. [Display currency] / [Billing currency]
		 *
		 * @staticvar float
		 */
		public static $CURRENCY_RATE; 
		
		/**
		 * Prepaid billing mode. 
		 * (All invoices payed from balance. Only balance deposit invoice allowed to pay from payment gateway)
		 * 
		 * @staticvar bool
		 */
		public static $PREPAID_MODE;
		
		/**
		 * Minimum balance deposit amount
		 * @staticvar
		 */
		public static $MIN_DEPOSIT;
		
		/**
		 * Service URL
		 *
		 * @staticvar string
		 */
		public static $SITE_URL;
		public static $MENUSTYLE;
		
		/**
		 * Prefix of user logins
		 *
		 * @staticvar string
		 */
		public static $USER_PREFIX;
		
		public static $USER_VAT;
		
		public static $INVOICE_CUSTOMID_FORMAT;
		
		public static $ROTATE_LOG_EVERY;
		public static $MAIL_POLL_MESSAGES;
		
		/**
		 * Either inline help is enabled in the current context
		 *
		 * @staticvar bool
		 * @see APPCONTEXT
		 */
		public static $INLINE_HELP;
		
		/**
		 * Path to tar command-line tool
		 *
		 * @staticvar string
		 */
		public static $TAR_PATH;
		
		/**
		 * Path to php command-line tool
		 *
		 * @staticvar string
		 */
		public static $PHP_PATH;
		
		/**
		 * Path to zendid command-line tool
		 *
		 * @staticvar string
		 */
		public static $ZENDID_PATH;
		
		/**
		 * EPP-DRS update status. Member of UPDATE_STATUS
		 *
		 * @staticvar string
		 * @see UPDATE_STATUS
		 */
		public static $UPDATE_STATUS;
		
		/**
		 * Equal to true is EPP-DRS being automatically updated in this moment.
		 *
		 * @staticvar string
		 */
		public static $ISUPDATERUNNING;
		
		/**
		 * Unified postback URL for all payment modules that extend IPostBackPaymentModule.
		 *
		 * @staticvar string
		 */
		public static $IPNURL;
		
		/**
		 * Unified redirection URL for all payment modules that extend IPostBackPaymentModule.
		 *
		 * @staticvar string
		 */
		public static $PDTURL;
		
		public static $PRODUCT_ID;
				
		/**
		 * Display phone format 
		 *
		 * @staticvar string
		 */
		public static $PHONE_FORMAT;
		
		public static $DEFAULT_COUNTRY;
		
		/**
		 * Revision number of this copy of EPP-DRS
		 *
		 * @staticvar int
		 */
		public static $APP_REVISION;
		public static $TRACKBACK_EMAIL;
		
		public static $PATH;
		
		/**
		 * Database connection string 
		 *
		 * @staticvar string
		 */
		public static $DATABASE_DSN;
		public static $DATABASE_CACHE;
		public static $DEV_DEBUG;
		
		/**
		 * Either this copy is in demo mode 
		 *
		 * @staticvar bool
		 */
		public static $DEV_DEMOMODE;
		
		/**
		 * API enabled for admin 
		 * @staticvar bool
		 */
		public static $API_ENABLED;
		
		/**
		 * Admin API access key
		 * @staticvar string
		 */
		public static $API_KEY;
		
		/**
		 * Admin API access key id
		 * @staticvar string
		 */
		public static $API_KEY_ID;
		
		public static $API_ALLOWED_IPS;
		
		/**
		 * 
		 * @staticvar bool
		 */
		public static $AUTO_DELETE;
		
		/**
		 * @staticvar bool
		 */
		public static $CLIENT_MANUAL_APPROVAL;
		
		/**
		 * List all available properties through reflection
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @return Array or names
		 */
		public static function GetKeys()
		{ 
			$retval = array();
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			foreach($ReflectionClassThis->getStaticProperties() as $Property)
			{
				$retval[] = $Property->name;
			}
			return($retval);
		}
		
		/**
		 * Get all values
		 * FIXME: Move to superclass, when php will have late static binding
		 *
		 * @param  $key Key name
		 * @return array Array or values
		 */
		public static function GetValues($key)
		{
			return get_class_vars(__CLASS__);
		}
		
		/**
		 * Get value of property by it's name
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @param  $key Key name
		 * @return string
		 */
		public static function GetValue($key)
		{
			//property_exists
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			if ($ReflectionClassThis->hasProperty($key))
			{
				return $ReflectionClassThis->getStaticPropertyValue($key);
			}
			else 
			{
				throw new Exception(sprintf(_("Called %s::GetValue('{$key}') for non-existent property {$key}"), __CLASS__));
			}
		}
	}
	
?>