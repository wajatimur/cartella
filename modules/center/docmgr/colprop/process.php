<?php

$objectId = $_REQUEST["objectId"];

$d = new DOCMGR_OBJECT($objectId);
$objInfo = $d->getInfo();

if ($_REQUEST["parentPath"]) 
{

  $parentPath = $_REQUEST["parentPath"];

  if ($parentPath=="/") $parentId = "0";
  else
  {

    $d = new DOCMGR_OBJECT($parentPath);
    $info = $d->getInfo();

    $parentId = $info["id"];

  }
  
}
