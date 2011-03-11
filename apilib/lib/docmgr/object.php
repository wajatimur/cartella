<?php

/****************************************************************************
	CLASS:	OBJECT
	PURPOSE:	master function for managing docmgr objects.  this includes
				    creating, saving, update, moving, or deleting
****************************************************************************/

class DOCMGR_OBJECT extends DOCMGR_AOBJECT
{

	public function get()
	{
	
		$class = "DOCMGR_".$this->objectInfo["object_type"];
		
		//just init the appropriate class based on our type and call 
		//its get method
		$o = new $class($this->apidata);
		$o->get();
	
	}	

	public function save()
	{

		//guest accounts stop here
		if (PERM::check(GUEST_ACCOUNT,1))
		{
			$this->throwError("You do not have permissions to manage this object");
			return false;
		}

		//create or update
		if ($this->objectId) 
		{

			//make sure we have permissions to manage this object
			if (!$this->permCheck("edit"))
			{
				$this->throwError("You do not have permissions to manage this object");
				return false;
			}
		
			$this->update($this->apidata);

			$event = OBJ_UPDATED;
			
		}
		else 
		{

			$this->create($this->apidata);
			$event = OBJ_CREATED;
			
		}

		//stop here if no object id
		if ($this->getError()) return false;

		//store the content
		$objType = $this->objectInfo["object_type"];
		$objClass = "DOCMGR_".$objType;

		//init our subclass and call it's update routine		
		$o = new $objClass($this->objectId);
		$o->update($this->apidata);

		//check for errors from update
		$err = $o->getError();

		if ($err)
		{

			//problem.  delete the new object and bail
			$this->delete();			
			$this->throwError($err[0],$err[1]);		

			return false;
					
		}

		//save keywords
		$k = new DOCMGR_UTIL_KEYWORD($this->objectId);
		$k->saveValues($this->apidata);

		//queue the object for indexing and thumbnailing
		$i = new DOCMGR_OBJINDEX($this->objectId);
		$i->run(USER_ID);

		$objClass = "DOCMGR_".$objType;

		//don't thumbnail in the background
		if (defined("DISABLE_BACKTHUMB")) $o->thumb();
			
		//lock if necessary
		if ($this->apidata["unlock"]) 
		{
			$l = new DOCMGR_UTIL_LOCK($this->objectId);
			$l->clear();
		}
                                      
		//log the event
		if ($event) logEvent($event,$this->objectId);

		//make sure we output the object id and path
		$this->PROTO->add("object_id",$this->objectId,1);
		$this->PROTO->add("object_path",$this->path,1);	

		$this->updateCollectionSizes();

		//return the object id for internally called functions
		return $this->objectId;

	}


	/****************************************************************************
		FUNCTION:	create
		PURPOSE:	creates a new object in the system
		INPUTS:		object_type -> collection,file,document,etc
				      name	-> object name
				      parent_id -> id of parent we store in (id or array)
				      summary -> object summary
				      object_owner -> account that will own this object
	****************************************************************************/

	protected function create($data) 
	{

		//extract all our data into easy to use variables arr["keyname"] => $keyname
		extract($data);

		//make sure they have permissions to insert objects
		if (!PERM::check(INSERT_OBJECTS))
		{
			$msg = "You do not have permissions to create objects";
			$this->throwError($msg);
			return false;
		}

		if (!$data["parent_id"] && $data["parent_path"]) 
		{

			$info = $this->objectFromPath($data["parent_path"]);
			$parent_id = $info["id"];

			//if no parent id and we are asked to make one on the spot, handle it
			if ($parent_id == null && $data["mkdir"]) 
			{
				$parent_id = DOCMGR_UTIL_COMMON::recurMkParent($data["parent_path"]);
			} 
				
		}

		//if moving to the root collection, make sure they have permission to do that
		if ($parent_id=="0" && !PERM::check(CREATE_ROOT))
		{
			$msg = "You do not have permissions to create objects in the root folder";
			$this->throwError($msg);
			return false;
		}

		if (!$name) {
			$this->throwError("Object name not specified");
			return false;
		}

		//no parent
		if ($parent_id==NULL) 
		{
			$this->throwError("No valid collection was passed to create object in");
			return false;
		}    

		//if passed exist override, come up with our new filename if one already exists in this collection
		if ($this->apidata["exist_rename"]) 
		{

			//keep going until we find one that doesn't exist 
			while (!checkObjName($this->conn,$name,$parent_id)) 
			{
				$name = $this->incrementName($name); 
			}

			//update passed name w/ the new one so other classes see it
			$this->apidata["name"] = $name;
			
		//make sure there isn't already an object with this name
		} 
		else if (!checkObjName($this->conn,$name,$parent_id)) 
		{

			//get the object's info and bail returning the existing object's id
			$sql = "SELECT id FROM docmgr.dm_view_objects WHERE parent_id='$parent_id' AND name='".$name."'";
			$info = $this->DB->single($sql);

			//if passed overwrite, try again with the object id set
			if ($this->apidata["overwrite"])
			{
			
				$this->objectId = $info["id"];
				$this->setObjectInfo();
				return $this->save();
					
			}
			//otherwise throw and error
			else
			{
			
				$this->throwError("Object with this name \"".$name."\" already exists in ".$this->path);
				$this->setObjectId($info["id"]);

				return false;

			}
						
		}

		//make sure we have permissions to create in this collection
		if ($parent_id==0) 
		{
			if (defined("ADMIN_ROOTLEVEL") && !PERM::check(ADMIN))
			{
		 		$this->throwError("You do not have permissions to create objects in the Root Level");
		 		return false;
			}
		} 
		else 
		{

			//if set, do not do a perm check on the parent.  This is for INTERNAL USE ONLY, not 
			//something that can be set by an API call
		 	$cb = DOCMGR_UTIL_OBJPERM::getUser($parent_id);
		 	if (!DOCMGR_UTIL_OBJPERM::check($cb,"edit"))
		 	{
				$this->throwError("You do not have permissions to create objects in this collection");
				return false;
			}
			
		}
			  
		//some default values
		if (!$object_owner) $object_owner = USER_ID;
	 	if (!$protected) $protected = "f";
		if (!$hidden) $hidden = "f";
			 	 
		$this->DB->begin();

		//insert into the main object table
		$option = null;
		$option["name"] = $name;
		$option["summary"] = $summary;
		$option["version"] = "0";
		$option["create_date"] = date("Y-m-d H:i:s");
		$option["object_type"] = $object_type;
		$option["object_owner"] = $object_owner;
		$option["last_modified"] = date("Y-m-d H:i:s");
		$option["modified_by"] = USER_ID;
		$option["protected"] = $protected;
		$option["hidden"] = $hidden;

		//insert the collection
		$this->objectId = $this->DB->insert("docmgr.dm_object",$option,"id");

		//store objectId in parent for other classes to use
		$this->setObjectId($this->objectId);
		
		//figure out the directory levels for this object.  We do this here for all objects 
		//until I think of a way that doesn't involve sequences
		if ($object_type!="collection") 
		{

			$level = $this->storeLevel();
			$data["object_directory"] = $level;

		}

		//setup the parent link for the collection
		$sql = "INSERT INTO docmgr.dm_object_parent (object_id,parent_id,account_id) VALUES ('".$this->objectId."','$parent_id','".USER_ID."');";
		$this->DB->query($sql);

		//inherit the parent's permissions
		if (!$this->apidata["noinherit"]) DOCMGR_UTIL_OBJPERM::inherit($this->objectId,$parent_id);

		//ensure the object owner has admin privs
		$arr = array();
		$arr["object_id"] = $this->objectId;
		$arr["type"] = "account";
		$arr["id"] = USER_ID;
		$arr["bitmask"] = "00000001";
		DOCMGR_UTIL_OBJPERM::add($arr);

		$this->DB->end();

		//send out an alert.  We send the alert out for the object, even though the
		//parent triggers it.  This allows a user to see the new file right away
		if ($object_type=="collection" || $object_type=="url" || $object_type=="document") 
			sendEventNotify($this->objectId,OBJ_CREATE_ALERT,$parent_id);

		$this->setObjectInfo();

		//look for db errors
		$err = $this->DB->error();

		if ($err) 
		{
			$this->throwError($err);
			return false;
		} 
		else 
		{
			return $this->objectId;
		}
			
	}

