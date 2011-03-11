<?php

/**************************************************************************
	CLASS:	bookmark
	PURPOSE:	handle specific processing for bookmarks
**************************************************************************/

class DOCMGR_BOOKMARK extends DOCMGR 
{

  protected function checkPerms()
  {

  	$check = true;

		//if passed a bookmark, make sure we own it or are an admin
		if ($this->apidata["bookmark_id"] && !PERM::check(ADMIN))
		{

			$sql = "SELECT account_id FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
			$info = $this->DB->single($sql);
			
			if ($info["account_id"]!=USER_ID) 
			{
				$check = false;
				$this->throwError("You do not have permissions to edit this bookmark");  
			}
					
		}  

		//passed an account id that's not ours and non-admin
		if (	$check==true && 
					$this->apidata["account_id"] && 
					$this->apidata["account_id"]!=USER_ID && 
					!PERM::check(ADMIN)
					)
		{
			$check = false;
			$this->throwError("You do not have permissions to edit bookmarks for other users");
		}

		return $check;
  
  }

	/***********************************************************************
		FUNCTION: get
		PURPOSE:	pulls a list of all bookmarks for this user
	***********************************************************************/
	public function get() 
	{

		//permissions checking
		if (!$this->checkPerms()) return false;

		//allow admins to edit other user's bookmarks
		if ($this->apidata["account_id"]) $aid = $this->apidata["account_id"];
		else $aid = USER_ID;

		//get the children of the bookmark
		$sql = "SELECT docmgr.dm_bookmark.*,
									(SELECT count(id) FROM docmgr.dm_view_colsearch WHERE parent_id=docmgr.dm_bookmark.object_id AND hidden='f') AS child_count
									 FROM docmgr.dm_bookmark WHERE account_id='$aid' ORDER BY lower(name)";
		$list = $this->DB->fetch($sql);

		if ($list["count"] > 0)
		{

			for ($i=0;$i<$list["count"];$i++) 
			{
	
	      $pids = DOCMGR_UTIL_COMMON::resolvePathIds($list[$i]["object_id"]);
				$list[$i]["object_path"] = DOCMGR_UTIL_COMMON::idToPath($pids);
	                  
				//$list[$i]["object_path"] = $this->objectPath($list[$i]["object_id"]);
	
				//get how many children we have here
				$this->PROTO->add("bookmark",$list[$i]);
	
			}

		}
		
	}

	/***********************************************************************
		FUNCTION: save
		PURPOSE:	pulls a list of all bookmarks for this user
	***********************************************************************/
	public function save() 
	{

		//permissions checking
		if (!$this->checkPerms()) return false;

		//process and update from the manager		
		if ($this->apidata["bookmark_id"]) 
		{
		
			$opt = null;
			$opt["name"] = $this->apidata["name"];
			$opt["expandable"] = $this->apidata["expand"];
			$opt["where"] = "id='".$this->apidata["bookmark_id"]."'";
			$this->DB->update("docmgr.dm_bookmark",$opt);

			$id = $this->apidata["bookmark_id"];

		//process a new bookmark
		} else {

			//allow admins to edit other user's bookmarks
			if ($this->apidata["account_id"]) $aid = $this->apidata["account_id"];
			else $aid = USER_ID;

			$opt = null;
			$opt["name"] = $this->apidata["name"];
			$opt["account_id"] = $aid;
			$opt["expandable"] = "t";
			$opt["protected"] = $this->apidata["protected"];
			$opt["object_id"] = $this->objectId;
			$id = $this->DB->insert("docmgr.dm_bookmark",$opt,"id");		
								
		}

		$this->PROTO->add("bookmark_id",$id);
	
	}

	/***********************************************************************
		FUNCTION: delete
		PURPOSE:	removes the passed bookmark
	***********************************************************************/
	public function delete() 
	{

		//permissions checking
		if (!$this->checkPerms()) return false;

		$sql = "SELECT account_id,protected FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
		$info = $this->DB->single($sql);
		
		if ($info["protected"]=="t") 
		{
			$this->throwError("This bookmark is protected and cannot be deleted");
		}
		else 
		{
		
			$sql = "DELETE FROM docmgr.dm_bookmark WHERE id='".$this->apidata["bookmark_id"]."'";
			$this->DB->query($sql);

		}
		
	}
			
}

