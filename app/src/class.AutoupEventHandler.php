<?

	class AutoupEventHandler
	{
		 public function OnEvent()
		 {
		 	$args = func_get_args();
		 	$message = $args[1][0];
		 	Log::Log("Autoupdate: {$message}", E_USER_NOTICE);
		 }
	}

?>