#!/usr/bin/php
<?php
//die("DIABLED\n");

/***************************************************
  begin configurable options
***************************************************/

define("ALT_FILE_PATH","/www/docmgr");

/***************************************************
  end configurable options
***************************************************/

/***************************************************
  get our includes
***************************************************/
include(ALT_FILE_PATH."/config/config.php");
include(ALT_FILE_PATH."/header/callheader.php");

/***************************************************
  db setup
***************************************************/
$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);
$GLOBALS["DB"] = $DB;

/***************************************************
  run our maintenance
***************************************************/

checkExpiredWorkflow();






/***************************************************
  begin function defs
***************************************************/

function checkExpiredWorkflow()
{

  global $DB;

  $sql = "SELECT id,account_id,object_id FROM docmgr.dm_workflow WHERE expire_notify='t'";
  $wf = $DB->fetch($sql);

  for ($i=0;$i<$wf["count"];$i++)
  {

    $now = date("Y-m-d");

    //get expired workflows
    $sql = "SELECT * FROM docmgr.dm_workflow_route WHERE
                      date_due<'$now' AND
                      workflow_id='".$wf[$i]["id"]."'";
    $expired = $DB->fetch($sql);

    //go no further if nothing found
    if ($expired["count"]==0) continue;

    //get creator info
    $a = new ACCOUNT($wf[$i]["account_id"]);
    $ainfo = $a->getInfo();
  
    //bail if the creator has no email
    if (!$ainfo["email"]) continue;

    //get the object name
    $sql = "SELECT name FROM docmgr.dm_object WHERE id='".$wf[$i]["object_id"]."'";
    $oinfo = $DB->single($sql);
    
    //notify creators there's an issue
    for ($c=0;$c<$expired["count"];$c++)
    {

      //get task owner
      $t = new ACCOUNT($expired[$c]["account_id"]);
      $tinfo = $t->getInfo();

      $sub = "DocMGR Expired Task Notification";
      $msg = "\"".$tinfo["first_name"]." ".$tinfo["last_name"]."\" has an expired task in a workflow 
              for the file \"".$oinfo["name"]."\".";
                
      send_email($ainfo["email"],ADMIN_EMAIL,$sub,$msg,null);

    }
  
  }

}

