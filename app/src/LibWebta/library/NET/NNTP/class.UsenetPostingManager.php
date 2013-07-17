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
     * @package    NET
     * @subpackage NNTP
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */

    /**
     * @name UsenetPostingManager
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class UsenetPostingManager extends Core
    {
        private $DB;

		function __construct()
		{
			$this->DB = Core::GetDBInstance(NULL, false);
		}
		
		private function ReplaceSpecialChars($text)
    	{
    	    return preg_replace("/(=([0-9A-F]){1,1}([0-9A-F]){1,1})/e", "chr(hexdec('\\2'.'\\3'))", $text);
    	}
			
		private function ConvertCharset($text, $headers, $subj, $def_enc, $post_enc)
		{	
			if (CF_ENABLE_ICONV && ICONV_VERSION != '')
			{
				if ($subj == 1)
				{
				    $GLOBALS["encod"] = $post_enc;
					$GLOBALS["def_enc"] = $def_enc;
					
					return charset_decode($text, $headers);
				}
				else
				{
					$cur_charset = $post_enc;
				
					if (CF_ENABLE_ICONV && ICONV_VERSION != '')
					{
						if (strtolower($cur_charset) != 'utf-8' && $cur_charset != '')
							return iconv(strtoupper($cur_charset), "UTF-8", $this->ReplaceSpecialChars($text));
						else
							if ($def_enc)
								return iconv(strtoupper($def_enc), "UTF-8", $this->ReplaceSpecialChars($text));
							else
								return $this->ReplaceSpecialChars($text);
					}
					else
						return $text;
				}
			}
			else
				return $text;
		}
		
		private function CheckTPID ($tpid, $groupid)
		{
			if ($tpid != '')
			{
				$sql = "SELECT id FROM `posts_{$groupid}` WHERE message_id='{$tpid}'";
				$res = $this->DB->GetOne($sql);
				$retval = ($res["id"] != 0) ? true : false;
			}
			else
				$retval = true;
			
			return $retval;
		}
        
		public function CheckMessageId($id, $groupid)
		{
		    $sql = "SELECT id FROM `posts_{$groupid}` WHERE message_id='{$id}'";
		    $res = $this->DB->GetOne($sql);
		    
		    if ($res > 0)
		      return false;
		    else 
		      return true;
		}
		
       public  function StorePost($posting)
       {
			if (($this->CheckTPID($posting->GetTParentId(), $posting->GetGroupId()) || 
				$posting->GetRef() == '')
			   )
			{
				if ($posting->GetMessageId() && $this->CheckMessageId($posting->GetMessageId(), $posting->GetGroupId()))
				{
					$sql = "
						INSERT INTO 
							`posts_{$posting->GetGroupId()}`
						SET
							`message_id` 	= ?,
							`message_pid` 	= ?,
							`message_tpid` 	= ?,
							`refs` 			= ?,						
							`dtposted` 		= ?,
							`subject` 		= ?,
							`from` 			= ?,
							`headers` 		= ?,
							`body`	 		= ?,
							`dtgrabbed` 	= NOW()
					";
	
					$this->DB->Execute($sql, 
											array(
													$posting->GetMessageId(),
													$posting->GetParentId(),
													$posting->GetTParentId(),
													$posting->GetRef(),
													$posting->GetDate(),
													$this->ConvertCharset($posting->GetSubject(), $posting->mHeaders, 1, $posting->GetEncoding(), $posting->GetEnc()),
													$this->ConvertCharset($posting->GetPoster(), $posting->mHeaders, 1, $posting->GetEncoding(), $posting->GetEnc()),
													$posting->GetHeaders(),
													$this->ConvertCharset($posting->GetBody(), $posting->mHeaders, 0, $posting->GetEncoding(), $posting->GetEnc())
												)
										);
					$id = $this->DB->Insert_ID();
				    Log::Log("NNTP: Article #".$posting->GetPostNumber()." (".$posting->GetMessageId().") added to database with ID {$id}.", 1, "NNTPLog");
					return true;
				}
				else 
				{
					Log::Log("NNTP: Skipping Article #".$posting->GetPostNumber().". Empty message ID.", 1, "NNTPLog");
					return false;
				}
			}
			else
			{
				Log::Log("NNTP: Skipping Article #".$posting->GetPostNumber().". No parrent message.", 1, "NNTPLog");
				return false;
			}
		}
    }
?>