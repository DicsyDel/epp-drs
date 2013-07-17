<?php

	class Observable
	{
		private $event_listeners = array();
		
		private $events_suspended = false;
		
		public function DefineEvents ($a) 
		{
			foreach ($a as $event) {
				if (!array_key_exists($event, $this->event_listeners)) {
					$this->event_listeners[$event] = array();
				}
			}
		}
		
		public function Fire ($event /** args */) 
		{
			if (!$this->events_suspended) {
				$listeners = $this->event_listeners[$event];
				if ($listeners) {
					$args = array_slice(func_get_args(), 1);
					foreach ($listeners as $callback) {
						$r = call_user_func_array($callback, $args);
						if ($r === false) {
							return false;
						}
					}
				}
			}
			return true;
		}
		
		
		/**
		 * Add event listener
		 * 
		 * Usage:
		 * 1.
		 * @param string $event
		 * @param callback $callback
		 * 
		 * 2.
		 * @param object $Listener
		 */
		public function AddListener ()	
		{
			$args = func_get_args();
			if (is_object($args[0])) {
				return $this->AddObjectListener($args[0]);
			}
			else if (is_string($args[0]) && is_callable($args[1])) {
				return $this->AddFnListener($args[0], $args[1]);
			}
			
			throw new Exception('Invalid arguments');
		}
		
		/**
		 * Add event listening function
		 *
		 * @param string $event
		 * @param string/array $callback
		 */
		private function AddFnListener ($event, $callback)
		{
			if (!array_key_exists($event, $this->event_listeners)) {
				throw new Exception("Event '$event' is not defined");
			}
			
			if (!is_callable($callback)) {
				throw new Exception("Listener is not callable");
			}
			
			$this->event_listeners[$event][] = $callback;
			
			return true;
		}
		
		/**
		 * Add event listener object
		 *
		 * @param $object
		 */
		private function AddObjectListener ($Listener)
		{
			$methods = get_class_methods($Listener);
			foreach (array_keys($this->event_listeners) as $event) {
				$method = 'On' . $event;
				if (in_array($method, $methods)) {
					$this->event_listeners[$event][] = array($Listener, $method); 
				}
			}
			
			return true;
		}
		
		/**
		 * Remove event listener
		 * 
		 * Usage:
		 * 1.
		 * @param string $event
		 * @param callback $callback
		 * 
		 * 2.
		 * @param object $Listener
		 */
		public function RemoveListener () 
		{
			$args = func_get_args();
			if (is_object($args[0])) {
				return $this->RemoveObjectListener($args[0]);
			}
			else if (is_string($args[0]) && is_callable($args[1])) {
				return $this->RemoveFnListener($args[0], $args[1]);
			}
			
			throw new Exception('Invalid arguments');
		}

		/**
		 * Remove event listener function
		 *
		 * @param string $event
		 * @param string/array $callback
		 */
		private function RemoveFnListener ($event, $callback)
		{
			if (!array_key_exists($event, $this->event_listeners)) {
				throw new Exception("Event '$event' is not defined");
			}
	
			if (false !== ($k = array_search($callback, $this->event_listeners[$event]))) {
				unset($this->event_listeners[$event][$k]);
				return true;
			}
			
			return false;
		}
		
		private function RemoveObjectListener ($Listener)
		{
			$methods = get_class_methods($Listener);
			foreach (array_keys($this->event_listeners) as $event) {
				$method = 'On' . $event;
				if (in_array($method, $methods)) {
					$callback = array($Listener, $method);
					if (false != ($k = array_search($callback, $this->event_listeners[$event]))) {
						unset($this->event_listeners[$k]);
					}
				}
			}
			
			return true;			
		}
		
		public function RemoveAllListeners () 
		{
			foreach (array_keys($this->event_listeners) as $event) {
				$this->event_listeners[$event] = array();
			}
		}
		
		public function ResumeEvents () 
		{
			$this->events_suspended = false;
		}
		
		public function SuspendEvents () 
		{
			$this->events_suspended = true;
		}
		
		/**
		 * Alias for AddListener
		 */
		public function On () 
		{
			$args = func_get_args();
			return call_user_func_array(array($this, 'AddListener'), $args);
		} 
		
		/**
		 * Alias for RemoveListener
		 */
		public function Un () 
		{
			return call_user_func_array(array($this, 'RemoveListener'), func_get_args());
		}
		
	}

?>