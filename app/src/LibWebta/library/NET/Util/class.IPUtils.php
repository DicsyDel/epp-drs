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
     * @package    NET
     * @subpackage Util
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */


    /**
	 * @name IPUtils
	 * @package NET
	 * @subpackage Util
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */
	class IPUtils extends Core 
	{
	    /**
	     * Validator instance
	     *
	     * @var Validator
	     * @access private
	     */
	    private $Validator;
	    
	    /**
	     * IPUtils constructor
	     * @ignore
	     *
	     */
        function __construct()
        {
            $this->Validator = Core::GetInstance("Validator");
        }
	    
        /**
         * Imask
         *
         * @param string $val
         * @access private
         * @return integer
         */
		private function Imask($val)
		{
			// use base_convert not dechex because dechex is broken and returns 0x80000000 instead of 0xffffffff
			return base_convert((pow(2,32) - pow(2, (32-$val))), 10, 16);
		}
		
		/**
		 * ImaxBlock
		 *
		 * @param integer $ibase
		 * @param integer $tbit
		 * @access private
		 * @return integer
		 */
		private function ImaxBlock($ibase, $tbit)
		{
			while ($tbit > 0)
			{
				$im = hexdec($this->Imask($tbit-1));
				$imand = $ibase & $im;
				if ($imand != $ibase)
				{
					break;
				}
				$tbit--;
			}
			return $tbit;
		}

		/**
		* Converts IP range
		* @access public
		* @param string $startip 
		* @param string $endip 
		* @return string
		*/
		public final function IPRange2CIDR($startip, $endip) 
		{
			// this function returns an array of cidr lists that map the range given
			$s = explode(".", $istart);
			// PHP ip2long does not handle leading zeros on IP addresses! 
			// 172.016 comes back as 172.14, seems to be treated as octal!
			$start = "";
			$dot = "";
			while (list($key,$val) = each($s))
			{
				$start = sprintf("%s%s%d",$start,$dot,$val);
				$dot = ".";
			}
			$end = "";
			$dot = "";
			$e = explode(".",$iend);
			while (list($key,$val) = each($e))
			{
				$end = sprintf("%s%s%d",$end,$dot,$val);
				$dot = ".";
			}
			
			$start = ip2long($start);
			$end = ip2long($end);
			$result = array();
			while ($end > $start)
			{
				$maxsize = $this->ImaxBlock($start,32);
				$x = log($end - $start + 1)/log(2);
				$maxdiff = floor(32 - floor($x));
				$ip = long2ip($start);

				if ($maxsize < $maxdiff)
					$maxsize = $maxdiff;

				array_push($result,"$ip/$maxsize");
				$start += pow(2, (32-$maxsize));
			}
			return $result;	
		}



		/**
		* Converts CIDR range to IPS array
		* @access public
		* @param string $ip IP range in CIDR notation
		* @return array
		*/
		public final function CIDR2List($ip) 
		{
			// validate IP address
			$num="([0-9]|1?\d\d|2[0-4]\d|25[0-5])";
			$range="([1-9]|1\d|2\d|3[0-2])";

			if(!preg_match("/^$num\.$num\.$num\.$num\/$range$/",$ip))
					return false;

			// Separate CIDR structure into network-IP and netmask
			$ip_arr = explode("/",$ip);

			// Calculate number of hosts in the subnet
			$mask_bits  = $ip_arr[1];
			
			if($mask_bits > 31 || $mask_bits < 0) 
				Core::RaiseError(_("Nonsense mask"));
				
			$host_bits  = 32-$mask_bits;
			$num_hosts  = pow(2,$host_bits)-1;

			// Netmask in decimal for use later: (hack around PHP always using signed ints)
			$netmask    = ip2long("255.255.255.255")-$num_hosts;

			// Calculate start and end
			// Store IP-addresses internally as longs, to ease compare of two
			// addresses in a sorted structure.
			$ip_start  = ip2long($ip_arr[0]);
			if($ip_start != ( $ip_start & $netmask ))
				Core::RaiseWarning(_("Address $ip not on network boundary"));
				
			$ip_end    = $ip_start + $num_hosts;

			for($i=0;$i<=$num_hosts;$i++)
				$ip_range[]=long2ip($ip_start+$i);

			return $ip_range;
		}


		/**
		* Converts IPs range in x.x.x.x-x notation to array of IPs
		* @access public
		* @param string $iprange 
		* @return array
		*/
		public final  function IPRange2List($iprange) 
		{
			$quads = explode(".", $iprange);
			list($start, $end) = explode("-", $quads[3]);
			$static = implode(".", array_slice($quads, 0, 3));
			foreach (range($start, $end) as $v)
				$retval[] = "{$static}.{$v}";
			return $retval;
		}
        
		/**
		 * Convert netbits to subnet mask
		 *
		 * @param int $bits
		 * @return string
		 */
        public final function Bits2SubnetMask($bits)
        {
            $bits = (int)$bits;
            
            if ($bits < 1 || $bits > 32)
                return false;
            
            $string = str_repeat("1", $bits).str_repeat("0", 32-$bits);
            foreach (str_split($string, 8) as $chunk)
                $retval .= bindec($chunk).".";
            
            return trim($retval, ".");
        }
        
        /**
         * Convert Subnet mask 2 net bits
         *
         * @param string $mask
         * @return int
         */
        public final function SubnetMask2Bits($mask)
        {
            if (!$this->Validator->IsIPAddress($mask))
                return false;
                        
            $binary = $this->IP2bin($mask);
           
            return substr_count($binary, "1");
        }
        
        /**
         * Convert IP to binary string
         *
         * @param string $ip Valid IP address
         * @return string Ip address in binary format
         */
        public final function IP2bin($ip)
        {
            $chunks = explode(".", $ip);
            $binaries = array_map("decbin", $chunks);
            foreach($binaries as &$bin)
                $bin = str_repeat("0", 8-strlen($bin)).$bin;  
            
            $str = implode("", $binaries);
            $str = str_repeat("0", 32-strlen($str)).$str;
            return $str;
        }
        
         /**
         * Convert binary 2 IP string
         *
         * @param string $bin IP address in binary format
         * @return string Normal IP address
         */
        public final function Bin2IP($bin)
        {
				// Convert binary IP to 32bit format
            $bin = str_repeat("0", 32-strlen($bin)).$bin; 
            
				// Split string to 8 bit chunks and convert to dec
            foreach (str_split($bin, 8) as $chunk)
                $retval .= bindec($chunk).".";
            
            return trim($retval, "."); 
        }
        
        
        /**
         * Split subnets into a few smaller subnets
         *
         * @param string $network_address Netword address
         * @param integer $length Network length
         * @param integer $needed_length Desired network length
         * @return array Subnets array with CIDR as key and array of adresses as a value
         */
        public final function SplitSubnet($network_address, $length, $needed_length)
        {
            $retval = array();

				// Go thru the length of net up to $needed_length, splitting each into 2 subnets
            for ($i = $length+1; $i <= $needed_length; $i++)
            {
					  // Get ubnets for $network_address with /$i lenght
                $retval[$i] = $this->GetSubnets($network_address, $i-1);
                
				       // Net address to be splitted is a last result of preious split
                if ($i != $needed_length)
                    $network_address = array_pop($retval[$i]);
            }
            
            unset($retval[$length]);
            
            return $retval;
        }
        
        /**
         * Return information about network
         *
         * @param string $ipaddress
         * @param string $mask
         * @return array
         */
        public final function GetNetInfo($ipaddress, $mask)
        {
            $retval = array();
            
            $ipaddress_bin = $this->IP2bin($ipaddress);
            $mask_bin = $this->IP2bin($mask);
            
            // Get network address
            $network_address_bin = ($ipaddress_bin & $mask_bin);            
            $retval["network_address"] = $this->Bin2IP($network_address_bin);
            
            // Get broadcast address
            $CIDR = $this->SubnetMask2Bits($mask);
            $bcast_mask = str_repeat("0", 32-(32-$CIDR)).str_repeat("1", 32-$CIDR);
            $bcast_address_bin = ($network_address_bin | $bcast_mask);
            $bcast_address = ($network_address_bin | $bcast_address_bin);
            $retval["broadcast_address"] = $this->Bin2IP($bcast_address);
            
            // Get gateway address
            $long = ip2long($retval["network_address"])+1;
            $retval["gateway"] = long2ip($long);
            
            $retval["hostmin"] = $retval["gateway"];
            $retval["hostmax"] = long2ip((ip2long($retval["broadcast_address"])-1));
            
            $retval["network_mask"] = $mask;
            
            $retval["CIDR"] = $this->SubnetMask2Bits($mask);
            
            $retval["hosts"] = $this->GetIPCountByNetmask($mask)-2;
            
            return $retval;
        }
        
        /**
         * Get all avaiable subnets from $network_address with CIDR $CIDR
         *
         * @param string $network_address Network address to split
         * @param integer $CIDR Desired net length
         * @return array Array of two subnets
         */
        private final function GetSubnets($network_address, $CIDR)
        {
				// Get subnet mask in binary format for net with $CIDR+1 length
            $nmask = $this->IP2bin($this->Bits2SubnetMask($CIDR+1));
            
				// Get bcast address for given net length in binary format
            $bcast_mask = str_repeat("0", 32-(32-$CIDR)).str_repeat("1", 32-$CIDR);
            
				// Get network adress in binary format
            $binaddr = $this->IP2bin($network_address);
            
				// This is a 1st network address
            $retval[] = $network_address;

				// Bitwise sum of 1st network address and broadcast address for the given net length gives us binary bcast address for last network
            $bcast_first_network = ($binaddr | $bcast_mask);
            
				// Bitwise multiply broadcast address of 1st network and subnet mask for desired network and receive an address of 2nd network for given net length.
            $bin_second_network = ($bcast_first_network & $nmask);
            
				// Convert network format from binary to human-readable
            $retval[] = $this->Bin2IP($bin_second_network);
            
            return $retval;
        }
        
        /**
         * Count number of IP by Network Mask
         *
         * @param string $netmask
         * @return integer
         */
        public final function GetIPCountByNetmask($netmask)
        {
            $bits = $this->SubnetMask2Bits($netmask); 
            $n = 32-$bits;
            
            return pow(2, $n);
        }
	}
?>