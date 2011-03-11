<?php

if ($errorMessage) $PROTO->add("error",$errorMessage);

if ($taskInfo) 
{

  //we need a viewable date
  $taskInfo["date"] = date_view($taskInfo["date_due"]);

  if ($taskInfo["priority"]==1) $taskInfo["priority_view"] = "Very Important";
  elseif ($taskInfo["priority"]=="2") $taskInfo["priority_view"] = "Important";
  elseif ($taskInfo["priority"]=="3") $taskInfo["priority_view"] = "Not Important";

  if (!$taskInfo["c_type_name"]) $taskInfo["c_type_name"] = "Not Selected";
  
  if ($taskInfo["completed"]=="t") 
  {
    $taskInfo["completed_view"] = "Yes";
    if ($taskInfo["date_completed"]) $taskInfo["date_completed_view"] = dateView($taskInfo["date_completed"]);
  }
  else $taskInfo["completed_view"] = "No";

  //rename to "contact" so we get the right tags on output
  if ($taskInfo["contact_group"]) $taskInfo["contact"] = $taskInfo["contact_group"];
  unset($taskInfo["contact_group"]);

  $PROTO->add("task",$taskInfo);

}

$PROTO->output();
