<?php

/********************************************************
	CLASS:	TASK
	PURPOSE:	for editing or retrieving task related info.
						for now, this class reads task info directly
						from data variables, so you'll have to make
						sure you're passing the right thing when
						updating the task
********************************************************/

class TASK {

	private $taskId;			//id of task
	private $errorMsg;		//error message from queries
	private $contactId;
	private $DB;
		
	/***************************************************
		FUNCTION: construct
		PURPOSE:	class constructor
	***************************************************/	
	function __construct($taskId = null) {

		//init our variables
		$this->DB = $GLOBALS["DB"];
		$this->errorMsg = null;
		
		if ($taskId) {

			$this->taskId = $taskId;	
				
			//make sure the user has permissions to edit task
			if (!$this->checkPerms()) {
				$this->throwError("Invalid permissions to edit task");
			}

		}
		
	}

	/***************************************************
		FUNCTION: save
		PURPOSE:	master function for saving all task related
							info
	***************************************************/	
	function save($data=null) {
	
		if (!$data) $data = $_POST;

		$this->DB->begin();

    //save basic info
		$this->saveBasic($data);

		//update account and event info
		$this->updateAccount($data["accountId"]);
            
		//update data specific to task type
		if ($data["taskType"]) {
			$func = "update".$data["taskType"]."task";
			if (method_exists($this,$func)) $this->$func($data);
		}

		$this->DB->end();

		return $this->taskId;

	}

	/***************************************************
		FUNCTION: saveBasic
		PURPOSE:	saves basic task information
	***************************************************/	
	function saveBasic($data=null) {

		if (!$data) $data = $_POST;

		//reformat date
		if ($data["due"]=="t" || $data["due"]=="1") $date = date("Y-m-d",strtotime($data["dateDue"]));
		else $date = null;

		//gather our data
		$opt = null;
		$opt["title"] = $data["title"];
		$opt["notes"] = $data["notes"];
		$opt["priority"] = $data["priority"];
		$opt["date_due"] = $date;
		$opt["due"] = $data["due"];	
		$opt["task_type"] = $data["taskType"]; 
		$opt["modified_date"] = date("Y-m-d H:i:s");
		$opt["modified_by"] = USER_ID;
		$str = null;
		foreach ($data AS $key=>$val) $str .= $key."=>".$val."\n";

		//if completed, set the completed date
		if ($data["completed"]!=null) {

			//some things pass 1 and 0 instead of t & f
			if ($data["completed"]=="1") 				$data["completed"] = "t";
			else if ($data["completed"]=="0")  	$data["completed"] = "f";

			if ($data["completed"]=="t") {
				if ($data["dateCompleted"]) $opt["date_completed"] = $data["dateCompleted"];
				else $opt["date_completed"] = date("Y-m-d");
			}
			
			$opt["completed"] = $data["completed"];

		}
				
		if ($this->taskId) {

			$opt["where"] = "id='".$this->taskId."'";
			$this->DB->update("task.task",$opt);

		} else {

			$opt["created_by"] = USER_ID;
			$opt["created_date"] = date("Y-m-d H:i:s");
			$this->taskId = $this->DB->insert("task.task",$opt,"id");

		}

		$this->index();
                                                
		$this->throwError($this->DB->error());
		
	}		

	function index() {

		$sql = "UPDATE task.task SET idxfti=
						setweight(to_tsvector('english',title),'A') ||
						setweight(to_tsvector('english',notes),'B')
						WHERE id='".$this->taskId."'";
		$this->DB->query($sql);

	}


	/***************************************************
		FUNCTION: updateAccount
		PURPOSE:	updates accounts that can view this task
	***************************************************/	
	function updateAccount($accountId=null) {

		//make sure accountid is an array (accept comma delimited strings als)
		if ($accountId && !is_array($accountId)) $accountId = explode(",",$accountId);

		//if still no account id, use the current user
		if (!$accountId) $accountId = array(USER_ID);

		//clear out what's in there
		$sql = "DELETE FROM task.task_account WHERE task_id='".$this->taskId."';";

		//add any new accounts if we have them
		if (count($accountId) > 0) {
	
			for ($i=0;$i<count($accountId);$i++) 
				$sql .= "INSERT INTO task.task_account (task_id,account_id) VALUES ('".$this->taskId."','".$accountId[$i]."');";

		}
	
		//run it
		$this->DB->query($sql);
		
	}


	/***************************************************
		FUNCTION: markComplete
		PURPOSE:	marks a task complete
	***************************************************/	
	function markComplete($notes=null,$lc=null) {

		$this->DB->begin();
	  
	  //mark task complete
	  $opt = null;
	  $opt["completed"] = "t";
	  $opt["date_completed"] = date("Y-m-d H:i:s");
	  if ($notes) $opt["notes"] = $notes;
		$opt["modified_date"] = date("Y-m-d H:i:s");
		$opt["modified_by"] = USER_ID;
	  $opt["where"] = "id='".$this->taskId."'";
	  
	  $this->DB->update("task.task",$opt);

		$this->DB->end();

	}

