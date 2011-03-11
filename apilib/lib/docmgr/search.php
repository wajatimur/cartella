<?php

require_once("apilib/lib/docmgr/lib/search_function.inc.php");
require_once("apilib/lib/docmgr/lib/tree.php");

class DOCMGR_SEARCH extends DOCMGR_AOBJECT
{

	/********************************************************************
		FUNCTION:	save
		PURPOSE:	retrieves document from the system
	********************************************************************/
	public function save()
	{

		$this->apidata["object_type"] = "search";

		//store in a saved searches folder.  override whatever the interface sends
		$this->apidata["parent_path"] = null;
		$this->apidata["parent_id"] = $this->getSavedSearchesFolder();

		$o = new DOCMGR_OBJECT($this->apidata);
		$o->save();

		//toss and error if we have one
		$err = $o->getError();    
		if ($err) $this->throwError($err[0],$err[1]);        

	}

	/********************************************************************
		FUNCTION:	get
		PURPOSE:	retrieves document from the system
	********************************************************************/
	function get() 
	{

		$sql = "SELECT params FROM docmgr.dm_search WHERE object_id='".$this->objectId."'";
		$info = $this->DB->single($sql);

		//just an extra entry in case we have to distinguish from the regular search
		$this->PROTO->add("search_id",$this->objectId);
		$this->PROTO->add("search_name",$this->objectInfo["name"]);
		$this->PROTO->add("search_params",$info["params"]);

	}

	/********************************************************************
		FUNCTION:	run
		PURPOSE:	retrieves document from the system
	********************************************************************/
	function run() 
	{

		$sql = "SELECT params FROM docmgr.dm_search WHERE object_id='".$this->objectId."'";
		$info = $this->DB->single($sql);

		//decode the command string to an array
		$apidata = $this->PROTO->decode($info["params"]); 
		
		//init the api and run the search
		$s = new DOCMGR_SEARCH($apidata);
		$s->search();

	}

 

  /*****************************************************************************************
  	FUNCTION: update
  	PURPOSE:  additional processing function, called from DOCMGR_OBJECT class.  handles doc specific
							processing when creating the object
	*****************************************************************************************/
	
	protected function update($data)
	{

		$opt = null;
		$opt["object_id"] = $this->objectId;
		$opt["params"] = $data["params"];

		if ($this->DB->insert("docmgr.dm_search",$opt)) return true;
		else return false;

	}

  /*****************************************************************************************
  	FUNCTION: delete
  	PURPOSE:  additional processing function, called from DOCMGR_OBJECT class.  handles doc specific
							processing when deleting the object
	*****************************************************************************************/
	protected function remove() 
	{

		//delete the database entry
		$sql = "DELETE FROM docmgr.dm_search WHERE object_id='".$this->objectId."'";
		return $this->DB->query($sql);

	}

	/***********************************************************************
		Displaying:
		This private function returns the link and the icon to be displayed
		in the finder in list view
		return $arr("link" => $link, "icon" => $icon);
	***********************************************************************/
	protected function listDisplay($info)
	{

		$arr["icon"] = THEME_PATH."/images/fileicons/search.png";
		$arr["link"] = "javascript:browseCollection('".$info["id"]."');";
		return $arr;
 
	}


	public function browsecol() 
	{

		//setup curval

		//if initializing a tree
		if ($this->apidata["init"])
		{
		
			//if passed a ceiling, use that as our root level
			if ($this->apidata["ceiling"]!=null) 
			{
	
				//if passed an object id, use that.  Otherwise get the id from the path
				if (is_numeric($this->apidata["ceiling"])) 
				{
					$ceiling = $this->apidata["ceiling"];
					$ceilPath =  DOCMGR_UTIL_COMMON::getPath($ceiling);
				}
				else 
				{
					$info = $this->objectFromPath($this->apidata["ceiling"]);
					$ceiling = $info["id"];
					$ceilPath = $this->apidata["ceiling"];
				}
				
			}
			else 
			{
				$ceiling = "0";
				$ceilPath = "/";
			}

			if ($this->objectId) $val = $this->objectId;
			else $val = array("0");

			//now expand our column
			loadBaseCollections($val,$ceiling,$ceilPath,1);
		
		}
		else
		{

			$this->expandSingleCol($this->objectId,$this->path,1);
			
		}

	}

