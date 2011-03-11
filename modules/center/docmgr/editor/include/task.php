<?php

/*********************************************************
  tea-specific processing for now
*********************************************************/
$taskId = $_REQUEST["taskId"];

//see if this task has a presaved letter associated with it
if ($taskId) { 

  $sql = "SELECT object_id FROM task.view_tea_task WHERE task_id='$taskId'";
  $info = $DB->single($sql);

  if ($info["object_id"]) $objectId = $info["object_id"];
 
}
 