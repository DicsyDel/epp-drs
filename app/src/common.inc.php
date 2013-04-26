<?	
	function microtime_float() 
	{ 
	   list($usec, $sec) = explode(" ", microtime()); 
	   return ((float)$usec + (float)$sec); 
	} 
	
	function GenerateCode ($num, $mach) {
		
	$let = array('Q', 'q', 'W', 'w', 'E', 'e', 'R', 'r', 'T', 't', 'Y', 'y', 'U', 'u',
			  	 'A', 'a', 'S', 's', 'D', 'd', 'F', 'f', 'G', 'g', 'H', 'h', 'J', 'j', 'K', 'k',
				 'Z', 'z', 'X', 'x', 'C', 'c', 'V', 'v', 'B', 'b', 'N', 'n', 'M', 'm', '0', '1',
				 '2', '3', '4', '5', '6', '7', '8', '9');
				 
	srand((float) microtime() * 10000000);
		for ($i=0;$i<$mach;$i++)
		{
			unset ($code);
			$ids = array_rand ($let, $num);
				foreach ($ids as $val)
					$code .=$let[$val];
			$return[$i]=strtoupper($code);
		}
		return $return;
	}
	
	function _getDaysInterval($startTime, $currTime)
        {
            $sdt = getdate($startTime);
            $cdt = getdate(($currTime) ? $currTime : time());
           
            // calculate days between two dates
            $ndays = 0;
            
            if ($cdt['year'] - $sdt['year'] > 1)
            {
                for ($y = $sdt['year'] + 1; $y < $cdt['year']; $y++)
                {
                    for ($m = 1; $m<=12; $m++)
                    {
                        $ndays += cal_days_in_month(CAL_GREGORIAN, $m, $y);
                    }
                }
            }
            
            if ($cdt['year'] - $sdt['year'] > 0)
            {
                for ($m = $cdt['mon']; $m <= 12; $m++)
                {
                    $ndays += cal_days_in_month(CAL_GREGORIAN, $m, $sdt['year']);
                }
                $ndays -= $sdt['mday'] + 1;
                
                for ($m = 1; $m < $cdt['mon']; $m++)
                {
                    $ndays += cal_days_in_month(CAL_GREGORIAN, $m, $cdt['year']);
                }
                $ndays += $cdt['mday'];
            }
            elseif ($cdt['year'] - $sdt['year'] == 0)
            {
                if ($cdt['mon'] - $sdt['mon'] > 0)
                {
                    for ($m = $sdt['mon']; $m < $cdt['mon']; $m++)
                    {
                        $ndays += cal_days_in_month(CAL_GREGORIAN, $m, $sdt['year']);
                    }
                    
                    $ndays -= $sdt['mday'] + 1;
                    $ndays += $cdt['mday'];
                }
                elseif ($cdt['mon'] - $sdt['mon'] == 0)
                {
                    $ndays = $cdt['mday'] - $sdt['mday'] - 1;
                }
            }
            
            return $ndays;
        }

	
	function CutText ($text, $length, $replace)
    {
        if (strlen($text)>$length)
        {

            $ct=strlen($text)-$length;
            $rest = substr($text, 0, -$ct);
                return $rest.$replace;

        }
        else
        {
                return $text;
        }

    }
	
	function stripstuff($str)
	{
		$retval = ereg_replace("[^a-z0-9._]", "", str_replace(array(" ","%20"), "", strtolower($str)));
		if (strlen($retval) > 25)
			$retval = substr($retval, 0, 25);
			
		return $retval;
	}
?>