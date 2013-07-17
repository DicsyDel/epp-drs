<?

	require_once(dirname(__FILE__) . '/simpletest/scorer.php');
	
	class ShellReporter extends SimpleReporter 
	{
    	
		
		
		function ShellReporter() 
		{
            $this->SimpleReporter();
            
			$this->FontColors =  array(
							            'black'  => "\033[30m",
							            'red'    => "\033[31m",
							            'green'  => "\033[32m",
							            'brown'  => "\033[33m",
							            'blue'   => "\033[34m",
							            'purple' => "\033[35m",
							            'cyan'   => "\033[36m",
							            'grey'   => "\033[37m",
							            'yellow' => "\033[33m",
							            
							            'end'	 => "\033[0m",
						   			);
			$this->FontStyles = array(
							            'normal'     => "\033[0m",
							            'bold'       => "\033[1m",
							            'light'      => "\033[1m",
							            'underscore' => "\033[4m",
							            'underline'  => "\033[4m",
							            'blink'      => "\033[5m",
							            'inverse'    => "\033[6m",
							            'hidden'     => "\033[8m",
							            'concealed'  => "\033[8m",
							            
							            'end'		 => "\033[0m",	
							    	);
			$this->Background = array(
							            'black'  => "\033[40m",
							            'red'    => "\033[41m",
							            'green'  => "\033[42m",
							            'brown'  => "\033[43m",
							            'yellow' => "\033[43m",
							            'blue'   => "\033[44m",
							            'purple' => "\033[45m",
							            'cyan'   => "\033[46m",
							            'grey'   => "\033[47m",
							            
							            'end'	 => "\033[0m",
							    	);
        }
		
        function PrintMessage($message, $newline = true, $fontcolor = false, $fontstyle = false, $background = false)
        {
        	if ($fontcolor)
        		echo $this->FontColors[$fontcolor];
        		
        	if ($fontstyle)
        		echo $this->FontStyles[$fontstyle];
        		
        	if ($background)
        		echo $this->Background[$background];
        		
        	echo $message;
        		
        	if ($fontcolor)
        		echo $this->FontColors['end'];
        		
        	if ($fontstyle)
        		echo $this->FontStyles['end'];
        		
        	if ($background)
        		echo $this->Background['end'];	
        		
        	if ($newline)
        		echo "\n";
        }
        
	    function paintHeader($test_name) 
	    {
	    	$this->PrintMessage($test_name, true, 'black', 'bold', 'grey');
            flush();
	    }
	    
	    function paintFooter($test_name) 
	    {
	    	if ($this->getFailCount() + $this->getExceptionCount() == 0) 
                $this->PrintMessage("OK", true, 'green', 'bold');
            else 
                $this->PrintMessage("FAIL", true, 'red', 'bold');
            
             
            $this->PrintMessage("Test cases run: ", false, 'black', 'bold', 'grey');
            $this->PrintMessage($this->getTestCaseProgress()."/".$this->getTestCaseCount(), false, 'black', 'bold', 'grey');
            $this->PrintMessage(" Passes: ".$this->getPassCount(), false, 'green', 'bold', 'grey');
            $this->PrintMessage(" Failures: ".$this->getFailCount(), false, 'red', 'bold', 'grey');
            $this->PrintMessage(" Exceptions: ".$this->getExceptionCount()." ", true, 'yellow', 'bold', 'grey');
	    }
	    
	    function paintStart($test_name, $size) 
	    {
	        //parent::paintStart($test_name, $size);
	    
	    }
	    
	    function paintEnd($test_name, $size) 
	    {
	        //parent::paintEnd($test_name, $size);
	    
	    }
	    
	    function paintException($message) 
	    {
            parent::paintException($message);
            $breadcrumb = $this->getTestList();
			array_shift($breadcrumb);
			$bc = implode(">", $breadcrumb);

			$this->PrintMessage("Exception: {$bc} -> {$message}", true, 'yellow');
        }
	    
	    function paintPass($message) 
	    {
	        parent::paintPass($message);
	        
			$breadcrumb = $this->getTestList();
			array_shift($breadcrumb);
			$bc = implode(">", $breadcrumb);
			
    		$this->PrintMessage("Pass: {$bc} -> {$message}", true, 'green');
	    }
	    
	    function paintFail($message) 
	    {
	        parent::paintFail($message);
	    	$breadcrumb = $this->getTestList();
			array_shift($breadcrumb);
			$bc = implode(">", $breadcrumb);
			
    		$this->PrintMessage("Fail: {$bc} -> {$message}", true, 'red');
	    }
	}
?>