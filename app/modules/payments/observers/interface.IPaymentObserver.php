<?
	interface IPaymentObserver
	{
		public function Notify(AbstractPaymentModule $payment_module, $status);
	}

?>