	/****************************************************************************
		FUNCTION:	update
		PURPOSE:	updates a new object in the system
		INPUTS:		object_type -> collection,file,document,etc
				      name	-> object name
				      parent_id -> id of parent we store in (id or array)
				      summary -> object summary
				      object_owner -> account that will own this object
	****************************************************************************/
	protected function update($data) 
	{

		//extract all our data into easy to use variables arr["keyname"] => $keyname
		extract($data);

		//sanity checking
		if (!$object_type) 
		{

			//try to pull object type from our objectinfo if not set
			if ($this->objectInfo) $object_type = $this->objectInfo["object_type"];
			else 
			{
				$this->throwError("Object type not specified");
				return false;
			}
			
		}

		if (!$this->objectId) 
		{
			$this->throwError("Object id not specified");
			return false;
		}

		//check to see if it's locked
		$l = new DOCMGR_UTIL_LOCK($this->objectId);
		
   	if ($l->isLocked())
   	{
   		$this->throwError("Object is locked from editing by another user");
   		return false;
   	} 
            
		//make sure there isn't already an object with this name (if we are renaming)
		if ($name && !checkObjName($this->conn,$name,$parent_id,$this->objectId)) 
		{
			$this->throwError("Object with this name already exists in this collection");
			return false;
		}

		//if it's "No summary available", blank it
		if ($summary==_NO_SUMMARY_AVAIL) $summary = null;

		//add in the object path
		$data["object_directory"] = $this->getObjectDir();

		$this->DB->begin();

		//insert into the main object table
		$option = null;
		if ($name) $option["name"] = $name;
		if ($summary) $option["summary"] = $summary;
		$option["last_modified"] = date("Y-m-d H:i:s");
		$option["modified_by"] = USER_ID;
		$option["where"] = "id='".$this->objectId."'";
		$this->DB->update("docmgr.dm_object",$option);

		logEvent(OBJ_PROP_UPDATE,$this->objectId);

		$this->DB->end();


		//look for db errors
		$err = $this->DB->error();
		if ($err) 
		{
			$this->throwError($err);
			return false;
		} 
		else 
		{
			return $this->objectId;
		}

	}


	function index()
	{

		if ($this->apidata["prop_only"]) $p = true;
		else $p = false;

		$o = new DOCMGR_OBJINDEX($this->objectId);
		$o->index($p);
	
	}

	function thumb()
	{

		$objClass = "DOCMGR_".$this->objectInfo["object_type"];
		
		$OBJ = new $objClass($this->objectId);
		$OBJ->thumb();
	
	}

	function preview()
	{

		$objClass = "DOCMGR_".$this->objectInfo["object_type"];

		$OBJ = new $objClass($this->objectId);
		if (method_exists($OBJ,"preview")) $OBJ->preview();

	}
                	
	/***************************************************************************
		deleting functions
	***************************************************************************/

