<?php

/**********************************************************************
	CLASS:	URL
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_WORKFLOW extends DOCMGR 
{

	private $workflowId;
	private $routeId;
	private $accountId;

  /*******************************************************************************
  	called from DOCMGR class
	*******************************************************************************/
	function ___construct()
	{

		//try to snag from api
		if ($this->apidata["route_id"]) 		$this->routeId = $this->apidata["route_id"];
		if ($this->apidata["workflow_id"]) 	$this->workflowId = $this->apidata["workflow_id"];

		//passed a standard task id, pull the route from it
		if ($this->apidata["task_id"])
		{

			$sql = "SELECT * FROM task.docmgr_task WHERE task_id='".$this->apidata["task_id"]."'";
			$info = $this->DB->single($sql);
			
			$this->routeId = $info["route_id"];
			$this->workflowId = $info["workflow_id"];
		
		}

		if ($this->routeId && !$this->workflowId) 
		{

			$sql = "SELECT workflow_id FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."'";
			$info = $this->DB->single($sql);
			
			$this->workflowId = $info["workflow_id"];
			
		}

	}

	function permCheck($mode="view")
	{

		$check = true;
	
		if ($this->workflowId)
		{

			if ($mode=="view")
			{

				$sql = "SELECT id FROM docmgr.dm_workflow WHERE 
								(id='".$this->workflowId."' AND	account_id='".USER_ID."') OR 
								id IN (SELECT workflow_id FROM docmgr.dm_workflow_route WHERE account_id='".USER_ID."')
								";

			}
			else
			{
		
				$sql = "SELECT id FROM docmgr.dm_workflow WHERE id='".$this->workflowId."' AND account_id='".USER_ID."'";

			}
			
			$info = $this->DB->single($sql);

			if (!$info) $check = false;
			
		}
		
		return $check;
	
	}

  /********************************************************************
  	FUNCTION: getlist
  	PURPOSE:  retrieves document from the system
	********************************************************************/
	public function getlist() 
	{
	
		//get all workflows we own, or are part of
		$sql = "SELECT dm_object.name AS object_name,dm_workflow.* FROM docmgr.dm_workflow
						LEFT JOIN docmgr.dm_object ON dm_object.id=dm_workflow.object_id
						WHERE 
							(account_id='".USER_ID."' OR 
							dm_workflow.id IN (SELECT workflow_id FROM docmgr.dm_workflow_route WHERE account_id='".USER_ID."')
							)";

		if ($this->apidata["filter"]=="current")
			$sql .= "	AND (dm_workflow.status IN ('nodist','pending') OR dm_workflow.status IS NULL) ";
		else if ($this->apidata["filter"]=="history")
			$sql .= "	AND (dm_workflow.status IN ('forcecomplete','complete','rejected')) ";

		$sql .= "	ORDER BY id DESC";

		$list = $this->DB->fetch($sql);

		//loop through and add sme extra information		
		for ($i=0;$i<$list["count"];$i++) 
		{

			$a = new ACCOUNT($list[$i]["account_id"]);
			$ainfo = $a->getInfo();
			$list[$i]["account_name"] = $ainfo["first_name"]." ".$ainfo["last_name"];
		
			$list[$i]["status_view"] = $this->viewStatus($list[$i]["status"]);	
			$list[$i]["date_create_view"] = dateView($list[$i]["date_create"]);
			$list[$i]["date_complete_view"] = dateView($list[$i]["date_complete"]);

			$list[$i]["object_path"] = $this->objectPath($list[$i]["object_id"]);

			$this->PROTO->add("workflow",$list[$i]);
			
		}

	}

	public function create()
	{

		//must have an object to link this to
		if (!$this->objectId)
		{
			$this->throwError("No object_id passed");
			return false;
		}
	
		//create a new workflow w/ the default values
		$opt = null;
		$opt["object_id"] = $this->objectId;
		$opt["status"] = "nodist";
		$opt["account_id"] = USER_ID;
		$opt["date_create"] = date("Y-m-d H:i:s");
		$this->workflowId = $this->DB->insert("docmgr.dm_workflow",$opt,"id");
	
		$this->PROTO->add("workflow_id",$this->workflowId);
		
	}

	public function getinfo() 
	{

		//permissions checking.  here, since we are just viewing, we see what object
		//this pertains to and check against that
		if (!$this->permCheck("view"))
		{
			$this->throwError("You do not have permissions to view this workflow");
			return false;
		}

		//get some basic info
		$sql = "SELECT * FROM docmgr.dm_workflow WHERE id='".$this->workflowId."'";
		$info = $this->DB->single($sql);

		//not found		
		if (!$info) $this->throwError("Error finding workflow information");
		else 
		{

			//add some document info
			$d = new DOCMGR_OBJECT($info["object_id"]);
			$obj = $d->getInfo();

			$info["object_name"] = $obj["name"];
			$info["object_path"] = $obj["object_path"];

			//recipients
			$sql = "SELECT * FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."' ORDER BY sort_order";
			$reciplist = $this->DB->fetch($sql);
		
			//translate status
			$info["status_view"] = $this->viewStatus($info["status"]);	
			$info["date_create_view"] = dateView($info["date_create"]);
			$info["date_complete_view"] = dateView($info["date_complete"]);

			//merge and extendin recipient info
			for ($i=0;$i<$reciplist["count"];$i++) 
			{

				$accountinfo = returnAccountInfo($reciplist[$i]["account_id"]);

				$reciplist[$i]["date_due_view"] = dateView($reciplist[$i]["date_due"]);
				$reciplist[$i]["date_complete_view"] = dateView($reciplist[$i]["date_complete"]);
				$reciplist[$i]["account_name"] = $accountinfo["first_name"]." ".$accountinfo["last_name"];
				$reciplist[$i]["account_login"] = $accountinfo["login"];
				$reciplist[$i]["status_view"] = $this->viewStatus($reciplist[$i]["status"]);
	
				$info["recipient"][$i] = $reciplist[$i];
			
			}

			//output to proto
			$this->PROTO->add("workflow",$info);
			
		}

	}

	protected function viewStatus($stat) 
	{
	
			$view = null;
			
			if ($stat=="forcecomplete") $view = "Force Complete";
			else if ($stat=="nodist") $view = "Not Distributed";
			else if ($stat=="pending") $view = "In Progress";
			else if ($stat=="complete") $view = "Completed";
			else if ($stat=="rejected") $view = "Rejected";
			else $view = "Not Distributed";
				
			return $view;
	}

	
	public function saveRecip() 
	{

		//permissions checking
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this workflow");
			return false;
		}
	
		$this->DB->begin();

		$accountId = $this->apidata["accountId"];
		if (!is_array($accountId)) $accountId = array($accountId);

		//update existing route
		if ($this->routeId)
		{
		
			$opt = null;
			$opt["task_type"] = $this->apidata["taskType"];
			$opt["task_notes"] = $this->apidata["taskNotes"];
			$opt["date_due"] = dateProcess($this->apidata["dateDue"]);
			$opt["where"] = "id='".$this->routeId."'";
			$this->DB->update("docmgr.dm_workflow_route",$opt);
		
		} 
		else 
		{

			//create a new task for every account passed		
			for ($i=0;$i<count($accountId);$i++) 
			{

				//make sure this account doesn't already have an entry in this stage
				$sql = "SELECT account_id FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."' AND
																		account_id='".$accountId[$i]."' AND sort_order='".$this->apidata["stage"]."'";
				$info = $this->DB->single($sql);
				
				if ($info) continue;
			
				$opt = null;
				$opt["workflow_id"] = $this->workflowId;
				$opt["account_id"] = $accountId[$i];
				$opt["task_type"] = $this->apidata["taskType"];
				$opt["task_notes"] = $this->apidata["taskNotes"];
				$opt["date_due"] = dateProcess($this->apidata["dateDue"]);
				$opt["sort_order"] = $this->apidata["stage"];
				$opt["status"] = "nodist";
				$this->DB->insert("docmgr.dm_workflow_route",$opt);
		
			}

		}

		$this->consolidate();
		
		$this->DB->end();

		$err = $this->DB->error();
		
		if ($err) $this->throwError($err);
	
	}

	public function deleteRecip() 
	{

		//permissions checking
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this workflow");
			return false;
		}

		$this->DB->begin();
			
		$sql = "DELETE FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."'";
		$this->DB->query($sql);
		
		$this->consolidate();
		
		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);

	}	

	//keeps everything in order in case someone gets goofy w/ the adding of stuff
	protected function consolidate() 
	{

		$sql = "SELECT DISTINCT sort_order FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."' ORDER BY sort_order"; 
		$list = $this->DB->fetch($sql);
		
		for ($i=0;$i<$list["count"];$i++) 
		{
		
			$so = $list[$i]["sort_order"];

			$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE sort_order='$so' AND workflow_id='".$this->workflowId."' ORDER BY id";
			$matches = $this->DB->fetch($sql,1);
			
			$sql = "UPDATE docmgr.dm_workflow_route SET sort_order='".($i)."' WHERE id IN (".implode(",",$matches["id"]).")";
			$this->DB->query($sql);
			
		}
	
	}

	//set workflow options
	public function setOpt() 
	{

		//permissions checking
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this workflow");
			return false;
		}
	
		//emailcomplete, emailexpired
		if ($this->apidata["option"]=="emailcomplete") $field = "email_notify";
		else $field = "expire_notify";
		
		if ($this->apidata["action"]=="set") $val = "t";
		else $val = "f";
		
		$opt = null;
		$opt[$field] = $val;
		$opt["where"] = "id='".$this->workflowId."'";
		$this->DB->update("docmgr.dm_workflow",$opt);
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	
	}	

	//transfer all the current routes into a template
	protected function transferTemplate($templateId) 
	{

		$workflowId = $this->workflowId;

  	$sql = "SELECT * FROM docmgr.dm_workflow_route WHERE workflow_id='$workflowId'";
  	$list = $this->DB->fetch($sql);
  
  	//delete any current template info
  	$sql = "DELETE FROM docmgr.dm_saveroute_data WHERE save_id='$templateId';";

  	//get the time in seconds
  	$today = time();
  
  	for ($i=0;$i<$list["count"];$i++) 
  	{

    	//get the date in seconds
    	$sec = strtotime($list[$i]["date_due"]);
    
    	$diff = $sec - $today;
    	$days = intValue($diff/86400);
    	if (!$days) $days = "0";
  
    	$opt = null;
    	$opt["save_id"] = $templateId;
    	$opt["account_id"] = $list[$i]["account_id"];
    	$opt["task_type"] = $list[$i]["task_type"];
    	$opt["task_notes"] = addslashes($list[$i]["task_notes"]);
    	$opt["sort_order"] = $list[$i]["sort_order"];
    	$opt["date_due"] = $days;
    	$opt["query"] = 1;
    	$sql .= $this->DB->insert("docmgr.dm_saveroute_data",$opt);

		}

		$this->DB->query($sql);

	}
	
	public function saveTemplate() 
	{

		$this->DB->begin();

    $opt = null;

    if ($this->apidata["template_id"]) 
    {

    	//permcheck
			$sql = "SELECT account_id FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."'";
			$info = $this->DB->single($sql);
			
			if ($info["account_id"]!=USER_ID) 
			{
				$this->throwError("You do not have permissions to edit this template");
				return false;
			}

			$templateId = $this->apidata["template_id"];    
    	$opt["where"] = "id='".$templateId."'";
    	$this->DB->update("docmgr.dm_saveroute",$opt);
    
    } else 
    {

	    $opt["account_id"] = USER_ID;
	    $opt["name"] = $this->apidata["template_name"];
	    $templateId = $this->DB->insert("docmgr.dm_saveroute",$opt,"id");

		}
		
    $this->transferTemplate($templateId);

    $this->DB->end();

		$err = $this->DB->error();
		if ($err) $this->throwError($err);

  }  

  public function getTemplates() 
  {
  
  	//get all saved templates for this user
  	$sql = "SELECT * FROM docmgr.dm_saveroute WHERE account_id='".USER_ID."'";
  	$tempList = $this->DB->fetch($sql);
  	
  	for ($i=0;$i<$tempList["count"];$i++) 
  	{
  
  		$this->PROTO->add("template",$tempList[$i]);
  		
		}
		
	}

	function deleteTemplate() 
	{

   	//permcheck
		$sql = "SELECT account_id FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."'";
		$info = $this->DB->single($sql);
			
		if ($info["account_id"]!=USER_ID) 
		{
			$this->throwError("You do not have permissions to edit this template");
			return false;
		}
	
		$sql = "DELETE FROM docmgr.dm_saveroute_data WHERE save_id='".$this->apidata["template_id"]."';";
		$sql .= "DELETE FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."';";
		$this->DB->query($sql);

		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}	

	public function getFromTemplate() 
	{

   	//permcheck
		$sql = "SELECT account_id FROM docmgr.dm_saveroute WHERE id='".$this->apidata["template_id"]."'";
		$info = $this->DB->single($sql);
			
		if ($info["account_id"]!=USER_ID) 
		{
			$this->throwError("You do not have permissions to edit this template");
			return false;
		}

		$this->DB->begin();
		
		//transfer saveroute data into workflow data
		$sql = "SELECT * FROM docmgr.dm_saveroute_data WHERE save_id='".$this->apidata["template_id"]."'
											ORDER BY sort_order";
		$reciplist = $this->DB->fetch($sql);

		$sql = "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id='".$this->workflowId."'";
		$this->DB->query($sql);
		
		for ($i=0;$i<$reciplist["count"];$i++) 
		{
		
			//calculate the date due
			$time = time();
			$diff = $time + ($list[$i]["date_due"] * 86400);
			$dateDue = date("Y-m-d H:i:s",$diff);

			//rn the insert
			$opt = null;
			$opt["workflow_id"] = $this->workflowId;
			$opt["account_id"] = USER_ID;
			$opt["task_type"] = addslashes($reciplist[$i]["task_type"]);
			$opt["status"] = "nodist";
			$opt["sort_order"] = $reciplist[$i]["sort_order"];
			$opt["task_notes"] = addslashes($reciplist[$i]["task_notes"]);			
			$opt["date_due"] = $dateDue;
			$this->DB->insert("docmgr.dm_workflow_route",$opt);
		
		}

		//fetch the new info
		$this->getinfo();

		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);

	}

	function begin()
	{

		//permissions checking
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this workflow");
			return false;
		}
	
		$this->nextStage("0");
	
	}

	//issue our alerts for a specific stage of a route
	protected function nextStage($stage) 
	{

		$workflowId = $this->workflowId;
		$this->DB->begin();
		
	  //get our list of routes assigned to this object
	  $sql = "SELECT * FROM docmgr.dm_workflow_route WHERE workflow_id='$workflowId' AND sort_order='$stage';";
	  $list = $this->DB->fetch($sql);

		if ($list["count"]==0) 
		{

				$sql = "SELECT object_id FROM docmgr.dm_workflow WHERE id='$workflowId'";
				$info = $this->DB->single($sql);

		    //log that it's finished
	      logEvent(OBJ_WORKFLOW_END,$info["object_id"]);

		    //see if we are supposed to notify the user this is complete
		    $this->sendCompleteNotify();

		    $sql = "UPDATE docmgr.dm_workflow SET status='complete',date_complete='".date("Y-m-d H:i:s")."' WHERE id='$workflowId';";
		    $this->DB->query($sql);

				//clear all shares
				$this->clearAllShares();
	
	  } 
	  else 
	  {

	  	//starting a new one
	  	if ($stage=="0") logEvent(OBJ_WORKFLOW_BEGIN,$this->objectId);

	 	  //make sure our primary status is still set to pending since we are not done
	 	  $sql = "Update docmgr.dm_workflow SET status='pending' WHERE id='$workflowId';";
			$this->DB->query($sql);
			
			$accounts = array();	
			$objname = $this->objectInfo["name"];
			
	  	for ($i=0;$i<$list["count"];$i++) 
	  	{

				$sql = "UPDATE docmgr.dm_workflow_route SET status='pending' WHERE id='".$list[$i]["id"]."';";
				$this->DB->query($sql);

		    if ($list[$i]["task_type"]=="edit") $msg = "Edit action required for \"".$objname."\"";
		    elseif ($list[$i]["task_type"]=="approve") $msg = "Approval action required for \"".$objname."\"";
		    elseif ($list[$i]["task_type"]=="comment") $msg = "Comment action required for \"".$objname."\"";
		    else $msg = "View action required for \"".$objname."\"";		

				//setup a task
				//gather our data
				$opt = null;
				$opt["title"] = addslashes($msg);
				$opt["notes"] = null;
				$opt["priority"] = "1";
				$opt["taskType"] = "docmgr";
				$opt["accountId"] = $list[$i]["account_id"];
				$opt["routeId"] = $list[$i]["id"];
				$opt["workflowId"] = $list[$i]["workflow_id"];
				$opt["objectId"] = $this->objectId;

				if ($list[$i]["date_due"]) 
				{
					$opt["dateDue"] = $list[$i]["date_due"];
					$opt["due"] = "t";
				} else {
					$opt["due"] = "f";				
				}

				//save the task
				$t = new TASK();
				$t->save($opt);

				//setup collections for sharing
				$this->addShare($list[$i]["task_type"],$list[$i]["account_id"]);
				
			}
		
		}	

		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
			  
	}
	
	
	protected function sendCompleteNotify($rejected=null) 
	{
	
			$workflowId = $this->workflowId;
			
	    //see if we are supposed to notify the user this is complete
	    $sql = "SELECT object_id,account_id,email_notify FROM docmgr.dm_workflow WHERE id='$workflowId'";
	    $info = $this->DB->single($sql);
	    
	    if ($info["email_notify"]=="t") 
	    {
	    
	      //get the user's email and send them a notification
				$a = new ACCOUNT($info["account_id"]);
				$ainfo = $a->getInfo();

	      if ($ainfo["email"]) 
	      {

	      	//send a rejected message if workflow was stopped that way
					if ($rejected) 
					{
						
	        	$sub = "Workflow Rejected";
	        	$msg = $this->objectInfo["name"]." was rejected by ".USER_FN." ".USER_LN;

					//send a completed message	      
					} else {
					
						$sub = "Workflow Completed";
	        	$msg = $this->objectInfo["name"].": Workflow was completed";
						
					}
					
	        send_email($ainfo["email"],ADMIN_EMAIL,$sub,$msg,null);
	      
	      }
	    
	    }
	
	}
	
	public function forceComplete() 
	{

		//permissions checking
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this workflow");
			return false;
		}

		$workflowId = $this->workflowId;

		$this->DB->begin();

		//clear the shared folders
		$this->clearAllShares();
		
		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE workflow_id='$workflowId';";
		$list = $this->DB->fetch($sql,1);
	
		//delete the tasks
		//$sql = "DELETE FROM docmgr.dm_task WHERE task_id IN (".implode(",",$list["id"]).");";
		$sql = "UPDATE docmgr.dm_workflow_route SET status='forcecomplete' WHERE workflow_id='$workflowId' AND status!='complete';";
		$sql .= "UPDATE docmgr.dm_workflow SET status='forcecomplete',date_complete='".date("Y-m-d H:i:s")."' WHERE id='$workflowId';";
	
		$this->DB->query($sql);

  	//mark our tasks as complete if they have not been already
  	$sql = "SELECT id FROM task.view_docmgr_task WHERE workflow_id='".$workflowId."' AND completed='f'";                          
  	$tasks = $this->DB->fetch($sql);
  	
  	for ($i=0;$i<$tasks["count"];$i++) 
  	{
  	
  		$t = new TASK($tasks[$i]["id"]);
  		$t->markComplete("Forced Complete");	//puts a comment showing this was forced closed
  	
  	}

		//log that it's completed
	  $sql = "SELECT object_id FROM docmgr.dm_workflow WHERE id='$workflowId'";
	  $info = $this->DB->single($sql);

		logEvent(OBJ_WORKFLOW_CLEAR,$info["object_id"]);

		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
	
	}

	public function markComplete() 
	{

		//make sure this user can edit this task
		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."' AND account_id='".USER_ID."'";
		$info = $this->DB->single($sql);
		
		if (!$info)
		{
			$this->throwError("You do not have permissions to edit this workflow task");
			return false;
		}

		$this->DB->begin();

		//update this route and delete the task from the alert
		$opt = null;
		$opt["status"] = "complete";
		$opt["comment"] = $this->apidata["comment"];
		$opt["where"] = "id='".$this->routeId."'";
		$this->DB->update("docmgr.dm_workflow_route",$opt);

		//clear the shared folders
		$this->clearShare($this->routeId);

  	//mark our tasks as complete if they have not been already
  	$sql = "SELECT id FROM task.view_docmgr_task WHERE route_id='".$this->routeId."' AND completed='f'";                          
  	$tasks = $this->DB->fetch($sql);
  	
  	for ($i=0;$i<$tasks["count"];$i++) 
  	{
  	
  		$t = new TASK($tasks[$i]["id"]);
  		$t->markComplete($this->apidata["taskComment"]);	//puts a comment showing this was forced closed
  	
  	}

		//figure out how many other tasks are left at this level
		$sql = "SELECT sort_order,workflow_id,task_type,
							(SELECT object_id FROM docmgr.dm_workflow WHERE id=workflow_id) AS object_id
							 FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."';";
		$info = $this->DB->single($sql);

		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE workflow_id='".$info["workflow_id"]."' 
								AND sort_order='".$info["sort_order"]."' AND status!='complete'";
		$list = $this->DB->fetch($sql);

		//set for beginWorkflow() later
		$this->workflowId = $info["workflow_id"];
		
		//if there are some left at this level, do nothing.If there are not, queue the approvers at the next stage
		if ($list["count"]=="0") 
		{

			$nextOrder = $info["sort_order"] + 1;

			//queue the tasks for the next stage.If this returns false, there are no objects left
			$this->nextStage($nextOrder);

		}

		//log in the object's logs
		if ($info["task_type"]=="approve") $lt = OBJ_WORKFLOW_APPROVE;
		else if ($info["task_type"]=="edit") $lt = OBJ_WORKFLOW_EDIT;
		else $lt = OBJ_WORKFLOW_VIEW;
		
		logEvent($lt,$info["object_id"]);

  	$this->DB->end();
  	
  	$err = $this->DB->error();
  	if ($err) $this->throwError($err);

	}

	public function rejectApproval() 
	{

		//make sure this user can edit this task
		$sql = "SELECT id FROM docmgr.dm_workflow_route WHERE id='".$this->routeId."' AND account_id='".USER_ID."'";
		$info = $this->DB->single($sql);
		
		if (!$info)
		{
			$this->throwError("You do not have permissions to edit this workflow task");
			return false;
		}

		$routeId = $this->routeId;
		
		$this->DB->begin();

		//figure out how many other tasks are left at this left
		$sql = "SELECT sort_order,workflow_id,
									(SELECT object_id FROM docmgr.dm_workflow WHERE dm_workflow.id=dm_workflow_route.workflow_id) AS object_id
									FROM docmgr.dm_workflow_route WHERE id='$routeId';";
		$info = $this->DB->single($sql);

		$workflowId = $info["workflow_id"];
		$objectId = $info["object_id"];

		//update this route and delete the task from the alert
		$sql = "UPDATE docmgr.dm_workflow_route SET status='rejected',comment='".$this->apidata["comment"]."' WHERE id='$routeId';";
		$sql .= "UPDATE docmgr.dm_workflow SET status='rejected',date_complete='".date("Y-m-d H:i:s")."' WHERE id='".$workflowId."';";
		$this->DB->query($sql);

  	//mark our tasks as complete if they have not been already
  	$sql = "SELECT id FROM task.view_docmgr_task WHERE workflow_id='".$workflowId."' AND completed='f'";                          
  	$tasks = $this->DB->fetch($sql);
  	
  	for ($i=0;$i<$tasks["count"];$i++) {
  	
  		$t = new TASK($tasks[$i]["id"]);
  		$t->markComplete("Object Rejected");	//puts a comment showing this was forced closed
  	
  	}

  	//clear all shares
  	$this->clearAllShares();
  	
  	//notify the user it's done
  	$this->sendCompleteNotify(1);

  	//log the rejection
  	logEvent(OBJ_WORKFLOW_REJECT,$objectId);

  	$this->DB->end();
  	
  	$err = $this->DB->error();
  	if ($err) $this->throwError($err);

	}

	//get all tasks for this user
	public function gettasks() 
	{

		$sql = "SELECT dm_workflow_route.*,dm_workflow.account_id AS workflow_account_id,
							object_id,(SELECT name FROM docmgr.dm_object WHERE id=object_id) AS object_name
							FROM docmgr.dm_workflow_route
							LEFT JOIN docmgr.dm_workflow ON dm_workflow_route.workflow_id=dm_workflow.id
							WHERE dm_workflow_route.account_id='".USER_ID."'
							";

		
		if ($this->routeId) $sql .= " AND dm_workflow_route.id='".$this->routeId."'";
		else	$sql .= "  AND dm_workflow_route.status='pending'";
						
		$routes = $this->DB->fetch($sql);

			for ($i=0;$i<$routes["count"];$i++) 
			{

				$info = returnAccountInfo($routes[$i]["workflow_account_id"]);
				$routes[$i]["workflow_account_name"] = $info["first_name"]." ".$info["last_name"];
				$routes[$i]["workflow_account_login"] = $info["login"];
				if ($routes[$i]["date_due"] > '1980-01-01') $routes[$i]["date_due"] = dateView($routes[$i]["date_due"]);
				else $routes[$i]["date_due"] = null;

				$routes[$i]["object_path"] = $this->objectPath($routes[$i]["object_id"]);

				$this->PROTO->add("task",$routes[$i]);

			}
	
	}

	public function delete() 
	{

		//permissions checking
		if (!$this->permCheck("edit"))
		{
			$this->throwError("You do not have permissions to edit this workflow");
			return false;
		}

		$workflowId = $this->workflowId;

		$this->DB->begin();

		//clear the shared folders
		$this->clearAllShares();
		
		//delete the tasks
		$sql = "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id='$workflowId';";
		$sql .= "DELETE FROM docmgr.dm_workflow WHERE id='$workflowId';";
	
		$this->DB->query($sql);

  	//mark our tasks as complete if they have not been already
  	$sql = "SELECT id FROM task.view_docmgr_task WHERE workflow_id='".$workflowId."'";
  	$tasks = $this->DB->fetch($sql);
  	
  	for ($i=0;$i<$tasks["count"];$i++) 
  	{
  	
  		$t = new TASK($tasks[$i]["id"]);
  		$t->delete();	//puts a comment showing this was forced closed
  	
  	}

		logEvent(OBJ_WORKFLOW_CLEAR,$this->objectId);

		$this->DB->end();
		
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
	
	}


	protected function clearShare($routeId)
	{

		//get the object for this workflow owned by this user
	
		$sql = "SELECT workflow_id,account_id FROM docmgr.dm_workflow_route WHERE id='".$routeId."'";
		$rinfo = $this->DB->single($sql);
		
		$sql = "SELECT id FROM docmgr.dm_object WHERE name='Workflow.".$this->workflowId."' AND 
																									hidden='t' AND 
																									object_owner='".$rinfo["account_id"]."'";
		$finfo = $this->DB->single($sql);
		
		//delete related file links
		$sql = "DELETE FROM docmgr.dm_object_perm WHERE workflow_id='".$this->workflowId."' AND account_id='".$rinfo["account_id"]."';";
		$sql .= "DELETE FROM docmgr.dm_object_parent WHERE workflow_id='".$this->workflowId."' AND account_id='".$rinfo["account_id"]."';";
		$this->DB->query($sql);

		//now delete the collection
		$d = new DOCMGR_OBJECT();
		$d->delete($finfo["id"],1);
		
	}
	
	protected function clearAllShares()
	{

		//delete related file links
		$sql = "DELETE FROM docmgr.dm_object_perm WHERE workflow_id='".$this->workflowId."';";
		$sql .= "DELETE FROM docmgr.dm_object_parent WHERE workflow_id='".$this->workflowId."';";
		$this->DB->query($sql);

		//get all related folders
		$sql = "SELECT id FROM docmgr.dm_object WHERE name='.Workflow".$this->workflowId."' AND hidden='t'";
		$list = $this->DB->fetch($sql);

		for ($i=0;$i<$list["count"];$i++)
		{

			//delete the folder.  don't do a permissions check on the delete
			$o = new DOCMGR_OBJECT();
			$o->delete($list[$i]["id"],1);
						
		}
	
	}


  /***********************************************************************
    FUNCTION:	addShare
    PURPOSE:	addShares the share settings for the current user, object,
              and the passed share accounts.  note, this stores a separate
							permission for the object for the passed user from what
							they may already have.  So, if the user has "view" and
							we give them "edit" here, they will have edit permisssions
							so long as the share is active.  The objperm::getuser function
							merges all set permissions so the highest given is available.
							Once the share is deleted, they will drop back to "view"
  ***********************************************************************/
  protected function addShare($levelName,$aid)
  {

  	$obj = $this->objectId;

		//edit level for user
    if ($levelName=="edit" || $levelName=="approve") $level = "edit";
    else if ($levelName=="comment" || $levelName=="view") $level = "view";
    
		//begin our transaction
    $this->DB->begin();
  
    //base permissions
    $cb = "00000000";

		//get child objects of this object so we can clear their permissions
		$d = new DOCMGR_OBJECT();
    $arr = $d->getChildObjects($obj);    
    $arr[] = $obj;
 
		//delete all permissions set for this account through the sharing utility
    $sql = "DELETE FROM docmgr.dm_object_perm WHERE account_id='".$aid."' AND workflow_id='".$this->workflowId."'";
    $this->DB->query($sql);

    if ($level=="edit") 
    {
    	//set edit mode
    	$cb = PERM::bit_set($cb,OBJ_EDIT);
    }
    else if ($level=="view") 
    {
    	//view only
    	$cb = PERM::bit_set($cb,OBJ_VIEW);
    }
    else
    {
    	//something wacky was passed
      $this->throwError("You passed an invalid share_level value.  your options are 'none','edit', and 'view'");
      break;
    }

    //add permissions for object
    $opt = null;
    $opt["object_id"] = $obj;
    $opt["type"] = "account";
    $opt["id"] = $aid;
    $opt["bitmask"] = $cb;
    $opt["workflow_id"] = $this->workflowId;
                                    
		//and set the permissions for the share user on the object, also reset perms on sub-objects if a collection
    DOCMGR_UTIL_OBJPERM::add($opt);

    //now we have to make a shared directory for this user, then put a dm_object_parent entry in there for them,
    //and send them an alert they have a new shared file waiting
    $folderId = $this->getWorkflowFolder($aid);

    //make an entry for this object in there, and that it's marked as from the share utility
    $opt = null;
    $opt["object_id"] = $obj;
    $opt["parent_id"] = $folderId;
    $opt["account_id"] = $aid;
    $opt["workflow_id"] = $this->workflowId;
    $this->DB->insert("docmgr.dm_object_parent",$opt);

		//end transaction
		$this->DB->end();

    $err = $this->DB->error();
    
    if ($err) $this->throwError($err);  
  
  }  


	/****************************************************************************
		FUNCTION:	getWorkflowFolder
		PURPOSE:	gets our shared folder in our home directory.  if it doesn't
							exist, create one
		INPUTS:		none
	****************************************************************************/
	protected function getWorkflowFolder($aid)
	{

		$retId = null;

		$a = new ACCOUNT($aid);
		$ainfo = $a->getInfo();

		$path = "/Users/".$ainfo["login"]."/.Workflow".$this->workflowId;
		$info = $this->objectFromPath(sanitize($path));

		if ($info) $retId = $info["id"];
		else
		{
		
			$objinfo = $this->objectFromPath("/Users/".$ainfo["login"]);
			$parentId = $objinfo["id"];
			
			//create a new folder to hold shared objects in.  we pretty much have to do this manually
			//to bypass api permission checking
			$option = null;
			$option["name"] = ".Workflow".$this->workflowId;
			$option["object_type"] = "collection";
			$option["version"] = 1;
			$option["create_date"] = date("Y-m-d H:i:s");
			$option["object_owner"] = $ainfo["id"];
			$option["last_modified"] = date("Y-m-d H:i:s");
			$option["modified_by"] = USER_ID;
			$option["protected"] = "f";
			$option["hidden"] = "t";
			
			//insert the collection
			$retId = $this->DB->insert("docmgr.dm_object",$option,"id");

			//setup the parent link for the collection
			$sql = "INSERT INTO docmgr.dm_object_parent (object_id,parent_id) VALUES ('".$retId."','$parentId');";
			$this->DB->query($sql);

			//inherit the parent's permissions
			DOCMGR_UTIL_OBJPERM::inherit($retId,$parentId);

		}

		//look for db errors
		$err = $this->DB->error();
		if ($err) 
		{
			$this->throwError($err);
			return false;
		} 
		else return $retId;
		
	}

}

