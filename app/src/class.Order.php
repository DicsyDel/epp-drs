<?php
	
	/**
	 * @category EPP-DRS 
	 * @package Invoicing
	 * @sdk-doconly
	 */
	class Order
	{
		private $Invoices;
		public $ID;
		public $Description;
		public $UserID;
		
		function __construct($userid = null)
		{
			$this->DB = Core::GetDBInstance();
			$this->UserID = $userid;
		}
		
		/**
		 * Save order in database
		 *
		 * @return Order
		 */
		public function Save()
		{
			if (!$this->ID)
			{
				$this->DB->Execute("INSERT INTO orders SET userid=?", array($this->UserID));
				$this->ID = $this->DB->Insert_ID();
			}
			else
				$this->DB->Execute("UPDATE invoices SET orderid=NULL WHERE orderid=?", array($this->ID));
			
			$this->Description = sprintf(_("%s Order #%s"), CONFIG::$COMPANY_NAME, $this->ID);

			if (count($this->Invoices) == 0)
				throw new ApplicationException(_("Empty order"), E_USER_ERROR);
			
			foreach ($this->Invoices as $Invoice)
			{
				$Invoice->SetOrderID($this->ID);
				$Invoice->Save();
			}
			
			return $this;
		}
		
		function MarkAsPaid($payment_module)
		{
			foreach ($this->Invoices as &$invoice)
				$invoice->MarkAsPaid($payment_module);
		}
		
		function MarkAsFailed($payment_module)
		{
			foreach ($this->Invoices as &$invoice)
				$invoice->MarkAsFailed($payment_module);
		}
		
		/**
		 * Load Order from database
		 *
		 * @param integer $id
		 * @return Order
		 */
		public static function Load($id)
		{
			$db = Core::GetDBInstance();
			
			$dborder = $db->GetRow("SELECT * FROM orders WHERE id=?", array($id));
			if (!$dborder)
				throw new ApplicationException(sprintf(_("Order with id = '%s' not found in database"), $id));
				
			$Order = new Order($dborder["userid"]);
			$Order->Description = sprintf(_("%s Order #%s"), CONFIG::$COMPANY_NAME, $id);
			$Order->ID = $id;
			$invoices = $db->Execute("SELECT id FROM invoices WHERE orderid=?", array($id));
			while($invoice = $invoices->FetchRow())
				$Order->Invoices[] = Invoice::Load($invoice['id']);
		
			return $Order;
		}
		
		function AddInvoice(Invoice $Invoice)
		{
			if (!($Invoice instanceof Invoice))
				throw new ApplicationException(sprintf(_("\$invoice must be instance of Invoice class")));
			
			if ($Invoice->UserID != $this->UserID)
				throw new ApplicationException(sprintf(_("Order userid != Invoice userid")));

			$Invoice->SetOrderID($this->ID);				
			$this->Invoices[] = $Invoice;
		}
		
		function GetInvoiceList()
		{
			return $this->Invoices;
		}
		
		function GetTotal ()
		{
			$sum = 0;
			foreach ($this->Invoices as $Invoice)
				$sum += $Invoice->GetTotal();
				
			return $sum;
		}
		
		function GetBillingTotal ()
		{
			return $this->GetTotal() / CONFIG::$CURRENCY_RATE;
		}
		
		function GetVAT ()
		{
			$sum = 0;
			foreach ($this->Invoices as $Invoice)
				$sum += $Invoice->GetVAT();
			
			return $sum;
		}
		
		function GetDiscount ()
		{
			$sum = 0;
			foreach ($this->Invoices as $Invoice)
				$sum += $Invoice->GetDiscount();
				
			return $sum;
		}
	}
?>