	/****************************************************************************
		FUNCTION:	delete
		PURPOSE:	deletes an object in the system
	****************************************************************************/
	public function delete($objarr=null,$noperms=null)
	{

		//default to class object
		if (!$objarr) $objarr = $this->objectId;

		//convert to array if not passed as one
		if (!is_array($objarr)) $objarr = array($objarr);
		
	  $oldParents = array();

		//loop through our objects and delete
		foreach ($objarr AS $obj) 
		{

			if (!$obj) continue;

			//if the person is trying to delete an object that has been shared with them
			$s = new DOCMGR_SHARE();
			if ($s->deleteShare($obj,USER_ID)) continue;

			//init for testing later		
			$LOCK = new DOCMGR_UTIL_LOCK($obj);

		  //figure out what kind of object this is
		  $sql = "SELECT id,name,object_type,protected FROM docmgr.dm_object WHERE id='".$obj."'";
		  $info = $this->DB->single($sql);
	
		  //if we can't find the obj, there's been an error.  Get out!!!	
		  if (!$info) 
		  {
		    $this->throwError("Could not find object \"".$obj."\" for removal");
		    return false;
		  } 
		  else if ($LOCK->isLocked($info["id"])) 
		  {
		  	$this->throwError("\"".$info["name"]."\" is currently locked","OBJ_LOCK");
		  	return false;
		  } 
		  else if ($info["protected"]=="t") 
		  {
		  	$this->throwError("\"".$info["name"]."\" is a protected object and cannot be deleted");
		  	return false;
		  }

		  //if told to bypass perm check, do it.  Note, this can only be called internally,
		  //and is currently only used on object storage folder creation
		  if (!$noperms)
		  {

				//make sure we are allow to edit anything in the destination folder, since we are 
			  //moving things there
			  $cb = DOCMGR_UTIL_OBJPERM::getUser($obj);

			  if (!DOCMGR_UTIL_OBJPERM::check($cb,"admin"))
			  {
			    $this->throwError("You do not have permissions to delete ".$info["name"]);
					return false;
				}

			}

		  //delete all children of this object
		  $sql = "SELECT object_id FROM docmgr.dm_object_parent WHERE parent_id='".$obj."';";
		  $list = $this->DB->fetch($sql,1);

		  //delete the children
		  if ($list["count"] > 0) $this->delete($list["object_id"]);

			//now delete our object			
			$this->DB->begin();
	
		  $className = "DOCMGR_".$info["object_type"];
	
		  //now hand off to our other functions for the object-specific processing
		  $c = new $className($obj);
		  $c->remove();

		  //get the old parents of this object
		  $sql = "SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id='$obj'";
		  $info = $this->DB->fetch($sql,1);
		  $oldParents = array_merge($oldParents,$info["parent_id"]);


			//all the tables we need to clear references too	
		  $arr = array(	"docmgr.dm_index_queue","docmgr.dm_object_perm","docmgr.dm_object_parent",
		  							"docmgr.dm_index","docmgr.keyword_value","docmgr.dm_alert",
										"docmgr.dm_bookmark","docmgr.dm_discussion","docmgr.dm_subscribe",
										"docmgr.dm_properties","docmgr.dm_locks","docmgr.dm_locktoken",
										"docmgr.dm_share");
		 
		  $sql = null;               
		                
		  foreach ($arr AS $table) 
		  {
		    $sql .= "DELETE FROM $table WHERE object_id='".$obj."';";
		  }

		  //workflow
		  $sql .= "DELETE FROM docmgr.dm_workflow_route WHERE workflow_id IN 
		  						(SELECT id FROM docmgr.dm_workflow WHERE object_id='".$obj."');";
			$sql .= "DELETE FROM docmgr.dm_workflow WHERE object_id='".$obj."';";
		  
		  //related files
		  $sql .= "DELETE FROM docmgr.dm_object_related WHERE object_id='".$obj."' OR related_id='".$obj."';";
	
		  //primary object removal and associated table entry removal
		  $sql .= "DELETE FROM docmgr.dm_object WHERE id='".$obj."'; ";

		  //run the query
		  $this->DB->query($sql);

		  $this->DB->end();

			//look for errors
		  $err = $this->DB->error();
		  if ($err) 
		  {
		    $this->throwError($err);
				break;
			} 
			else 
			{

		    //send out an alert
		    sendEventNotify($obj,OBJ_REMOVE_ALERT);
	
		  }
		  
		}	//end for loop

		//update everyone's size
		$this->updateCollectionSizes($oldParents);

	}	


	/****************************************************************************
		FUNCTION:	move
		PURPOSE:	relocates an object to a new parent or parents
	****************************************************************************/
	public function move() 
	{

		//convert our object id into an array.  this is an array of objects we are moving
		if (is_array($this->objectId)) $objectArr = $this->objectId;
		else $objectArr = array($this->objectId);

		//now get the parent id from the parent_path parameter
		if ($this->apidata["dest_parent_id"]) $parent = $this->apidata["dest_parent_id"];
		else 
		{

			$info = $this->objectFromPath($this->apidata["dest_parent_path"]);

			if (!$info) 
			{

				$msg = "Error moving object.  Could not find parent in system";
				$this->throwError($msg);
				return false;

			} 
			else $parent = $info["id"];

		}

		//get where we are moving it from.  this has to be specified because a file can
		//be in multiple locations
		if ($this->apidata["source_parent_id"]) $source = $this->apidata["source_parent_id"];
		else 
		{

			$info = $this->objectFromPath($this->apidata["source_parent_path"]);

			if (!$info) 
			{

				$msg = "Error moving object.  Original location not specified";
				$this->throwError($msg);
				return false;

			} 
			else $source = $info["id"];

		}


		//if moving to the root collection, make sure they have permission to do that
		if ($parent=="0" && !PERM::check(CREATE_ROOT))
		{
			$msg = "You do not have permissions to move objects to the root folder";
			$this->throwError($msg);
			return false;
		}
	
		//make sure we are not moving something to a category where an object with its name already exists
		//there may be a more efficient way to do this without so many queries to the db
		foreach ($objectArr AS $curObj) 
		{

			$sql = "SELECT name FROM docmgr.dm_object WHERE id='$curObj'";
			$info = $this->DB->single($sql);

			if (!checkObjName($this->conn,sanitize($info["name"]),$parent)) 
			{
				$this->throwError($info["name"]." already exists in destination collection");
				return false;
			}
	
		}
	
	
		//make sure we are allow to edit anything in the destination folder, since we are 
		//moving things there
		$cb = DOCMGR_UTIL_OBJPERM::getuser($parent);
		if (!$this->permCheck("edit",$cb))
		{
			$this->throwError("You do not have permissions to move this object to the destination folder");
			return false;
		}

		//start the move
		$this->DB->begin();

		//get the old parents for later
		$sql = "SELECT object_id,parent_id,account_id FROM docmgr.dm_object_parent WHERE object_id IN (".implode(",",$objectArr).") AND parent_id='$source';";
		$list = $this->DB->fetch($sql);

		for ($i=0;$i<$list["count"];$i++)
		{
		
			//if we own the link we are moving, no need to do a permissions check (this happens when sharing is used)
			if ($list[$i]["account_id"]!=USER_ID)
			{
			
				//make sure we have permissions to move this object
				$cb = DOCMGR_UTIL_OBJPERM::getUser($list[$i]["object_id"]);
				if (!DOCMGR_UTIL_OBJPERM::check($cb,"admin")) 
				{
					$this->throwError("You do not have permission to move this object");
					break;
				}
				
			}

			//delete from our source folder and add to the new one
			$sql = "UPDATE docmgr.dm_object_parent SET parent_id='$parent' WHERE object_id='".$list[$i]["object_id"]."' AND parent_id='$source';";
			$this->DB->query($sql);

			//log it
			logEvent(OBJ_MOVED,$list[$i]["object_id"]);

		}

		//update collection sizes
		$this->updateCollectionSizes($objectArr);
		$this->updateCollectionSizes(array($source));

		$this->DB->end();

		$err = $this->DB->error();
		if ($err) $this->throwError($err);
	
	}
	
