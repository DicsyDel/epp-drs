<?
     // Load Core Utils
	Core::Load("CoreUtils");

	function addslashesm($string)
	{
		return get_magic_quotes_gpc() ? $string : addslashes($string);
	}
	
	
	/**
	* header() replacement - dealing with windows
	* @access public
	* @param string $url URL to redirect to
	* @return void
	* @deprecated
	*/
	function redirect($url)
	{
		CoreUtils::Redirect($url);
	}
	
	/**
	 * Redirect parent document
	 *
	 * @param string $url
	 * @deprecated
	 */
	function redirectparent($url)
	{
		CoreUtils::RedirectParent($url);
	}


	/**
	* Submit HTTP post to $url with form fields $fields
	* @access public
	* @param string $url URL to redirect to
	* @param string $fields Form fields
	* @return void
	* @deprecated 
	*/
	function redirect_post($url, $fields)
	{
		CoreUtils::RedirectPOST($url, $fields);
	}

	
	function CutText ($text, $length, $replace)
    {
        if (strlen($text)>$length)
        {
            $ct=strlen($text)-$length;
            $rest = substr($text, 0, -$ct);
                return $rest.$replace;
        }
        else
            return $text;

    }
	
	function ifsetor()
	{
		//
	}
	
?>