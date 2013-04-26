<?
	/**
	 * Smarty plugin
	 * 
	 * File: resource.string.php
	 * Type: Resource
	 * Name: string
	 * Goal: Get template from string
	 *
	 */
	function smarty_resource_string_source($tpl_name, &$tpl_source, &$smarty_obj)
	{
		$tpl_source = $tpl_name;
		
		return true;
	}
	
	function smarty_resource_string_template($tpl_name, &$tpl_source, &$smarty_obj)
	{
		$tpl_source = $tpl_name;
		
		return true;
	}
	
	function smarty_resource_string_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj)
	{
		return time();
	}
	
	function smarty_resource_string_secure($tpl_name, &$smarty_obj)
	{
		return true;
	}
	
	function smarty_resource_string_trusted($tpl_name, &$smarty_obj)
	{
		// Nothing to do
	}

?>