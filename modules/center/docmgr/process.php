<?php


//id passed in url
if ($_REQUEST["objectId"])
{
  $d = new DOCMGR_OBJECT($_REQUEST["objectId"]);
  $info = $d->getInfo();
  //$_SESSION["browseCeiling"] = $info["object_path"];
}

//path passed in url
else if ($_REQUEST["objectPath"])
{
  //$_SESSION["browseCeiling"] = stripsan($_REQUEST["objectPath"]);
}


//default ceiling
if (!$_SESSION["browseCeiling"]) 
{
  $_SESSION["browseCeiling"] = "/Users/".USER_LOGIN;
}

