<?php

	abstract class OteTestSuite
	{
		private $report = array();
		
		private $passed = true;
		
		/**
		 * Returns true when test suite passed
		 *
		 * @return bool
		 */
		public function Passed ()
		{
			return $this->passed;
		}
		
		/**
		 * Get array of test cases results {boolean passed, string message}
		 *
		 * @return array
		 */
		public function GetReport ()
		{
			return $this->report;
		}
		
		public function AssertTrue ($expr, $message)
		{
			$passed = (bool)$expr;
			$this->passed &= $passed;
			
			$this->report[] = array(
				'passed' => $passed,
				'message' => $message 
			);
		}
		
		public function Fail ($message)
		{
			$this->passed = false;		
			$this->report[] = array(
				'passed' => false,
				'message' => $message 
			);
		}
		
		public function Pass ($message)
		{
			$this->report[] = array(
				'passed' => true,
				'message' => $message
			);
		}
		
		/**
		 * Get suite name
		 *  
		 * @return string
		 */
		abstract function GetName ();
		
		/**
		 * Initialize test envirounment and variables
		 *
		 * @param DataForm $TestConf
		 */
		abstract function SetUp (DataForm $TestConf);
		
		/**
		 * Run suite
		 */
		abstract function Run ();
	}

?>