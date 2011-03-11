<?php
$contactId = $_REQUEST["contactId"];
$objectId = $_REQUEST["objectId"];

if ($contactId) 
{

  if (!is_array($contactId)) $contactId = explode(",",$contactId);

  if ($objectId) 
  {

    //basically we run the merge again, but don't output anything
    $opt = null;
    $opt["command"] = "tea_merge_run";
    $opt["mode"] = "letter";
    $opt["contact_id"] = $contactId;
    $opt["object_id"] = $objectId;
    $opt["log"] = 1;
    $opt["log_type"] = CTYPE_LETTER;
    $data = callAPI($opt);

  }
  
}

die;
