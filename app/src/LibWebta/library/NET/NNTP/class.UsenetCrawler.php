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
     * @name UsenetCrawler
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class UsenetCrawler extends Core
    {
        private $PostsLimit;
        private $DB;
        private $NNTP;
        private $NNTPPort;
        private $NNTPUser;
        private $NNTPPass;
        private $InitPerformed;
        private $CurrentGroupIndex;
        private $GroupName;
        public $Status;
        private $MinPostSize;
        private $MaxPostSize;
		public  $Report;
		public  $NewsGroups;
		public  $NNTPGroup;
		public  $NNTPHost;
       
        /**
         * Usenet crawler constructor
         *
         * @param string $host
         * @param string $port
         * @param string $login
         * @param string $password
         * @param string $groupname
         * @return void
         */
        function __construct($host, $port, $login, $password, $groupname)
        {
            $this->DB = Core::GetDBInstance(NULL, true);

            $this->MaxPostSize = 0;
            $this->MinPostSize = 0;
            $this->PostsLimit   = 0;
            
            $this->NNTPHost = $host;
            $this->NNTPPort = $port;
            $this->NNTPUser = $login;
            $this->NNTPPass = $password;
 			$this->NNTP = new NNTPClient();
			$this->NNTPGroup = $groupname;

			$sgroup = $this->SetGroup($groupname);
			if (!$sgroup)
				return false;
		    else
		        return true;
        }

        /**
         * Function sets maximum and minimum post size
         *
         * @param integer $min
         * @param integer $max
         * @return void
         */
		public function SetPostSize($min, $max)
		{
			$this->MaxPostSize = $max;
			$this->MinPostSize = $min;
		}
        
		/**
		 * Sets limit of fetched posts
		 *
		 * @param integer $limit
		 * @return void
		 */
		public function SetPostsLimit($limit)
		{
			$this->PostsLimit = $limit;
		}

		/**
		 * Initialize NNTP connection and selecting curent group
		 * 
		 * @return void
		 */
        private function Initialize()
        {
			if(!$this->NNTP->connect($this->NNTPHost, $this->NNTPPort, $this->NNTPUser, $this->NNTPPass, 10))
                Core::RaiseError("NNTP error: Cannot connect to server");
            else
            {
	           	Log::Log("NNTP: Found ".count($this->NewsGroups)." groups in database", 1, "NNTPLog");
	            
	            for ($i = 0; $i < count($this->NewsGroups); $i++)
	            {
	                $group = &$this->NewsGroups[$i];
					
	                $result = $this->NNTP->SelectGroup($group->GetName());
					
	                if ($result !== FALSE)
	                {
	                    $group->SetFirst($result['first']);
	                    $group->SetLast($result['last']);
	                }
	                else
	                    Core::RaiseError('NNTP error: cannot select group', 1, "NNTPLog");
	            }
	
	            $this->InitPerformed = true;
            }
        }

		/**
		 * Selecting group
		 *
		 * @return string Group name
		 */
        private function SelectGroup()
        {
            for ($i = 0; $i < count($this->NewsGroups); $i++)
            {
                if (!$this->NewsGroups[$i]->IsTagged() && ($this->NewsGroups[$i]->GetUnreadPostingsNumber() > 0))
                {
                    $this->CurrentGroupIndex = $i;
                    $this->GroupName = $this->NewsGroups[$i]->GetName();
                    $this->NewsGroups[$i]->SetTagged();
                    $this->NNTP->SelectGroup($this->GroupName);
					
            		Log::Log("NNTP: Group `{$this->GroupName}` selected", 1, "NNTPLog");
					
                    return $this->NewsGroups[$i];
                }
            }
			
            Log::Log("NNTP: No groups to select", 1, "NNTPLog");
			
            return false;
        }

		/**
		 * Geting posts from server
		 *
		 * @return object
		 */
        public function GetPosting()
        {
            if (!$this->InitPerformed)
                $this->Initialize();

            if (strlen($this->CurrentGroupIndex) == 0)
                $group = $this->SelectGroup();
            else
                $group = $this->NewsGroups[$this->CurrentGroupIndex];

            if ($group !== false)
            {
                $posting = $this->FetchPosting($group);
                while (false === $posting)
                {
                    if ($this->Status == 1)
                    {
                        $group = $this->SelectGroup();
                        if ($group == false) 
                        	return false;
                    }
                    else 
                        break;
                }

                return $posting;
            }
            else
                return false;

        }

		/**
		 * Fetching post from server
		 *
		 * @param string $group
		 * @return object
		 */
        function FetchPosting($group)
        {
            if ($this->GroupName !== $group->GetName())
            {
                $res = $this->NNTP->SelectGroup($group->GetName());

                if ($res !== FALSE)
                {
                    $group->SetFirst($res['first']);
                    $group->SetLast($res['last']);
                }
                else
                    Core::RaiseError('Fetch Posting: select group error');
            }



            if ($group->GetUnreadPostingsNumber() > 0)
            {
                if ($group->GetUnreadPostingsNumber() > $this->PostsLimit && $this->PostsLimit)
                    $current = $group->GetLast() - $this->PostsLimit;
                else
                    $current = $group->GetLastRead() >= $group->GetFirst() ? $group->GetLastRead() + 1 : $group->GetFirst();
				
                if ($current)
                	$overview = $this->NNTP->GetOverview($current);
               	
                while (($overview === FALSE || $overview["Bytes"] == 0) && ($current <= $group->GetLast()))
                {
                	Log::Log("NNTP: Skipping Article #{$current}. Empty overwiew.", 1, "NNTPLog");
                	
					$group->SetLastRead($current);
					$this->SetGroupStatus($group);
					
					$current++;
					$overview = $this->NNTP->GetOverview($current);
                }
				
                
                
                if (isset($overview["Bytes"]) && $overview["Bytes"] > 0)
                {
                    $header = $this->NNTP->GetArticleHead($current);
                    $body   = $this->NNTP->GetArticleBody($current, true);
					
                    if ($this->FilterPosting($header->fields, $body))
                    {
                        $posting = new UsenetPosting($header, $body, $group->getId(), $group->getName(), $current, $group->GetEncoding());
						
						if ($posting->GetEnc())
							$group->SetDetectedEncoding = $posting->GetEnc();
						
                        $this->Status = 0;

            			Log::Log("NNTP: Fetching Article #{$current}", 1, "NNTPLog");

                        $this->Report[$group->GetName()]++;
                    }
                    else
                    {
           				Log::Log("NNTP: Skipping Article #{$current}. Filtered", 1, "NNTPLog");
					
                        $this->Status = 2;     // skipped posting
                        $posting = false;
                    }

                    $group->SetLastRead($current);
                    $this->SetGroupStatus($group);

                    return $posting;

                }
                else
                {
                    $this->Status = 1;
                    return false;
                }
            }
            else
            {
                $this->Status = 1;       // end of group
                return false;
            }
        }
	
		/**
		* Set newsgroup for grabbing
		* @param string $group
		* @return void
		*/
		private function SetGroup($groupname)
		{
			$this->NewsGroups = array();
			$row = $this->DB->GetRow("SELECT * FROM newsgroups WHERE name='".addslashes($groupname)."'");
			if ($row)
			{
				$this->NewsGroups[] = new UsenetGroup($row['id'], $row['name'], $row['server_first'], $row['server_last'], $row['db_last'], $row["def_enc"], $row["auto_enc"]);
				return true;
			}
			else
				return false;
		}

        /**
         * Geting groups from database
         * @return void
         */
        protected function GetGroups()
        {
            $this->NewsGroups = array();

            $rows = $this->DB->Execute("SELECT * FROM newsgroups");
            while ($row = $rows->FetchRow())
            {
            	if ($row["id"])
                	$this->NewsGroups[] = new UsenetGroup($row['id'], $row['name'], $row['server_first'], $row['server_last'], $row['db_last'], $row["def_enc"], $row["auto_enc"]);
            }
        }

        /**
         * Update group status in database
         *
         * @param object $group
         * @return void
         */
        protected function SetGroupStatus(&$group)
        {
            $sql = "
                UPDATE newsgroups
                SET
                    db_last='".$group->GetLastRead()."',
                    server_first='".$group->GetFirst()."',
                    server_last='".$group->GetLast()."',
					dtupdated=NOW()
                WHERE
                    id='".$group->GetId()."'
            ";

            $this->DB->Execute($sql);
        }

        /**
         * Set minimal size of post
         *
         * @param integer $v
         * @return void
         */
        public function SetMinSize($v)
        {
            $this->MinPostSize = $v;
        }

        /**
         * Set maximum size of post
         *
         * @param integer $v
         * @return void
         */
        public function SetMaxSize($v)
        {
            $this->MaxPostSize = $v;
        }

        /**
         * Filter current post
         *
         * @param string $headers
         * @param string $body
         * @return bool
         */
        private function FilterPosting(&$headers, &$body)
        {
            $length = strlen($body);
            
            if (($length < $this->MinPostSize && $this->MinPostSize != 0) || ($length >= $this->MaxPostSize && $this->MaxPostSize != 0))
                return false;
            else
            	return true;
        }
    }

?>