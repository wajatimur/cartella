<?php

$objectPath = $_REQUEST["objectPath"];
$parentPath = $_REQUEST["parentPath"];
$objectType = $_REQUEST["objectType"];
$objectId = $_REQUEST["objectId"];
$editor = $_REQUEST["editor"];

//printout from fck only
if ($_REQUEST["action"]=="print") {

  if (defined("TEA_ENABLE")) include("include/fck-print.php");
  else {
  
    //at this point, we if have a pdf just output to the browser, otherwise create it from the posted content
    $objectName = array_pop(explode("/",$objectPath));
    outputPDF($_POST["editor_content"],$objectName);

  }
  
}
  
//tea specific task processing
if (defined("TEA_ENABLE")) include("include/task.php");

//get general object information
if ($objectId || $objectPath) 
{

  if ($objectId) $obj = $objectId;
  else $obj = $objectPath;

  $d = new DOCMGR_OBJECT($obj);
  $objinfo = $d->getInfo();

	$objectType = $objinfo["object_type"];
	$objectId = $objinfo["id"];
	$objectPath = $objinfo["object_path"];
	$objectName = $objinfo["name"];
        	
	//if no parent path, set it
	if (!$parentPath) 
	{
		$arr = explode("/",$objectPath);
		array_pop($arr);
		$parentPath = implode("/",$arr);
	}

	//get the editor for our thingy
	$editor = getEditorType($objectName);

}

//tea specific contact processing
if (defined("TEA_ENABLE")) include("include/contact.php");

//make sure there's temp storage available for uploaded files
if ($objectId) 
{

  //make sure this file is a storage folder
  $d = new DOCMGR_OBJECT();
  $d->createStorage($objectId);

} 
else 
{

  //make sure this file is a storage folder
  $d = new DOCMGR_OBJECT();
  $d->createTemp();

}

