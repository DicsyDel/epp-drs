<?php
	
	/**
	 * A factory to create Enums (constant lists *doh*)
	 *
	 */
	class EnumFactory
	{
		public static function CookEnumFromArray($class_name, $args)
		{
			$eval_str = "class {$class_name} { ";
			foreach ($args as $k=>$v)
			{
				$k = strtoupper(preg_replace("/[^A-Za-z0-9]+/", "_", $k));
				$v = str_replace("'", "\'", $v);
				$eval_str .= " const {$k} = '{$v}';";
			}
			$eval_str .= " } ";
			
			eval($eval_str);
		}
		
		public static function GetValue($class_name, $key)
		{
			//property_exists
			$ReflectionClassThis = new ReflectionClass($class_name);
			if ($ReflectionClassThis->hasConstant($key))
			{
				return $ReflectionClassThis->getConstant($key);
			}
			else 
			{
				throw new Exception(sprintf(_("Called %s::GetValue('%s') for non-existent property %s"), $class_name, $key, $key));
			}
		}
	}
?>