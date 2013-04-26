<?php
	
	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Modules
     * @subpackage PaymentModules
     * @sdk
     */

	/**
	 * Base class to be extended by all registry modules.
	 * If you override any methods, its a good idea to call a parent::__construct() in your code, to be sure that all needed properties are set.
	 * @name AbstractRegistryModule
	 * @package Modules
     * @subpackage PaymentModules
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	abstract class AbstractPaymentModule extends Module
	{
		private $Observers = array();
		
		public $FailureReason;
		
		public static function GetDBInstance()
		{
			return Core::GetDBInstance();
		}
		
		
		public function AttachObserver(IPaymentObserver $obj, 
				$phace = EVENT_HANDLER_PHACE::SYSTEM)
		{
			
			if (!array_key_exists($phace, $this->Observers))
			{
				$this->Observers[$phace] = array();
			}
			
			if (array_search($obj, $this->Observers[$phace]) !== false)
				throw new Exception(_('Observer already attached to payment module'));
				
			$this->Observers[$phace][] = $obj;			
		}
		
		/*
		public function DetachObserver(IPaymentObserver $obj, $is_owned_by_user = false) 
		{
        	if (!$is_owned_by_user)
			{
				if (($i = array_search($obj, $this->Observers)) === false)
					throw new Exception(_('Observer not attached to payment module'));
				
				array_splice($this->Observers, $i, 1);
			}
			else
			{
				if (($i = array_search($obj, $this->UserObservers)) === false)
					throw new Exception(_('Observer not attached to payment module'));
				
				array_splice($this->UserObservers, $i, 1);
			}
	    }
	    */
	
	    public function NotifyObservers($payment_status) 
	    {
	    	foreach (EVENT_HANDLER_PHACE::GetKeys() as $phace)
			{
				if (array_key_exists($phace, $this->Observers))
				{
					foreach ($this->Observers[$phace] as $observer)
					{
						$observer->Notify($this, $payment_status);
					}
				}				
			}	    	
	    }
	    
	    public function SetFailureReason($failure_reason)
	    {
	    	$this->FailureReason = $failure_reason;
	    }
	}	
?>