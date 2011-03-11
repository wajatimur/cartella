<?php

/********************************************************************************************

	Filename:
		common.inc.php
      
	Summary:
		this file contains functions common to all modules in this application.
		They should still be somewhat generic
            
	Modified:
              
		09-02-2004
			Code cleanup.  Moved functions that don't belong out
                        
*********************************************************************************************/

function returnCatOwner($info,$id,$pass_array,$owner = null) {

	if (!$pass_array) $pass_array[] = $id;

	//see if there is an owner for this key
	$key = array_search($id,$info["id"]);

	if (!$owner) $owner = $info["parent_id"][$key];

	//this exits if we are at the top.  
	//it now also exits if a category owns itself.  This should not happen, and will 
	//crash the webserver in a neverending loop if not checked here
	if ($owner!=0 && $owner!=$id) 
	{

	  //if the owner is already in there, we're looping back on our self.  bail
	  if (!in_array($owner,$pass_array)) 
	  {
  		$pass_array[] = $owner;
  		$pass_array = returnCatOwner($info,$owner,$pass_array);
    }
    
	}
	return $pass_array;
		
}

function simpleNavArray($id,$catInfo=null,$permArr=null,$parentId=null) 
{

  global $DB;

	if (!$catInfo) 
	{
	
		//get all collections that need to be displayed
		$sql = "SELECT DISTINCT id,name,parent_id,object_type FROM docmgr.dm_view_collections ORDER BY name";
		$catInfo = $DB->fetch($sql,1);

  }

  if (!$permArr)
  {

		//get our collections we are allowed to see
		$sql = "SELECT id FROM docmgr.dm_view_collections WHERE ".permString();

		if (!PERM::check(ADMIN))
		{
      $permList = $DB->fetch($sql,1);
      $permArr = &$permList["id"];
    }

	}
	
	//only keep going if we are not at the root level
	if ($id) 
	{

		//get our array of category owners
		$ownerArray = array_reverse(returnCatOwner($catInfo,$id,null,$parentId));
		$num = count($ownerArray);
		
		for ($row=0;$row<$num;$row++) 
		{

			$obj = $ownerArray[$row];
      $hide = false;
      
			//get info for the current collection
			$key = array_search($ownerArray[$row],$catInfo["id"]);

			//handle permissions
			if ($permArr)
			{
			  $key = array_search($ownerArray[$row],$permArr);
			  if ($key===FALSE) $hide = true;
      }

			//make sure the user has permissions to see this collection
			if ($hide) $arr[] = "Hidden";
			else
			{
	      $name = $catInfo["name"][$key];
	      $arr[] = $name;
      }
      
		}

	}
	
	return $arr;

}


//log an event for an object in the database
function logEvent($logType,$objectId,$data = null, $accountId = null) {

  global $DB;
  
	if (defined("USER_ID")) $accountId = USER_ID;
	else $accountId = $accountId;

	if (!$accountId) $accountId = "0";
	
	$opt = null;
	$opt["object_id"] = $objectId;
	$opt["log_type"] = $logType;
	$opt["account_id"] = $accountId;
	$opt["log_time"] = date("Y-m-d H:i:s");

	//optional data for the log
	if ($data) $opt["log_data"] = $data;
	$DB->insert("docmgr.dm_object_log",$opt);

}

function returnLoglist() {

	$data = file_get_contents("config/logtypes.xml");
	return parseGenericXml("log_type",$data);

}

function returnLogType($logArr,$logType) {

	//get out if we don't have an array of possible logs
	if (!$logArr) return false;
	
	if (!in_array($logType,$logArr["link_name"])) return false;

	$langtext = "_LT_".$logType;
	
	if (defined($langtext)) $text = constant($langtext);
	else {
	
		$key = array_search($logType,$logArr["link_name"]);
		$text = $logArr["name"][$key];

	}

	return $text;	

}

