<?php
	
	class Application
	{
		private static $Observers = array();
		
		public static function AttachObserver (IGlobalObserver $observer, 
				$phace = EVENT_HANDLER_PHACE::SYSTEM)
		{
			if (!array_key_exists($phace, self::$Observers))
			{
				self::$Observers[$phace] = array();
			}
			
			if (array_search($observer, self::$Observers[$phace]) !== false)
				throw new Exception(_('Observer already attached to class <Application>'));
				
			self::$Observers[$phace][] = $observer;			
		}

		/*
		public static function DetachObserver (IGlobalObserver $observer, $is_owned_by_user = false)
		{
			if (!$is_owned_by_user)
			{
				if (($i = array_search($observer, self::$Observers)) === false)
					throw new Exception(_('Observer not attached to class <Application>'));
				
				array_splice(self::$Observers, $i, 1);
			}
			else
			{
				if (($i = array_search($observer, self::$UserObservers)) === false)
					throw new Exception(_('Observer not attached to class <Application>'));
				
				array_splice(self::$UserObservers, $i, 1);
			}
		}
		*/
		
		public static function FireEvent ($event_name /* args1, args2 ... argN */)
		{
			try
			{
				$args = func_get_args();
				array_shift($args); // First argument is event name
				
				Log::Log(sprintf('Fire %s', $event_name), E_USER_NOTICE);
						
				foreach (EVENT_HANDLER_PHACE::GetKeys() as $phace)
				{
					if (array_key_exists($phace, self::$Observers))
					{
						foreach (self::$Observers[$phace] as $observer)
						{
							Log::Log(sprintf("Execute %s:On%s", get_class($observer), $event_name), E_USER_NOTICE);
							call_user_func_array(array($observer, "On{$event_name}"), $args);
						}
					}				
				}				
			}
			catch(Exception $e)
			{
				Log::Log(sprintf("Application::FireEvent thrown exception: %s, file: %s", $e->getMessage(), $e->getFile()), E_ERROR);
			}
				
			return;
		}
	}
?>