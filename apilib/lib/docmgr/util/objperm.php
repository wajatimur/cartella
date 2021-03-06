<?php

/****************************************************************************
	CLASS:	OBJECT
	PURPOSE:	master function for managing docmgr objects.  this includes
				    creating, saving, update, moving, or deleting
****************************************************************************/

class DOCMGR_UTIL_OBJPERM
{

	/**************************************************************************
		FUNCTION:	savePermissions
		PURPOSE:	stores account and group permissions for the object
	**************************************************************************/
	public function save($objId,$permarr,$reset = null) 
	{

		global $DB;

		$objarr = array($objId);

		//init a docmgr instance so we can get some info about the object
		$d = new DOCMGR($objId);
		$info = $d->getObjectInfo();			

		//reset permissions on all children of this object.  this only works if it's a collection
		if ($info["object_type"]=="collection")
		{
		
			if ($reset)
			{
				$children = $d->getChildObjects($objId);
				if ($children) $objarr = array_merge($objarr,$children);
			}
							
		}
		//otherwise we always reset the children, because they are storage or page folders of files/documents
		else
		{

			$children = $d->getChildObjects($objId);
			if ($children) $objarr = array_merge($objarr,$children);
		
		}

		foreach ($objarr AS $obj)
		{
		
			//first, clear out existing permissions
			$sql = "DELETE FROM docmgr.dm_object_perm WHERE object_id='".$obj."';";
			$DB->query($sql);
			
			if (count($permarr)>0) 
			{
			
				foreach ($permarr AS $perm) 
				{
	
					//the field we are updating
					if ($perm["type"]=="account") $field = "account_id";
					else $field = "group_id";

					$bitmask = DOCMGR_UTIL_OBJPERM::textToBit($perm["value"]);

					//if passed, label as a share-only permission
					if ($perm["share"]) $share = $perm["share"];
					else $share = "f";

					if ($perm["workflow_id"]) $workflow_id = $perm["workflow_id"];
					else $workflow_id = "0";
	
					$opt = null;
					$opt["object_id"] = $obj;
					$opt[$field] = $perm["id"];
					$opt["bitmask"] = $bitmask;
					$opt["share"] = $share;
					$opt["workflow_id"] = $workflow_id;
					$DB->insert("docmgr.dm_object_perm",$opt);
				
				}
			
			}

		}

		return true;

	}

