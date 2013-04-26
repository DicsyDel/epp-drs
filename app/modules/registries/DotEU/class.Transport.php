<?php

if (!class_exists("DotBETransport"))
	require_once (MODULES_PATH . "/registries/DotBE/class.Transport.php");

class DotEUTransport extends DotBETransport
{
	protected $ExtPrefix = "eurid";		
	protected $ExtNamespace = "http://www.eurid.eu/xml/epp/eurid-1.0";
	protected $ExtSchemaLocation = "http://www.eurid.eu/xml/epp/eurid-1.0 eurid-1.0.xsd";

	protected function ReadGreeting()
	{
		parent::ReadGreeting();
		unset($this->ExtURIs["secDNS"], $this->ExtURIs["dss"]);
	}
}
?>