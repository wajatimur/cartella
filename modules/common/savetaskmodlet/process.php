<?php

if ($_REQUEST["action"]=="update") {

  //look for an entry
  $sql = "SELECT account_id FROM modlet.tasks WHERE account_id='".USER_ID."'";
  $info = $DB->single($sql);

  //assemble data
  $opt = null;
  $opt["container"] = $_REQUEST["container"];
  $opt["account_id"] = USER_ID;
  $opt["name"] = $_REQUEST["taskName"];
  $opt["daterange"] = $_REQUEST["taskSpan"];
  
  if ($info) {
  
    $opt["where"] = "account_id='".USER_ID."'";
    $DB->update("modlet.tasks",$opt);  
  
  } else {
  
    $DB->insert("modlet.tasks",$opt);
      
  }

  $moduleError = $DB->error();
  
}
