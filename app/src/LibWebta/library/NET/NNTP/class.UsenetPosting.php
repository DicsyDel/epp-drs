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
     * @name UsenetPosting
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class UsenetPosting
    {
        public $mPostNumber;
        public $mMessageId;
        public $mGroupId;
        public  $mParentId;
        public  $mGroupName;
        public  $mSize;
        public  $mHeaders = array();
        public  $mBody;

        function __construct($header, $body, $groupId, $groupName, $number, $enc)
        {
			$unparsed = $header;
			$header = $this->ParseHeaders($header);
		
            $this->mGroupId     = $groupId;
            $this->mGroupName   = $groupName;
            $this->mHeaders     = $header;
			$this->mUnparsed    = $unparsed;
            $this->mBody        = $body;
            $this->mPostNumber  = $number;
			$this->mEnc  		= $enc;

            $this->mSize        = strlen($this->mBody);
            $this->mMessageId   = $header['message-id'];

            if ($header['references'])
            {
				$tmp = explode(' ', $header['references']);
				$this->mTParentId = $tmp[0];
			    $this->mParentId = $tmp[sizeof($tmp)-1];
            }
        }

		private function ParseHeaders ($headers)
		{
			$hdrs = array();
			foreach((array)$headers as $k=>$v)
			{
				$tmp = explode(":", $v);
				
				if (count($tmp) == 1)
				    $hdrs[$key] .= trim($v);
				elseif (count($tmp) > 1)
				{
				    $key = strtolower($tmp[0]);
				
				    array_shift($tmp);
				
				    $value = trim(implode(":", $tmp));
				    $hdrs[$key] = $value;
				}
			}
			
			return $hdrs;
		}
		
		public function GetEncoding()
		{
			return $this->mEnc;
		}
		
        public function GetSubject()
        {
            return $this->mHeaders['subject'];
        }
			
		public function GetEnc()
		{
			$encod = false;
			if (!$this->mHeaders["content-charset"] || strlen($this->mHeaders["content-charset"]) < 3)
			{
				preg_match_all("(charset=(['\"]*)([A-Za-z0-9-]*)(['\"]*))",  @implode("\n",$this->mUnparsed), $matches);
				
				if ($matches[2][0])
					$cur_charset = $matches[2][0];
				else
					$cur_charset = false;
				
				$encod = $cur_charset;
			}
			else
				$encod =  $this->mHeaders["content-charset"];
			
			return $encod;
		}
		
        public function GetPoster()
        {
            $res = $this->mHeaders['from'];
            
            if (preg_match("/\S+@\S+\s*\((.*)\)/", $res, $match))
                $res = $match[1];
            elseif (preg_match("/([\"\'])?(.+?)(?(1)\\1|)\s*\<((\S+)?(@)?\S+)?(.*?)\>/", $res, $match))
                $res = $match[2];
            elseif(preg_match("/\<?(\S+?)@\S+\>?/", $res, $match))
                $res =  $match[1];

            return $res;
        }

        public function GetDate()
        {
            return date("Y-m-d H:i:s", strtotime($this->mHeaders['date']));
        }

        public function GetGroupId()
        {
            return $this->mGroupId;
        }
		
        public function GetGroupName()
        {
            return $this->mGroupName;
        }

        public function GetPostNumber()
        {
            return $this->mPostNumber;
        }

        public function GetMessageId()
        {
            return trim($this->mMessageId);
        }

        public function GetParentId()
        {
            return ($this->mParentId) ? trim($this->mParentId) : "";
        }
		
		public function GetTParentId()
        {
            return ($this->mTParentId) ? trim($this->mTParentId) : "";
        }
		
		public function GetRef()
        {
            return $this->mHeaders['references'];
        }
		
        public function GetHeaders()
        {
            foreach ((array)$this->mHeaders as $key => $value)
                $joined_headers .= $key.":".$value."\n";

            return $joined_headers;
        }

        public function GetBody()
        {
            return $this->mBody;
        }
    }
?>