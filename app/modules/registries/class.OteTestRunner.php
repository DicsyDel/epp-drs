<?php

	class OteTestRunner
	{
		function Run (OteTestSuite $Test, DataForm $DF)
		{
			$name_upper = strtoupper($Test->GetName());
			$name_lower = strtolower($Test->GetName());
			
			$filename = sprintf('/tmp/eppdrs-%s-certtest-%s.log', $name_lower, date('YmdHis')); 
		    Log::RegisterLogger("File", $name_upper, $filename);
			Log::SetDefaultLogger($name_upper);			
			
			// Run test
			$Test->SetUp($DF);
			try
			{
				$Test->Run();
			}
			catch (Exception $Error) {}
			
			// Check passed
			$passed = $Test->Passed() && (!isset($Error));
			
			// Write output file
			$out_filename = sprintf("eppdrs-%s-certtest-%s.log", $name_lower, $passed ? 'passed' : 'failed');
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $out_filename . '"');
			
			foreach ($Test->GetReport() as $i => $item)
			{
				$n = $i+1;
				print str_pad("{$n}. {$item['message']}", 60, ' ', STR_PAD_RIGHT);
				printf("[%s]\n", $item['passed'] ? 'OK' : 'FAIL');
			}
			if (isset($Error))
			{
				print "[Exception] {$Error->getMessage()} at {$Error->getFile()} line {$Error->getLine()}\n";
			}
			
			print "\n\n";
			
			// Append system log to output
			print file_get_contents($filename);
			unlink($filename);
			
			die();			
		}
	}

?>