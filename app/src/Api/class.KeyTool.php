<?php
class EppDrs_Api_KeyTool
{
	function GenerateKeyPair ()
	{
	    return array(
	    	"key-id" => base64_encode($this->Rand(8)),
	    	"key" => base64_encode($this->Rand(64))
	    );
	}
	
	private function Rand ($len)
	{
		$fp = @fopen ( '/dev/urandom', 'rb' );
		if ($fp !== false) {
			$pr_bits .= @fread ($fp, $len);
			@fclose ($fp);
		} else {
			//If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
			$pr_bits = "";
			for($cnt = 0; $cnt < $len; $cnt ++) {
				$pr_bits .= chr (mt_rand (0, 255));
			}
		}
            
		return $pr_bits;
	} 
}

if (realpath($_SERVER["argv"][0]) == __FILE__)
{
	print "EPP-DRS KeyTool. Generate keys\n";
	
	$key_tool = new EppDrs_Api_KeyTool();
	$keys = $key_tool->GenerateKeyPair();
	
	print "   key-id: {$keys["key-id"]}\n";
	print "   key:    {$keys["key"]}\n\n";
}

?>