	/****************************************************************************
		FUNCTION:	getInfo
		PURPOSE:	adds generic data object into the output queue
	****************************************************************************/
	public function getInfo() 
	{

		if (!$this->objectId) return false;

		//make some pretty fields for viewing
		$a = new ACCOUNT();
		$arr = $a->getInfo($this->objectInfo["modified_by"]);

		$this->objectInfo["view_modified_by"] = $arr["first_name"]." ".$arr["last_name"];
		$this->objectInfo["view_last_modified"] = dateView($this->objectInfo["last_modified"]);

		//$this->objectInfo["object_path"] = $this->path;

		$arr = $a->getInfo($this->objectInfo["object_owner"]);
		$this->objectInfo["owner_name"] = $arr["first_name"]." ".$arr["last_name"];

		//get the parents of this object
		$sql = "SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id='".$this->objectId."'";
		$info = $this->DB->fetch($sql);

		//pass as an array
		for ($i=0;$i<$info["count"];$i++) 
		{
			$this->objectInfo["parents"][$i] = $info[$i]["parent_id"];
		}

		//merge in view info for collections (list, thumb, whatever)
		if ($this->objectInfo["object_type"]=="collection") 
		{
			$this->objectInfo = @array_merge($this->objectInfo,$this->getView());
		}

		//add in the object path
    $this->objectInfo["object_directory"] = $this->getObjectDir();
        
		//merge in lock info
		$arr = array();
		$arr["count"] = 1;
		$arr[0] = $this->objectInfo;

		$l = new DOCMGR_UTIL_LOCK();
		$l->addToObject($arr);

		$this->objectInfo = $arr[0];

		//add to our output stream		
		$this->PROTO->add("object",$this->objectInfo);

		//return for internal calls
		return $this->objectInfo;
	
	}
	
	/****************************************************************************
		FUNCTION:	getId
		PURPOSE:	returns an xml string containing the object id
	****************************************************************************/
	public function getId() 
	{
	
		$this->PROTO->add("object_id",$this->objectId);
			
	}
				
	/****************************************************************************
		FUNCTION:	saveParent
		PURPOSE:	stores a new parent for an object
	****************************************************************************/
	public function saveParent($pid=null) 
	{

		//perm check
		if (!$this->permCheck("admin"))
		{
		  $this->throwError("You do not have permissions to manage this object");
			return false;
		}

		//if not passed directly, snag from xml 
		if (!$pid) $pid = $this->apidata["parent_id"];

		//make array
		if (!is_array($pid)) $pid = array($pid);

		//object can't be moved into itself		
		if (in_array($this->objectId,$pid)) 
		{
			$this->throwError("An object cannot be it's own parent");
			return false;
		}

		//we are doing an update, make sure an object with the same name does not already exist
		if (!checkObjName($this->conn,sanitize($this->objectInfo["name"]),$pid,$this->objectId)) 
		{
		  $this->throwError(ERROR_MESSAGE);
		  return false;
		}


		//get the old parents for later
		$sql = "SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id='".$this->objectId."'";
		$info = $this->DB->fetch($sql,1);
		$oldParents = $info["parent_id"];
	
		//remove the old entries
		$sql = "DELETE FROM docmgr.dm_object_parent WHERE object_id='".$this->objectId."';";
	
		//insert new entries
		foreach ($pid AS $p) 
		{

			$cb = DOCMGR_UTIL_OBJPERM::getUser($p);
			if (!PERM::is_set($cb,OBJ_ADMIN))
			{
			    $this->throwError("You do not have permissions to move this object to the destination folder");
				  return false;
		  }

		  $sql .= "INSERT INTO docmgr.dm_object_parent (object_id,parent_id,account_id) VALUES ('".$this->objectId."','".$p."','".USER_ID."');";

		}

		//run the query	
		if (!$this->DB->query($sql)) 
		{
		  $this->throwError("Unble to update object parent");
		  return false;
		}
		else
		{

		
			$this->updateCollectionSizes();
			$this->updateCollectionSizes($oldParents);
		
		}
		
	}

	/********************************************************
		mass conversion utility.  not working yet
	********************************************************/
	public function massconvert()
	{

		$objectArr = $this->objectId;
		
		foreach ($objectArr AS $object)
		{
		
			$this->objectId = $object;
			$this->setObjectInfo();
		
			//convert each one, one at a time
			$this->convert();
			
		}
	
	}

