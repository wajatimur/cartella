<?php

$contactId = $_REQUEST["contactId"];

//situations to handle:
//	msoffice with word file
//  msoffice with document file
//	fckeditor with document file

if ($contactId && $objectId && $editor=="dso") 
{

	//if a document with a contact id, force it to fckeditor, convert to a word file.  we need this to happen
	//to handle merges correctly.  hopefully this won't be too confusing
	if ($objectType=="document") $editor = "fck";
	else 
	{		

		$opt = null;
		$opt["command"] = "tea_merge_run";
		$opt["mode"] = "letter";
		$opt["contact_id"] = $contactId;
		$opt["output"] = "file";
		$opt["object_id"] = $objectId;
		$data = callAPI($opt);
		
		if ($data["error"]) die($data["error"]);
		else $directPath = $data["file"];

		//replace full path with the site url
		$directPath = str_replace(SITE_PATH,SITE_URL,$directPath);

	}

}

//convert to non-array
if (is_array($contactId)) $contactId = implode(",",$contactId);
	