  public function browse() 
  {

  	if ($this->objectId!=null) $parent = $this->objectId;
  	else if ($this->objectPath=="/") $parent = "0";
  	else 
  	{

  		//if passed to make this collection. go for it!
  		if ($this->apidata["mkdir"]) 
  		{

  			//make the folder we are browsing
  			$parent = DOCMGR_UTIL_COMMON::recurMkParent($this->apidata["path"]);
  			
  			//set the perms for our new parent for the permcheck that happens later
				$this->objectBitset = DOCMGR_UTIL_OBJPERM::getUser($parent);

			} 
			else 
			{
	  		$this->throwError("Collection ".$this->apidata["path"]." does not exist");	
	  		return false;
			}
			
		}

  	//do we have permissions to view this
  	if ($parent!=null && !$this->permCheck("view"))
  	{
  		$this->throwError("You do not have permissions to browse ".$this->path);
  		return false;
		}

		$this->apidata["parent_id"] = $parent;

    $data = execCategory($this->apidata);

    $path = $this->path;

    //add permission info
    $data = DOCMGR_UTIL_OBJPERM::addToObject($data);

    //add lock info
    $l = new DOCMGR_UTIL_LOCK();
    $l->addToObject($data);

    $this->PROTO->add("count",$data["count"]);
    $this->PROTO->add("totalCount",$data["searchCount"]);

    //now, convert it all to data
    for ($i=0;$i<$data["count"];$i++) 
    {

      if ($this->path=="/") $data[$i]["object_path"] = "/".$data[$i]["name"];
      else $data[$i]["object_path"] = $this->path."/".$data[$i]["name"];

      //make some pretty fields for viewing
      $data[$i]["last_modified_view"] = dateView($data[$i]["last_modified"]);
      $data[$i]["filesize_view"] = displayFileSize($data[$i]["filesize"]);
      $data[$i]["object_directory"] = $data[$i]["level1"]."/".$data[$i]["level2"];

      //where or not it's openoffice compatible
      $info = fileInfo($data[$i]["name"]);

      $data[$i]["openoffice"] = $info["openoffice"];

      //return the parent we were browsing under
			$data[$i]["parent_id"] = $parent;

      //return the object type and load
      $objectClass = "DOCMGR_".$data[$i]["object_type"];

      $o = new $objectClass();
      $data[$i] = array_merge($data[$i],$o->listDisplay($data[$i]));

      //fix the icon link to only have the name
      $arr = explode("/",$data[$i]["icon"]);
      $data[$i]["icon"] = $arr[count($arr)-1];

      //add file extension
      if ($data[$i]["object_type"]=="file") 
      {
          $data[$i]["type"] =  $type = return_file_type($data[$i]["name"]);
          $data[$i]["extension"] =  $type = return_file_extension($data[$i]["name"]);

          //if asked, return image size also
          if ($this->apidata["show_image_size"] && $data[$i]["type"]=="image")
          {
          	$arr = $this->getImageSize($data[$i]["id"]);
          	$data[$i]["image_width"] = $arr["width"];
          	$data[$i]["image_height"] = $arr["height"];
					}

      }

			$this->PROTO->add("object",$data[$i]);
  
    }

    //get default view for this collection
		$o = new DOCMGR_OBJECT($parent);
		$view = $o->getView();
		$this->PROTO->add("default_view",$view["default_view"]);
		$this->PROTO->add("account_view",$view["account_view"]);
		
    //get keywords
    $k = new DOCMGR_UTIL_KEYWORD($this->objectId);
    $list = $k->getlist();

    if ($list["count"]>0)
    {
    	unset($list["count"]);
    	$this->PROTO->add("keyword",$list);
		}

		return $data;

  }

  private function getImageSize($id)
  {
  
  	$sql = "SELECT level1,level2,
  					(SELECT id FROM docmgr.dm_file_history WHERE 
  						dm_file_history.object_id=dm_view_objects.id AND 
  						dm_file_history.version=dm_view_objects.version) AS file_id
						FROM docmgr.dm_view_objects WHERE id='$id'";
		$info = $this->DB->single($sql);
		
		$file = DATA_DIR."/".$info["level1"]."/".$info["level2"]."/".$info["file_id"].".docmgr";
		
		$ret = array();
		
		//get our file  
		if (file_exists($file))
		{  
			$arr = getImageSize($file);
			$ret["width"] = $arr[0];
			$ret["height"] = $arr[1];
		}
		
		return $ret;
		
  }

