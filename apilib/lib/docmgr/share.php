<?php

/**************************************************************************
	CLASS:	share
	PURPOSE:	handle specific processing for user file sharing
**************************************************************************/
class DOCMGR_SHARE extends DOCMGR 
{

  /***********************************************************************
    FUNCTION:	getlist
    PURPOSE:	returns all share settings for the current user and
              current object
  ***********************************************************************/
  function getlist()
  {

    if (!is_array($this->objectId)) $this->objectId = array($this->objectId);
  
    $sql = "SELECT * FROM docmgr.dm_share WHERE object_id IN (".implode(",",$this->objectId).") AND account_id='".USER_ID."'";
    $list = $this->DB->fetch($sql);
    
    $a = new ACCOUNT();
    
    for ($i=0;$i<$list["count"];$i++)
    {
    
      //convert the shared account name
      $info = $a->cachedGetInfo($list[$i]["share_account_id"]);
      $list[$i]["share_account_name"] = $info["full_name"];

      //current perms of object based on hiearchy 
      if (PERM::is_set($list[$i]["bitmask"],OBJ_EDIT))	$list[$i]["bitmask_text"] = "edit";
      elseif (PERM::is_set($list[$i]["bitmask"],OBJ_VIEW))	$list[$i]["bitmask_text"] = "view";

    }
    
    unset($list["count"]);
    $list = arrayMSort($list,"share_account_name");

    $this->PROTO->add("share",$list);

  }

  /***********************************************************************
    FUNCTION:	getaccounts
    PURPOSE:	returns a list of all accounts we can share with
  ***********************************************************************/
  function getaccounts()
  {
  
    $a = new ACCOUNT();
    $filter = null;
    
    if ($this->apidata["search_string"])
    {
      $filter["login"] = $this->apidata["search_string"];
      $filter["name"] = $this->apidata["search_string"];
    }
    
    $results = $a->search($filter);

    for ($i=0;$i<$results["count"];$i++)
    {

    	//skip ourselves
    	if ($results[$i]["id"]==USER_ID) continue;
    
      $arr = array();
      $arr["id"] = $results[$i]["id"];
      $arr["name"] = $results[$i]["full_name"];
      $arr["login"] = $results[$i]["login"];
      
      $this->PROTO->add("account",$arr);

    }
  
  
  }

  /***********************************************************************
    FUNCTION:	save
    PURPOSE:	saves the share settings for the current user, object,
              and the passed share accounts.  note, this stores a separate
							permission for the object for the passed user from what
							they may already have.  So, if the user has "view" and
							we give them "edit" here, they will have edit permisssions
							so long as the share is active.  The objperm::getuser function
							merges all set permissions so the highest given is available.
							Once the share is deleted, they will drop back to "view"
  ***********************************************************************/
  function save()
  {
  
    if (!is_array($this->objectId)) $this->objectId = array($this->objectId);

		//begin our transaction
    $this->DB->begin();
  
    foreach ($this->objectId AS $obj)
    {

      //base permissions
      $cb = "00000000";

      //delete the current row
      $sql = "DELETE FROM docmgr.dm_share WHERE object_id='".$obj."' AND
                                            account_id='".USER_ID."' AND
                                            share_account_id='".$this->apidata["share_account_id"]."';";

			//we also need to delete shared settings on the children.  THINK OF THE CHILDREN!

			//get child objects of this object so we can clear their permissions
			$d = new DOCMGR_OBJECT();
      $arr = $d->getChildObjects($obj);    
      $arr[] = $obj;
 
			//delete all permissions set for this account through the sharing utility
      $sql .= "DELETE FROM docmgr.dm_object_perm WHERE object_id IN (".implode(",",$arr).") AND 
      																			account_id='".$this->apidata["share_account_id"]."' AND
      																			share='t';";

			//run the query
      $this->DB->query($sql);

			//handle passed permission setting.  "none" usually means we are deleting the share
      if ($this->apidata["share_level"]=="none") 
      {

      	//delete the parent entry
	      $sql = "DELETE FROM docmgr.dm_object_parent WHERE object_id='".$obj."' AND 
      																				account_id IN ('".USER_ID."','".$this->apidata["share_account_id"]."') AND
      																				share='t';";
				$this->DB->query($sql);

		    //now we have to make a shared directory for this user, then put a dm_object_parent entry in there for them,
		    //and send them an alert they have a new shared file waiting
	 		  $folderId = $this->getSharedWithFolder($this->apidata["share_account_id"]);

				//update the size of the folder
				DOCMGR_UTIL_COMMON::updateCollectionSizes($folderId);
				
      	//nothing more to do
      	continue;

      }
      else if ($this->apidata["share_level"]=="edit") 
      {
      	//set edit mode
      	$cb = PERM::bit_set($cb,OBJ_EDIT);
      }
      else if ($this->apidata["share_level"]=="view") 
      {
      	//view only
      	$cb = PERM::bit_set($cb,OBJ_VIEW);
      }
      else
      {
      	//something wacky was passed
        $this->throwError("You passed an invalid share_level value.  your options are 'edit' and 'view'");
        break;
      }

      //build the share query
      $opt = null;
      $opt["object_id"] = $obj;
      $opt["account_id"] = USER_ID;
      $opt["share_account_id"] = $this->apidata["share_account_id"];
      $opt["bitmask"] = $cb;

			//run it      
      $this->DB->insert("docmgr.dm_share",$opt);    

      //add the permission
			$opt = null;
			$opt["object_id"] = $obj;
			$opt["type"] = "account";
			$opt["id"] = $this->apidata["share_account_id"];
			$opt["bitmask"] = $cb;
			$opt["share"] = "t";

			//and set the permissions for the share user on the object, also reset perms on sub-objects if a collection
      DOCMGR_UTIL_OBJPERM::add($opt);

    	//see if we have to link the user to the object via sharing
    	$sql = "SELECT object_id FROM docmgr.dm_object_parent WHERE object_id='$obj' AND
    																										account_id='".$this->apidata["share_account_id"]."' AND 
    																										share='t';";
			$info = $this->DB->single($sql);

			//it's not shared yet, so set it up				
			if (!$info)
			{

		    //now we have to make a shared directory for this user, then put a dm_object_parent entry in there for them,
		    //and send them an alert they have a new shared file waiting
  		  $folderId = $this->getSharedWithFolder($this->apidata["share_account_id"]);

  		  //make sure an object with the same name doesn't already exist in their shared folder
  		  $sql = "SELECT name FROM docmgr.dm_object WHERE id='$obj'";
  		  $info = $this->DB->single($sql);
  		  
  		  if (!checkObjName($GLOBALS["conn"],sanitize($info["name"]),$folderId))
  		  {
  		  	$this->throwError("An object named \"".$info["name"]."\" already exists in this user's Shared Folder collection");
  		  	return false;
				}

		    //make an entry for this object in there, and that it's marked as from the share utility
		    $opt = null;
		    $opt["object_id"] = $obj;
	      $opt["parent_id"] = $folderId;
	      $opt["account_id"] = $this->apidata["share_account_id"];
	      $opt["share"] = "t";
	      $this->DB->insert("docmgr.dm_object_parent",$opt);

				//update the size of the folder
				DOCMGR_UTIL_COMMON::updateCollectionSizes($folderId);

				//show an alert that the object was shared w/ that user
				$opt = null;
				$opt["account_id"] = $this->apidata["share_account_id"];
				$opt["object_id"] = $obj;
				$opt["alert_type"] = "OBJ_SHARE_ALERT";
				$this->DB->insert("docmgr.dm_alert",$opt);

			}
			                               
		}  

		//end transaction
		$this->DB->end();
  
    $err = $this->DB->error();
    
    if ($err) $this->throwError($err);  
  
  }  


