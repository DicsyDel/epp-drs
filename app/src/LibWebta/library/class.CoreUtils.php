<?
	/**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    Core
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

	/**
     * @name CoreUtils
     * @abstract 
     * @category   LibWebta
     * @package    Core
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class CoreUtils extends Core
	{
		/**
		 * Redirects page to $url
		 *
		 * @param string $url
		 * @static 
		 */
		public static function Redirect($url)
		{
			$_SESSION["mess"] = $GLOBALS["mess"];
			$_SESSION["okmsg"] = $GLOBALS["okmsg"];
			$_SESSION["errmsg"] = $GLOBALS["errmsg"];
			$_SESSION["err"] = $GLOBALS["err"];
			
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{
				echo "
				<script type='text/javascript'>
				<!--
				document.location.href='{$url}';
				-->
				</script>
				<meta http-equiv='refresh' content='0;url={$url}'>
				";
	  			exit();
	  			
			} 
			else 
			{
				header("Location: {$url}");
				exit();
			}
		}
		
		/**
		 * Redirect parent URL
		 *
		 * @param string $url
		 * @static 
		 */
		public static function RedirectParent($url)
		{
			echo "
			<script type='text/javascript'>
			<!--
				parent.location.href='{$url}';
			-->
			</script>";
  			die();
		}
		
		/**
		* Submit HTTP post to $url with form fields $fields
		* @access public
		* @param string $url URL to redirect to
		* @param string $fields Form fields
		* @return void
		* @static 
		*/
		public static function RedirectPOST($url, $fields)
		{
			$form = "
			<html>
			<head>
			<script type='text/javascript'>
			function MM_findObj(n, d) { //v4.01
			  var p,i,x;  if(!d) d=document; if((p=n.indexOf('?'))>0&&parent.frames.length) {
				d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
			  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
			  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
			  if(!x && d.getElementById) x=d.getElementById(n); return x;
			}
			</script>
			</head>
			<body>
			<form name='form1' method='post' action='$url'>";
			foreach ($fields as $fk=>$fv)
				$form .= "<input type='hidden' id='$fk' name='$fk' value='$fv'>";
			$form .= "</form>
			<script type='text/javascript'>
			MM_findObj('form1').submit();
			</script>
			</body>
			</html>
			";
			
			die($form);
		}
	}
?>