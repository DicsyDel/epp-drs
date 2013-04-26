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
     * @package    Graphics
     * @subpackage Captcha
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("Graphics/Captcha");	
    
	/**
	 * @category   LibWebta
     * @package    Graphics
     * @subpackage Captcha
     * @name Graphics_Captcha_Test
	 *
	 */
	class Graphics_Captcha_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Graphics/CAPTCHA test');
        }
        
        function testGraphics_Captcha_Captcha() 
        {
			$options["length"] = 6;
			
			$CAPTCHA = new Captcha($options);

			$string = $CAPTCHA->GetPhrase();
			
			$this->assertTrue($string, "CAPTCHA Phrase generated");
			
			$png = $CAPTCHA->GetImage();
			$this->assertTrue($png, "CAPTCHA Image generated");
			
        }
    }

?>