	/****************************************************************
	  data parameters (keys)
	  search_string -> string of text search db with
	  search_options -> file_name,summary,file_contents: comma delimited
	  search_objects -> file,collection,url,document...: comma delimited
	  limit -> how many results to return
	  offset -> where to start results
	  begin_date -> start date filter from
	  end_date -> end date filter on
	  sort_field -> sort by field	('edit','size','rank','name')
	  sort_dir -> sort in direction ('ASC' or 'DESC')
	****************************************************************/
	public function search($sqlFilter=null) 
	{

	  //passed a path to limit collections to
	  if ($this->apidata["colfilter"]) 
	  	$colfilter = $this->apidata["colfilter"];
	  else if ($this->path && $this->path!="/") 
	  	$colfilter = $this->objectId;
	  else 
	  	$colfilter = null;

	  if ($this->objectId) $colfilter = $this->objectId;

	  //load our search options 
	  $opt = null;
	  $opt["conn"] = $this->conn;
	  $opt["string"] = $this->apidata["search_string"];				//string to search for

	  //optional parameters
	  if ($this->apidata["search_option"]) 	$opt["search_option"] = $this->apidata["search_option"];	//search in name, summary or content
	  if ($this->apidata["limit"]) 					$opt["limit"] = $this->apidata["limit"];
	  if ($this->apidata["offset"])					$opt["offset"] = $this->apidata["offset"];
	  if ($this->apidata["sort_field"])			$opt["sortField"] = $this->apidata["sort_field"];			//field to sort by
	  if ($this->apidata["sort_dir"])				$opt["sortDir"] = $this->apidata["sort_dir"];			//sort direction
	  if ($this->apidata["reset"])					$opt["reset"] = $this->apidata["reset"];

	  //if passed the sql filter, add it to the mix.  note we don't allow this to be passed from the API, 
	  //only in a direct function call.  We never want anyone passing sql statements directly into the API
	  //from outside the system
	  if ($sqlFilter)                       $opt["sql_filter"] = $sqlFilter;
       
	  if ($this->apidata["filter"])
	  {

	  	if (!is_array($this->apidata["filter"])) $this->apidata["filter"] = array($this->apidata["filter"]);
	  	if (!is_array($this->apidata["match"])) $this->apidata["match"] = array($this->apidata["match"]);
	  	if (!is_array($this->apidata["value"])) $this->apidata["value"] = array($this->apidata["value"]);

	  	$opt["filter"] = $this->apidata["filter"];
	  	$opt["match"] = $this->apidata["match"];
	  	$opt["value"] = $this->apidata["value"];

	  }
	  
	  if ($this->apidata["keywordFilter"])
	  {

	  	if (!is_array($this->apidata["keywordFilter"])) $this->apidata["keywordFilter"] = array($this->apidata["keywordFilter"]);
	  	if (!is_array($this->apidata["keywordMatch"])) $this->apidata["keywordMatch"] = array($this->apidata["keywordMatch"]);
	  	if (!is_array($this->apidata["keywordValue"])) $this->apidata["keywordValue"] = array($this->apidata["keywordValue"]);

	  	$opt["keywordFilter"] = $this->apidata["keywordFilter"];
	  	$opt["keywordMatch"] = $this->apidata["keywordMatch"];
	  	$opt["keywordValue"] = $this->apidata["keywordValue"];

	  }

	  //restrict to files within current column
	  if ($colfilter) $opt["colfilter"] = $colfilter;
	
	  //restrict responds to certain object ids.  This would be if we want info on a set of objects
		if ($this->apidata["object_filter"]) $opt["object_filter"] = $this->apidata["object_filter"];

		//show only shared files
		if ($this->apidata["share_filter"]) $opt["share_filter"] = $this->apidata["share_filter"];

	  //execute our search
	  $data = execSearch($opt);

	  //add permissions
    $data = DOCMGR_UTIL_OBJPERM::addToObject($data);

    //add lock info
    $l = new DOCMGR_UTIL_LOCK();
    $l->addToObject($data);

	  $this->PROTO->add("count",$data["count"]);
    $this->PROTO->add("totalCount",$data["searchCount"]);
    $this->PROTO->add("time",$data["timeCount"]);
    
    //the path we were searching in
    if ($this->path) $path = $this->path;
    else $path = "/";
    
    $this->PROTO->add("path",$path);

	  //get all collections that need to be displayed
	  $sql = "SELECT DISTINCT id,name,parent_id,object_type FROM docmgr.dm_view_collections ORDER BY name";
	  $catInfo = $this->DB->fetch($sql,1);
	                                
	  //now, convert it all to data
	  for ($i=0;$i<$data["count"];$i++) 
	  {
	
      //if we have a path filter, make sure we have the version of the file that's under that path
			$data[$i]["object_path"] = $this->getCurrentPath($data[$i]["id"]);

			//if ($this->path)
			//else $data[$i]["object_path"] = $this->objectPath($data[$i]["id"]);
	
      //make some pretty fields for viewing
      $data[$i]["last_modified_view"] = dateView($data[$i]["last_modified"]);
      $data[$i]["filesize_view"] = displayFileSize($data[$i]["filesize"]);
      $data[$i]["object_directory"] = $data[$i]["level1"]."/".$data[$i]["level2"];

      //where or not it's openoffice compatible
      $info = fileInfo($data[$i]["name"]);
      $data[$i]["openoffice"] = $info["openoffice"];
      $data[$i]["openoffice_edit"] = $info["openoffice_edit"];

      //return the object type and load
      $objectClass = "DOCMGR_".$data[$i]["object_type"];

      $o = new $objectClass();
      $data[$i] = array_merge($data[$i],$o->listDisplay($data[$i]));

			//fix the icon link to only have the name
			$arr = explode("/",$data[$i]["icon"]);
			$data[$i]["icon"] = $arr[count($arr)-1];

      //add file extension
      if ($data[$i]["object_type"]=="file") 
      {
          $data[$i]["type"] =  $type = return_file_type($data[$i]["name"]);
          $data[$i]["extension"] =  $type = return_file_extension($data[$i]["name"]);
      }

      //convert rank to something viewable
      if ($data[$i]["ts_rank"]) $data[$i]["rank"] = $data[$i]["ts_rank"]*100;
      else $data[$i]["rank"] = "100";
      
      $this->PROTO->add("object",$data[$i]);
	  
	  }

    //get keywords
    $k = new DOCMGR_UTIL_KEYWORD($this->objectId);
    $list = $k->getlist();

    if ($list["count"]>0)
    {
    	unset($list["count"]);
    	$this->PROTO->add("keyword",$list);
		}
                       	
	}

