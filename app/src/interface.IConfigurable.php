<?php

	/**
	 * @package Modules
	 */
	interface IConfigurable
	{
		/**
	     * Must return a DataForm object that will be used to draw a configuration form.
	     * @return DataForm object
	     */
		public static function GetConfigurationForm();
	}
?>