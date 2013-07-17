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
     * @name UsenetGroup
     * @category   LibWebta
     * @package    NET
     * @subpackage NNTP
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class UsenetGroup
    {
        public $mId;
        public $mGroupName;
        public $mFirst;
        public $mLast;
        public $mLastRead;
        public $mTagged = false;

        /**
         * Usenet group constructor
         *
         * @param string $id
         * @param string $name
         * @param integer $first
         * @param integer $last
         * @param integer $lastRead
         * @param string $def_enc
         * @param bool $auto_enc
         */
        function __construct($id, $name, $first, $last, $lastRead, $def_enc, $auto_enc)
        {
            $this->mId          = $id;
            $this->mGroupName   = $name;
            $this->mFirst       = $first;
            $this->mLast        = $last;
            $this->mLastRead    = $lastRead;
			$this->mDefEnc    	= $def_enc;
			$this->mAutoEnc     = $auto_enc;
			$this->mDetectedEnc = false;
        }
		
        /**
         * Set detected encoding
         *
         * @param string $enc
         */
		public function SetDetectedEncoding($enc)
		{
			$this->mDetectedEnc = $enc;	
		}
		
		/**
		 * Get encoding
		 *
		 * @return string
		 */
		public function GetEncoding()
		{
			if ($this->mAutoEnc && $this->mDetectedEnc)
				return $this->mDetectedEnc;
			else
				return $this->mDefEnc;
		}
        
		/**
		 * Get ID
		 *
		 * @return string
		 */
        public function GetId()
        {
            return $this->mId;
        }

        /**
         * Get name
         *
         * @return string
         */
        public function GetName()
        {
            return $this->mGroupName;
        }

        /**
         * Get number of first article
         *
         * @return integer
         */
        public function GetFirst()
        {
            return $this->mFirst;
        }

        /**
         * Set first article number
         *
         * @param integer $i
         */
        public function SetFirst($i)
        {
            $this->mFirst = $i;
        }
        
        /**
         * Get last server article number
         *
         * @return string
         */
        public function GetLast()
        {
            return $this->mLast;
        }
        
        /**
         * Set last artcile numver
         *
         * @param string $i
         */
        public function SetLast($i)
        {
            $this->mLast = $i;
        }
        
        /**
         * Set number of last readed article
         *
         * @param string $i
         */
        public function SetLastRead($i)
        {
            $this->mLastRead = $i;
        }

        /**
         * Get number of last readed article
         *
         * @return string
         */
        public function GetLastRead()
        {
            return $this->mLastRead;
        }

        /**
         * Get number of postings
         *
         * @return integer
         */
        public function GetPostingsNumber()
        {
            $postings = $this->mLast - $this->mFirst;
            return $postings;
        }

        /**
         * Get Unread postings number
         *
         * @return integer
         */
        public function GetUnreadPostingsNumber()
        {
            $first = max($this->mLastRead, $this->mFirst);
            return $this->mLast - $first;
        }

        /**
         * Set Tagged
         *
         */
        public function SetTagged()
        {
            $this->mTagged = true;
        }

        /**
         * Is Tagged
         *
         * @return bool
         */
        public function IsTagged()
        {
            return $this->mTagged;
        }
    }
?>