	/**********************************************************
		for converting an object to a new type
	**********************************************************/
	public function convert() 
	{

		//must have valid object
		if (!$this->objectInfo) 
		{
			$this->throwError("Invalid object passed");
			return false;
		}

		//only allow document and file conversion
		if ($this->objectInfo["object_type"]!="file" && $this->objectInfo["object_type"]!="document") 
		{
			$this->throwError("You may only convert files or documents");
			return false;
		}

		//if converting to a document, pretend it's html
		if ($this->apidata["convert_type"]=="document")
		{
			$this->apidata["to"] = "html";
		}

		//did we pass the type we want to convert to
		if (!$this->apidata["to"]) {
			$this->throwError("You did not specifiy what you want to convert the file to");
			return false;
		}

		if ($this->objectInfo["object_type"]=="document") 
		{				
			$ext = "html";
			$destName = $this->objectInfo["name"].".".$this->apidata["to"];
			$srcName = $this->objectInfo["name"].".html";
		}
		else 
		{
			$ext = return_file_extension($this->objectInfo["name"]);
			$destName = str_replace(".".$ext,".".$this->apidata["to"],$this->objectInfo["name"]);
			$srcName = $this->objectInfo["name"];
		}

		//bail if no extension
		if (!$ext) 
		{
			$this->throwError("Object's extension could not be determined");
			return false;
		}

		//make sure we are within a type that we can convert to
		$srcInfo = return_file_info($srcName);
		$destInfo = return_file_info($destName);
		$srcType = $srcInfo["openoffice"];
		$destType = $srcInfo["openoffice"];
		
		//matching types, go for it
		if ($srcType!=$destType && $ext!="pdf")
		{
			$this->throwError("Incompatible destination type specified");
			return false;
		}
		

		//get our file from docmgr and setup a working file to play with		
		$worker = $this->setupWorker();

		//fire up openoffice
		$oo = new OPENOFFICE($worker,$this->objectId);
		$newfile = $oo->convert($this->apidata["to"]);

		//return the content if asked
		if ($this->apidata["return"]=="content") 
		{

			$this->PROTO->add("content",file_get_contents($newfile));

		} 
		//save to docmgr in this directory if asked
		else if ($this->apidata["return"]=="docmgr")
		{

			//get the parent if not passed
			if ($this->apidata["parent_path"])
			{
				$displayPath = $this->apidata["parent_path"];
			}
			else
			{
				$displayArr = explode("/",$this->objectInfo["display_path"]);
				array_pop($displayArr);
				$displayPath = implode("/",$displayArr);
			}
					
			$opt = array();
			$opt["exist_rename"] = 1;
			$opt["mkdir"] = 1;
			$opt["parent_path"] = sanitize($displayPath);
			$opt["name"] = sanitize($destName);

			//converting to a document document
			if ($this->apidata["convert_type"]=="document")
			{

				//remove the file extension added earlier
				$opt["name"] = str_replace(".html","",$opt["name"]);

				$opt["editor_content"] = file_get_contents($newfile);
			
				$o = new DOCMGR_DOCUMENT($opt);
				$this->objectId = $o->save();
			
			}
			else
			{
			
				$opt["filepath"] = $newfile;

				$o = new DOCMGR_FILE($opt);
				$this->objectId = $o->save();

			}
						
		}
		else 
		{

			//get our file type to pass to the browser
			if ($type = return_file_mime(strtolower($destName))) header ("Content-Type: $type");
			else $type="application/octet-stream";
	 
			// send headers to browser to initiate file download
			header ("Content-Type: ".$type);
			header ("Content-Type: application/force-download");
			header ("Content-Length: ".filesize($newfile));
			header ("Content-Disposition: attachment; filename=\"$destName\"");
			header ("Content-Transfer-Encoding:binary");
			header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header ("Pragma: public");
			readfile_chunked($newfile);
			die;

		} 
			                                
	}

	protected function setupWorker() 
	{

		//make our temp directory
		$tmpdir = TMP_DIR."/".USER_LOGIN;
		recurmkdir($tmpdir);
	
		//get the path to our document
		if ($this->objectInfo["object_type"]=="document") {

			$realname = $this->objectInfo["name"];
			$version = $this->objectInfo["version"];
 
			$sql = "SELECT id FROM docmgr.dm_document WHERE object_id='".$this->objectId."' AND version='$version'";
			$info = $this->DB->single($sql);
			$documentId = $info["id"];

			// get the filename
			$filename = DOC_DIR."/".$this->getObjectDir()."/".$documentId.".docmgr";

			$dest = $tmpdir."/worker.html";

		//get path to our file		
		} else {

			$realname = $this->objectInfo["name"];
			$version = $this->objectInfo["version"];
	
			$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='".$this->objectId."' AND version='$version'";
			$info = single_result($this->conn,$sql);
			$file_id = $info["id"];
	
			// get the filename
			$filename = DATA_DIR."/".$this->getObjectDir()."/".$file_id.".docmgr";
			$ext = return_file_extension($realname);
			
			$dest = $tmpdir."/worker.".$ext;
				              		
		}

		//copy to working location
		copy ($filename,$dest);                               		

		return $dest;
	
	}

	public function getSubscription() {
	
		$sql = "SELECT * FROM docmgr.dm_subscribe WHERE account_id='".USER_ID."' AND object_id='".$this->objectId."';";
		$list = $this->DB->fetch($sql);
		
		for ($i=0;$i<$list["count"];$i++) 
		{
			$this->PROTO->add("subscribe",$list[$i]);
		}

		//look for db errors	
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}


	public function updateSubscription() {

		$type = $this->apidata["type"];

		$sql = "DELETE FROM docmgr.dm_subscribe WHERE object_id='".$this->objectId."' AND account_id='".USER_ID."';";
		
		if ($type) {

			if (!is_array($type)) $type = array($type);
			if (!$this->apidata["send_email"]) $this->apidata["send_email"] = "f";
			if (!$this->apidata["send_file"]) $this->apidata["send_file"] = "f";
		
			//pull our settings from our checkbox
			for ($i=0;$i<count($type);$i++) 
			{

				$opt = null;
				$opt["object_id"] = $this->objectId;
				$opt["account_id"] = USER_ID;
				$opt["send_email"] = $this->apidata["send_email"];
				$opt["event_type"] = $type[$i];
				$opt["send_file"] = $this->apidata["send_file"];
				$opt["query"] = 1;
				$sql .= $this->DB->insert("docmgr.dm_subscribe",$opt).";";

			}

		}

		$this->DB->query($sql);		

		//look for db errors
		$err = $this->DB->error();
		if ($err) $this->throwError($err);

	}

