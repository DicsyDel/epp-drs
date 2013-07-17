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
     * @subpackage VideoUtil
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	require_once(dirname(__FILE__)."/class.VideoUtil.php");	

	/**
	 * @category   LibWebta
     * @package    Graphics
     * @subpackage VideoUtil
     * @name Graphics_VideoUtil_Test
	 *
	 */
	class Graphics_VideoUtil_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('VideoUtil test');
        }
        
        function testMplayer() 
        {

			$VideoUtil = new VideoUtil();
			$load = $VideoUtil->LoadFile(dirname(__FILE__)."/testvideo/2.wmv");
	
			$this->assertTrue($load, "Movie file loaded");
			
			$length = $VideoUtil->GetLength();
			
			$this->assertTrue($length > 0, "Length detected");
			
			$VideoUtil->SetDimensions(100, 120);
			
			$path = ini_get("session.save_path") ? ini_get("session.save_path") : "/tmp";
			
			$path .="/video_thumbs";
			
			$VideoUtil->SetOutputPath($path);
			
			$VideoUtil->Cut(3);
			
			$check = file_exists("{$path}/0.jpg");
			$check &= file_exists("{$path}/1.jpg");
			$check &= file_exists("{$path}/2.jpg");
			
			$this->assertTrue($check, "Thumbnails created");
			
			@unlink("{$path}/0.jpg");
			@unlink("{$path}/1.jpg");
			@unlink("{$path}/2.jpg");
        }
      	
        function testFFMpeg() 
        {
			$VideoUtil = new VideoUtil();
			$load = $VideoUtil->LoadFile(dirname(__FILE__)."/testvideo/2.mpeg");
	
			$this->assertTrue($load, "Movie file loaded");
			
			$length = $VideoUtil->GetLength();
			
			$this->assertTrue($length > 0, "Length detected");
			
			$VideoUtil->SetDimensions(100, 120);
			
			$path = ini_get("session.save_path") ? ini_get("session.save_path") : "/tmp";
			
			$path .="/video_thumbs";
			
			$VideoUtil->SetOutputPath($path);
			
			$VideoUtil->Cut(3);
			
			$check = file_exists("{$path}/0.jpg");
			$check &= file_exists("{$path}/1.jpg");
			$check &= file_exists("{$path}/2.jpg");
			
			$this->assertTrue($check, "Thumbnails created");
			
			@unlink("{$path}/0.jpg");
			@unlink("{$path}/1.jpg");
			@unlink("{$path}/2.jpg");
        }  
    }
?>