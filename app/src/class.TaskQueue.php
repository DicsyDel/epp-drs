<?php

	class TaskQueue
	{
		private static $Instance;
		
		/**
		 * @return TaskQueue
		 */
		public static function GetInstance ()
		{
			if (self::$Instance === null)
			{
				self::$Instance = new TaskQueue();
			}
			return self::$Instance;
		}

		
		/**
		 * Database connection
		 */
		private $Db;
		
		public function __construct()
		{
			$this->Db = Core::GetDBInstance();
		}
		
		public function Put (Task $Task)
		{
			$this->Db->StartTrans();
			try
			{
				$this->Db->Execute(
					"INSERT INTO task_queue SET userid = ?, job_classname = ?, job_object = ?, dtadded = NOW()",
					array($Task->userid, get_class($Task->JobObject), serialize($Task->JobObject))
				);
				$Task->id = $this->Db->Insert_ID();			
				$Task->added_date = time();
			
				foreach ($Task->GetAllTargets() as $Target)
				{
					$Target->status = TargetStatus::IN_PROGRESS;				
					$this->Db->Execute(
						"INSERT INTO task_target SET taskid = ?, target = ?, status = ?, fail_count = 0",
						array($Task->id, $Target->target, $Target->status)
					);
					$Target->id = $this->Db->Insert_ID();
				}
			}
			catch (Exception $e)
			{
				$this->Db->RollbackTrans();
				throw new Exception(sprintf(_("Failed to put task into queue. Reason %s"), $e->getMessage()));
			}
			$this->Db->CompleteTrans();
			
			return $Task;
		}
		
		/**
		 * @return Task
		 */
		public function Peek ($offset=0)
		{
			$offset = (int)$offset;
			$row = $this->Db->GetRow("SELECT * FROM task_queue ORDER BY dtadded ASC LIMIT $offset, 1");
			if (!$row)
			{
				return null; 
			}
			return new Task($row['id']);
		}
		
		public function Remove (Task $Task)
		{
			$this->Db->Execute(
				"DELETE FROM task_queue WHERE id = ?",
				array($Task->id)
			);
			$this->Db->Execute(
				"DELETE FROM task_target WHERE taskid = ?",
				array($Task->id)
			);
		}
		
		public function Count ()
		{
			$row = $this->Db->GetRow("SELECT COUNT(*) AS c FROM task_queue");
			return $row['c'];
		}
	}

	
	class TargetStatus
	{
		const OK = 1;
		const FAILED = 2;
		const IN_PROGRESS = 0;		
	}
	
	class Task 
	{
		public static $MAX_FAIL_COUNT = 5;
		
		public 
			$id,
			$userid,
			$added_date,
			$JobObject;
			
		private $active_targets;
			
		private $targets;
		
		private $Db;
		
		public function __construct()
		{
			$this->Db = Core::GetDBInstance();
			
			$args = func_get_args();
			if (count($args) == 1 && is_numeric($args[0]))
			{
				$this->constructLoad($args[0]);
			}
			else if (count($args) == 3)
			{
				$this->constructCreate($args[0], $args[1], $args[2]);
			}
			else
			{
				throw new Exception("Constructor is not applicable by such arguments");
			}
		}
		
		private function constructLoad ($id)
		{
			// Load task
			$row = $this->Db->GetRow(
				"SELECT * FROM task_queue WHERE id = ?",
				array($id)
			);
			if (!$row)
			{
				throw new Exception(sprintf(_("Task ID=%s not found in database"), $id));
			}
			if (!class_exists($row['job_classname']))
			{
				throw new Exception(sprintf(_("Job class %s not exists"), $row['job_classname']));
			}
			$this->id = $row['id'];
			$this->userid = $row['userid'];
			$this->added_date = strtotime($row['dtadded']);
			$this->JobObject = unserialize($row['job_object']);
			
			// Load task targets
			$rows = $this->Db->GetAll(
				"SELECT * FROM task_target WHERE taskid = ?",
				array($this->id)
			);
			foreach ($rows as $row)
			{
				$Target = new TaskTarget();
				$Target->id = $row['id'];
				$Target->taskid = $row['taskid'];
				$Target->target = $row['target'];
				$Target->status = (int)$row['status'];
				$Target->fail_count = $row['fail_count'];
				$Target->fail_reason = $row['fail_reason'];
				
				$this->targets[] = $Target;
				if ($Target->status == TargetStatus::IN_PROGRESS)
				{
					$this->active_targets[] = $Target;
				}
			}
			
		}
		
		private function constructCreate ($userid, $JobObject, $targets)
		{
			$this->userid = $userid;
			$this->JobObject = $JobObject;
			$this->targets = array();
			foreach ($targets as $targetname)
			{
				$Target = new TaskTarget();
				$Target->target = $targetname;
				$this->targets[] = $Target;
				$this->active_targets[] = $Target;
			} 
		}
		
		public function TargetFailed (TaskTarget $Target)
		{
			if (($index = array_search($Target, $this->active_targets)) !== false)
			{
				$Target->fail_count++;
				$this->Db->Execute(
					"UPDATE task_target SET fail_count = ?, fail_reason = ? WHERE id = ?",
					array($Target->fail_count, $Target->fail_reason, $Target->id)
				);
				if ($Target->fail_count >= self::$MAX_FAIL_COUNT)
				{
					unset($this->active_targets[$index]);
					$Target->status = TargetStatus::FAILED;
					$this->Db->Execute(
						"UPDATE task_target SET status = ? WHERE id = ?",
						array(TargetStatus::FAILED, $Target->id)
					);
				}
			}
		}
		
		public function TargetCompleted (TaskTarget $Target)
		{
			if (($index = array_search($Target, $this->active_targets)) !== false)
			{
				unset($this->active_targets[$index]);
				$Target->status = TargetStatus::OK;
				$this->Db->Execute(
					"UPDATE task_target SET status = ? WHERE id = ?",
					array(TargetStatus::OK, $Target->id)
				);				
			}
		}
		
		public function GetActiveTargets ()
		{
			return $this->active_targets;
		}
		
		public function GetAllTargets ()
		{
			return $this->targets;
		}
		
		public function HasActiveTargets ()
		{
			return !empty($this->active_targets);
		}
	}
	
	class TaskTarget
	{
		public 
			$id,
			$taskid,
			$target,
			$status,
			$fail_count,
			$fail_reason;
	}
	
	
	class BulkUpdateContactJob
	{
		public 
			$TLD,
			$clids;
			
		public function __construct($TLD, $clids)
		{
			if (empty($clids))
			{
				throw new Exception(_("No one contact was specified"));
			}
			$this->TLD = $TLD;
			$this->clids = $clids;
		}
	}
	
	class BulkUpdateNSJob
	{
		public 
			$TLD,
			$nslist;
		
		public function __construct($TLD, array $nslist)
		{
			$this->TLD = $TLD;
			foreach ($nslist as $NS)
			{
				if ($NS instanceof Nameserver)
				{
					$this->nslist[] = $NS;
				}
				else
				{
					throw new Exception (_('Argument must be array of Nameserver objects'));
				}
			}
		}
	}
	
	class BulkRegisterDomainJob
	{
		public 
			$tlds,
			$periods,
			$contact_list,
			$ns_list,
			$extra;
			
		public function __construct($tlds, array $periods, array $contact_list, array $ns_list, $extra=array())
		{
			$this->tlds = $tlds;
			$this->periods = $periods;
			$this->contact_list = $contact_list;
			$this->ns_list = $ns_list;
			$this->extra = $extra;
		}
	}

?>