	/****************************************************
		FUNCTION: createLink
		PURPOSE:	returns a link non-users can use to view 
							a file or document
	****************************************************/
	public function createLink()
	{

		//make sure it's a valid object type
		if ($this->objectInfo["object_type"]!="document" && $this->objectInfo["object_type"]!="file")
		{
			$err = "You can only create links for documents and files";
		} else {
		
			$time = time();

			//generate a unique hash		
			$link = md5($time.USER_LOGIN);
			$expires = $time + ($this->apidata["expire"] * 3600); 
			
			//first create a valid link reference in the database
			$opt = null;
			$opt["object_id"] = $this->objectId;
			$opt["link"] = $link;
			$opt["account_id"] = USER_ID;
			$opt["created"] = date("Y-m-d H:i:s",$time);
			$opt["expires"] = date("Y-m-d H:i:s",$expires);
			$this->DB->insert("docmgr.object_link",$opt);
			
			$url = SITE_URL."api.php?viewobj=".$link;	

			$err = $this->DB->error();
				
		}

		//return the error if there is one, otherwise show the link		
		if ($err) $this->throwError($err);
		else $this->PROTO->add("object_link",$url);

	}
	
	public function getView()
	{

		//bail if no object
		if (!$this->objectId) return false;

		$ret = array();
	
		$sql = "SELECT * FROM docmgr.object_view WHERE object_id='".$this->objectId."' AND account_id IN ('0','".USER_ID."')";
		$list = $this->DB->fetch($sql,1);

		if ($list["count"]>0)
		{

			//default view			
			$key = array_search("0",$list["account_id"]);
			if ($key!==FALSE) $ret["default_view"] = $list["view"][$key];
			
			//account view
			$key = array_search(USER_ID,$list["account_id"]);
			if ($key!==FALSE) $ret["account_view"] = $list["view"][$key];

		}

		return $ret;
	
	}

	public function getAlerts() 
	{

		$xml = file_get_contents("config/alerts.xml");
		$arr = XML::decode($xml);
		$num = count($arr["alert"]);
		
		$sql = "SELECT dm_alert.*,name,object_type FROM docmgr.dm_alert
						LEFT JOIN docmgr.dm_object ON dm_alert.object_id = dm_object.id
						WHERE dm_alert.account_id='".USER_ID."'";
		$list = $this->DB->fetch($sql);
		
		for ($i=0;$i<$list["count"];$i++) 
		{

			//get the alert description
			for ($c=0;$c<$num;$c++)
			{

				if ($arr["alert"][$c]["link_name"]==$list[$i]["alert_type"])
				{			
					$list[$i]["description"] = $arr["alert"][$c]["name"];
					break;
				}	
			
			}

			$this->PROTO->add("alert",$list[$i]);

		}

		//look for db errors	
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}

	public function getAllSubscriptions() 
	{

		$sql = "SELECT DISTINCT dm_subscribe.object_id,name,object_type FROM docmgr.dm_subscribe
						LEFT JOIN docmgr.dm_object ON dm_subscribe.object_id = dm_object.id
						WHERE dm_subscribe.account_id='".USER_ID."'";
		$list = $this->DB->fetch($sql);

		for ($i=0;$i<$list["count"];$i++) 
		{

			$this->PROTO->add("subscribe",$list[$i]);

		}

		//look for db errors	
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
		
	}

	public function clearAlert()
	{

		//the bit at the end keeps users from deleting other people's alerts
		$sql = "DELETE FROM docmgr.dm_alert WHERE id='".$this->apidata["alert_id"]."' AND account_id='".USER_ID."'";
		$this->DB->query($sql);	

		//look for db errors	
		$err = $this->DB->error();
		if ($err) $this->throwError($err);
	
	}

	protected function storeLevel()
	{
	
		$level1 = $this->DB->increment_seq("docmgr.level1");
		$level2 = $this->DB->increment_seq("docmgr.level2");

		//this should never change for an object, but we'll pass a delete query just to be safe"
		$sql = "DELETE FROM docmgr.dm_dirlevel WHERE object_id='".$this->objectId."';
						INSERT INTO docmgr.dm_dirlevel (object_id,level1,level2) VALUES ('".$this->objectId."','$level1','$level2');";
		$this->DB->query($sql);                 

		$level = $level1."/".$level2;

	}

	public function getPerms()
	{
	
		$cb = DOCMGR_UTIL_OBJPERM::getUser($this->objectId);

		if (!DOCMGR_UTIL_OBJPERM::check($cb,"admin")) 
		{
			$this->throwError("You do not have permission to manage this object");
		}
		else
		{
			DOCMGR_UTIL_OBJPERM::getList($this->objectId,$this->apidata);
		}
			
	}

	public function savePerms()
	{
	
		//make sure they have permissions to manage the object.  if not, bail          
		$cb = DOCMGR_UTIL_OBJPERM::getUser($this->objectId);

		if (!DOCMGR_UTIL_OBJPERM::check($cb,"admin")) 
		{
			$this->throwError("You do not have permission to manage this object");
		}
		else
		{

			DOCMGR_UTIL_OBJPERM::save($this->objectId,$this->apidata["perm"],$this->apidata["reset_perms"]);

			logEvent(OBJ_PERM_UPDATE,$this->objectId);

		}
			
	}

