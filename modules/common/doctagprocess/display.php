<?php

$xml = createXMLHeader();

if ($errorMessage) $xml .= xmlEntry("error",$errorMessage);
if ($tagId) $xml .= xmlEntry("tagId",$tagId);
$xml .= xmlEntry("tagmode",$tagMode);

//if passed a contact id, get tags for this contact
if ($objectId && !is_array($objectId)) {

  $sql = "SELECT tag_id FROM docmgr.dm_tag_link WHERE object_id='$objectId'";
  $cur = total_result($conn,$sql);
  
}

for ($i=0;$i<$tagList["count"];$i++) {

  $xml .= "<tag>\n";
  $xml .= xmlEntry("id",$tagList[$i]["id"]);
  $xml .= xmlEntry("name",$tagList[$i]["name"]);
  
  //if set for this contact
  if ($objectId && $cur["count"]) {
  
    if (in_array($tagList[$i]["id"],$cur["tag_id"])) $xml .= xmlEntry("set","1");
    else $xml .= xmlEntry("set","0");
    
  } else $xml .= xmlEntry("set","0");

  $xml .= "</tag>\n";
  
}

$xml .= createXMLFooter();

die($xml);


