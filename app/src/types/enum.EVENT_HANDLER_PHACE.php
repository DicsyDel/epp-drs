<?php

final class EVENT_HANDLER_PHACE
{
	const BEFORE_SYSTEM = "BEFORE_SYSTEM";	
	
	const SYSTEM = "SYSTEM";
	
	const AFTER_SYSTEM = "AFTER_SYSTEM";
		
	static function GetKeys()
	{
		$ref = new ReflectionClass(__CLASS__);
		return array_keys($ref->getConstants());
	}
}