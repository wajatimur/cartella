<?php

/**********************************************************************
	CLASS:	DISCUSSION
	PURPOSE:	handles specific processing for document objects
**********************************************************************/
class DOCMGR_DISCUSSION extends DOCMGR 
{

  /********************************************************************
  	FUNCTION: getlist
  	PURPOSE:  gets list of topics for the object
	********************************************************************/
	public function getlist() 
	{

		$sql = "SELECT dm_discussion.* FROM docmgr.dm_discussion
								WHERE object_id='".$this->objectId."' AND owner='0' ORDER BY time_stamp DESC";
		$list = $this->DB->fetch($sql);

		for ($i=0;$i<$list["count"];$i++) {

			$sql = "SELECT max(time_stamp) FROM docmgr.dm_discussion WHERE owner='".$list[$i]["id"]."'";
			$info = $this->DB->single($sql);
		
			$list[$i]["time_stamp_view"] = dateView($list[$i]["time_stamp"]);
			$list[$i]["reply_time_stamp"] = $info["max"];
			$list[$i]["reply_time_stamp_view"] = dateView($info["max"]);
			$list[$i]["account_name"] = returnAccountName($list[$i]["account_id"]);

			$this->PROTO->add("topic",$list[$i]);
		
		}
	
	}

  /********************************************************************
  	FUNCTION: getthread
  	PURPOSE:  gets the thread lsit for the current topic
	********************************************************************/
	public function getthread() 
	{

		$sql = "SELECT header FROM docmgr.dm_discussion WHERE id='".$this->apidata["topic_id"]."'";
		$info = $this->DB->single($sql);
	
		$sql = "SELECT dm_discussion.* FROM docmgr.dm_discussion WHERE object_id='".$this->objectId."' 
						AND (owner='".$this->apidata["topic_id"]."' OR id='".$this->apidata["topic_id"]."') ORDER BY time_stamp DESC";
		$list = $this->DB->fetch($sql);

		$this->PROTO->add("thread_name",$info["header"]);
		
		for ($i=0;$i<$list["count"];$i++) {
		
			$list[$i]["time_stamp_view"] = dateView($list[$i]["time_stamp"]);
			$list[$i]["account_name"] = returnAccountName($list[$i]["account_id"]);
						
			$this->PROTO->add("topic",$list[$i]);
		
		}

	}

  /********************************************************************
  	FUNCTION: newTopic
  	PURPOSE:  save new topic
	********************************************************************/
	public function newtopic() 
	{
	
		$opt = null;
		$opt["object_id"] = $this->objectId;
		$opt["header"] = $this->apidata["message_subject"];
		$opt["account_id"] = USER_ID;
		$opt["content"] = $this->apidata["editor_content"];
		$opt["owner"] = "0";
		$opt["time_stamp"] = date("Y-m-d H:i:s");
		
		$topicId = $this->DB->insert("docmgr.dm_discussion",$opt,"id");
		
		if ($topicId) 
		{
			sendEventNotify($this->objectId,"OBJ_COMMENT_POST_ALERT");
			$this->PROTO->add("topic_id",$topicId);
		}
		else $this->throwError("Error inserting new topic");	
	
	}

  /********************************************************************
  	FUNCTION: replyTopic
  	PURPOSE:  post reply to topic
	********************************************************************/
	public function reply() 
	{
	
		$opt = null;
		$opt["object_id"] = $this->objectId;
		$opt["header"] = $this->apidata["message_subject"];
		$opt["account_id"] = USER_ID;
		$opt["content"] = $this->apidata["editor_content"];
		$opt["owner"] = $this->apidata["topic_id"];
		$opt["time_stamp"] = date("Y-m-d H:i:s");
		
		$replyId = $this->DB->insert("docmgr.dm_discussion",$opt,"id");
		
		if ($replyId) 
		{
			sendEventNotify($this->objectId,"OBJ_COMMENT_POST_ALERT");
			$this->PROTO->add("reply_id",$replyId);
		}
		else $this->throwError("Error inserting topic reply");	
	
	}

  /********************************************************************
  	FUNCTION: editTopic
  	PURPOSE:  update existing topic
	********************************************************************/
	public function edit() 
	{

		if (!$this->checkPerms())
		{
			$this->throwError("You do not have permissions to edit this topic");
		}
		else
		{
	
			$opt = null;
			$opt["content"] = $this->apidata["editor_content"];
			//$opt["time_stamp"] = date("Y-m-d H:i:s");
			$opt["where"] = "id='".$this->apidata["topic_id"]."'";
			$this->DB->update("docmgr.dm_discussion",$opt);
			
			$err = $this->DB->error();
			if ($err) $this->throwError($err);
		
		}
		
	}

  /********************************************************************
  	FUNCTION: deleteTopic
  	PURPOSE:  delete the current topic
	********************************************************************/
	public function delete() 
	{

		if (!$this->checkPerms())
		{
			$this->throwError("You do not have permissions to delete this topic");
		}
		else
		{

			//delete topic and all its children.  	
			$sql = "DELETE FROM docmgr.dm_discussion WHERE id='".$this->apidata["topic_id"]."' OR owner='".$this->apidata["topic_id"]."'";
			$this->DB->query($sql);
		
			$err = $this->DB->error();
			if ($err) $this->throwError($err);

		}
			
	}

	protected function checkPerms()
	{

		$check = true;
	
		//make sure we own this topic that we are editing
		if (!PERM::check(ADMIN))
		{
		
			$sql = "SELECT id FROM docmgr.dm_discussion WHERE id='".$this->apidata["topic_id"]."' AND account_id='".USER_ID."'";
			$info = $this->DB->single($sql);
			
			if (!$info) $check = false;
		
		}
	
		return $check;	

	}
			
}
	