	/****************************************************************************
		FUNCTION:	getSharedWithFolder
		PURPOSE:	gets our shared folder in our home directory.  if it doesn't
							exist, create one
		INPUTS:		none
	****************************************************************************/
	protected function getSharedWithFolder($aid)
	{

		$retId = null;

		$a = new ACCOUNT($aid);
		$ainfo = $a->getInfo();

		$path = "/Users/".$ainfo["login"]."/Shared With Me";
		$info = $this->objectFromPath(sanitize($path));

		if ($info) $retId = $info["id"];
		else
		{

		
			$objinfo = $this->objectFromPath("/Users/".$ainfo["login"]);
			$parentId = $objinfo["id"];
			
			//create a new folder to hold shared objects in.  we pretty much have to do this manually
			//to bypass api permission checking
			$option = null;
			$option["name"] = "Shared With Me";
			$option["object_type"] = "collection";
			$option["version"] = 1;
			$option["create_date"] = date("Y-m-d H:i:s");
			$option["object_owner"] = $ainfo["id"];
			$option["last_modified"] = date("Y-m-d H:i:s");
			$option["modified_by"] = USER_ID;
			$option["protected"] = "f";

			//insert the collection
			$retId = $this->DB->insert("docmgr.dm_object",$option,"id");

			//setup the parent link for the collection
			$sql = "INSERT INTO docmgr.dm_object_parent (object_id,parent_id) VALUES ('".$retId."','$parentId');";
			$this->DB->query($sql);

			//inherit the parent's permissions
			DOCMGR_UTIL_OBJPERM::inherit($retId,$parentId);

			//make a bookmark
			$opt = null;
			$opt["name"] = "Shared With Me";
			$opt["account_id"] = $aid;
			$opt["expandable"] = "t"; 
      $opt["object_id"] = $retId;
      $this->DB->insert("docmgr.dm_bookmark",$opt);

			                                          
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

	public function deleteShare($obj=null,$aid=null)
	{

		if (!$obj) $obj = $this->objectId;
		if (!$aid) $aid = $this->apidata["share_account_id"];
		
		$ret = false;
		
		//see if the user is being shared this object
		$sql = "SELECT object_id,account_id,share_account_id FROM docmgr.dm_share WHERE object_id='$obj' AND share_account_id='".$aid."'";
		$info = $this->DB->single($sql);	

		if ($info)
		{

			//delete linked share info		
			$sql = "DELETE FROM docmgr.dm_object_perm WHERE object_id='$obj' AND account_id='".$info["share_account_id"]."' AND share='t';";
			$sql .= "DELETE FROM docmgr.dm_object_parent WHERE object_id='$obj' AND account_id='".$info["share_account_id"]."' AND share='t';";
			$sql .= "DELETE FROM docmgr.dm_share WHERE object_id='$obj' AND 
																									account_id='".$info["account_id"]."' AND
																									share_account_id='".$info["share_account_id"]."';";
			$this->DB->query($sql);

			$ret = true;	

	    //now we have to make a shared directory for this user, then put a dm_object_parent entry in there for them,
	    //and send them an alert they have a new shared file waiting
 		  $folderId = $this->getSharedWithFolder($info["share_account_id"]);

			//update the size of the folder
			DOCMGR_UTIL_COMMON::updateCollectionSizes($folderId);
				
		}
		
		return $ret;

	}

//end class
}


