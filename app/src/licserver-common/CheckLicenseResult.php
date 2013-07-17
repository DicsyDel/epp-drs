<?php

class CheckLicenseResult
{
	public $valid;
	public $expire_date;
	public $message;
	
	function ToXml ()
	{
		$doc = new SimpleXMLElement("<LIBXML_NOXMLDECL-not-works/>");
		$element = $doc->addChild("CheckLicenseResult");
		$element->addChild("valid", (int)$this->valid);
		$element->addChild("expire_date", $this->expire_date ? date("Y-m-d H:i", $this->expire_date) : null);
		$element->addChild("message", $this->message);
		
		return $element;
	}
	
	function FromXml (SimpleXMLElement $element)
	{
		$this->valid = (bool)(int)$element->valid;
		if ((string)$element->expire_date)
			$this->expire_date = strtotime((string)$element->expire_date);
		else
			$this->expire_date = null;
		$this->message = (string)$element->message; 
	}
}

?>