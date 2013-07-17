<?php
class EPPNLTransport extends SSLTransport 
{
	private $sidnExt = "http://rxsd.domain-registry.nl/sidn-ext-epp-1.0";
	
	function Request ($command, $data = array())
	{
		$Resp = parent::Request($command, $data);
		if ($Resp->Code != 1000 && $Resp->Code != 1001)
		{
			if (count($this->LastResponse->response->extension))
			{
				$Ext = $this->LastResponse->response->extension->children($this->sidnExt);
				$Ext = $Ext[0];
				foreach ($Ext->response->msg as $msg)
					$Resp->ErrMsg .= ". {$msg}";
					
				$Resp->SidnErrCode = "{$Ext->response->msg->attributes()->code}";
			}
		}
		
		return $Resp;
	}
	
	protected function ParseTemplate($filename, $tags = array())
	{
		if ((strpos($filename, "domain-") !== false || strpos($filename, "host-") !== false) 
			&& ($tags["name"] || $tags["hostname"]))
		{
			if ($tags["name"] && substr($tags["name"], -1) == "*")
				$tags["name"] = substr($tags["name"], 0, -1);
			elseif ($tags["hostname"] && substr($tags["hostname"], -1) == "*")
				$tags["hostname"] = substr($tags["hostname"], 0, -1);
		}
		return parent::ParseTemplate($filename, $tags);
	}

}