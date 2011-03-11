<?php


$today = date("Y-m-d");

for ($i=0;$i<$taskList["count"];$i++) 
{

	if ($taskList[$i]["date_due"]=="2000-01-01") $taskList[$i]["date_due"] = null;

	//reformat our data
	if ($taskList[$i]["date_due"]) $taskList[$i]["date_due_view"] = date_view($taskList[$i]["date_due"]);

	//is it expired
	if ($taskList[$i]["date_due"] && $taskList[$i]["date_due"] <= $today) $taskList[$i]["expired"] = 1;

	//priority
	if ($taskList[$i]["priority"]=="1") $taskList[$i]["priority_view"] = "High";
	elseif ($taskList[$i]["priority"]=="2") $taskList[$i]["priority_view"] = "Normal";
	elseif ($taskList[$i]["priority"]=="3") $taskList[$i]["priority_view"] = "Low";

	//task type
	if ($taskList[$i]["contact_id"]) $taskList[$i]["category"] = "Contact";	
	else if ($taskList[$i]["contract_id"]) $taskList[$i]["category"] = "Contract";	

	//conver to xml
	$PROTO->add("task",$taskList[$i]);

}

$PROTO->output();
