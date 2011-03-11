<?php

$contactId = $_REQUEST["contactId"];

//merge all the information into a pdf
if ($contactId) 
{

  //convert to array
  if (!is_array($contactId)) $contactId = explode(",",$contactId);

  //if there's a task id, mark it complete
  if ($_REQUEST["taskId"] && !$_REQUEST["printpreview"]) 
  {

  	$t = new TASK($_REQUEST["taskId"]);
  	
    //if there's a contact, we pass our letter content as an array so the unique merged content is stored for each contact
    if ($contactId) $t->setContactId($contactId);

    //just store the unmerged letter for the task, since the task could be for many people
    $t->markComplete(null,smartslashes($_POST["editor_content"]));

	}

	//create the letter
	$opt = null;
	$opt["mode"] = "letter";
	$opt["command"] = "tea_merge_run";
	$opt["contact_id"] = $contactId;
	$opt["output"] = "pdf";
	$opt["object_id"] = $objectId;

	//no task id, make sure it gets recorded in their history
	if (!$_REQUEST["printpreview"] && !$_REQUEST["taskId"]) $opt["log"] = 1;

	$data = callAPI($opt);

	//throw an error if found, otherwise output the pdf
	if ($data["error"]) die($data["error"]);
	else outputFile($data["file"]);
	
} 
else 
{

	//at this point, we if have a pdf just output to the browser, otherwise create it from the posted content
	$objectName = array_pop(explode("/",$objectPath));
	outputPDF($_POST["editorContent"],$objectName);

}
	