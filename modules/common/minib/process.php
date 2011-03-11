<?php

$objectName = null;
$mode = $_REQUEST["mode"];
$filter = $_REQUEST["filter"];

//if asked to save the session, do it and quit
if ($_REQUEST["action"]=="savePath")
{
  $_SESSION["minibPath"] = stripsan($_REQUEST["savePath"]);
  die;
}

//figure out what extensions to show
if ($filter=="msoffice") {

  $str = file_get_contents("config/extensions.xml");
  $ext = array();
  $data = XML::decode($str);

  for ($i=0;$i<count($data["object"]);$i++) {

    //show only files we can open with the dsoframer msoffice module
    if ($data["object"][$i]["dsoframer"]) $ext[] = $data["object"][$i]["extension"];  
  
  }
  
  $filter = implode(",",$ext);

}


//default to their home directory
if ($_REQUEST["browsePath"]) 
  $browsePath = $_REQUEST["browsePath"];
else if ($_SESSION["minibPath"])
  $browsePath = $_SESSION["minibPath"];
else 
  $browsePath = "/Users/".USER_LOGIN;

//if the browse path has a trailing slash, kill it
if ($browsePath[strlen($browsePath)-1]=="/") $browsePath = substr($browsePath,0,strlen($browsePath)-1);