	//new is an array with "id,type,value"
	public function add($opts)
	{

		global $DB;
	
		$objId = $opts["object_id"];
		$type = $opts["type"];
		$id = $opts["id"];
		$bitmask = $opts["bitmask"];
		$share = $opts["share"];
		$workflowId = $opts["workflow_id"];
		$reset = $opts["reset"];
	
    //get current permissions for this object
    $sql = "SELECT * FROM docmgr.dm_object_perm WHERE object_id='".$objId."'";
    $perms = $DB->fetch($sql);

    $p = array();
   
   	//basically, rebuild to submit 
    for ($i=0;$i<$perms["count"];$i++)
    {
    
    	$arr = array();
    	
    	if ($perms[$i]["group_id"])
    	{
				$arr["type"] = "group";
				$arr["id"] = $perms[$i]["group_id"];
			}
			else
			{
				$arr["type"] = "account";
				$arr["id"] = $perms[$i]["account_id"];
			}

			$arr["value"] = DOCMGR_UTIL_OBJPERM::bitToText($perms[$i]["bitmask"]);

			//if this matches the account or group for our new entry, skip it, because we are replacing it
			if ($arr["type"]!=$type || $arr["id"]!=$id) $p[] = $arr;

		}
		
		//and now the new one
		$arr = array();
		$arr["type"] = $type;
		$arr["id"] = $id;
		$arr["value"] = DOCMGR_UTIL_OBJPERM::bitToText($bitmask);
		
		if ($share) $arr["share"] = $share;
		if ($workflowId) $arr["workflow_id"] = $workflowId;
		
		$p[] = $arr;

		DOCMGR_UTIL_OBJPERM::save($objId,$p,$reset);

	}

	
	/**************************************************************************
		FUNCTION:	getPerms
		PURPOSE:	returns a list of all groups and accounts in the system, and
							what their permissions are for the current object
	**************************************************************************/
	public function getList($objId,$filters) 
	{

		global $DB,$PROTO;
	
		$search_string = $filters["search_string"];
		$perm_filter = $filters["perm_filter"];

		//get permissions for this 
		$sql = "SELECT account_id,group_id,bitmask FROM docmgr.dm_object_perm WHERE object_id='".$objId."'";
		$objPerm = $DB->fetch($sql,1);

		if ($perm_filter!="groups") 
		{
			
			//get matching accounts

			//setup our filter
			$sf = array();

			if ($filters["location_filter"]) $sf["childLocationId"] = $filters["location_filter"];  //ewp specific option
	
			if ($search_string) {

				$sf["login"] = $search_string;
				$sf["name"] = $search_string;

			}

			$a = new ACCOUNT();
			$accountList = $a->getList($sf,"name");

			if (!$accountList["count"]) $accountList["count"] = count($accountList);

		} 
		
		if ($perm_filter!="accounts") 
		{
		
			//get our groups sorted by name and limit by our filter
			$sql = "SELECT * FROM auth_groups";

			//setup group filter 
			$filter = array();
			if ($filters["location_filter"]) $filter[] = " (child_location_id='".$filters["location_filter"]."' OR child_location_id='0') ";		//ewp specific filter
			if ($search_string) $filter[] = " name ILIKE '".$search_string."%' ";

			//assemble the filter
			if (count($filter)>0) $sql .= " WHERE ".implode(" AND ",$filter)." ";
			$groupList = $DB->fetch($sql);

		}

		$searchResults = array();
		
		for ($i=0;$i<$groupList["count"];$i++) {
		
			$searchResults["id"][] = $groupList[$i]["id"];
			$searchResults["name"][] = $groupList[$i]["name"];
			$searchResults["type"][] = "group";
		
		}  
	
		for ($i=0;$i<$accountList["count"];$i++) {

			$searchResults["id"][] = $accountList[$i]["id"];
			$searchResults["name"][] = $accountList[$i]["full_name"];
			$searchResults["type"][] = "account";
		
		}

		$num = count($searchResults["id"]);

		/**************************************************
			handle the Everyone group
		**************************************************/
    //figure out if the everyone box is checked
    if (@in_array("0",$objPerm["group_id"]))
    {
    	$key = array_search("0",$objPerm["group_id"]);
			$checkBitset = $objPerm["bitmask"][$key];
		}

		//if there is a bitmask for this account set, check it out
		$perm = null;

		if ($checkBitset) 
		{
      if (PERM::is_set($checkBitset,OBJ_ADMIN)) 			$perm = "admin";
		  elseif (PERM::is_set($checkBitset,OBJ_EDIT)) 	$perm = "edit";
		  elseif (PERM::is_set($checkBitset,OBJ_VIEW)) 	$perm = "view";
		}

		//add the "Everyone" entry
		$arr = array();
		$arr["id"] = "0";
		$arr["name"] = "Everyone";
		$arr["type"] = "group";
		if ($perm) $arr["perm"] = $perm;
		$PROTO->add("entry",$arr);

	  /**************************************************
	  	handle the rest of the groups
		**************************************************/
		for ($i=0;$i<$num;$i++) 
		{
	
		  $perm = null;
			$checkBitset = null;
				 
		  //if there's an object, set which ones are checked
		  if ($objPerm) 
		  {
	
		    //figure out if a box is checked
		    if ($searchResults["type"][$i]=="group" && @in_array($searchResults["id"][$i],$objPerm["group_id"])) 
		    {
		      $key = array_search($searchResults["id"][$i],$objPerm["group_id"]);
		      $checkBitset = $objPerm["bitmask"][$key];
		    } 
		    else if ($searchResults["type"][$i]=="account" && @in_array($searchResults["id"][$i],$objPerm["account_id"])) 
		    {
		      $key = array_search($searchResults["id"][$i],$objPerm["account_id"]);
		      $checkBitset = $objPerm["bitmask"][$key];	
		    }
	
		    if ($checkBitset) 
		    {
		      if (PERM::is_set($checkBitset,OBJ_ADMIN)) 			$perm = "admin";
		      elseif (PERM::is_set($checkBitset,OBJ_EDIT)) 	$perm = "edit";
		      elseif (PERM::is_set($checkBitset,OBJ_VIEW)) 	$perm = "view";
		    }
	
		    //skip if looking for only selected entries    
		    if (!$perm && $perm_filter=="selected") continue;
		
		  }

		  $arr = array();
		  $arr["id"] = $searchResults["id"][$i];
		  $arr["name"] = $searchResults["name"][$i];
		  $arr["type"] = $searchResults["type"][$i];
		  if ($perm) $arr["perm"] = $perm;

		  $PROTO->add("entry",$arr);

		}

	  DOCMGR_UTIL_OBJPERM::current($objId);

	  return true;
		
	}
	
