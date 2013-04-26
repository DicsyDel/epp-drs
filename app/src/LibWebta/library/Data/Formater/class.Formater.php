<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    Data
     * @subpackage Formater
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * 
     */
	
    /**
     * @name Formater
     * @category   LibWebta
     * @package    Data
     * @subpackage Formater
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */	
	class Formater extends Core
	{
		/**
		 * Convert bytes to readable string
		 *
		 * @static 
		 * @param int $bytes
		 * @return string
		 */
		static function Bytes2String($bytes)
		{
		    if (!$bytes)
		      $bytes = 0;
		    
			if ($bytes < 1024)
				return "{$bytes} bytes";
			elseif ($bytes >= 1024 && $bytes < 1024*1024)
				return round($bytes/1024, 2)." KB";
			elseif ($bytes >= 1024*1024 && $bytes < 1024*1024*1024)
				return round($bytes/1024/1024, 2)." MB";
			else 
				return round($bytes/1024/1024/1024, 2)." GB";
		}
		
		/**
	     * Fuzzinate a string
	     *
	     * @static 
	     * @return timestamp
	     * @param  string $string
	     */
	    static function Date2Fuzzy($string)
	    {
			$time = strtotime($string);
	        return (self::FuzzyTimeString($time));
	    }
		
	    /**
	     * Compares two dates.
	     *
	     * Returns:
	     *
	     *     < 0 if date1 is less than date2;
	     *     > 0 if date1 is greater than date2;
	     *     0 if they are equal. 
	     *
	     * @static 
	     * @return int
	     * @param  string|timestamp $date1
	     * @param  string|timestamp $date2
	     */
	    static function CompareDates($date1, $date2)
	    {
	    	
	    	if (!is_numeric($date1)) {
	            $date1 = self::TimeString2Stamp($date1);
	        }
	        if (!is_numeric($date2)) {
	            $date2 = self::TimeString2Stamp($date2);
	        }
        
	        if ($date1 < $date2) {
	            return -1;
	        } else if ($date1 > $date2) {
	            return 1;
	        } else {
	            return 0;
	        }
	    }
	    
	     /**
	     * Converts a date/time string to Unix timestamp
	     *
	     * @static 
	     * @return timestamp
	     * @param  string $string
	     */
	    static function TimeString2Stamp($string)
	    {
	        return strtotime($string);
	    }
	    
	    /**
	     * Converts Unix timestamp to a date/time string using format given
	     *
	     * Special options can be passed for the format parameter.  These are
	     * set format types.  The options currently include:
	     *
	     *     o mysql
	     *
	     * If the time parameter isn't supplied, then the current local time
	     * will be used.
	     *
	     * @static 
	     * @return string
	     * @param  integer $time
	     * @param  string  $format
	     */
	    static function TimeStamp2String($time = 0, $format = 'Y-m-d H:i:s')
	    {
	        if ($format == 'mysql') {
	            $format = 'Y-m-d H:i:s';
	        }
	        
	        if ($time == 0) {
	            $time = time();
	        }
	        
	        return date($format, $time);
	    }
	    
	    /**
	     * Converts a Unix timestamp or date/time string to a specific format.
	     *
	     * Special options can be passed for the format parameter.  These are
	     * set format types.  The options currently include:
	     *
	     *     o mysql
	     *
	     * If the time parameter isn't supplied, then the current local time
	     * will be used.
	     *
	     * @static 
	     * @return string
	     * @param  integer|string $time
	     * @param  string         $format
	     * @see    TimeString2Stamp()
	     * @see    TimeStamp2String()
	     */
	    static function TimeFormat($time = 0, $format = 'Y-m-d H:i:s')
	    {
	        if (!is_numeric($time)) {
	            $time = self::TimeStringToStamp($time);
	        }
	        
	        if ($time == 0) {
	            $time = time();
	        }
	
	        return self::TimeStamp2String($time, $format);
	    }
	    
	    /**
	     * Converts a Unix timestamp or date/time string to a human-readable 
	     * format, such as '1 day, 2 hours, 42 mins, and 52 secs'
	     *
	     * Based on the word_time() function from PG+ (http://pgplus.ewtoo.org)
	     *
	     * @static 
	     * @return string
	     * @param  integer|string $time
	     * @see    TimeString2Stamp()
	     */
	    static function Time2HumanReadable($time = 0)
	    {
	        if (!is_numeric($time)) {
	            $time = self::TimeString2Stamp($time);
	        }
	
	        if ($time == 0) {
	            return 'Unknown';
	        } else {
	            if ($time < 0) {
	                $neg = 1;
	                $time = 0 - $time;
	            } else {
	                $neg = 0;
	            }
	    
	            $days = $time / 86400;
	            $days = floor($days);
	            $hrs  = ($time / 3600) % 24;
	            $mins = ($time / 60) % 60;
	            $secs = $time % 60;
	    
	            $timestring = '';
	            if ($neg) {
	                $timestring .= 'negative ';
	            }
	            if ($days) {
	                $timestring .= "$days day" . ($days == 1 ? '' : 's');
	                if ($hrs || $mins || $secs) {
	                    $timestring .= ', ';
	                }
	            }
	            if ($hrs) {
	                $timestring .= "$hrs hour" . ($hrs == 1 ? '' : 's');
	                if ($mins && $secs) {
	                    $timestring .= ', ';
	                }
	                if (($mins && !$secs) || (!$mins && $secs)) {
	                    $timestring .= ' and ';
	                }
	            }
	            if ($mins) {
	                $timestring .= "$mins min" . ($mins == 1 ? '' : 's');
	                if ($mins && $secs) {
	                    $timestring .= ', ';
	                }
	                if ($secs) {
	                    $timestring .= ' and ';
	                }
	            }
	            if ($secs) {
	                $timestring .= "$secs sec" . ($secs == 1 ? '' : 's');
	            }
	            return $timestring;
	        }
	    }
	
	    /**
	     * Give a slightly more fuzzy time string. such as: yesterday at 3:51pm
	     *     
	     *
	     * @static 
	     * @return string
	     * @param  integer|string $time
	     * @param  integer $tz_offset timezone offset
	     * @see    TimeString2Stamp()
	     */
	   static function FuzzyTimeString($time = 0, $tz_offset = null)
	    {
            $time = self::CorrectTime($time, $tz_offset);
	
	        $now = self::CorrectTime(0, $tz_offset);
	        $sodTime = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
	        $sodNow  = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
	        
	        if ($sodNow == $sodTime) {
	            return 'today at ' . date('g:ia', $time); // check 'today'
	        } else if (($sodNow - $sodTime) <= 86400) {
	            return 'yesterday at ' . date('g:ia', $time); // check 'yesterday'
	        } else if (($sodNow - $sodTime) <= 432000) {
	            return date('l \a\\t g:ia', $time); // give a day name if within the last 5 days
	        } else if (date('Y', $now) == date('Y', $time)) {
	            return date('M j \a\\t g:ia', $time); // miss off the year if it's this year
	        } else {
	            return date('M j, Y \a\\t g:ia', $time); // return the date as normal
	        }
	    }
	    
	    
	    /**
	     * Correct time with current timezone offset
	     * 
	     * @static 
	     * @param integer time time to convert
	     * @param float tz_offset timezone offset in hours
	     * 
	     * @return integer time
	     */
	    public static function CorrectTime($time = 0, $tz_offset = null)
	    {
	        if (!is_numeric($time))
	            $time = self::TimeString2Stamp($time);
	        
	        if (!$time)
	        	$time = time();
	        	        	
	        return  (is_null($tz_offset) ? $time : $time - date('Z') + $tz_offset * 3600);
	    }
	    
	    
	    
	    /**
	     * Method for calculating time past till now
	     * 
	     * @static 
	     * @param integer timestamp time
	     * @param float tz_offset timezone offset in hours
	     * @param integer maxdepth depth for time items (years, months, ...)
	     * @param string suffix suffix for formatted string
	     * 
	     * @return string formatted result string
	     */
		public static function TimeAgo($timestamp = 0, $tz_offset = null, $maxdepth = 2, $suffix = "ago", $needtz = true)
		{
			if (!$timestamp) return "never";
			
            $timestamp = self::CorrectTime($timestamp, $needtz ? $tz_offset : null);

			// Store the current time
			$current_time = self::CorrectTime(0, $tz_offset);
			  
			// Determine the difference, between the time now and the timestamp
			$difference = $current_time - $timestamp;
			
			// Set the periods of time
			$periods = array("second", "min", "hr", "day", "week", "month", "year", "decade");
			  
			// Set the number of seconds per period
			$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);
			
			// Determine which period we should use, based on the number of seconds lapsed.
			// If the difference divided by the seconds is more than 1, we use that. Eg 1 year / 1 decade = 0.1, so we move on
			// Go from decades backwards to seconds       
			for ($val = sizeof($lengths) - 1; ($val >= 0) && (($number = $difference / $lengths[$val]) <= 1); $val--);
			
			// Ensure the script has found a match
			if ($val < 0) $val = 0;
			  
			// Determine the minor value, to recurse through
			$new_time = $current_time - ($difference % $lengths[$val]);
			
			// Set the current value to be floored
			$number = floor($number);
			  
			// If required create a plural
			if($number != 1) $periods[$val].= "s";
			  
			// Return text
			$text = sprintf("%d %s ", $number, $periods[$val]);   
			
			// Ensure there is still something to recurse through, and we have not found 1 minute and 0 seconds.
			if (($val >= 1) && (($current_time - $new_time) > 0) && ($maxdepth - 1 > 0)){
				$text .= self::TimeAgo($new_time, $tz_offset, --$maxdepth, "", false);
			}
			          
			return $text . $suffix;
		}
	}
?>