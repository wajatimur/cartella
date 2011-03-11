<?php

$thumb = "../images/thumbnails/file.png";
 
define("ALT_FILE_PATH","../");

//call this file to get our path to the thumbnails
include("../config/config.php");
include("../config/ldap-config.php");
include("../lib/filefunctions.php");
include("../lib/pgsql.php");
include("../lib/logger.php");
include("../lib/proto/xml.php");
include("../lib/xml.php");
include("../app/common.php");
include("../lib/account/account.php");

$objectId = $_REQUEST["objectId"];
$sessionId = $_REQUEST["sessionId"];

$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);
$GLOBALS["DB"] = $DB;

if ($sessionId && $sessionId!="[DOCMGR_SESSION_MARKER]") 
{
  session_id($sessionId);
  session_start();
} else {

  //the only thing that should use this is openoffice during conversions
  if ($_REQUEST["login"] && $_REQUEST["password"]) 
  {
  
     //check to see if the user and password combo exist
     $a = new ACCOUNT();
     if ($accountInfo = $a->password_check($_REQUEST["login"],$_REQUEST["password"])) $_SESSION["authorize"] = 1;
           
  }

}

//stop here if not authorized by session id or username/password
if (!$_SESSION["authorize"] && !$_SESSION["api"]["authorize"]) die("Invalid session paramaters set");

$sql = "SELECT DISTINCT id,name,(level1 || '/' || level2) AS file_path,
        (SELECT id FROM docmgr.dm_file_history WHERE dm_file_history.object_id=dm_view_objects.id ORDER BY version DESC LIMIT 1) AS file_id
        FROM docmgr.dm_view_objects
        WHERE id='$objectId'";
$list = $DB->fetch($sql);

for ($i=0;$i<$list["count"];$i++) {

  $key = $list[$i]["id"];
  $fileid = $list[$i]["file_id"];
  $filepath = $list[$i]["file_path"];
  $filename = $list[$i]["name"];
 
} 

$DB->close();


define("DATA_DIR",FILE_DIR."/data");


//put our path in a variableË
$d = DATA_DIR."/".$filepath;

//if the thumb_dir is an absolute path, point directly to it.
//if it's relative, move up a directory to get to the file
if ($d[0]=="/") $filepath = $d."/".$fileid.".docmgr";
else $filepath = "../".$d."/".$fileid.".docmgr";

if (!file_exists($filepath)) {
  $filepath = "../themes/default/images/thumbnails/file.png";
  $filename = "file.png";
}

$fileinfo = fileInfo($filename);
$mime = $fileinfo["mime_type"];

header("Content-Type: $mime");
readfile($filepath);