	public function trash()
	{

		//convert our object id into an array.  this is an array of objects we are moving
		if (is_array($this->objectId)) $objectArr = $this->objectId;
		else $objectArr = array($this->objectId);
	
		$path = "/Users/".USER_LOGIN."/Trash";
		$info = $this->objectFromPath(sanitize($path));
		
		//no collection, make one
		if (!$info) 
		{

			$path = "/Users/".USER_LOGIN;
			$info = $this->objectFromPath(sanitize($path));
			$parent = $info["id"];
			
			if (!$parent)
			{
				$this->throwError("There was an error creating the trash folder");
				return false;
			}

			//make the collection
			$opt = null;		
			$opt["name"] = "Trash";
			$opt["object_type"] = "collection";
			$opt["protected"] = 1;
			$opt["parent_id"] = $parent;
			$parentId = $this->create($opt);

			if (!$parentId)
			{
				$this->throwError("There was an error creating the trash folder");
				return false;
			}

			//make a bookmark for the user
			$opt = null;
			$opt["name"] = "Trash";
			$opt["account_id"] = USER_ID;
			$opt["protected"] = 1;
			$opt["object_id"] = $parentId;
			$id = $this->DB->insert("docmgr.dm_bookmark",$opt,"id");
			
		}
		else
		{

			$parentId = $info["id"];		
		
		}


		$oldParents = array();

		//now move everything into the trash folder
		foreach ($objectArr AS $object)
		{

			//init for later
			$LOCK = new DOCMGR_UTIL_LOCK($object);

		  $sql = "SELECT id,name,object_type,protected FROM docmgr.dm_object WHERE id='".$object."'";
		  $info = $this->DB->single($sql);

		  //if we can't find the obj, there's been an error.  Get out!!!	
		  if (!$info) {
	
		    $this->throwError("Could not find object \"".$object."\" for removal");
		    return false;
	
		  } else if ($LOCK->isLocked($info["id"])) {
	
		  	$this->throwError("\"".$info["name"]."\" is locked");
		  	return false;

		  } else if ($info["protected"]=="t") {
	
		  	$this->throwError("\"".$info["name"]."\" is a protected object and cannot be deleted");
		  	return false;
	
		  }

			//if the person is trying to delete an object that has been shared with them
			$s = new DOCMGR_SHARE();
			if ($s->deleteShare($object,USER_ID)) continue;

			//make sure we are allow to edit anything in the destination folder, since we are 
		  //moving things there
		  $cb = DOCMGR_UTIL_OBJPERM::getUser($object);
		  if (!DOCMGR_UTIL_OBJPERM::check($cb,"admin"))
		  {
		    $this->throwError("You do not have permissions to delete this ".$info["name"]);
				return false;
			}

			//get the old parents for later
			$sql = "SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id IN (".implode(",",$objectArr).");";
			$info = $this->DB->fetch($sql,1);
			$oldParents = array_merge($oldParents,$info["parent_id"]);

			//delete all parents of this object
			$sql = "DELETE FROM docmgr.dm_object_parent WHERE object_id='$object'";
			$this->DB->query($sql);		

			//get the object's name
			$sql = "SELECT name FROM docmgr.dm_object WHERE id='$object'";
			$info = $this->DB->single($sql);		
			$fileName = sanitize($info["name"]);

			//keep going until we find one that doesn't exist     
			while (!checkObjName($this->conn,sanitize($fileName),$parentId))
			{
				$fileName = $this->incrementName($fileName);
			}

			//update if it had to be changed
			if ($fileName != $info["name"])
			{

				$sql = "UPDATE docmgr.dm_object SET name='".$fileName."' WHERE id='$object'";
				$this->DB->query($sql);			
			
			}

			//set it up in the trash folder
			$opt = null;
			$opt["parent_id"] = $parentId;
			$opt["object_id"] = $object;
			$opt["account_id"] = USER_ID;
			$this->DB->insert("docmgr.dm_object_parent",$opt);

		}

		//update trash folder size
		$this->updateCollectionSizes($parentId);		  
		$this->updateCollectionSizes($oldParents);

	}

	//empties the user's trash folder
	public function emptyTrash() 
	{

		$path = "/Users/".USER_LOGIN."/Trash";
		$info = $this->objectFromPath(sanitize($path));

		if (!$info) return false;
		
		$sql = "SELECT id FROM docmgr.dm_view_objects WHERE parent_id='".$info["id"]."'";
		$list = $this->DB->fetch($sql,1);

		if ($list["count"] > 0) $this->delete($list["id"]);
	
	}

	//gets content only for a file or docmgr document
	public function getContent()
	{
	
		if ($this->objectInfo["object_type"]=="document")
		{

			$d = new DOCMGR_DOCUMENT($this->objectId);
			return $d->get();		
		
		}
		else
		{
		
			//make sure it passes back the content of the file in the response
			$d = new DOCMGR_FILE($this->objectId);
			return $d->get("contentonly");		
		
		}
	
	}

	/************************************************************
		FUNCTION:	getProperties
		PURPOSE:	(webdav only) pulls an object's properties as
							set by a webdav client
		INPUTS:		none
		RETURNS:	none
		OUTPUTS:	properties -> array
	************************************************************/
	public function getProperties()
	{

		if (!$this->objectId) $this->objectId = "0";
		
		$sql = "SELECT data FROM docmgr.dm_properties WHERE object_id='".$this->objectId."'";
		$info = $this->DB->single($sql);

		$this->PROTO->add("properties",unserialize($info["data"]));

		if ($this->DB->error()) $this->throwError($this->DB->error());
	
	}

	/************************************************************
		FUNCTION:	saveProperties
		PURPOSE:	(webdav only) save's an object's properties as
							set by a webdav client
		INPUTS:		apidata:
								data -> array of properties set by client
		RETURNS:	none
		OUTPUTS:	none;
	************************************************************/
	public function saveProperties()
	{

		if (!$this->objectId) $this->objectId = "0";
		
		$sql = "SELECT data FROM docmgr.dm_properties WHERE object_id='".$this->objectId."'";
		$info = $this->DB->single($sql);

		if (is_array($this->apidata["properties"])) $this->apidata["properties"] = serialize($this->apidata["properties"]);

		$opt = null;
		$opt["data"] = $this->apidata["properties"];

		if ($info)
		{
			$opt["where"] = "object_id='".$this->objectId."'";
			$this->DB->update("docmgr.dm_properties",$opt);
		}
		else
		{
			$opt["object_id"] = $this->objectId;
			$this->DB->insert("docmgr.dm_properties",$opt);
		}

	}

	protected function updateCollectionSizes($objId=null)
	{

		if (!$objId) $objId = $this->objectId;
		
		if (!$objId) return false;

		DOCMGR_UTIL_COMMON::updateCollectionSizes($objId);
		
	}


	public function getStorage($objId=null)
	{
	
		if (!$objId) $objId = $this->objectId;
		
		return $this->createStorage($objId);
	
	}

	/****************************************************************************
		FUNCTION:	create
		PURPOSE:	creates a storage folder for files for this object
		INPUTS:		none
	****************************************************************************/

