<?php

if ($_REQUEST["updateFeed"]) {

  $sql = "SELECT url FROM modlet.rssfeed WHERE account_id='".USER_ID."' AND container='".$_REQUEST["container"]."'";
  $info = single_result($conn,$sql);

  $opt = null;
  $opt["name"] = $_REQUEST["feedName"];
  $opt["url"] = $_REQUEST["feedPath"];
  $opt["container"] = $_REQUEST["container"];
  $opt["account_id"] = USER_ID;

  if ($info) {
  
    $opt["where"] = "account_id='".USER_ID."' AND container='".$_REQUEST["container"]."'";
    $func = "dbUpdateQuery";
    
  } else {
  
    $func = "dbInsertQuery";
    
  }
  
  $res = $func($conn,"modlet.rssfeed",$opt);
  
  if (!$res) $error = 1;
  
}
  