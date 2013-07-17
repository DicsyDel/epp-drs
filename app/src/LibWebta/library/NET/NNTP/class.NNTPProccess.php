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
	
	Core::Load("NET/NNTP/class.UsenetCrawler.php");
	Core::Load("NET/NNTP/class.UsenetPostingManager.php");
	
	/**
     * @name NNTPProccess
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class NNTPProccess extends NNTPCore
	{
		/**
		 * UsenetCrawler instanse
		 *
		 * @var UsenetCrawler
		 */
		private $UsenetCrawler;
		
		/**
		 * Posts limit fot crawling
		 *
		 * @var int
		 */
		private $PostsLimit;
		
		/**
		 * Minimum post size for store in symbols
		 *
		 * @var int
		 */
		private $PostSizeMin;
		
		
		/**
		 * Max post size for store in symbols
		 *
		 * @var int
		 */
		private $PostSizeMax;
		
		/**
		 * Default posts limit
		 *
		 */
		const CF_POSTS_LIMIT = 10000;
		
		/**
		 * Default post min size
		 *
		 */
		const POST_SIZE_MIN = 1;
		
		/**
		 * Default post max size
		 *
		 */
		const POST_SIZE_MAX = 100000;
		
		/**
		 * Constructor
		 *
		 * @param array $data [host,port,login,password,groupname]
		 * @return void
		 */
		function __construct($data)
		{
			parent::__construct();
			
			$this->UsenetCrawler = new UsenetCrawler($data["host"], $data["port"], $data["login"], $data["password"], $data["groupname"]);
			
			$this->PostsLimit = (defined("CF_POSTS_LIMIT")) ? CF_POSTS_LIMIT : self::POSTS_LIMIT;
			$this->PostSizeMin = (defined("CF_POST_SIZE_MIN")) ? CF_POST_SIZE_MIN : self::POST_SIZE_MIN;
			$this->PostSizeMax = (defined("CF_POST_SIZE_MAX")) ? CF_POST_SIZE_MAX : self::POST_SIZE_MAX;
			
			$this->UsenetCrawler->SetPostsLimit($this->PostsLimit);
			$this->UsenetCrawler->SetMinSize($this->PostSizeMin);
			$this->UsenetCrawler->SetMaxSize($this->PostSizeMax);
		}
		
		/**
		Start grab from current group
		@access public
		@return void
		*/
		public function go()
		{
			Log::Log("Start fetching '{$this->UsenetCrawler->NNTPGroup}' from '{$this->UsenetCrawler->NNTPHost}'...\n", 1, "NNTPLog");
			
			if ($this->UsenetCrawler)
			{			
				$manager = new UsenetPostingManager();
				$count = 0;
				while (($count++ < $this->PostsLimit))
				{
				    $posting = $this->UsenetCrawler->GetPosting();
				    if ($posting != false && $this->UsenetCrawler->Status == 0)
				    {
					   $manager->StorePost($posting);
					   unset($posting);
				    }
				    
				    if ($posting == false && $this->UsenetCrawler->Status == 1)
				        break;
				}
			}

			Log::Log("End fetching '{$this->UsenetCrawler->NNTPGroup}' from '{$this->UsenetCrawler->NNTPHost}'...\n", 1, "NNTPLog");
		}
	}
?>
