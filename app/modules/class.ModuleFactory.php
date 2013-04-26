<?php
	
	/**
	 * Base class for modules factory
	 * 
	 * @package Modules
	 */
	class ModuleFactory
	{
		/**
		 * Path to Drivers directory
		 *
		 * @var string
		 */
		protected $ModulesPath;
		
		/**
		 * Module list
		 *
		 * @var array
		 */
		public $Modules;
		
		public function ListModules()
		{
			return $this->Modules;
		}
		
		/**
		 * Set modules directory
		 *
		 * @param string $path
		 */
		public function SetModulesDirectory($path)
		{
			$this->ModulesPath = $path;
		}
		
		
		public final static function Load($path, $name)
		{
			if (class_exists($name)) 
				return true;
			
			PHPParser::LoadPHPFile($path);
				
			if (!class_exists($name))
				throw new ApplicationException(sprintf(_("%s must contain %s class."), $path, $name), E_ERROR);
				
			return true;
		}
	}
?>