	//just show a single level of collections
	protected function expandSingleCol($curValue,$curPath,$showSearch=null) 
	{
	
	  if (!PERM::check(ADMIN)) $ps = " AND ".permString();
	  else $ps = null;

	  if ($showSearch) $table = "dm_view_colsearch";
	  else $table = "dm_view_collections";
	  
	  //somehow, this is faster than the two table query method
	  $sql = "SELECT DISTINCT id,name,parent_id,object_type,
	           (SELECT count(id) FROM 
	             (SELECT id,parent_id FROM docmgr.".$table." WHERE hidden='f') AS mytable
	             WHERE parent_id=".$table.".id ".$ps.") AS child_count
	             FROM docmgr.".$table." WHERE parent_id='$curValue' ".$ps." AND hidden='f' ORDER BY name
	              ";
	  $list = $this->DB->fetch($sql);
	
	  for ($i=0;$i<$list["count"];$i++) 
	  {

	  	if ($curPath=="/") $path = "/".$list[$i]["name"];
	  	else $path = $curPath."/".$list[$i]["name"];
	
	    //first, get the info for this file
	    $arr = array();
	    $arr["id"] = $list[$i]["id"];
	    $arr["name"] = $list[$i]["name"];
	    $arr["child_count"] = $list[$i]["child_count"];
	    $arr["path"] = $path;
	    $arr["object_type"] = $list[$i]["object_type"];
	    $this->PROTO->add("collection",$arr);
	
	  }
	
	}


	/****************************************************************************
		FUNCTION:	getSavedSearchesFolder
		PURPOSE:	gets our shared folder in our home directory.  if it doesn't
							exist, create one
		INPUTS:		none
	****************************************************************************/
	protected function getSavedSearchesFolder()
	{

		$retId = null;

		$path = "/Users/".USER_LOGIN."/Saved Searches";
		$info = $this->objectFromPath(sanitize($path));

		if ($info) $retId = $info["id"];
		else
		{

			//create a new folder to hold shared objects in.  we pretty much have to do this manually
			//to bypass api permission checking
			$opt = null;
			$opt["name"] = "Saved Searches";
			$opt["parent_path"] = "/Users/".USER_LOGIN;

			$c = new DOCMGR_COLLECTION($opt);
			$retId = $c->save();
			
			//make a bookmark
			$opt = null;
			$opt["name"] = "Saved Searches";
			$opt["account_id"] = USER_ID;
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

}
