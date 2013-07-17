<?php

	class Debug
	{
		
		/**
		 * Generate execution backtrace
		 *
		 * @return HTML string
		 */
		public static function Backtrace(Exception $e = null)
		{
			if (!is_null($e))
				$backtrace = $e->getTrace();
			else
				$backtrace = debug_backtrace();
				
			foreach ($backtrace as $bt) 
			{
				$args = '';
				foreach ((array)$bt['args'] as $a) 
				{
					if (!empty($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
					case 'integer':
					case 'double':
						$args .= $a;
						break;
					case 'string':
						$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
						$args .= "\"$a\"";
						break;
					case 'array':
						$args .= 'Array('.count($a).')';
						break;
					case 'object':
						$args .= 'Object('.get_class($a).')';
						break;
					case 'resource':
						$args .= 'Resource('.strstr($a, '#').')';
						break;
					case 'boolean':
						$args .= $a ? 'True' : 'False';
						break;
					case 'NULL':
						$args .= 'Null';
						break;
					default:
						$args .= 'Unknown';
					}
				}
				if ($bt['file'])
					$output .= "
								<li>{$bt['file']}:{$bt['line']}
								<br>{$bt['class']}{$bt['type']}{$bt['function']}($args)
								</li>
								";
				else
					$output .= "
								<li>
								<br>{$bt['class']}{$bt['type']}{$bt['function']}($args)
								</li>
								";
			}
			if (!empty($output)) 
				return "<ul class='backtrace'>$output</ul>";
			else 
				return (false);

		}
	}
	
?>