//return a query to filter our objects to only allow those a non-admin can see
function permString() {

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

//get a list of all possible alerts
function returnAlertList() {

        if (defined("ALT_FILE_PATH")) $file = ALT_FILE_PATH."config/alerts.xml";
        else $file = "config/alerts.xml";
	$data = file_get_contents("$file");
	return parseGenericXml("alert",$data);

}

//return the alert name for this type
function returnAlertType($alertArr,$alertType) {

	//get out if we don't have an array of possible alerts
	if (!$alertArr) $alertArr = returnAlertList();

	if (!in_array($alertType,$alertArr["link_name"])) return false;

	$el = "_AT_".$alertType;

        if (defined($el)) $alertMsg = constant($el);
	else {
	
		$key = array_search($alertType,$alertArr["link_name"]);
		$alertMsg = $alertArr["name"][$key];
		
	}
	        
	return $alertMsg;	

}

function createEventMsg($objName,$eventType,$actionName = null) {

	$alertArr = returnAlertList();

	$el = "_".$eventType;

        if (defined($el)) $str = constant($el);
        else $str = returnAlertType($alertArr,$eventType);;

        //append the name of the created file if necessary
        if ($eventType=="OBJ_CREATE_ALERT" && $actionName) $str .= ": ".$actionName;

        $msg = "The following event ocurred: \"".$objName."\"<br><br><b>".$str."</b>\n";

	return $msg;

}

//send a subscription alert for all users for this object
function sendEventNotify($objectId,$eventType,$parent = null) {
       
    global $DB;

    //get all names
    $sql = "SELECT id,name,parent_id FROM docmgr.dm_view_objects WHERE id='$objectId' OR object_type='collection'";
    $catInfo = $DB->fetch($sql,1);

    //get our array of category owners
    $objArr = array_reverse(returnCatOwner($catInfo,$objectId,null));
        
	//get all users that are subscribed to this file and arent the current user
	$sql = "SELECT * FROM docmgr.dm_subscribe WHERE object_id IN (".implode(",",$objArr).") AND event_type='$eventType' AND account_id!='".USER_ID."';";
	$list = $DB->fetch($sql);

	//get out if there's no subscribers to this object and event
	if (!$list["count"]) return false;

	$sql = NULL;

	//get the object's information
	$sql = "SELECT name,object_type,version FROM docmgr.dm_object WHERE id='$objectId';";
	$oInfo = $DB->single($sql);

	$sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='$objectId' ORDER BY version DESC LIMIT 1";
        $fileinfo = $DB->single($sql);

        //if a collection is passed, we need to recognize this
        if ($parent) {
          $sql = "SELECT name FROM docmgr.dm_object WHERE id='$parent'";
          $pInfo = $DB->single($sql);
          $objName = $pInfo["name"];
        } else $objName = $oInfo["name"];
	
	for ($i=0;$i<$list["count"];$i++) {
	
		//add the alert
		$sql = "INSERT INTO docmgr.dm_alert (account_id,object_id,alert_type)
						VALUES
						('".$list[$i]["account_id"]."','$objectId','$eventType');";	
		$DB->query($sql);

      if ($oInfo["object_type"]=="collection") $module="colprop";
      elseif ($oInfo["object_type"]=="url") $module="urlprop";
      elseif ($oInfo["object_type"]=="document") $module="docprop";
      else $module="fileprop";

			$link = SITE_URL."index.php?module=".$module."&objectId=".$objectId;

			$msg = createEventMsg($objName,$eventType,$oInfo["name"]);					
			$msg .= "<br>View Object Properties";
			$msg .= "<br><br><a href=\"".$link."\">".$link."</a>\n";

			$sub = "DocMGR Event Notification \"".$objName."\"";	

			$aInfo = returnAccountInfo($list[$i]["account_id"]);

			//if the user wants the attachment, send the file with the attachment
			if ($list[$i]["send_file"]=="t" && $oInfo["object_type"]=="file" && 

			    ($eventType=="OBJ_LOCK_ALERT" || $eventType=="OBJ_CREATE_ALERT")) {
			
			    $sql = "SELECT id FROM docmgr.dm_file_history WHERE object_id='$objectId' AND version='".$oInfo["version"]."'";
			    $fileinfo = $DB->single($sql);
			    
			    $filePath = DATA_DIR."/".returnObjPath($DB->getConn(),$objectId)."/".$fileinfo["id"].".docmgr";

			    //assemble our attachment array
			    $attach[0]["name"] = $oInfo["name"];
			    $attach[0]["path"] = $filePath;
                         			
			} else $attach = null;

			send_email($aInfo["email"],ADMIN_EMAIL,$sub,$msg,$attach);

	}

	return true;
		
}

//send a task notify alert for this account
function sendTaskNotify($conn,$objId,$accountId) {

	if (!$objId) return false;
	if (!$accountId) return false;
	
	//get the email address
	$info = returnAccountInfo($conn,$accountId,null);
	$addr = $info["email"];
	
	if (!$addr) return false;

	//get the object name
	$sql = "SELECT name FROM docmgr.dm_object WHERE id='$objId';";
	$info = single_result($conn,$sql);

	if (!$info) return false;	

	$link = SITE_URL."index.php?module=file&includeModule=filetask&objectId=".$objId;

	$msg = createEventMsg($info["name"],"OBJ_TASK_ALERT");					
	$msg .= "<br>"._VIEW_FILE_TASK;
	$msg .= "<br><br><a href=\"".$link."\">".$link."</a>\n";
	
	$sub = "DocMGR "._TASK_NOTIFICATION." \"".$info["name"]."\"";	

	send_email($addr,ADMIN_EMAIL,$sub,$msg,null);

	return true;
		
}


/******************************************************************************
	scan a file for a virus.  this returns "clean" if nothing was found.
	it returns the name of the virus if an infection is found.  If there
	is a scan error, it returns false
*******************************************************************************/
function clamAvScan($filepath) {

	if (!defined("CLAMAV_SUPPORT")) return false;

	$app = APP_CLAMAV;
	$str = `$app --infected "$filepath"`;
	
	//return false if there is a scanning error
	if (strstr($str,"Scanned files: 0")) return false;

	//if no infected files are found, return true;
	if (strstr($str,"Infected files: 0")) return "clean";
	else 
	{
	
		//viruses were found, display the found virus information
		$pos = strpos($str,"----------- SCAN SUMMARY -----------");
		$vf = trim(substr($str,0,$pos));

		$pos = strpos($vf,":") + 1;
		$vf = _VIRUS_WARNING."! ".substr($vf,$pos);					

		return $vf;
	
	}
}

/****************************************************************************
	this function compares the md5 sum of the file we're accessing
	to the stored value created at the time of file upload.  If
	the values do not match, we return false.
****************************************************************************/
function fileChecksum($conn,$id,$filepath) {

	//sanity checking
	if (!$id) return false;
	if (!$filepath) return false;
	if (!is_file($filepath)) return false;

	//get the stored md5sum
	$sql = "SELECT md5sum FROM docmgr.dm_file_history WHERE id='$id';";
	$info = single_result($conn,$sql);

	//get the md5sum for the file we're trying to access
	$md5sum = md5_file($filepath);

	//make sure values exist for both
	if (!$md5sum || !$info["md5sum"]) return false;

	//return true if they match
	if ($md5sum==$info["md5sum"]) return true;
	else return false;
	
}

/**************************************************************************
	This function creates a checksum.md5 file with the path
	of the file and its checksum.  it returns the path
	to the checksum file if successful, false on failure
**************************************************************************/
function createChecksum($conn,$id,$filename) {

	//sanity checking
	if (!$id) return false;
	if (!$filename) return false;

	//get the stored md5sum
	$sql = "SELECT md5sum FROM docmgr.dm_file_history WHERE id='$id';";
	$info = single_result($conn,$sql);

	$md5sum = $info["md5sum"];

	//create a temp directory for our user
	$dir = TMP_DIR."/".USER_LOGIN;
	$file = $dir."/checksum.md5";
	
	if (!is_dir("$dir")) mkdir("$dir");

	$str = $md5sum."  ./".$filename."\n";

	//make sure the file doesn't already exist
	@unlink($file);

	$fp = fopen("$file",w);
	fwrite($fp,$str);
	fclose($fp);

	return "$file";
	
}

//checks to see if a program with the passed pid is running
function isPidRunning($pid) {

    if (!$pid) return false;

    $str = `ps --no-headers --pid $pid`;
     
    if (strstr($str,$pid)) return true;
    else return false;
       
}

//checks to see if a program of the passed name is running       
function checkIsRunning($app) {

  $cmd = "ps aux | grep \"".$app."\" | grep -c -v grep";
  $num = `$cmd`;

  if ($num > 0) return true;
  else return false;

}

//runs a program in the background
function runProgInBack($prog,$file = null) {

  //if no file, create an output file
  if (!$file) $file = "/dev/null";

  //$pid = exec("$prog 1>/tmp/prog1 2>/tmp/prog2");

  //output errors to the console if debug is turned on
  if (defined("DEBUG") && DEBUG > 0) $pid = exec("$prog >> $file & echo \$!");
  else $pid = exec("$prog >> $file 2>/dev/null & echo \$!");

  return $pid;

}

function createTempFile($ext = null) {

  if (!$ext) $ext = "txt";

  if (defined("USER_ID")) $fn = TMP_DIR."/".USER_ID."_".rand().".".$ext;
  else  $fn = TMP_DIR."/".rand().".".$ext;

  //if the file exists, remove it and create a new one with open permissions
  if (file_exists($fn)) unlink($fn);
  
  //create our empty file
  $fp = fopen($fn,"w");
  fclose($fp);

  //set the permissions as open as possible.  This way if an external script
  //is run as root, we can remove it as the webuser later
  chmod($fn,0777);

  return $fn;

}

//reformats our inline document for proper display
function formatEditorStr($str) {
  
  //re-add session id.  also replace the "&" w/ an html entity
  $sess = "sessionId=".session_id();
  $str = str_replace("&[DOCMGR_SESSION_MARKER]","&amp;".$sess,$str);

  //just in case it was encoded
  $str = str_replace("%5BDOCMGR_SESSION_MARKER%5D",$sess,$str);
  
  //make sure we have a doctype
  $str = fixDoctype($str);

  return $str;

}

//removes the session id and cleans up other items for document saving
function cleanupEditorStr($str) {

  //make sure we have a doctype
  $str = fixDoctype($str);

  //remove the current session id
  $sess = "sessionId=".session_id();
  $str = str_replace($sess,"[DOCMGR_SESSION_MARKER]",$str);

  //fckeditor removes our & signs
  $str = str_replace("&amp;","&",$str);

  return $str;
  
}

//converts a string to an array that we can run php array functions on
function strtoarray($str) {

  if (!$str) return false;

  $arr = array();
  $len = strlen($str);

  for ($i=0;$i<$len;$i++) $arr[] = $str[$i];

  return $arr;

}


//check for an existing object with the new object's name
function checkObjName($conn,$name,$parentId,$objectId = null) {

  //first check to see if all our characters are valid
  if (defined("DISALLOW_CHARS")) {
    //treat both strings as arrays, make sure no characters in name are in our checkstr array  
    //yes, I know strings are arrays.  I did it this way for cleaner code
    $checkArr = strtoarray(DISALLOW_CHARS);
    $nameArr = strtoarray($name);

    $len = strlen($name);
    for ($i=0;$i<$len;$i++) {
      if (in_array($nameArr[$i],$checkArr)) {
        define("ERROR_MESSAGE",_INVALID_CHAR_IN_NAME." ".DISALLOW_CHARS);
        define("ERROR_CODE","OBJECT_EXISTS");
        return false;
      }
    }
    
  }  
    
  //if we have an object with no parents, get the parents
  if ($parentId==NULL && $objectId) {
  
    $sql = "SELECT parent_id FROM docmgr.dm_view_objects WHERE id='$objectId'";
    $list = total_result($conn,$sql);
    
    $parentId = $list["parent_id"];
  
  }

  //make sure parentId is an array before we continue
  if ($parentId==NULL) $parentId = "0";
  if (!is_array($parentId)) $parentId = array($parentId);

  $sql = "SELECT id FROM docmgr.dm_view_objects WHERE name='".$name."' AND parent_id IN (".implode(",",$parentId).")";
  
  //if objectId is passed, we are doing an update and want to make sure the updated name doesn't
  //exist with another object
  if ($objectId) $sql .= " AND id!='$objectId'";

  $exists = num_result($conn,$sql);
  if ($exists > 0) {
    //get the name of the parents for the error message
    if (!$parentId[0])
      $parentName = _HOME;
    else {
      
      $sql = "SELECT name FROM docmgr.dm_object WHERE id IN (".implode(",",$parentId).")";
      $info = total_result($conn,$sql);
      $parentName = implode("\" "._OR." \"",$info["name"]);

    }
    
    $msg = _OBJ_WITH_NAME." \"".$name."\" "._ALREADY_EXISTS_IN." \"".$parentName."\"";
    define("ERROR_MESSAGE",$msg);
    define("ERROR_CODE","OBJECT_EXISTS");

    return false;
       
  }

  return true;
       
}


/**********************************************************
  checkObjType
    Here we verify the module we are working on
    in designed to operate on the object being
    passed to it.
**********************************************************/
function checkObjType($conn,$module,$objectId) {

  //sanity checking
  if (!$module) return false;
  if (!$objectId) return false;

  $modPath = $_SESSION["siteModInfo"][$module]["module_path"];
  $modArr = explode("/",$modPath);
  
  //remove modules/center/ from the array
  array_shift($modArr);
  array_shift($modArr);

  //get the object type for this object
  $sql = "SELECT object_type FROM docmgr.dm_object WHERE id='$objectId'";
  $info = single_result($conn,$sql);

  //get out if something didn't work right
  if (!$info) return false;
    
  //loop through our modules in our path and see if our object_type is in there
  //if it is, then we are in a subdirectory of the object's owning module
  foreach ($modArr AS $mod) {
    if ($mod==$info["object_type"]) return true;  
  }
  
  //if we make it this far, we didn't find a match so return false;
  return false;

}


//storeObjLevel inserts a record in the database with the two level ids
//the object will use when writing files to the filesystem
function storeObjLevel($objId,$level1,$level2) 
{

  global $DB;

  //this should never change for an object, but we'll pass a delete query just to be safe"
  $sql = "DELETE FROM docmgr.dm_dirlevel WHERE object_id='$objId';
          INSERT INTO docmgr.dm_dirlevel (object_id,level1,level2) VALUES ('$objId','$level1','$level2');";
  if ($DB->query($sql)) return true;
  else return false;
  
}

function getObjectDir($objId) {

  global $DB;

  //get the values for this object
  $sql = "SELECT level1,level2 FROM docmgr.dm_dirlevel WHERE object_id='$objId'";
  $info = $DB->single($sql);

  //get out if nothings found
  if (!$info) return false;

  //merge into a dir structure and return
  return $info["level1"]."/".$info["level2"];

}
 
//legacy function
function returnObjPath($conn,$objId) {
 
  return getObjectDir($objId);
 
}
 
function setupWebImages($content) {

  global $DB;

  //first setup our session marker
  $objectId = extractObjectsFromText($content);
  $_SESSION["siteViewObject"] = null;

  if (count($objectId) > 0) {

    $sql = "SELECT DISTINCT id,name,(level1 || '/' || level2) AS file_path,
            (SELECT id FROM docmgr.dm_file_history WHERE dm_file_history.object_id=dm_view_objects.id ORDER BY version DESC LIMIT 1) AS file_id
            FROM docmgr.dm_view_objects
            WHERE id IN (".implode(",",$objectId).")";
    $list = $DB->fetch($sql);
  
    for ($i=0;$i<$list["count"];$i++) {
  
      $key = $list[$i]["id"];
      $_SESSION["siteViewObject"]["$key"]["file_id"] = $list[$i]["file_id"];
      $_SESSION["siteViewObject"]["$key"]["file_path"] = $list[$i]["file_path"];
      $_SESSION["siteViewObject"]["$key"]["name"] = $list[$i]["name"];
   
    } 

  }

  //setup the session marker
  $content = str_replace("[DOCMGR_SESSION_MARKER]",session_id(),$content);
  return $content;

}

function extractObjectsFromText($haystack) {

  $needle = "sessionId=[DOCMGR_SESSION_MARKER]&objectId=";
  $objects = array();
  
  while ( ($pos=strpos($haystack,$needle))!==FALSE )  {
  
    //cut off after this find
    $haystack = substr($haystack,$pos + strlen($needle));  

    //find next quote
    $endpos = strpos($haystack,"\"");  
    $object = substr($haystack,0,$endpos);
    $objects[] = $object;

  }

  return $objects;
}