	public function createStorage($objId=null,$nocreate=null)
	{

		if (!$objId) $objId = $this->objectId;

		//get the object type
		$sql = "SELECT object_type FROM docmgr.dm_object WHERE id='$objId'";
		$info = $this->DB->single($sql);
		$objType = $info["object_type"];

		//the dot on the first character signifies it's hidden
		$name = ".object".$objId."_storage";

		//make sure this doesn't already exist.  if it does, return the object_id
		$sql = "SELECT id,object_type FROM docmgr.dm_object WHERE name='".$name."' AND hidden='t'";
		$info = $this->DB->single($sql);

		if ($info) 
		{

			$returnId = $info["id"];

			//okay, so for files we need to make sure we have edit access to the storage folder.  The only
			//time storage would be used would be if we're viewing the file as html, or converting it to html
			//so, in this case, when we have view access to the file and we want to convert to html, we'll use
			//our temp folder instead
			if ($objType=="file")
			{

				$cb = DOCMGR_UTIL_OBJPERM::getUser($returnId);
				if (DOCMGR_UTIL_OBJPERM::bitToText($cb)=="view")
				{
			
					//view only access.  use our temp folder
					$returnId = $this->createTemp();

					//don't try to delete anything in the storage folder
					$nocreate = 1;
					
				}			

			}
			
			//clear out existing files if set
			if (!$nocreate && $this->apidata["clearall"])
			{
			
				$sql = "SELECT object_id FROM docmgr.dm_object_parent WHERE parent_id='".$returnId."'";
				$list = $this->DB->fetch($sql);

				for ($i=0;$i<$list["count"];$i++)
				{
					$o = new DOCMGR_OBJECT($list[$i]["object_id"]);
					$o->delete();
				}

				$sql = "SELECT object_id FROM docmgr.dm_object_parent WHERE parent_id='".$returnId."'";
				$list = $this->DB->fetch($sql);
			
			}

		} 
		else 
		{

			//okay, so for files we need to make sure we have edit access to the storage folder.  The only
			//time storage would be used would be if we're viewing the file as html, or converting it to html
			//so, in this case, when we have view access to the file and we want to convert to html, we'll use
			//our temp folder instead
			if ($objType=="file")
			{

				$cb = DOCMGR_UTIL_OBJPERM::getUser($objId);
				if (DOCMGR_UTIL_OBJPERM::bitToText($cb)=="view")
				{
			
					//view only access.  use our temp folder
					$returnId = $this->createTemp();
					
				}			

			}

			//still not found, make one
			if (!$returnId)
			{
			
				//we store it under the object itself.  that way, if we move it somewhere else,
				//the storage folder automatically goes w/ it
				$opt = null;
				$opt["name"] = $name;
				$opt["hidden"] = "t";
				$opt["parent_id"] = $objId;
			
				$c = new DOCMGR_COLLECTION($opt);
				$returnId = $c->save();

			}
			
		}
		
		//look for db errors
		$err = $this->DB->error();
		if ($err) 
		{
			$this->throwError($err);
			return false;
		} 
		else 
		{

			$this->PROTO->add("storage_id",$returnId);

			return $returnId;

		}
    	
	}

	//creates a .temp folder in the user directory
	public function createTemp() 
	{

		$path = "/Users/".USER_LOGIN;
		$info = $this->objectFromPath(sanitize($path));
		$parentId = $info["id"];
		$name = ".temp_storage";
		
		//see if it exists
		$sql = "SELECT id FROM docmgr.dm_view_objects WHERE name='$name' AND parent_id='$parentId'";
		$tempinfo = $this->DB->single($sql);
		
		//if we have one, empty it out
		if ($tempinfo["id"]) 
		{
		
			$sql = "SELECT id FROM docmgr.dm_view_objects WHERE parent_id='".$tempinfo["id"]."'";
			$list = $this->DB->fetch($sql);

			for ($i=0;$i<$list["count"];$i++) 
			{
				$o = new DOCMGR_OBJECT($list[$i]["id"]);
				$o->delete();
			}
			
			$tempFolderId = $tempinfo["id"];

		//otherwise make one	
		} else {

			$opt = null;
			$opt["name"] = $name;
			$opt["hidden"] = "t";
			$opt["parent_id"] = $parentId;
			
			$c = new DOCMGR_COLLECTION($opt);
			$returnId = $c->save();

		}

		//look for db errors
		$err = $this->DB->error();
		if ($err) 
		{
			$this->throwError($err);
			return false;
		} 
		else 
		{
		
			$this->PROTO->add("storage_id",$tempFolderId);
			return $tempFolderId;
			
		}
		
	}

	//creates a .temp folder in the user directory
	public function emptyTemp() 
	{

  	$path = "/Users/".USER_LOGIN;
  	$info = $this->objectFromPath(sanitize($path));

  	if (!$info) return false;
  		
		$parentId = $info["id"];
		$name = ".temp_storage";
		
		//see if it exists
		$sql = "SELECT id FROM docmgr.dm_view_objects WHERE name='$name' AND parent_id='$parentId'";
		$tempinfo = $this->DB->single($sql);
		
		//if we have one, empty it out
		if ($tempinfo["id"]) {
		
			$sql = "SELECT id FROM docmgr.dm_view_objects WHERE parent_id='".$tempinfo["id"]."'";
			$list = $this->DB->fetch($sql);
			for ($i=0;$i<$list["count"];$i++) 
			{
				$o = new DOCMGR_OBJECT($list[$i]["id"]);
				$o->delete();
			}
			
		}
			
	}
	
	//makes a storage directory for our object and moves our temp files into it
	protected function tempToStorage() 
	{
	
		//create storage for the object
		$stid = $this->createStorage(null,1);

		$path = "/Users/".USER_LOGIN."/.temp_storage";
		$info = $this->objectFromPath(sanitize($path));
		$tempid = $info["id"];

		if (!$stid) 
		{

			$this->throwError("Error transferring files from temporary storage to document storage");

		} 
		else if ($tempid) 
		{
	
			//move files into it
			$sql = "UPDATE docmgr.dm_object_parent SET parent_id='$stid' WHERE parent_id='$tempid'";
			$this->DB->query($sql);

			//look for db errors
			$err = $this->DB->error();
			if ($err) 
			{
				$this->throwError($err);
				return false;
			} 
			else 
			{
				$this->PROTO->add("storage_id",$stid);

				return $stid;
				
			}
		
		}

	}

}
