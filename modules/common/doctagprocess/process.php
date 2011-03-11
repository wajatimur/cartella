<?php

$objectId = $_REQUEST["objectId"];
$tagId = $_REQUEST["tagId"];
$tagMode = $_REQUEST["tagMode"];

if ($_REQUEST["action"]=="showTags") {

  //get our current list of tags
  $sql = "SELECT * FROM docmgr.dm_tag WHERE account_id='".USER_ID."' ORDER BY name";
  $tagList = $DB->fetch($sql);
  $xmlMode = "showTags";

} else if ($_REQUEST["action"]=="updateTag") {

  $opt = null;
  $opt["name"] = $_REQUEST["tagText"];
  $opt["where"] = "id='".$_REQUEST["tagId"]."'";
  if (!$DB->update("docmgr.dm_tag",$opt)) $errorMessage = "Tag update failed";
  $xmlMode = "updateTag";
  
} else if ($_REQUEST["action"]=="newTag") {

  $opt = null;
  $opt["name"] = $_REQUEST["tagText"];
  $opt["account_id"] = USER_ID;
  if (!$tagId = $DB->insert("docmgr.dm_tag",$opt,"id")) $errorMessage = "Tag creation failed";
  $xmlMode = "newTag";

} else if ($_REQUEST["action"]=="deleteTag") {

  $sql = "DELETE FROM docmgr.dm_tag WHERE id='$tagId';
          DELETE FROM docmgr.dm_tag_link WHERE tag_id='$tagId'";
  if (!$DB->query($sql)) $errorMessage = "Tag removal failed";
  $xmlMode = "deleteTag";

} else if ($_REQUEST["action"]=="setTag") {

  //handle multiple contacts if passed
  if (!is_array($objectId)) $objectId = array($objectId);

  $sql = null;
    
  for ($i=0;$i<count($objectId);$i++) {

    //always makes ure we don't already have an entry in there
    $sql .= "DELETE FROM docmgr.dm_tag_link WHERE tag_id='$tagId' AND object_id='".$objectId[$i]."';";

    //now add one if asked
    if ($_REQUEST["setMode"]=="enable") {
      $sql .= "INSERT INTO docmgr.dm_tag_link (tag_id,object_id) VALUES ('$tagId','".$objectId[$i]."');";
    }
  
  }

  if (!$DB->query($sql)) $errorMessage = "Setting tag for contact failed";
  $xmlMode = "setTag";
  
}
