<?php

$contactId = $_REQUEST["contactId"];
$email = $_REQUEST["email"];
$objectPath = $_REQUEST["objectPath"];
$emailContent = $_REQUEST["editor_content"];

//make sure our temp folder exists, and make sure it's empty
$attachdir = TMP_DIR."/".USER_LOGIN."/email";

if (!is_dir($attachdir)) recurmkdir($attachdir);
else {
  $dir = $attachdir."/*";
  `rm $dir`;
}

if ($contactId) 
{

  if (!is_array($contactId)) $contactId = explode(",",$contactId);
  
  $sql = "SELECT first_name,last_name,email FROM addressbook.contact WHERE id IN (".implode(",",$contactId).")";
  $list = $DB->fetch($sql);
  
  $arr = array();
  
  for ($i=0;$i<$list["count"];$i++) 
  {

    if (!$list[$i]["email"]) $list[$i]["email"] = "UNKNOWN EMAIL ADDRESS";  
    $arr[] = $list[$i]["first_name"]." ".$list[$i]["last_name"]." <".$list[$i]["email"].">";  

  }

  $email = implode(",",$arr);
  
}

//get the type of object
if ($objectId || $objectPath) {

  if ($objectId) $obj = $objectId;
  else $obj = $objectPath;

  $d = new DOCMGR_OBJECT($obj);
  $objinfo = $d->getInfo();

  $objectType = $objinfo["object_type"]; 
  $objectId = $objinfo["id"]; 
  $objectPath = $objinfo["object_path"]; 
 
}

//if not content and there's a sig, set it to teh signature
if (!$emailContent && $_SESSION["accountSettings"]["letter_sig"]) 
{
  $emailContent = $_SESSION["accountSettings"]["letter_sig"];
  $emailContent = str_replace("<body>","<body><p>&nbsp;</p>",$emailContent);
}

