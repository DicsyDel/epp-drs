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
     * @package    NET_API
     * @subpackage Ventrilo
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     * @filesource
     */

	Core::Load("NET/API/Ventrilo");
	
	/**
	 * @category   LibWebta
     * @package    NET_API
     * @subpackage Ventrilo
     * @name NET_API_Ventrilo_Test
	 *
	 */
	class NET_API_Ventrilo_Test extends UnitTestCase 
	{
        function NET_API_Ventrilo_Test() 
        {
            $this->UnitTestCase('NET/API/Ventrilo test');
        }
        
        function testVentrilo() 
        {
        	$template = '[Server]
						Name={$name}
						Phonetic={$phonetic}
						Comment=0
						Port={$port}
						Auth={$auth}
						Duplicates={$duplicates}
						AdminPassword={$adminpassword}
						Password={$password}
						MaxClients={$maxclients}
						SendBuffer={$sendbuffer}
						RecvBuffer={$recvbuffer}
						Diag={$diag}
						LogonTimeout={$logontimeout}
						CloseStd={$closestd}
						FilterWave=0
						FilterTTS=0
						TimeStamp={$timestamp}
						PingRate={$pingrate}
						ExtraBuffer={$extrabuffer}
						ChanWidth={$chanwidth}
						ChanDepth={$chandepth}
						ChanClients={$chanclients}
						DisableQuit={$disablequit}
						VoiceCodec={$voicecodec}
						VoiceFormat={$voiceformat}
						SilentLobby={$silentlobby}
						#SpamChat=1,1000,2
						#SpamComment=1,1000,2
						#SpamWave=1,1000,2
						#SpamTTS=1,1000,2
						AutoKick={$autokick}
						
						[Intf]
						Intf1={$intf1}
						
						
						[Status]
						# Examples:
						#
						# Intf=0.0.0.0
						# Intf=127.0.0.1
						# Intf=external.mydomain.com
						Password=
						ReplyInterval=50
						
						# FilterGen=127.0.0.1
						# FilterGen=192.168.0.0/16
						# FilterDetail=127.0.0.1
						# FilterDetail=10.0.0.0/8';
        	
        	$Ventrilo = new Ventrilo("xxx.xxx.xxx.xxx", 22, "username", "password", "/home/vent", $template);
			
			// Get codecs list
			$codecs = $Ventrilo->GetCodecs();
			$this->assertTrue(count($codecs) > 0, "Server return list of supported codecs");
			
			// Get servers list
			$servers = $Ventrilo->ListServers();
			$this->assertTrue(count($servers) > 0, "Server return list of virtualhosts");
			
			$ports = array_keys($servers);
			$port = $ports[0];
			
			// get virtualhost
			$VHost = $Ventrilo->GetVhost("3784");
			$this->assertTrue(is_object($VHost), "Virtual host getted");
			
			// Start virtualhost
			$this->assertTrue($VHost->Start(), "Virtual successfully started");
			
			$this->assertTrue($VHost->Stop(), "Virtual successfully stoped");
				
			$this->assertTrue($VHost->Restart(), "Virtual successfully restarted");
			
			$VHost->ChangePort("9767");
        }
        
    }


?>