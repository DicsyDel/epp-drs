<?php

	class PHPParser
	{
		/**
		 * Replace PHP start and end tags
		 *
		 * @param string $path_to_file Relative path starting from CONFIG::$PATH. Path traversals allowed. 
		 */
		public static final function StripPHPTags($code, $replace_with='')
		{
			// Strp php open tags
			$retval = preg_replace("/^[^\<]?<\?(php)?/", $replace_with, $code);
					
			// Strip php close tags
			$retval = preg_replace("/\?>$/", $replace_with, $retval);
			
			return $retval;
		}
		
		
		/**
		 * Read and eval() unencoded PHP file
		 *
		 * @param string $path_to_file Relative path starting from CONFIG::$PATH. Path traversals allowed. 
		 */
		public static final function SafeLoadPHPFile($path_to_file)
		{				
			$path = realpath(CONFIG::$PATH."/{$path_to_file}");
			
			self::LoadPHPFile($path);
		}
		
		/**
		 * Read and eval() unencoded PHP file
		 *
		 * @param string $path absolute path to file
		 */
		public static final function LoadPHPFile($path)
		{
			$content = @file_get_contents($path);
			if (!$content)
				throw new Exception(sprintf(_("Failed to load file '%s'. Make sure that file exists and readable"), $path));
			
			// Check if file encoded by zend include it else eval
			if (stristr($content, "<?php @Zend;"))
				include_once($path);
			else 
			{				
				// Strip php open tags
				$content = self::StripPHPTags($content);
				
				if (!Validator::ScanForDebugCode($content) || CONFIG::$DEV_DEBUG)
				{
					// eval module content
					eval($content);
				}
				else 
				{
					throw new Exception(sprintf(_("File %s: invoked code contains disallowed function calls or classes. Please consult EPP-DRS online docs for a full list of disallowed names."), $path), E_ERROR);
				}
				unset($content);
			}
		}
	}
?>