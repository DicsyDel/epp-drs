<?php
	/**
	 * Base methods for building REST-based observer.
	 * @sdk-doconly
	 * @category EPP-DRS
	 * @package Common
	 */

	/**
	 * 
	 * @category EPP-DRS
	 * @package Common
	 *
	 */
	class RESTObserver
	{			
		public function Request($method, $args)
		{
			$args['method'] = $method;			
			$url = $this->Config->GetFieldByName("{$method}URL")->Value;
			if (!$url) return;
						
			try
			{
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$r = curl_exec($ch);				
				curl_close($ch);
			}
			catch(Exception $e){}
		}
	}
?>