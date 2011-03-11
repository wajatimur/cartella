<?php

//if passed a user id, use it.  make sure we
//have permissions to view that person's tasks
//if ($_REQUEST["accountId"] && $_REQUEST["accountId"]!=USER_ID) {
//} else 

$accountId = USER_ID;

  //everything for the enxt month
  $date = date("Y-m-d",time()+WEEK_SEC);

  if ($_REQUEST["filter"]=="all") $filtersql = null;
  else if ($_REQUEST["filter"]=="complete") $filtersql = " AND completed='t' ";
  else if ($_REQUEST["filter"]=="incomplete") $filtersql = " AND completed='f' ";
  
  if ($_REQUEST["date"]=="all") $datesql = null;
  else if ($_REQUEST["date"]=="week") $datesql = " date_due<='".date("Y-m-d",mktime(0,0,0,date("m"),date("d")+7,date("Y")))."' ";
  else if ($_REQUEST["date"]=="month") $datesql = " date_due<='".date("Y-m-d",mktime(0,0,0,date("m")+1,date("d"),date("Y")))."' ";
  else if ($_REQUEST["date"]=="day") $datesql = " date_due='".date("Y-m-d")."'";

  if ($_REQUEST["searchString"]) $ss = " AND lower(title) LIKE '%".strtolower($_REQUEST["searchString"])."%' ";
  else $ss = null;

  //setup a group filter as well.  limit role filters to tasks assigned to roles in this location
  if (strlen(USER_GROUPS) > 0) $rolefilter = " (account_id='$accountId' OR (role_id IN (".USER_GROUPS."))) ";
  else $rolefilter = " account_id='$accountId' ";

  if ($_REQUEST["sort"]=="date") {
  
    if ($datesql) $datesql = " AND ".$datesql;
  
    $sql = "SELECT id,title,priority,date_due,completed,task_type FROM task.view_tasks WHERE ".$rolefilter." ".$filtersql." ".$ss." AND date_due IS NOT NULL ".$datesql."
            UNION
            SELECT id,title,priority,'2000-01-01' AS date_due,completed,task_type FROM task.view_tasks WHERE ".$rolefilter." ".$filtersql." ".$ss." AND date_due IS NULL
            ORDER BY date_due DESC
            ";  
  
  } else {

    if ($_REQUEST["sort"]=="priority") $sortstr = " priority ASC ";
    elseif ($_REQUEST["sort"]=="title") $sortstr = " title ASC";
    elseif ($_REQUEST["sort"]=="category") $sortstr = " task_type ASC";
    elseif ($_REQUEST["sort"]=="datecompleted") $sortstr = " date_completed DESC";
    else $sortstr = " title ASC";

    if ($datesql) $datesql = " AND (date_due IS NULL OR ".$datesql.") ";

    //get our tasks
    $sql = "SELECT * FROM task.view_tasks WHERE ".$rolefilter." ".$filtersql." ".$datesql." ".$ss." ORDER BY ".$sortstr;
  
  }

  $taskList = list_result($conn,$sql);