	public function current($objId)
	{

		global $DB,$PROTO;
		
	  //get the current user's permissions
	  $sql = "SELECT bitmask,object_owner FROM docmgr.dm_object_perm 
	  					LEFT JOIN docmgr.dm_object ON object_id=id
	  					WHERE object_id='".$objId."' AND
										(account_id='".USER_ID."' OR group_id IN (".USER_GROUPS."))";

		$list = $DB->fetch($sql,1);
		
		if ($list["count"] > 0)
		{
		
			$permval = min($list["bitmask"]);
			
      if ($list["object_owner"][0]==USER_ID || PERM::is_set($permval,OBJ_ADMIN)) 			$perm = "admin";
      elseif (PERM::is_set($permval,OBJ_EDIT)) 	$perm = "edit";
      elseif (PERM::is_set($permval,OBJ_VIEW)) 	$perm = "view";
		
      $PROTO->add("current_object_perm",$perm);
		
		} else 
		{
		
      $PROTO->add("current_object_perm","none");
		
		}
	
	}


	public function inherit($objId,$parentId = null)
	{
	
		global $DB;
	
	  //get the parent id if not passed
	  if ($parentId==null) 
	  {
	
	    $sql = "SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id='".$objId."';";
	    $info = $DB->single($sql);
	    $parentId = $info["parent_id"]; 

		}
		
    //we have a parent, pull it's permissions
    if ($parentId!=null) 
    {
	    
		  //get the parent's permissions
		  $sql = "SELECT * FROM docmgr.dm_object_perm WHERE object_id='$parentId' AND (account_id IS NOT NULL OR group_id IS NOT NULL)";
		  $list = $DB->fetch($sql);
	    
    }

    //if we found permissions, use those.  Otherwise, use default
    //if passed noinherit, then just use default permissions
    if ($list["count"]>0)
    {

    	$perm = array();

			//insert the new ones
			for ($i=0;$i<$list["count"];$i++) 
			{

				//convert our bitmask numbers to their text versions
				if (PERM::is_set($list[$i]["bitmask"],OBJ_ADMIN)) $str = "admin";
				else if (PERM::is_set($list[$i]["bitmask"],OBJ_EDIT)) $str = "edit";
				else if (PERM::is_set($list[$i]["bitmask"],OBJ_VIEW)) $str = "view";

				$arr = array();
				
				if ($list[$i]["account_id"]) 
				{
					$arr["id"] = $list[$i]["account_id"];
					$arr["type"] = "account";
				} 
				else 
				{
					$arr["id"] = $list[$i]["group_id"];
					$arr["type"] = "group";
				}
				
				$arr["value"] = $str;				
				$arr["share"] = $list[$i]["share"];
				
				$perm[] = $arr;
				
			}

			DOCMGR_UTIL_OBJPERM::save($objId,$perm);
			   
    } 
	 
	}

	public function getUserText($bitval,$owner) 
	{

		$bit = null;
		$txt = null;
		
		//get the bitmask for this object if the user isn't an admin
		if (PERM::check(ADMIN))
		{
		
			$bit = OBJ_ADMIN;
			$txt = "admin";
		
		} 
		else 
		{
		
			$txt = "none";
		
			if (!$bitval) 
			{
				$bitmask = OBJ_EDIT;
				$txt = "edit";
			}
		
			if ($owner==USER_ID) 
			{

				$bit = OBJ_ADMIN;
				$txt = "admin";

			} 
			else 
			{
		
				//current perms of object based on hiearchy 
				if (PERM::is_set($bitval,OBJ_ADMIN)) $txt = "admin";
				elseif (PERM::is_set($bitval,OBJ_EDIT))$txt = "edit";
				elseif (PERM::is_set($bitval,OBJ_VIEW))$txt = "view";
				elseif (PERM::is_set($bitval,OBJ_TASK))$txt = "task";
		
			}
		
		}

		$arr = array();
		$arr["bitmask"] = $bit;
		$arr["bitmask_text"] = $txt;
		
		return $arr;
		
	}

