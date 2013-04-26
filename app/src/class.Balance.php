<?php

	class Balance 
	{
		public $ID;
		public $ClientID;
		public $Total;
		
		
		/**
		 * @var DBBalance
		 */
		private $DBBalance;
		
		public function __construct()
		{
			$this->DBBalance = DBBalance::GetInstance();
		}
		
		public function CreateOperation ($type, $amount)
		{
			$Ret = new BalanceOperation();
			$Ret->BalanceID = $this->ID;
			$Ret->Type = $type;
			$Ret->InvoiceID = 0;
			$Ret->Description = '';
			$Ret->Amount = $Ret->IsWithdraw() ? -1*abs($amount) : abs($amount);
			if ($Ret->IsWithdraw() && ($this->Total + $Ret->Amount < 0))
				throw new Exception(_("Insufficient balance for this payment"));
			
			return $Ret;
		}
		
		public function ApplyOperation (BalanceOperation $Operation)
		{
			$Operation->Date = time();
			$this->DBBalance->SaveOperation($Operation, $this);
		}
	}
	
	class BalanceOperation 
	{
		public $ID;
		public $BalanceID;
		public $InvoiceID;
		public $Type;
		public $Amount = 0.00;
		public $Date;
		public $Description;

		public function IsWithdraw ()
		{
			return $this->Type == BalanceOperationType::Withdraw;
		}
		
		public function IsDeposit ()
		{
			return $this->Type == BalanceOperationType::Deposit;
		}
	}
	
	class BalanceOperationType
	{
		const Withdraw = "Withdraw";
		const Deposit = "Deposit";
	}

?>