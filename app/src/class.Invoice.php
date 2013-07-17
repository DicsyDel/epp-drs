<?php
	
	/**
	 * @category EPP-DRS
	 * @package Invoicing
	 * @sdk-doconly 
	 */
	class Invoice
	{
		/**
		 * Invoice ID
		 *
		 * @var integer
		 */
		public $ID;
		
		/**
		 * User ID
		 *
		 * @var integer
		 */
		public $UserID;
		
		/**
		 * Order ID
		 *
		 * @var integer
		 */
		public $OrderID;
		
		/**
		 * Custom invoice ID. Pattern set using CONFIG::INVOICE_CUSTOMID_FORMAT
		 * @var unknown_type
		 */
		public $CustomID;
		
		/**
		 * Invoice purpose - a member of INVOICE_PURPOSE
		 *
		 * @var string (INVOICE_PURPOSE::*)
		 */
		public $Purpose;
		
		/**
		 * Item ID
		 *
		 * @var integer
		 */
		public $ItemID;
		
		/**
		 * Description
		 *
		 * @var string
		 */
		public $Description;
		
		/**
		 * Invoice Status
		 *
		 * @var integer (0 - Pending, 1 - Paid, 2 - Failed)
		 */
		public $Status;
		
		/**
		 * Invoice is internal
		 * @var integer
		 */
		public $Hidden;
		
		/**
		 * Invoice can be deleted without payment
		 * 
		 * @var integer
		 */
		public $Cancellable;
		
		/**
		 * Payment module name
		 *
		 * @var string
		 */
		public $PaymentModuleName;
		
		/**
		 * Discount
		 *
		 * @var float
		 */
		private $Discount;
		
		/**
		 * VAT
		 *
		 * @var float
		 */
		private $VAT;
		
		/**
		 * VAT (in percents)
		 *
		 * @var float
		 */
		private $VATPercent;
		
		/**
		 * Total amount
		 *
		 * @var float
		 */
		private $Total;
		
		/**
		 * Invoice notes
		 *
		 * @var string
		 */
		public $Notes;
		
		/**
		 * Invoice creation date
		 *
		 * @var datetime
		 */
		public $CreatedAt;
		
		/**
		 * Action status
		 *
		 * @var int
		 */
		public $ActionStatus;
		
		/**
		 * Action fail reason
		 *
		 * @var string
		 */
		public $ActionFailReason;
		
		/**
		 * DB Field Property map
		 *
		 * @var array
		 */
		private $FieldPropertyMap = array(
			'id' 				=> 'ID',
			'userid'			=> 'UserID',
			'orderid' 			=> 'OrderID',
			'description'		=> 'Description',
			'status'			=> 'Status',
			'hidden'			=> 'Hidden',
			'cancellable'		=> 'Cancellable',
			'payment_module'	=> 'PaymentModuleName',
			'dtcreated'			=> 'CreatedAt',
			'notes'				=> 'Notes',
			'total'				=> 'Total',
			'vat'				=> 'VATPercent',
			'action_status' 	=> 'ActionStatus',
			'action_fail_status'=> 'ActionFailReason',
			'customid'			=> 'CustomID'
		);
		
		/**
		 * ADODB
		 *
		 * @var ADODBConnection
		 */
		private $DB;
		
		/**
		 * Invoice Observers
		 *
		 * @var array
		 */
		private static $ClassObservers = array();
		
		private $events_suspended;
		
		public function SuspendEvents ()
		{
			$this->events_suspended = true;
		}
		
		public function ResumeEvents ()
		{
			$this->events_suspended = false;
		}
		
		public function FireEvent($event_name /* args1, args2 ... argN */)
		{
			if ($this->events_suspended)
				return;
			
			$args = func_get_args();
			array_shift($args); // First argument is event name
			
			Log::Log(sprintf('Fire %s', $event_name), E_USER_NOTICE);

			foreach (EVENT_HANDLER_PHACE::GetKeys() as $phace)
			{
				if (array_key_exists($phace, self::$ClassObservers))
				{
					foreach (self::$ClassObservers[$phace] as $observer)
					{
						Log::Log(sprintf("Execute %s:On%s", get_class($observer), $event_name), E_USER_NOTICE);
						call_user_func_array(array($observer, "On{$event_name}"), $args);
					}
				}				
			}
		}
		

		public static function AttachObserver(IInvoiceObserver $InvoiceObserver, 
				$phace = EVENT_HANDLER_PHACE::SYSTEM)
		{
			if (!array_key_exists($phace, self::$ClassObservers))
			{
				self::$ClassObservers[$phace] = array();
			}
			
			if (array_search($InvoiceObserver, self::$ClassObservers[$phace]) !== false)
				throw new Exception(_('Observer already attached to class <Invoice>'));
			
			self::$ClassObservers[$phace][] = $InvoiceObserver;			
		}
		
		/**
		 * Constructor
		 *
		 * @param string $purpose
		 * @param integer $item_id
		 * @param integer $user_id
		 */
		function __construct($purpose, $item_id, $user_id)
		{
			$this->DB = Core::GetDBInstance();
			$this->UserID = $user_id;
			$this->Purpose = $purpose;
			$this->ItemID = $item_id;
			$this->Hidden = 0;
			$this->Status = INVOICE_STATUS::PENDING;
			$this->ActionStatus = INVOICE_ACTION_STATUS::PENDING;
			
			$userinfo = $this->DB->GetRow("SELECT * FROM users WHERE id=?", array($this->UserID));
			
			//
			// � ����������� �� ���������� ������� �������� ����� � ��������
			//
			switch($this->Purpose)
			{
				case INVOICE_PURPOSE::DOMAIN_CREATE:
				case INVOICE_PURPOSE::DOMAIN_RENEW:
				case INVOICE_PURPOSE::DOMAIN_TRANSFER:
				case INVOICE_PURPOSE::DOMAIN_TRADE:
				case INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING:
						
					if (is_numeric($item_id)) {
						$Domain = DBDomain::GetInstance()->Load($item_id);
					} else {
						$Domain = $item_id;
						$this->ItemID = $items_id = $Domain->ID; 
					}
					
					if ($this->Purpose == INVOICE_PURPOSE::DOMAIN_CREATE || 
						$this->Purpose == INVOICE_PURPOSE::DOMAIN_RENEW || 
						$this->Purpose == INVOICE_PURPOSE::PREREGISTRATION_DROPCATCHING)
						$period = $Domain->Period;
					$min_period = $this->Purpose == INVOICE_PURPOSE::DOMAIN_RENEW ?
							(int)$Domain->GetConfig()->renewal->min_period : 1;
					$period = max($period, $min_period);
								
					$price = $this->DB->GetRow("
						SELECT * FROM prices WHERE TLD=? AND purpose=? AND period=?", 
						array($Domain->Extension, $this->Purpose, $period)
					);
					if ($price === array())
						throw new Exception(sprintf("Failed to find price for %s TLD: %s period: %s", $this->Purpose, $Domain->Extension, $period));
					
					// Get discount
					if ($userinfo["packageid"] != 0)
					{
						$discount = $this->DB->GetRow(
							"SELECT * FROM discounts WHERE TLD=? AND purpose=? AND packageid=?", 
							array($Domain->Extension, $this->Purpose, $userinfo["packageid"])
						);
						$this->Discount = round($price["cost"]/100*$discount["discount"], 2);
					}
					else 
						$this->Discount = 0;

					$this->Total = $price["cost"]-$this->Discount;

					break;
			}
			
			// ����������� ���
			$this->VATPercent = 0;
			if ((float)$userinfo["vat"] > -1)
				$this->VATPercent = (float)$userinfo["vat"];
			else
				$this->VATPercent = (float)$this->DB->GetOne("SELECT vat FROM countries WHERE code=?", array($userinfo["country"]));			
			
			if ($this->VATPercent > 0)
			{
				$this->VAT = round(($this->Total/100*$this->VATPercent), 2);
				$this->Total = $this->Total+$this->VAT;
			}
			else
				$this->VAT = 0;
		}
		
		/**
		 * Save invoice in database
		 *
		 * @return Invoice
		 */
		public function Save()
		{
			if (!$this->ID)
			{
				$this->DB->Execute("
				INSERT INTO
					invoices
				SET
					userid		= ?,
					orderid 	= ?,
					purpose		= ?,
					total		= ?,
					dtcreated	= NOW(),
					status		= ?,
					cancellable = ?,
					hidden		= ?,
					description = ?,
					itemid		= ?,
					vat			= ?,
					action_status = ?,
					action_fail_reason = ?
				", array(
					$this->UserID, 
					$this->OrderID, 
					$this->Purpose, 
					$this->Total, 
					$this->Status,
					$this->Cancellable,
					$this->Hidden,
					$this->Description, 
					$this->ItemID, 
					$this->VATPercent,
					$this->ActionStatus,
					$this->ActionFailReason
				));
				
				$this->ID = $this->DB->Insert_ID();
				
				$this->CustomID = str_replace("%id%", $this->ID, CONFIG::$INVOICE_CUSTOMID_FORMAT);
				
				// Защита от дурака
				if ($this->CustomID == CONFIG::$INVOICE_CUSTOMID_FORMAT) 
				{
					$this->CustomID .= $this->ID;
				}
				$this->DB->Execute("UPDATE invoices SET customid = ? WHERE id = ?", 
						array($this->CustomID, $this->ID));
				
				$this->FireEvent("Issued", $this);
			}
			else
			{
				$this->DB->Execute("
				UPDATE
					invoices
				SET
					orderid 	= ?,
					dtupdated	= NOW(),
					status		= ?,
					cancellable	= ?,
					hidden		= ?,
					description = ?,
					itemid		= ?,
					payment_module = ?,
					notes	    = ?,
					action_status = ?,
					action_fail_reason = ?
				WHERE id = ?
				", array(
					$this->OrderID, 
					$this->Status, 
					$this->Cancellable,
					$this->Hidden,
					$this->Description, 
					$this->ItemID, 
					$this->PaymentModuleName,
					$this->Notes,
					$this->ActionStatus,
					$this->ActionFailReason,
					$this->ID
				));
			}
			
			// Auto mark invoice as paid if Total == 0
			if ($this->GetTotal() == 0 && $this->Status == INVOICE_STATUS::PENDING)
				$this->MarkAsPaid(null);
			
			return $this;
		}
		
		/**
		 * Load invoice from database
		 *
		 * @param integer $id
		 * @return Invoice
		 */
		public static function Load($id)
		{
			$db = Core::GetDBInstance();
			
			$dbinvoice = $db->GetRow("SELECT * FROM invoices WHERE id=?", array($id));
			if (!$dbinvoice)
				throw new ApplicationException(sprintf(_("Invoice with id = '%s' not found in database"), $id));
			
			$invoice = new Invoice($dbinvoice["purpose"], $dbinvoice["itemid"], $dbinvoice["userid"]);
			foreach ($dbinvoice as $k=>$v)
			{
				if ($invoice->FieldPropertyMap[$k])
					$invoice->{$invoice->FieldPropertyMap[$k]} = $v;
			}
				
			return $invoice;
		}
		
		/**
		 * Mark invoice as paid
		 *
		 * @param IPaymentModule $payment_module
		 */
		function MarkAsPaid($payment_module)
		{
			if ($this->Status == INVOICE_STATUS::PAID)
				throw new ApplicationException(_("Invoice already marked as paid"), E_ERROR);
			
			$this->Status = INVOICE_STATUS::PAID;
			if (is_object($payment_module))
				$this->PaymentModuleName = $payment_module->GetModuleName();
			else
				$this->PaymentModuleName = _("Manual");
				
			// Hide payed from balance invoice.
			if (CONFIG::$PREPAID_MODE && $this->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT)
			{
				$this->Hidden = 1;
			}			
				
			$this->Save();
			
			try
			{
				$this->FireEvent("Paid", $this, $payment_module);
			}
			catch(Exception $e)
			{
				// �� ��� ������ �� ������. ��� ���������� � ����� ����������.
				// ���� ���� ����� ����� ���� ����� ������, �� ���������� ���������� ������.
			}
		}
		
		/**
		 * Mark invoice as failed
		 *
		 * @param IPaymentModule $payment_module
		 */
		function MarkAsFailed($payment_module)
		{
			if ($this->Status == INVOICE_STATUS::FAILED)
				throw new ApplicationException(_("Invoice already marked as failed"), E_ERROR);
				
			$this->Status = INVOICE_STATUS::FAILED;
			
			if ($payment_module !== null)
				$this->Notes = $payment_module->GetFailureReason();
			else
				$this->Notes = _("Manual reject by registrar");
				
			$this->Save();
			$this->FireEvent("Failed", $this, $payment_module);
		}
		
		/**
		 * Set OrderID
		 *
		 * @param integer $orderid
		 */
		function SetOrderID($orderid)
		{
			$this->OrderID = $orderid;
		}
		
		/**
		 * Return invoice discount
		 *
		 * @return float
		 */
		function GetDiscount()
		{
			return $this->Discount;
		}
		
		/**
		 * Return invoice VAT in percents
		 *
		 * @return float
		 */
		function GetVATPercent()
		{
			return $this->VATPercent;
		}
		
		/**
		 * Return Invoice VAT
		 *
		 * @return float
		 */
		function GetVAT()
		{
			return $this->VAT;
		}
		
		/**
		 * Set invoice total
		 *
		 * @param float $total
		 */
		function SetTotal($total)
		{
			if (!$this->Total)
			{
				if ($this->VATPercent > 0 && $this->Purpose != INVOICE_PURPOSE::BALANCE_DEPOSIT)
				{
					$this->VAT = round(($total/100*$this->VATPercent), 2);
					$this->Total = $total+$this->VAT;
				}
				else
				{
					$this->VAT = 0;
					$this->VATPercent = 0;
					$this->Total = $total;
				}
			}
			else
				throw new ApplicationException(_("Cannot redeclare already calculated invoice 'Total'"), E_ERROR);
		}
		
		/**
		 * Return Invoice Total calculated in display currency
		 *
		 * @return float
		 */
		function GetTotal()
		{
			return $this->Total;
		}
		
		/**
		 * Return Invoice total calculated in billing currency
		 *
		 */
		function GetBillingTotal ()
		{
			return $this->Total / CONFIG::$CURRENCY_RATE;
		}
		
		/**
		 * Delete Invoice
		 *
		 */
		function Delete()
		{
			$this->DB->Execute("DELETE FROM invoices WHERE id=?", array($this->ID));
		}
		
		/**
		 * Convert Invoice object to Array
		 *
		 * @return array
		 */
		function ToArray()
		{
			return array(
				"ID"		=> $this->ID,
				"UserID"	=> $this->UserID,
				"OrderID"	=> $this->OrderID,
				"Total"		=> $this->Total,
				"VAT"		=> $this->VAT,
				"Discount"  => $this->Discount,
				"Description"	=> $this->Description,
			);
		}
	}
?>