	/***************************************************
		FUNCTION: markIncomplete
		PURPOSE:	marks a task incomplete
	***************************************************/	
	function markIncomplete() {

		$this->DB->begin();

	  //mark task incomplete
	  $opt = null;
	  $opt["completed"] = "f";
	  $opt["where"] = "id='".$this->taskId."'";
		$opt["modified_date"] = date("Y-m-d H:i:s");
		$opt["modified_by"] = USER_ID;
	  $this->DB->update("task.task",$opt);

	  $this->DB->end();

	}

	/***************************************************
		FUNCTION: deleteTask
		PURPOSE:	permanently removes a task
	***************************************************/	
	function delete() {

	  //delete main task entry
	  $this->DB->begin();

	  $sql = "DELETE FROM task.task WHERE id='".$this->taskId."';";
	  $sql .= "DELETE FROM task.task_account WHERE task_id='".$this->taskId."';";
	  $sql .= "DELETE FROM task.task_role WHERE task_id='".$this->taskId."';";
	  $sql .= "DELETE FROM task.docmgr_task WHERE task_id='".$this->taskId."';";
		$this->DB->query($sql);

		$this->DB->end();

	}

	/***************************************************
		FUNCTION: checkPerms
		PURPOSE:	verifies a user can edit this task id
	***************************************************/	
	function checkPerms() {

		$ret = false;
		
	  //make sure user has permissions
	  if (!bitset_compare(BITSET,MANAGE_TASK,ADMIN)) {
	  
	  	$sql = "SELECT account_id FROM task.task_account WHERE task_id='".$this->taskId."'";
	  	$info = $this->DB->fetch($sql,1);
  
	  	if (@in_array(USER_ID,$info["account_id"])) $ret = true;
	  	
		} else $ret = true;
		
		return $ret;

	}

	/***************************************************
		FUNCTION: throwError
		PURPOSE:	store error message thrown by class function
	***************************************************/	
	function throwError($msg) {
	
		$this->errorMsg = $msg;
		
	}
	
	/***************************************************
		FUNCTION: getError
		PURPOSE:	returns current error message
	***************************************************/	
	function getError() {
	
		return $this->errorMsg;
	
	}

	/***************************************************
		FUNCTION: getId
		PURPOSE:	returns current taskId
	***************************************************/	
	function getId() {
	
		return $this->taskId;
	
	}


	function updateDocMGRTask($data) {

		if (!$data) $data = $_POST;

		//is there an entry for this task
		$sql = "SELECT task_id FROM task.docmgr_task WHERE task_id='".$this->taskId."'";
		$info = $this->DB->single($sql);
	
		$opt = null;
		$opt["object_id"] = $data["objectId"];
		$opt["task_id"] = $this->taskId;
		if ($data["routeId"]) $opt["route_id"] = $data["routeId"];
		if ($data["workflowId"]) $opt["workflow_id"] = $data["workflowId"];

		//update
		if ($info) {
			$opt["where"] = "task_id='".$this->taskId."'";
			$this->DB->update("task.docmgr_task",$opt);
		} else {
			$this->DB->insert("task.docmgr_task",$opt);
		}
	
	}

	/******************************************************************
		FUNCTION: saveNotes
		PURPOSE:	updates notes only for a task.  we use this instead of
							save to cut down on overhead when doing a 
							"save as you type"
	*****************************************************************/	
	function saveNotes($data=null) {

		if (!$data) $data = $_POST;
	
		$opt = null;
		$opt["notes"] = $data["notes"];
		$opt["where"] = "id='".$this->taskId."'"; 
		$this->DB->update("task.task",$opt);

		$this->index();
		
	}

	/******************************************************************
		FUNCTION: getTask
		PURPOSE:	master function for retrieving task information
	*****************************************************************/	
	function getTask() {

		//make sure we ahve a task id
		if (!$this->taskId) {
			$this->throwError("Task ID not specified");
			return false;
		}
		
		//get info for our task entry, except for the letter content
		$sql = "SELECT * FROM task.task WHERE id='".$this->taskId."'";
		$taskInfo = $this->DB->single($sql);

		//contact information
		if ($taskInfo["task_type"]) {
			$func = "get".$taskInfo["task_type"]."Task";
			if (method_exists($this,$func)) $taskInfo = array_merge($taskInfo,$this->$func());
		}

		return $taskInfo;		
	
	}

	/******************************************************************
		FUNCTION: getDocMGRTask
		PURPOSE:	retrieves specific information for a contact task
	*****************************************************************/	
	function getDocMGRTask() {

	  //just query our full view and use tha tinstead
    $sql = "SELECT * FROM task.view_docmgr_task WHERE task_id='".$this->taskId."'";
		$taskInfo = $this->DB->single($sql);

		$sql = "SELECT * FROM docmgr.dm_object WHERE id='".$taskInfo["object_id"]."'";
		$objInfo = $this->DB->single($sql);
		
		$taskInfo["object_name"] = $objInfo["name"];

		return $taskInfo;

	}
	
//end class
}

