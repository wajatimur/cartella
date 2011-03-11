<?php

/**********************************************************************
	CLASS:	UTIL
	PURPOSE:	contains public functions for processing that aren't
						available to the outside world
**********************************************************************/
class DOCMGR_UTIL_COMMON extends DOCMGR {

  /*********************************************************
  	FUNCTION: getChildCollections
  	PURPOSE:returns all collection children of the current object
	*********************************************************/
	public function getChildCollections($objId=null)
	{

		global $DB;

		if (!$objId) $objId = $this->objectId;

		$sql = "with recursive all_categories as 
						(
							select * from docmgr.dm_view_collections where parent_id='".$objId."'
							union all
							select b.* from all_categories a, docmgr.dm_view_collections b where a.id = b.parent_id
						) select * from all_categories order by parent_id;
						";
		$list = $DB->fetch($sql,1);

		return $list["object_id"];

	}

	function getPath($id)
	{

		$pids = DOCMGR_UTIL_COMMON::resolvePathIds($id);
		$path = DOCMGR_UTIL_COMMON::idToPath($pids);
		
		return $path;	
	
	}

	function idToPath($ids)
	{

		if ($ids=="0") return "/";

		$ids = implode(",",arrayReduce(explode(",",$ids)));
		
		$sql = "SELECT DISTINCT id,name FROM docmgr.dm_view_objects WHERE id IN (".$ids.")";
		$list = $this->DB->fetch($sql,1);
		
		$str = null;
		
		$arr = array_reverse(explode(",",$ids));
		
		foreach ($arr AS $pid)
		{
		
			if ($pid=="0") $str .= "/";
			else
			{
		
				$key = array_search($pid,$list["id"]);
				$name = $list["name"][$key];
						
				//if ($key===FALSE) $name = "Hidden";
				//else $name = $list["name"][$key];
		
				if ($str=="/") $str .= $name;
				else $str .= "/".$name;
		
			}
		
		}

		return $str;

	}

	function resolvePathIds($objId)
	{

		if ($objId=="0") return "0";

		$ret = null;

		if ($_SESSION["api"]["parentIds"])
		{

			$pids = $_SESSION["api"]["parentIds"];

			//we are browsing.make sure the current path is part of this one
			$pidarr = $this->objectIdAllPaths($objId);
			$parr = explode(",",$pids);

			for ($i=0;$i<count($pidarr);$i++)
			{

				$temp = explode(",",$pidarr[$i]);

				//echo $pidarr[$i]."=>".$pids."\n";
				//check for a normal match
				if ($pidarr[$i]==$pids)
				{
					$ret = $pidarr[$i];
					break;
				} 
				//check for a sub match
				else if (strstr($pidarr[$i],$pids))
				{
					$ret = $pidarr[$i];
					break;
				} 
				//check and see if the recent child is anywhere in there
				//and merge together
				else if ( ($key = array_search($parr[0],$temp))!==FALSE)
				{
								
					$arr = array_slice($temp,0,$key);
					$ret = implode(",",$arr).",".$pids;
				} 

			}
		
		} 

		if (!$ret) $ret = $this->objectIdPath($objId);

		return $ret;
		
	}
		
	function recurMkParent($parentPath) 
	{

		$parentId = null;
		
		$arr = explode("/",$parentPath);
		array_shift($arr);				//remove the blank empty directory
		$path = "/";	

		if (count($arr)==0) return false;

		foreach ($arr AS $name) 
		{
		
			//store previous one
			$prevpath = $path;

			//make new one
			if ($path=="/") $path .= $name;
			else $path .= "/".$name;

			//see if it exists			
			$d = new DOCMGR();
			$obj = $d->objectFromPath(sanitize($path));	

			//doesn't exist, make it and store the parentId
			if (!$obj) 
			{

				$data = array();
				$data["parent_path"] = $prevpath;
				$data["name"] = $name;
				$data["object_type"] = "collection";

				$o = new DOCMGR_OBJECT($data);
				$parentId = $o->save();

			} else $parentId = $obj["id"];
		
		}		

		return $parentId;	
	
	}

	function objectIdPath($id)
	{

		global $DB;

		if (!$id) $path = "0";
		else
		{

			$sql = "SELECT docmgr.getobjpath('".$id."','') AS objpath;";
			$info = $DB->single($sql);

			return $info["objpath"];

		}

		return $path;
		

	}

	/*********************************************************
		FUNCTION:	objectIdPath
		PURPOSE:	returns an array of parent ids of an object
							including the object itself
	*********************************************************/
	public function objectIdAllPaths($id) 
	{
 
		global $DB;
		
		if (!$id) $path = "0";
		else 
		{
 
			$sql = "SELECT docmgr.get_all_paths('".$id."') AS objpath;";
			$info = $DB->fetch($sql,1);
 
			$path = $info["objpath"];
 
		}
 
		return $path;
 
	}


	public function updateCollectionSizes($objId)
	{

		//convert to an array
		if (!is_array($objId)) $objId = array($objId);

		//loop through and set it all up		
		foreach ($objId AS $obj)
		{

			if (!$obj) continue;
	
			$paths = DOCMGR_UTIL_COMMON::objectIdAllPaths($obj);

			if (count($paths)==0) continue;
			
			foreach ($paths AS $path)
			{
		
				if (!$path) continue;
		
				//set the filesize of all collections to the sume of it's children
				//start from the bottom and work your way out
				$arr = array_reverse(explode(",",$path));
				
				//figure out which one are collections
				$sql = "SELECT id FROM docmgr.dm_object WHERE id in (".$path.") AND object_type='collection'";
				$list = $this->DB->fetch($sql,1);
				
				$arr = array_values(array_intersect($arr,$list["id"]));
				
				for ($i=0;$i<count($arr);$i++)
				{
				
					$sql = "UPDATE docmgr.dm_object SET filesize=
										(
											SELECT sum(filesize::bigint) FROM docmgr.dm_view_objects WHERE parent_id='".$arr[$i]."'
										)
										WHERE id='".$arr[$i]."'";
					$this->DB->query($sql);
				
				}	
					
			
			}
	
		}
		
	}

}
