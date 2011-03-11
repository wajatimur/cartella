<?php

$mode = $_REQUEST["mode"];
$col1 = $_REQUEST["column1"];
$col2 = $_REQUEST["column2"];
$cont1 = $_REQUEST["containerId1"];
$cont2 = $_REQUEST["containerId2"];
$accountId = $_REQUEST["accountId"];
$groupId = $_REQUEST["groupId"];
$saveModule = $_REQUEST["saveModule"];

if ($mode=="group") {
  $table = "group_dashboard";
  $idVal = $groupId;
  $field = "group_id";
} else {
  $table = "dashboard";
  $idVal = $accountId;
  $field = "account_id";
}  

  //clear out the old settings
  $sql = "DELETE FROM $table WHERE $field='$idVal' AND module='$saveModule';";
  
  //insert the new settings for col1
  for ($i=0;$i<count($col1);$i++) {
  
    $opt = null;
    $opt["query"] = 1;	//return the query only
    $opt["module"] = $saveModule;
    $opt["display_column"] = 1;
    $opt["modlet"] = $col1[$i];
    $opt["container_id"] = $cont1[$i];
    $opt["$field"] = $idVal;
    $opt["sort_order"] = $i + 1;
    $sql .= dbInsertQuery(null,$table,$opt);
  
  }

  //insert the new settings for col 2
  for ($i=0;$i<count($col2);$i++) {
  
    $opt = null;
    $opt["query"] = 1;	//return the query only
    $opt["module"] = $saveModule;
    $opt["display_column"] = 2;
    $opt["modlet"] = $col2[$i];
    $opt["container_id"] = $cont2[$i];
    $opt["$field"] = $idVal;
    $opt["sort_order" ] = $i+1;
    $sql .= dbInsertQuery(null,$table,$opt);
  
  }

  if (db_query($conn,$sql)) $queryReturn = "success";
  else $queryReturn = "error";

