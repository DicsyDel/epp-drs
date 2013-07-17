<?php

	class DBBalance 
	{
		private $DB;
		
		private static $FieldPropertyMap = array(
			'id' => 'ID',
			'clientid' => 'ClientID',
			'total' => 'Total'
		);
		
		private static $OperationFieldPropertyMap = array(
			'id' 			=> 'ID',
			'balanceid'		=> 'BalanceID',
			'invoiceid'		=> 'InvoiceID',
			'amount'		=> 'Amount',
			'operation_date'=> 'Date',
			'operation_type'=> 'Type',
			'description'	=> 'Description'
		);		
		
		private static $Instance;
		
		protected function __construct()
		{
			$this->DB = Core::GetDBInstance();
		}
		
		/**
		 * @return DBBalance
		 */
		public static function GetInstance ()
		{
			if (self::$Instance === null)
				self::$Instance = new DBBalance();
			return self::$Instance;
		}		
		
		public function LoadClientBalance ($clientid)
		{
			$row = $this->DB->GetRow("SELECT * FROM balance WHERE clientid = ?", array($clientid));
			if (!$row)
				throw new Exception(sprintf("Balance for client ID=%s not found", $clientid));
			$row['total'] = round($row['total'], 2);
			$Balance = new Balance();
			foreach (self::$FieldPropertyMap as $name => $prop)
				$Balance->{$prop} = $row[$name];
			return $Balance;
		}
		
		public function LoadBalanceHistory ($query)
		{
			$rows = (array)$this->DB->GetAll($query);
			
			$ret = array();
			foreach ($rows as $row)
			{
				$Operation = new BalanceOperation();
				foreach (self::$OperationFieldPropertyMap as $name => $prop)
					$Operation->{$prop} = $row[$name];
				$Operation->Date = strtotime($Operation->Date);
				
				$ret[] = $Operation;
			}
			
			return $ret;
		}
		
		public function SaveOperation (BalanceOperation $Operation)
		{
			$row = array();
			foreach (self::$OperationFieldPropertyMap as $name => $prop)
				if ($Operation->{$prop} !== null)
					$row[$name] = $Operation->{$prop};
			if ($row["operation_date"]) $row["operation_date"] = date("Y-m-d H:i:s", $row["operation_date"]);
			
			// Prepare SQL statement			
			unset($row['id']);
			$set = array();
			$bind = array();
			foreach ($row as $field => $value) 
			{
				$set[] = "`$field` = ?";
				$bind[] = $value;
			}
			$set = join(', ', $set);			
			
			$this->DB->StartTrans();
			try 
			{
				if ($Operation->ID)
				{
					// Perform Update
					$bind[] = $Operation->ID;
					$this->DB->Execute("UPDATE balance_history SET $set WHERE id = ?", $bind);				
				}
				else
				{
					// Perform Insert
					$this->DB->Execute("INSERT INTO balance_history SET $set", $bind);
					$Operation->ID = $this->DB->Insert_ID();
					$this->DB->Execute(
						"UPDATE balance SET `total` = (SELECT SUM(amount) FROM balance_history WHERE balanceid = ?) WHERE id = ?", 
						array($Operation->BalanceID, $Operation->BalanceID)
					);
				}
			}
			catch (Exception $e)
			{
				$this->DB->RollbackTrans();
				throw new ApplicationException($e->getMessage(), $e->getCode());
			}
			$this->DB->CompleteTrans();
			
			return $Operation;
		}
	}

?>