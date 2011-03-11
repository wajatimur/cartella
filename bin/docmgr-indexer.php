<?php

/******************************************************************************
  Indexer script.  It will only work if called from the root docmgr directory
******************************************************************************/

//set which DocMGR user id the script should run as.  defaults to 
//"admin" user
define("USER_ID","1");

/*****************************************************************************
  end configurable options
*****************************************************************************/

/******************************************************************************
    preliminary configuration and variable setting
******************************************************************************/

//get our includes
//first call the config file to get our settings, call our base functions, and get our wrapper
include("header/header.inc.php");
include("app/common.php");
include("app/common-docmgr.php");
include("app/openoffice.php");
include("app/client.php");
include("apilib/auth.php");

//setup which apps are available to docmgr
setExternalApps();

//register our autoloader so we can call
//api functions direct from the client
spl_autoload_register('client_autoload');

//get our account info
$a = new ACCOUNT(USER_ID);
$info = $a->getInfo();

$a = new AUTH($info["login"],$info["password"]);
if ($a->getError()) die($a->getError()."\n");

//allow indexing of a certain objectId.  This is for debugging only
if (in_array("--index-object",$argv)) 
{

  //we are looking for the id passed after our parameter
  $key = array_search("--index-object",$argv) + 1;
  $obj = $argv[$key];
  
  if ($obj) 
  {

    //init the class
    $d = new DOCMGR_OBJECT($obj);

    //index the item    
    echo "Indexing object ".$obj."\n";
    $d->index();
    
    echo "Thumbnailing object ".$obj."\n";
    $d->thumb();

    echo "Preview Thumbnailing object ".$obj."\n";
    $d->preview();

  } else echo "Invalid object id passed\n";
  
  die;
  
}

//reindex the entire collection of objects
if (in_array("--reindex-all",$argv)) {

  $sql = "SELECT id FROM docmgr.dm_object ORDER BY id";
  $list = $DB->fetch($sql);
  
  for ($i=0;$i<$list["count"];$i++)
  {
  
    echo "Queueing Object ".$list[$i]["id"]."\n";

    $opt = null;
    $opt["object_id"] = $list[$i]["id"];
    $DB->insert("docmgr.dm_index_queue",$opt);
  
  }

}


/*******************************************************************************
    Now it's time to process our batch.  We continue to run until
    there are no more batches left
*******************************************************************************/


while (1) {

    //get the ids of all the objects in this batch
    $sql = "SELECT * FROM docmgr.dm_index_queue ORDER BY id";
    $list = $DB->fetch($sql);

    debugMsg(1,"No objects found in the queue.  Exiting");

    //get out if there's nothing to do
    if (!$list["count"]) exit;

    debugMsg(1,$list["count"]." objects found in the queue.  Proceeding to index");

    //if this is a tsearch2 configuration, we need to go ahead and index the name and summaries
    //for all files.  This should be quick, and will make the files be returned if a user 
    //tries a search before the indexer finishes
    for ($i=0;$i<$list["count"];$i++) 
    {

      echo "Quick indexing ".$list[$i]["object_id"]."\n";

      $opt = null;
      $opt["prop_only"] = 1;
      $opt["object_id"] = $list[$i]["object_id"];

      $d = new DOCMGR_OBJECT($opt);
      $d->index();

    }
    
    //loop through again and run the full index
    for ($i=0;$i<$list["count"];$i++) 
    {
      
      $obj = &$list[$i]["object_id"];
      
      echo "Full indexing ".$obj."\n";

      //init the class
      $d = new DOCMGR_OBJECT($obj);

      //index the item    
      echo "Indexing object ".$obj."\n";
      $d->index();
    
      echo "Thumbnailing object ".$obj."\n";
      $d->thumb();

      echo "Preview Thumbnailing object ".$obj."\n";
      $d->preview();

      //delete this item from the queue
      $sql = "DELETE FROM docmgr.dm_index_queue WHERE id='".$list[$i]["id"]."'";
      $DB->query($sql);

    }

    //sleep 10 seconds before checking for another batch
    sleep(5);

}

function debugMsg($level,$msg) {

  if (php_sapi_name()=="cli") $sep = "\n";
  else $sep = "<br>";
    
  if (DEBUG >= $level) echo $msg.$sep;
           
}
            