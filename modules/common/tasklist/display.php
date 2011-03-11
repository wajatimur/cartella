<?php

if ($errorMessage) $PROTO->add("error",$errorMessage);
else {

  for ($i=0;$i<$taskList["count"];$i++) 
  {

    $taskList[$i]["date"] = dateView($taskList[$i]["event_date"]);
    if (!$taskList[$i]["c_type_name"]) $taskList[$i]["c_type_name"] = "Not Selected";

    $PROTO->add("task",$taskList[$i]);

  }

}

$PROTO->output();
