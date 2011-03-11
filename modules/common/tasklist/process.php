<?php

/*******************************************************************
  NAME: tasklist
  PURPOSE: returns info for a contactid in xml format
*******************************************************************/
    
$contactId = $_REQUEST["contactId"];
$contractId = $_REQUEST["contractId"];

//no contact id, stop here
if (!$contactId && !$contractId) {
  $errorMessage = "No contact or contract specified";
  return false;
}

if ($contactId) $sql = "SELECT * FROM task.view_tea_task WHERE contact_id='$contactId'";
elseif ($contractId) $sql = "SELECT * FROM task.view_tea_task WHERE contract_id='$contractId'";

if ($_REQUEST["filter"]=="incomplete") $sql .= " AND completed='f' ";
elseif ($_REQUEST["filter"]=="complete") $sql .= " AND completed='t' ";

$sql .= " ORDER BY date_due";

$taskList = list_result($conn,$sql);
