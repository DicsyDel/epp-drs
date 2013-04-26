<?php

	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Common
     * @sdk
     */

	/**
	 * A simple list with access to changes.
	 * @name IChangelist
	 * @category   EPP-DRS
	 * @package    Common
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */

	interface IChangelist
	{
		/**
		 * Append item to the end of the list
		 *
		 * @param mixed $item
		 */
		public function Add ($item);
		
		/**
		 * Remove item from the list
		 *
		 * @param mixed $item
		 */
		public function Remove ($item);
		
		/**
		 * Find internal index of item in list 
		 *
		 * @param mixed $item
		 * @return int
		 */
		public function IndexOf ($item);
		
		/**
		 * Return list of added items
		 * 
		 * @return mixed
		 */
		public function GetAdded ();
		
		/**
		 * Return list of removed items
		 * 
		 * @return mixed
		 */
		public function GetRemoved ();
		
		/**
		 * Return underlying list
		 * 
		 * @return mixed
		 */
		public function GetList ();
		
		/**
		 * Check for changes
		 * @return bool True if list has modifications
		 */
		public function HasChanges ();
	}
	
	
	/**
	 * Simplest array-based implementation of IChangelist
	 * @name Changelist
	 * @category   EPP-DRS
	 * @package    Common
	 * @author Marat Komarov <http://webta.net/company.html>
	 * @author Igor Savchenko <http://webta.net/company.html>
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class Changelist implements IChangelist
	{
		
		protected $List;
		protected $Added = array();
		protected $Removed = array();
		
		/**
		 * Create changelist 
		 *
		 * @param array $list
		 * @param array $changedList Modified list. Optional
		 */
		public function __construct ($list, $changedList=null)
		{
			$this->List = $list;
			
			if ($changedList !== null)
				$this->SetChangedList($changedList);
		}
		
		/**
		 * Forcefully set a modified list. 
		 * Will check for differences between current one and update Added and Removed properties. 
		 *
		 * @param array $changedList
		 */
		public function SetChangedList ($changedList) 
		{
			// Reset previous changes
			$this->Added = array();
			$this->Removed = array();
			
			// Mark all items as removed
			$this->Removed = $this->List;
			foreach ($changedList as $item)
			{
				if (($i = $this->IndexOf($item)) === false)
					// Item not presented in original list, so it was added
					$this->Added[] = $item;
				else
				{
					//Item exists, remove it from $this->Removed list
					$pos = array_search($item, $this->Removed);
					array_splice($this->Removed, $pos, 1);
				}
			}
			
			// Set changed list as current list
			$this->List = $changedList;
		}
		
		/**
		 * Returns a list array
		 *
		 * @return array
		 */
		public function GetList ()
		{
			return $this->List;
		}
		
		/**
		 * Adds new item to the end of the list
		 *
		 * @param mixed $item
		 */
		public function Add ($item)
		{
			if ($this->IndexOf($item) === false)
			{
				$this->List[] = $item;
				$this->Added[] = $item;
			}
		}
		
		/**
		 * Removes item from the list
		 *
		 * @param mixed $item
		 */
		public function Remove ($item)
		{
			if (($i = $this->IndexOf($item)) !== false)
			{
				array_splice($this->List, $i, 1);
				if (($ai = array_search($item, $this->Added)) !== false)
				{
					array_splice($this->Added, $ai, 1);
				}
				else
				{
					$this->Removed[] = $item;
				}
			}
		}
		
		/**
		 * Finds internal index of item in list 
		 *
		 * @param mixed $item
		 * @return int
		 */	
		public function IndexOf ($item)
		{
			return array_search($item, $this->List);
		}
		
		/**
		 * Returns array of added items
		 * 
		 * @return array
		 */	
		public function GetAdded ()
		{
			return $this->Added;
		}
		
		/**
		 * Returns a list of removed items
		 * 
		 * @return array
		 */
		public function GetRemoved ()
		{
			return $this->Removed;
		}
		
		/**
		 * Returns True if list has modifications
		 */
		public function HasChanges ()
		{
			return $this->Added || $this->Removed;
		}
		
		/**
		 * Convert ChangeList to Array;
		 *
		 * @return array
		 */
		public function ToArray()
		{
			return array(
				'added' 	=> $this->Added,
				'removed'	=> $this->Removed,
				'list'		=> $this->List
			);
		}
	}


?>