	public function addToObject($data) 
	{

		global $DB;
		
		//if the user isn't an admin, requery the database and get permissions for our current objects
		if ($data["count"] > 0)
		{

			$idArr = array();
			foreach ($data AS $curObj) if ($curObj["id"]) $idArr[] = $curObj["id"];

			//query all the passed objects at once so we only have to hit the DB once.  the extra share subquery will
			//make sure only root shared objects (not objects in a shared collection) will show as "shared"
			$sql = "SELECT dm_object_perm.*,
									(SELECT object_id FROM docmgr.dm_share WHERE dm_share.object_id=dm_object_perm.object_id AND share_account_id='".USER_ID."') AS share_object_id 
									FROM docmgr.dm_object_perm WHERE object_id IN (".implode(",",$idArr).");";
			$permArr = $DB->fetch($sql,1);
		
			//shortcut.  site admins get full permissions no matter what
			if (PERM::check(ADMIN))
			{
			
				for ($i=0;$i<$data["count"];$i++)
				{

					$data[$i]["bitmask"] = "00000001";
					$data[$i]["bitmask_text"] = "admin";

					//merge in whether or not this is shared w/ the current user
					$data[$i]["share"] = DOCMGR_UTIL_OBJPERM::getObjShare($data[$i]["id"],$permArr);

				}
			
			}
			else
			{
	
				//spit out our search results
				for ($i=0;$i<$data["count"];$i++) 
				{
	
					//get this user's permissions for this object
					$bitmask = DOCMGR_UTIL_OBJPERM::getObjBitmask($data[$i]["id"],$permArr);
	
					//convert the bitmask value into something the client can use
					$arr = DOCMGR_UTIL_OBJPERM::getUserText($bitmask,$data[$i]["object_owner"]);
					$data[$i] = array_merge($data[$i],$arr);

					//merge in whether or not this is shared w/ the current user
					$data[$i]["share"] = DOCMGR_UTIL_OBJPERM::getObjShare($data[$i]["id"],$permArr);
				
				}
	
			}
					
		}
		
		return $data;

	}

	//get the user's bitmask for the object from the array of permissions passed
	public function getObjBitmask($objId,$arr) 
	{
	
		if (!is_array($arr)) return false;
		
		//return all permissions that pertain to this user and this object
		
		//first, narrow it down to the object
		$keys = @array_keys($arr["object_id"],$objId);
		
		if (count($keys) == 0) return false;
		
		$groupArray = @explode(",",USER_GROUPS);
		$bitmask = "00000000";

		//now loop through our keys and start stacking up permissions for this user or their groups
		foreach ($keys AS $key) 
		{

			//first get the account_id
			if ($arr["account_id"][$key]==USER_ID) $bitmask = PERM::bit_or($bitmask,$arr["bitmask"][$key]);
			
			if ($arr["group_id"][$key]!=NULL) 
			{
				if (@in_array($arr["group_id"][$key],$groupArray)) $bitmask = PERM::bit_or($bitmask,$arr["bitmask"][$key]);
			}
		
		}
		 
		return $bitmask;
	 
	}
	 	
	//get the user's bitmask for the object from the array of permissions passed
	public function getObjShare($objId,$arr) 
	{

		$share = "f";
			
		if (!is_array($arr)) return $share;

		//first, narrow it down to the object
		$keys = @array_keys($arr["object_id"],$objId);

		if (count($keys) == 0) return $share;
		
		//now loop through our keys and start stacking up permissions for this user or their groups
		foreach ($keys AS $key) 
		{

			//first get the account_id
			if ($arr["account_id"][$key]==USER_ID && $arr["share_object_id"][$key]) 
			{
				$share = $arr["share"][$key];
				break;
			}
		}			

		return $share;
	 
	}
	 	

	public function check($objBit,$perm)
	{

		$check = false;

		//if nothing set, allow view only
		//if (!strstr($objBit,"1"))
		//{
		//	if ($perm=="view") $check = true;	
		//}
		//else
		//{
		
			if ($perm=="admin")	
			{
	
				if (PERM::is_set($objBit,OBJ_ADMIN)) $check = true;
			
			}	
			else if ($perm=="edit")	
			{
	
				if (PERM::is_set($objBit,OBJ_ADMIN) ||
						PERM::is_set($objBit,OBJ_EDIT)
						) $check = true;
			
			}	
			else if ($perm=="view")	
			{
	
				if (PERM::is_set($objBit,OBJ_ADMIN) ||
						PERM::is_set($objBit,OBJ_EDIT) ||
						PERM::is_set($objBit,OBJ_VIEW)
						) $check = true;
	
			}	
			
		//}
		
		return $check;
	
	}

	//return a query to filter our objects to only allow those a non-admin can see
	public function query() 
	{
	
		$sql = "(";
	
		//if there is an entry for a group this user belongs to, they can see the object.
		if (defined("USER_GROUPS") && strlen(USER_GROUPS)>0)
			$sql .= " group_id IN (".USER_GROUPS.") OR ";
	
		$sql .= " account_id='".USER_ID."' OR ";
	
		//set default permissions for a file if no perms are set
		if (DOCMGR_UTIL_OBJPERM_LEVEL=="strict" || PERM::check(GUEST_ACCOUNT,1))
			$sql .= " object_owner='".USER_ID."')";
		else
			$sql .= " bitmask ISNULL)";
	
		return $sql;
	
	}

  /**********************************************************************
    FUNCTION:	getuser
    PURPOSE:	return user permissions for the object
  **********************************************************************/
  public function getUser($objId) 
  {
  
  	global $DB;
  	
    $cb = "00000000";
    
	  //give the user admin access to all files
	  if (PERM::check(ADMIN)) $cb = PERM::bit_set($cb,OBJ_ADMIN);

	  //check for other object properties which an admin doesn't necessarily have access to
	  $sql = "SELECT object_owner,status,status_owner, 
	            (SELECT id FROM docmgr.dm_view_workflow WHERE object_id='$objId' AND account_id='".USER_ID."' AND status='pending' LIMIT 1) AS task
	            FROM docmgr.dm_object WHERE id='$objId';";
	  $info = $DB->single($sql);

	  //if it's the file's owner, give them all rights
	  if ($info["object_owner"]==USER_ID) $cb = PERM::bit_set($cb,OBJ_ADMIN);

	  //if this is set, there is a pending task for this user on this object
	  if ($info["task"]) $cb = PERM::bit_set($cb,OBJ_TASK);

	  //get the rest of the permissions for this file if it's a non-admin
	  if (!PERM::check(ADMIN))
	  {
	  
	    //get the file's info 
	    $sql = "SELECT status,status_owner,account_id,group_id,bitmask FROM docmgr.dm_view_perm WHERE object_id='$objId'";
	    $perm = $DB->fetch($sql,1);

	    if (!$perm["count"]) 
	    {
	
	      //if no perms are set, give them view perms unless we are in strict mode or a guest account
	      if (DOCMGR_UTIL_OBJPERM_LEVEL!="strict" && !PERM::check(GUEST_ACCOUNT,1))
	      {
	      	$cb = PERM::bit_set($cb,OBJ_VIEW);
				}
				
	    } 
	    else 
	    {

	      //extract the group ids into an array from our define
	      $gArr = array();
	      if (strlen(USER_GROUPS) > 0) $gArr = explode(",",USER_GROUPS);

	      //figure out the user's permissions for this file based on their account id and
	      //groups they belong to
	      for ($i=0;$i<$perm["count"];$i++) 
	      {

	        if ($perm["account_id"][$i]==USER_ID) $cb = PERM::bit_or($cb,$perm["bitmask"][$i]);
	        else if (in_array($perm["group_id"][$i],$gArr)) $cb = PERM::bit_or($cb,$perm["bitmask"][$i]);
	            
	      }    
	    
	    }
	
	  }

	  //make sure all permissions run downhill
	  if (PERM::is_set($cb,OBJ_ADMIN)) $cb = PERM::bit_set($cb,OBJ_EDIT);
	  if (PERM::is_set($cb,OBJ_EDIT)) $cb = PERM::bit_set($cb,OBJ_VIEW);

	  return $cb;
	
	}

	public function bitToText($bitval)
	{
	
		//current perms of object based on hiearchy 
		if (PERM::is_set($bitval,OBJ_ADMIN)) $txt = "admin";
		elseif (PERM::is_set($bitval,OBJ_EDIT))$txt = "edit";
		elseif (PERM::is_set($bitval,OBJ_VIEW))$txt = "view";
		elseif (PERM::is_set($bitval,OBJ_TASK))$txt = "task";
 
		
		return $txt;
		
	}

	public function textToBit($txt)
	{
	
		$blank = "00000000";
	
		//the bitmask value			
		if ($txt=="admin") 				$bitmask = PERM::bit_set($blank,OBJ_ADMIN);
		elseif ($txt=="edit") 		$bitmask = PERM::bit_set($blank,OBJ_EDIT);
		elseif ($txt=="view") 		$bitmask = PERM::bit_set($blank,OBJ_VIEW);
		else											$bitmask = $blank;
	
		return $bitmask;
	
	}

}


		